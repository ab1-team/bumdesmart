<?php

namespace App\Livewire\MasterData;

use App\Models\Business;
use App\Models\Owner;
use App\Models\Role;
use App\Models\User;
use App\Traits\WithTable;
use App\Utils\TableUtil;
use Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

class MasterBusiness extends Component
{
    use WithFileUploads, WithTable, \App\Traits\TenantSync;

    public $titleModal;

    public $id;      // Business ID

    public $ownerId; // Selected Owner ID

    // Form fields
    public $businessName;

    public $address;

    public $phone;

    public $email;

    public $username;

    public $password;

    // Owners list for dropdown
    public $ownersList = [];

    public $importFile;

    public $importStep = 'idle';

    public $isContextual = false; // Flag for owner context

    public $selectedBusinessId;

    protected function rules()
    {
        return [
            'ownerId' => 'required|exists:owners,id',
            'businessName' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:25',
            'email' => 'required|email|max:255',
        ];
    }

    public function mount()
    {
        $owner_id = request()->query('owner_id');
        $this->loadOwners();
        if ($owner_id) {
            $this->ownerId = $owner_id;
            $this->isContextual = true;
            $this->headers = ['no', 'business name', 'email', 'no. telp', 'address', 'aksi'];

            // Hanya buka modal otomatis jika belum punya bisnis sama sekali
            $hasBusiness = Business::where('owner_id', $owner_id)->exists();
            if (! $hasBusiness) {
                $this->create();
            }
        }
    }

    public function loadOwners()
    {
        $this->ownersList = Owner::orderBy('nama_usaha')->pluck('nama_usaha', 'id')->toArray();
    }

    public function openImport($id)
    {
        $this->selectedBusinessId = $id;
        $this->importFile = null;
        $this->importStep = 'idle';
        $this->dispatch('show-modal', modalId: 'importModal');
    }

    public function processImport()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $this->importStep = 'processing';

        try {
            $business = Business::with('owner')->findOrFail($this->selectedBusinessId);
            $owner = $business->owner;

            // Initialize tenant context
            tenancy()->initialize($owner);

            // Fetch the business record from the tenant database to get the correct local ID
            $tenantBusiness = \App\Models\Business::where('owner_id', $owner->id)->first();
            if (! $tenantBusiness) {
                // Fallback to central business if not found, but this shouldn't happen
                $tenantBusiness = $business;
            }
            $activeBusinessId = $tenantBusiness->id;

            $path = $this->importFile->getRealPath();
            $file = fopen($path, 'r');

            // Skip header
            $header = fgetcsv($file);

            DB::beginTransaction();

            // 2. Cache Master Data (Tenant DB) to avoid repetitive queries
            $categories = \App\Models\Category::pluck('id', 'nama_kategori')->toArray();
            $brands = \App\Models\Brand::pluck('id', 'nama_brand')->toArray();
            $units = \App\Models\Unit::pluck('id', 'nama_satuan')->toArray();

            $productUpserts = [];
            $successCount = 0;
            $now = now();

            while (($row = fgetcsv($file)) !== false) {
                if (count($row) < 5) {
                    continue;
                }

                $namaProduk = $row[0];
                $kategori = $row[1] ?: 'General';
                $brand = $row[2] ?: 'N/A';
                $satuan = $row[3] ?: 'Pcs';
                $sku = $row[4] ?: 'SKU-'.strtoupper(Str::random(8));
                $barcode = $row[5] ?: (string) mt_rand(100000, 999999).mt_rand(1000000, 9999999);
                $hargaBeli = (float) str_replace(['.', ','], ['', '.'], $row[6] ?? 0);
                $hargaJual = (float) str_replace(['.', ','], ['', '.'], $row[7] ?? 0);
                $stok = (float) str_replace(['.', ','], ['', '.'], $row[8] ?? 0);

                // Find/Create Master Data from Cache
                if (! isset($categories[$kategori])) {
                    $cat = \App\Models\Category::create(['nama_kategori' => $kategori, 'business_id' => $activeBusinessId, 'icon' => 'box']);
                    $categories[$kategori] = $cat->id;
                }
                if (! isset($brands[$brand])) {
                    $brd = \App\Models\Brand::create(['nama_brand' => $brand, 'business_id' => $activeBusinessId]);
                    $brands[$brand] = $brd->id;
                }
                if (! isset($units[$satuan])) {
                    $unt = \App\Models\Unit::create(['nama_satuan' => $satuan, 'business_id' => $activeBusinessId, 'inisial_satuan' => $satuan]);
                    $units[$satuan] = $unt->id;
                }

                $productUpserts[] = [
                    'business_id' => $activeBusinessId,
                    'sku' => $sku,
                    'barcode' => $barcode,
                    'category_id' => $categories[$kategori],
                    'brand_id' => $brands[$brand],
                    'unit_id' => $units[$satuan],
                    'nama_produk' => $namaProduk,
                    'harga_beli' => $hargaBeli,
                    'harga_jual' => $hargaJual,
                    'stok_aktual' => $stok,
                    'metode_biaya' => 'SYSTEM',
                    'biaya_rata_rata' => $hargaBeli,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $successCount++;
            }

            // High Performance Batch Insert/Upsert
            // Since we might not have 'id' yet, we use unique check or simple loop for safety if data size is reasonable
            // For now, let's use a chunked insert to be safe and efficient
            foreach (array_chunk($productUpserts, 100) as $chunk) {
                foreach ($chunk as $pData) {
                    \App\Models\Product::updateOrCreate(
                        ['business_id' => $pData['business_id'], 'sku' => $pData['sku']],
                        $pData
                    );
                }
            }

            // Re-fetch product IDs to link Batches and Movements
            $allProducts = \App\Models\Product::where('business_id', $activeBusinessId)
                ->whereIn('sku', array_column($productUpserts, 'sku'))
                ->pluck('id', 'sku');

            $batchInserts = [];
            $movementInserts = [];

            foreach ($productUpserts as $pData) {
                $sku = $pData['sku'];
                $stok = $pData['stok_aktual'];
                if (isset($allProducts[$sku])) {
                    $pId = $allProducts[$sku];

                    $batchInserts[] = [
                        'business_id' => $activeBusinessId,
                        'product_id' => $pId,
                        'no_batch' => 'MIGRATION-'.date('Ymd'),
                        'tanggal_pembelian' => $now,
                        'harga_satuan' => $pData['harga_beli'],
                        'jumlah_awal' => $stok,
                        'jumlah_saat_ini' => $stok,
                        'tanggal_kadaluarsa' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $movementInserts[] = [
                        'business_id' => $activeBusinessId,
                        'product_id' => $pId,
                        'tanggal_perubahan_stok' => $now,
                        'jenis_perubahan' => 'adjustment',
                        'jumlah_perubahan' => $stok,
                        'reference_id' => 0,
                        'reference_type' => 'migration',
                        'catatan' => 'Migrasi data awal sistem',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Bulk Insert Batches & Movements
            if (! empty($batchInserts)) {
                \App\Models\ProductBatch::insert($batchInserts);
            }
            if (! empty($movementInserts)) {
                \App\Models\StockMovement::insert($movementInserts);
            }

            DB::commit();
            fclose($file);
            tenancy()->end();

            $this->dispatch('hide-modal', modalId: 'importModal');
            $this->dispatch('alert', type: 'success', message: $successCount.' data produk + Barcode berhasil dimigrasikan ke Toko!');
        } catch (\Exception $e) {
            DB::rollBack();
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $this->dispatch('alert', type: 'error', message: 'Gagal import: '.$e->getMessage());
        }

        $this->importStep = 'idle';
    }

    public function downloadTemplate()
    {
        return response()->streamDownload(function () {
            echo "Nama Produk,Kategori,Brand,Satuan,SKU,Barcode,Harga Beli,Harga Jual,Stok\n";
            echo "Indomie Goreng,Makanan & Snack,Indofood,Pcs,IDM-001,8998866200293,2500,3000,100\n";
            echo "Aqua 600ml,Minuman,Danone,Pcs,AQUA-600,8886008101053,2800,3500,500\n";
        }, 'template_migrasi_produk.csv');
    }

    public function resetForm()
    {
        $currentOwner = $this->ownerId;
        $this->reset('id', 'businessName', 'address', 'phone', 'email', 'username', 'password');

        if ($this->isContextual) {
            $this->ownerId = $currentOwner;
        } else {
            $this->reset('ownerId');
        }
    }

    public function create()
    {
        $this->resetForm();
        $this->resetValidation();
        $this->titleModal = 'Tambah Business';
        $this->dispatch('show-modal', modalId: 'masterBusinessModal');
    }

    public function store()
    {
        $this->validate();

        // Create new Business under selected owner (CENTRAL)
        $business = Business::create([
            'owner_id' => $this->ownerId,
            'nama_usaha' => $this->businessName,
            'alamat' => $this->address,
            'no_telp' => $this->phone,
            'email' => $this->email,
        ]);

        // START CROSS-DATABASE SYNC: Push to Tenant Database
        try {
            $owner = Owner::find($this->ownerId);

            $username = $this->username ?: (strtolower(str_replace(' ', '_', $this->businessName)).'_owner');
            $password = $this->password ?: 'password';

            tenancy()->run($owner, function () use ($business, $owner, $username, $password) {
                // 1. Ensure Migrations have run
                try {
                    $currentDb = DB::connection()->getDatabaseName();
                    \Log::info("Syncing tenant {$owner->id} using database: {$currentDb}");

                    if (! Schema::hasTable('migrations') || DB::table('migrations')->count() == 0) {
                        \Log::error('Running migrations for tenant: '.$owner->id);
                        $exitCode = Artisan::call('tenants:migrate', [
                            '--tenants' => [$owner->id],
                            '--force' => true,
                        ]);
                        \Log::error("Migration exit code: {$exitCode}. Output: " . Artisan::output());
                    }
                } catch (\Exception $e) {
                    \Log::error("Migration failed for tenant {$owner->id}: ".$e->getMessage());
                }

                // 2. Ensure Base Data Exists (Fallback if initial seeding failed)
                if (! Schema::hasTable('menus') || DB::table('menus')->count() == 0) {
                    \Log::error('Seeding menus for tenant: '.$owner->id);
                    (new \Database\Seeders\MenuSeeder)->run();
                }
                if (! Schema::hasTable('akun_level3s') || DB::table('akun_level3s')->count() == 0) {
                    \Log::error('Seeding accounts for tenant: '.$owner->id);
                    (new \Database\Seeders\AccountSeeder)->run();
                }

                // Ensure other core seeders are run if needed
                if (! Schema::hasTable('roles') || DB::table('roles')->count() == 0) {
                    (new \Database\Seeders\UserSeeder)->run();
                }

                // 3. Create Business Locally (Operational - TENANT DB)
                Business::updateOrCreate(
                    ['id' => $business->id],
                    [
                        'owner_id' => $this->ownerId,
                        'nama_usaha' => $this->businessName,
                        'alamat' => $this->address,
                        'no_telp' => $this->phone,
                        'email' => $this->email,
                    ]
                );

                // 4. Initialize accounting accounts (CoA Level 4) in Tenant DB
                \App\Utils\AccountUtil::initializeBusinessAccounts($business->id);

                // 5. Create default roles in Tenant DB
                $roleData = [
                    ['nama_role' => 'owner', 'deskripsi' => 'Role owner'],
                    ['nama_role' => 'admin', 'deskripsi' => 'Role admin'],
                ];

                foreach ($roleData as $rd) {
                    Role::updateOrCreate(
                        ['business_id' => $business->id, 'nama_role' => $rd['nama_role']],
                        ['deskripsi' => $rd['deskripsi']]
                    );
                }

                // Assign All Menus to 'owner' and 'admin' roles
                $newRoleIds = Role::where('business_id', $business->id)->pluck('id');
                $menuIds = DB::table('menus')->pluck('id');

                if ($menuIds->isNotEmpty()) {
                    $roleMenus = [];
                    foreach ($newRoleIds as $roleId) {
                        foreach ($menuIds as $menuId) {
                            $roleMenus[] = [
                                'role_id' => $roleId,
                                'menu_id' => $menuId,
                            ];
                        }
                    }
                    DB::table('role_menu')->whereIn('role_id', $newRoleIds)->delete();
                    DB::table('role_menu')->insert($roleMenus);
                }

                // 6. Create default user (owner) in Tenant DB
                $ownerRole = Role::where('business_id', $business->id)->where('nama_role', 'owner')->first();

                if (! $ownerRole) {
                    throw new \Exception('Gagal membuat Role Owner di database tenant.');
                }

                User::updateOrCreate(
                    ['business_id' => $business->id, 'username' => $username],
                    [
                        'role_id' => $ownerRole->id,
                        'nama_lengkap' => $this->businessName, // Use business name as fallback for user name
                        'initial' => substr($this->businessName, 0, 3),
                        'no_hp' => $this->phone,
                        'password' => Hash::make($password),
                    ]
                );

                \Log::error("Successfully synchronized business {$business->id} to tenant ".tenant('id'));
            });

        } catch (\Exception $e) {
            \Log::error('Gagal sinkronisasi tenant: '.$e->getMessage());
            $this->dispatch('alert', type: 'error', message: 'Gagal sinkronisasi tenant: '.$e->getMessage());

            return;
        }
        // END CROSS-DATABASE SYNC

        $message = "Business berhasil ditambahkan. Username Default: {$username} / Password: {$password}";

        $this->dispatch('hide-modal', modalId: 'masterBusinessModal');
        $this->dispatch('alert', type: 'success', message: $message);
        $this->resetForm();
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        $business = Business::with('owner')->find($id);

        if ($business) {
            DB::beginTransaction();
            try {
                // 1. INITIALIZE TENANT CONTEXT FOR CLEANUP
                $owner = $business->owner;
                tenancy()->initialize($owner);

                // Start Transaction in Tenant DB
                DB::connection('tenant')->beginTransaction();

                $bId = $business->id;

                // 2. DELETE DETAIL DATA WITHOUT CASCADE (TENANT DB)
                // Only sale_details and purchase_details need manual cleanup before their parents
                DB::connection('tenant')->table('sale_details')->whereIn('sale_id', function ($query) use ($bId) {
                    $query->select('id')->from('sales')->where('business_id', $bId);
                })->delete();

                DB::connection('tenant')->table('purchase_details')->whereIn('purchase_id', function ($query) use ($bId) {
                    $query->select('id')->from('purchases')->where('business_id', $bId);
                })->delete();

                // 3. DELETE OPERATIONAL DATA WITHOUT CASCADE (TENANT DB)
                // Table names are based on migration audit
                $tablesWithBusinessId = [
                    'payments', 'stock_movements', 'batch_movements', 'product_batches',
                    'sales', 'purchases', 'sales_returns', 'purchases_returns',
                    'stock_opnames', 'stock_adjustments', 'inventories', 'cash_drawers',
                    'company_settings',
                ];

                foreach ($tablesWithBusinessId as $tableName) {
                    DB::connection('tenant')->table($tableName)->where('business_id', $bId)->delete();
                }

                // 4. DELETE MASTER DATA (TENANT DB)
                // product_prices depends on products
                DB::connection('tenant')->table('product_prices')->whereIn('product_id', function ($query) use ($bId) {
                    $query->select('id')->from('products')->where('business_id', $bId);
                })->delete();

                $masterTables = ['products', 'categories', 'brands', 'units', 'shelves', 'customers', 'suppliers', 'customer_groups'];
                foreach ($masterTables as $tableName) {
                    DB::connection('tenant')->table($tableName)->where('business_id', $bId)->delete();
                }

                // 5. DELETE AUTH & BUSINESS (TENANT DB)
                // Note: jurnals, balances, and accounts have cascadeOnDelete in migrations,
                // so they will be deleted automatically when the business record is deleted.
                DB::connection('tenant')->table('users')->where('business_id', $bId)->delete();
                DB::connection('tenant')->table('roles')->where('business_id', $bId)->delete();
                DB::connection('tenant')->table('businesses')->where('id', $bId)->delete(); // This triggers cascades

                DB::connection('tenant')->commit();
                tenancy()->end();

                // 5. FINALLY DELETE FROM CENTRAL DB
                User::where('business_id', $bId)->delete();
                Role::where('business_id', $bId)->delete();
                $business->delete();

                DB::commit();
                $this->dispatch('alert', type: 'success', message: 'Business dan seluruh data terkait di Database Tenant berhasil dibersihkan total.');
            } catch (\Exception $e) {
                DB::rollBack();
                if (tenancy()->initialized) {
                    DB::connection('tenant')->rollBack();
                    tenancy()->end();
                }
                $this->dispatch('alert', type: 'error', message: 'Gagal membersihkan data: '.$e->getMessage());
            }
        }
    }

    #[Layout('layouts.app')]
    #[Title('Master Business')]
    public function render()
    {
        $query = Business::with('owner');

        // Apply owner filter if in contextual mode
        if ($this->ownerId) {
            $query->where('owner_id', $this->ownerId);
        }

        $headers = [
            TableUtil::setTableHeader('id', '#', false, false),
        ];

        // Only show owner column if not in contextual mode
        if (! $this->ownerId) {
            $headers[] = TableUtil::setTableHeader('owner.nama_usaha', 'Owner', true, true);
        }

        $headers = array_merge($headers, [
            TableUtil::setTableHeader('nama_usaha', 'Business Name', true, true),
            TableUtil::setTableHeader('email', 'Email', true, true),
            TableUtil::setTableHeader('no_telp', 'No. Telp', true, true),
            TableUtil::setTableHeader('alamat', 'Address', true, true),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ]);

        $businesses = TableUtil::paginate($this, $query, $headers, 10);

        return view('livewire.master-data.master-business', [
            'businesses' => $businesses,
            'headers' => $headers,
        ]);
    }
}
