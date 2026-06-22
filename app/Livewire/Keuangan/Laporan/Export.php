<?php

namespace App\Livewire\Keuangan\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AkunLevel1;
use App\Models\Business;
use App\Models\cashDrawer;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchasesReturn;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SalesReturn;
use App\Models\StockOpname;
use App\Utils\InventarisUtil;
use App\Utils\KeuanganUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Export extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->all();

        if (! isset($data['laporan']) || ! method_exists($this, $data['laporan'])) {
            abort(404, 'Laporan tidak ditemukan');
        }

        $owner = tenant();

        if ($owner) {
            $business = Business::where('owner_id', $owner->id)->first();
        } else {
            $business = Business::find(auth()->user()?->business_id) ?? Business::first();
        }

        view()->share('business', $business);

        return $this->{$data['laporan']}($data);
    }

    private function periodeSubtitle(string $tahun, string $bulan, ?string $hari = null): string
    {
        $parts = [];
        if ($bulan != '-') {
            try {
                $parts[] = Carbon::createFromDate((int) $tahun, (int) $bulan, 1)->isoFormat('MMMM');
            } catch (\Throwable $e) {
                $parts[] = $bulan;
            }
        }
        $parts[] = $tahun;
        $sub = 'Periode: '.implode(' ', $parts);
        if ($hari !== null && $hari != '-') {
            $sub .= ' | Tanggal: '.$hari;
        }
        return $sub;
    }

    private function buildExcel(string $title, string $subtitle, array $headers, array $rows, array $totalsRow, string $filename, array $numberCols = [], array $columnWidths = [])
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));

        $colCount = max(count($headers), 1);
        $lastCol = Coordinate::stringFromColumnIndex($colCount);

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', $subtitle);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headerRow = 4;
        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}{$headerRow}", $h);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray($headerStyle);

        $rowIndex = $headerRow + 1;
        foreach ($rows as $r) {
            foreach ($r as $i => $val) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue("{$col}{$rowIndex}", $val);
            }
            $rowIndex++;
        }

        if (! empty($rows)) {
            $dataEndRow = $rowIndex - 1;
            $dataRange = "A{$headerRow}:{$lastCol}{$dataEndRow}";
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        if (! empty($totalsRow)) {
            $totalLabelCol = Coordinate::stringFromColumnIndex($colCount - 1);
            $totalValueCol = Coordinate::stringFromColumnIndex($colCount);
            $sheet->mergeCells("A{$rowIndex}:".Coordinate::stringFromColumnIndex(max($colCount - 2, 1)).$rowIndex);
            $sheet->setCellValue("A{$rowIndex}", '');
            $sheet->setCellValue($totalLabelCol.$rowIndex, 'Total:');
            $sheet->setCellValue($totalValueCol.$rowIndex, $totalsRow[$colCount - 1] ?? '');
            $totalStyle = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ];
            $sheet->getStyle("A{$rowIndex}:{$lastCol}{$rowIndex}")->applyFromArray($totalStyle);
            $sheet->getStyle($totalLabelCol.$rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($totalValueCol.$rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowIndex++;
        }

        foreach ($numberCols as $nc) {
            $col = Coordinate::stringFromColumnIndex($nc);
            $sheet->getStyle("{$col}".($headerRow + 1).":{$col}{$rowIndex}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle($col.($headerRow + 1).":{$col}{$rowIndex}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $defaultWidths = [5, 12, 28, 22, 20, 18, 14, 18, 18, 16, 16, 16];
        foreach ($defaultWidths as $idx => $w) {
            $col = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        foreach ($columnWidths as $idx => $w) {
            $col = Coordinate::stringFromColumnIndex((int) $idx + 1);
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        $writer = new Xlsx($spreadsheet);
        $tmpPath = storage_path('app/tmp_'.uniqid().'.xlsx');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function buildGroupedExcel(string $title, string $subtitle, array $summaryRow, array $groups, string $filename, array $numberCols = [], array $columnWidths = [])
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));

        $maxCols = 1;
        foreach ($groups as $g) {
            $maxCols = max($maxCols, count($g['headers']));
        }
        $lastCol = Coordinate::stringFromColumnIndex($maxCols);

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', $subtitle);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowIndex = 3;

        if (! empty($summaryRow)) {
            foreach ($summaryRow as $sRow) {
                $col = 1;
                foreach ($sRow as $cell) {
                    $c = Coordinate::stringFromColumnIndex($col);
                    $sheet->setCellValue("{$c}{$rowIndex}", $cell);
                    $col++;
                }
                $sheet->getStyle("A{$rowIndex}:{$lastCol}{$rowIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$rowIndex}:{$lastCol}{$rowIndex}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $rowIndex++;
            }
            $rowIndex++;
        }

        $sectionHeaderStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
        ];
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];
        $subtotalStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];

        foreach ($groups as $group) {
            $headers = $group['headers'];
            $rows = $group['rows'];
            $groupTitle = $group['title'] ?? null;
            $subtotals = $group['subtotals'] ?? [];
            $colCount = count($headers);
            $grpLastCol = Coordinate::stringFromColumnIndex($colCount);

            if ($groupTitle) {
                $sheet->mergeCells("A{$rowIndex}:{$grpLastCol}{$rowIndex}");
                $sheet->setCellValue("A{$rowIndex}", $groupTitle);
                $sheet->getStyle("A{$rowIndex}:{$grpLastCol}{$rowIndex}")->applyFromArray($sectionHeaderStyle);
                $rowIndex++;
            }

            $headerRowIdx = $rowIndex;
            foreach ($headers as $i => $h) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue("{$col}{$headerRowIdx}", $h);
            }
            $sheet->getStyle("A{$headerRowIdx}:{$grpLastCol}{$headerRowIdx}")->applyFromArray($headerStyle);
            $rowIndex++;

            $dataStartRow = $rowIndex;
            foreach ($rows as $r) {
                foreach ($r as $i => $val) {
                    $col = Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue("{$col}{$rowIndex}", $val);
                }
                $rowIndex++;
            }
            $dataEndRow = $rowIndex - 1;

            if (! empty($rows)) {
                $dataRange = "A{$headerRowIdx}:{$grpLastCol}{$dataEndRow}";
                $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            if (! empty($subtotals)) {
                foreach ($subtotals as $st) {
                    $stCol = 1;
                    foreach ($st as $cell) {
                        $c = Coordinate::stringFromColumnIndex($stCol);
                        $sheet->setCellValue("{$c}{$rowIndex}", $cell);
                        $stCol++;
                    }
                    $sheet->getStyle("A{$rowIndex}:{$grpLastCol}{$rowIndex}")->applyFromArray($subtotalStyle);
                    $sheet->getStyle($grpLastCol.$rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $rowIndex++;
                }
            }

            $rowIndex++;
        }

        foreach ($numberCols as $nc) {
            $col = Coordinate::stringFromColumnIndex($nc);
            $sheet->getStyle("{$col}3:{$col}{$rowIndex}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle($col."3:{$col}{$rowIndex}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $defaultWidths = [5, 12, 28, 22, 20, 18, 14, 18, 18, 16, 16, 16];
        foreach ($defaultWidths as $idx => $w) {
            $col = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        foreach ($columnWidths as $idx => $w) {
            $col = Coordinate::stringFromColumnIndex((int) $idx + 1);
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $sheet->getRowDimension(1)->setRowHeight(22);

        $writer = new Xlsx($spreadsheet);
        $tmpPath = storage_path('app/tmp_'.uniqid().'.xlsx');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function penjualanHarian(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';

        $query = Sale::with(['customer', 'payments', 'user'])
            ->whereYear('tanggal_transaksi', $tahun);
        if ($bulan != '-') $query->whereMonth('tanggal_transaksi', $bulan);
        if ($hari != '-') $query->whereDay('tanggal_transaksi', $hari);

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_contains($data['sub_laporan'], ':')) {
                [$type, $id] = explode(':', $data['sub_laporan']);
                if ($type === 'user') $query->where('user_id', $id);
                elseif ($type === 'cat') {
                    $query->whereHas('saleDetails.product', function ($q) use ($id) {
                        $q->where('category_id', $id);
                    });
                } elseif ($type === 'cus') {
                    $query->where('customer_id', $id);
                }
            } else {
                $query->where('user_id', $data['sub_laporan']);
            }
        }

        $sales = $query->orderBy('tanggal_transaksi', 'desc')->get();

        $summary = [
            'total_transactions' => $sales->count(),
            'total_sales' => $sales->sum('total'),
            'avg_transaction' => $sales->count() > 0 ? $sales->avg('total') : 0,
        ];

        $groups = [
            'Cash' => ['items' => [], 'total' => 0],
            'Transfer/Qris' => ['items' => [], 'total' => 0],
            'Piutang' => ['items' => [], 'total' => 0],
        ];

        foreach ($sales as $sale) {
            $dibayar = (float) $sale->dibayar;
            $utang = (float) $sale->jumlah_utang;

            if ($dibayar > 0) {
                $metode = 'tunai';
                $payment = $sale->payments->whereIn('metode_pembayaran', ['tunai', 'transfer', 'qris', 'cash'])->first();
                if ($payment) $metode = $payment->metode_pembayaran;

                if (in_array(strtolower($metode), ['transfer', 'qris'])) {
                    $groups['Transfer/Qris']['items'][] = ['sale' => $sale, 'amount' => $dibayar, 'metode' => $metode];
                    $groups['Transfer/Qris']['total'] += $dibayar;
                } else {
                    $groups['Cash']['items'][] = ['sale' => $sale, 'amount' => $dibayar, 'metode' => 'Cash'];
                    $groups['Cash']['total'] += $dibayar;
                }
            }

            if ($utang > 0) {
                $groups['Piutang']['items'][] = ['sale' => $sale, 'amount' => $utang, 'metode' => 'Piutang'];
                $groups['Piutang']['total'] += $utang;
            }
        }

        $title = 'Laporan Penjualan Harian';
        $subtitle = $this->periodeSubtitle($tahun, $bulan, $hari);

        $summaryRow = [
            ['Rangkuman', '', ''],
            ['Total Penjualan', 'Jumlah Transaksi', 'Rata-rata'],
            ['Rp '.number_format($summary['total_sales'], 2, '.', ','), $summary['total_transactions'], 'Rp '.number_format($summary['avg_transaction'], 2, '.', ',')],
        ];

        $tableHeaders = ['No', 'No. Invoice', 'Waktu', 'Pelanggan', 'Pembayaran', 'Inisial', 'Nominal', 'Status'];
        $excelGroups = [];
        foreach ($groups as $groupName => $groupData) {
            if (count($groupData['items']) === 0) continue;
            $rows = [];
            foreach ($groupData['items'] as $index => $item) {
                $rows[] = [
                    $index + 1,
                    $item['sale']->no_invoice ?? '-',
                    $item['sale']->tanggal_transaksi,
                    $item['sale']->customer->nama_pelanggan ?? 'Guest',
                    ucfirst($item['metode']),
                    $item['sale']->user->initial ?? '-',
                    (float) $item['amount'],
                    ucfirst($item['sale']->status ?? 'paid'),
                ];
            }
            $excelGroups[] = [
                'title' => $groupName,
                'headers' => $tableHeaders,
                'rows' => $rows,
                'subtotals' => [
                    array_merge(array_fill(0, 6, ''), ['Total '.$groupName, '']),
                ],
            ];
        }

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-penjualan-harian.xlsx',
            [7],
            [5, 22, 20, 22, 14, 10, 16, 12]
        );
    }

    public function stokMinimum(array $data)
    {
        $query = Product::with('category')
            ->whereColumn('stok_aktual', '<=', 'stok_minimal')
            ->where('is_active', true);
        if (isset($data['sub_laporan']) && str_starts_with($data['sub_laporan'], 'cat:')) {
            $catId = str_replace('cat:', '', $data['sub_laporan']);
            $query->where('category_id', $catId);
        }
        $products = $query->get()
            ->map(function ($p) {
                $p->kekurangan = $p->stok_minimal - $p->stok_aktual;
                $p->suggested_order = ($p->stok_minimal * 2) - $p->stok_aktual;
                return $p;
            })->sortByDesc('kekurangan');

        $headers = ['No', 'Produk', 'Kategori', 'Stok Aktual', 'Stok Minimal', 'Kekurangan', 'Saran Order'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->nama_produk ?? $p->product_name,
                $p->category->nama_kategori ?? '-',
                (int) $p->stok_aktual,
                (int) $p->stok_minimal,
                (int) $p->kekurangan,
                (int) $p->suggested_order,
            ];
        }

        return $this->buildExcel(
            'Laporan Stok Minimum',
            'Periode: '.Carbon::now()->isoFormat('MMMM Y'),
            $headers,
            $rows,
            [],
            'laporan-stok-minimum.xlsx',
            [],
            [5, 32, 22, 14, 14, 14, 14]
        );
    }

    public function jurnalTransaksi(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $payments = Payment::where('business_id', auth()->user()->business_id)
            ->where('tanggal_pembayaran', 'LIKE', $tahun.'-'.$bulan.'-%')
            ->with(['accountDebit', 'accountKredit', 'user'])->get();

        $headers = ['No', 'Tanggal', 'No Jurnal', 'Rekening Debit', 'Rekening Kredit', 'Keterangan', 'User', 'Nominal'];
        $rows = [];
        $total = 0;
        foreach ($payments as $i => $p) {
            $rows[] = [
                $i + 1,
                Carbon::parse($p->tanggal_pembayaran)->format('d/m/Y'),
                $p->no_jurnal ?? '-',
                ($p->rekening_debit ?? '-').' '.($p->accountDebit->nama ?? ''),
                ($p->rekening_kredit ?? '-').' '.($p->accountKredit->nama ?? ''),
                $p->keterangan ?? '-',
                $p->user->nama_lengkap ?? '-',
                (float) $p->nominal,
            ];
            $total += (float) $p->nominal;
        }
        $totalsRow = ['', '', '', '', '', '', '', $total];

        return $this->buildExcel(
            'Jurnal Transaksi',
            $this->periodeSubtitle($tahun, $bulan),
            $headers,
            $rows,
            $totalsRow,
            'laporan-jurnal-transaksi.xlsx',
            [8]
        );
    }

    public function bukuBesar(array $data)
    {
        $business = view()->shared('business');
        $kodeAkun = $data['sub_laporan'] ?? null;
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        if (! $kodeAkun) {
            abort(404, 'Sub laporan (kode akun) wajib dipilih');
        }

        $akun = Account::where('kode', $kodeAkun)->with([
            'balance' => function ($query) use ($business, $tahun) {
                $query->where('business_id', $business->id)->where('tahun', $tahun);
            },
        ])->first();

        $payments = Payment::where([
            ['business_id', auth()->user()->business_id],
            ['tanggal_pembayaran', 'LIKE', $tahun.'-'.$bulan.'-%'],
        ])->where(function ($q) use ($kodeAkun) {
            $q->where('rekening_debit', $kodeAkun)->orWhere('rekening_kredit', $kodeAkun);
        })->orderBy('tanggal_pembayaran', 'asc')->orderBy('id', 'asc')->get();

        $saldoAwalDebit = $akun->balance->debit_00 ?? 0;
        $saldoAwalKredit = $akun->balance->kredit_00 ?? 0;

        if ($akun->jenis_mutasi == 'debit') {
            $saldoAwal = $saldoAwalDebit - $saldoAwalKredit;
        } else {
            $saldoAwal = $saldoAwalKredit - $saldoAwalDebit;
        }

        $saldoBulanLaluDebit = 0;
        $saldoBulanLaluKredit = 0;
        for ($i = 1; $i < $bulan; $i++) {
            $colDebit = 'debit_'.str_pad($i, 2, '0', STR_PAD_LEFT);
            $colKredit = 'kredit_'.str_pad($i, 2, '0', STR_PAD_LEFT);
            $saldoBulanLaluDebit += $akun->balance->$colDebit ?? 0;
            $saldoBulanLaluKredit += $akun->balance->$colKredit ?? 0;
        }

        if ($akun->jenis_mutasi == 'debit') {
            $saldoBulanLalu = $saldoBulanLaluDebit - $saldoBulanLaluKredit;
        } else {
            $saldoBulanLalu = $saldoBulanLaluKredit - $saldoBulanLaluDebit;
        }

        $totalDebit = 0;
        $totalKredit = 0;
        $totalSaldo = $saldoAwal + $saldoBulanLalu;

        $title = 'Buku Besar '.($akun->nama ?? '');
        $subtitle = 'Kode Akun: '.$kodeAkun.' | '.$this->periodeSubtitle($tahun, $bulan);

        $headers = ['No', 'Tanggal', 'Ref', 'Keterangan', 'Debit', 'Kredit', 'Saldo', 'P'];
        $rows = [];

        $rows[] = [
            '',
            $tahun.'-01-01',
            '',
            'Komulatif Transaksi Awal Tahun '.$tahun,
            (float) $saldoAwalDebit,
            (float) $saldoAwalKredit,
            (float) $saldoAwal,
            '',
        ];
        $rows[] = [
            '',
            $tahun.'-'.$bulan.'-01',
            '',
            'Komulatif Transaksi s/d Bulan Lalu',
            (float) $saldoBulanLaluDebit,
            (float) $saldoBulanLaluKredit,
            (float) $totalSaldo,
            '',
        ];

        foreach ($payments as $index => $payment) {
            $debit = 0;
            $kredit = 0;

            if ($payment->rekening_debit == $kodeAkun) {
                $debit = (float) $payment->total_harga;
            }
            if ($payment->rekening_kredit == $kodeAkun) {
                $kredit = (float) $payment->total_harga;
            }

            if ($akun->jenis_mutasi == 'debit') {
                $saldo = $debit - $kredit;
            } else {
                $saldo = $kredit - $debit;
            }

            $totalDebit += $debit;
            $totalKredit += $kredit;
            $totalSaldo += $saldo;

            $rows[] = [
                $index + 1,
                Carbon::parse($payment->tanggal_pembayaran)->format('Y-m-d'),
                $payment->id ?? '',
                $payment->catatan ?? '-',
                $debit,
                $kredit,
                $totalSaldo,
                $payment->p ?? '',
            ];
        }

        $namaBulan = '';
        try {
            $namaBulan = Carbon::createFromDate((int) $tahun, (int) $bulan, 1)->isoFormat('MMMM');
        } catch (\Throwable $e) {
            $namaBulan = $bulan;
        }

        $subtotals = [
            [
                '', '', '', 'Total Transaksi Bulan '.$namaBulan,
                $totalDebit, $totalKredit, $totalSaldo, '',
            ],
            [
                '', '', '', 'Total Transaksi Sampai Dengan Bulan '.$namaBulan,
                $totalDebit + $saldoBulanLaluDebit, $totalKredit + $saldoBulanLaluKredit, '', '',
            ],
            [
                '', '', '', 'Total Transaksi Komulatif Sampai Dengan '.$tahun,
                $totalDebit + $saldoBulanLaluDebit + $saldoAwalDebit, $totalKredit + $saldoBulanLaluKredit + $saldoAwalKredit, '', '',
            ],
        ];

        $excelGroups = [
            [
                'title' => null,
                'headers' => $headers,
                'rows' => $rows,
                'subtotals' => $subtotals,
            ],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            [],
            $excelGroups,
            'laporan-buku-besar-'.str_replace('.', '_', $kodeAkun).'.xlsx',
            [5, 6, 7],
            [5, 14, 10, 44, 16, 16, 18, 5]
        );
    }

    public function neraca(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $akunLevel1s = AkunLevel1::with([
            'akunLevel2.akunLevel3.accounts' => function ($query) use ($business) {
                $query->where('business_id', $business->id);
            },
            'akunLevel2.akunLevel3.accounts.balance' => function ($query) use ($business, $tahun) {
                $query->where('business_id', $business->id)->where('tahun', $tahun);
            },
        ])->where('id', '<=', '3')->get();

        $title = 'Laporan Neraca';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $headers = ['Kode', 'Nama Akun', 'Saldo'];
        $rows = [];
        $saldoAkunLevel1 = [];
        $totalLiabilitasEkuitas = 0;

        $excelGroups = [];
        foreach ($akunLevel1s as $akunLevel1) {
            $groupRows = [];
            $saldoAkunLevel1[$akunLevel1->id] = 0;

            foreach ($akunLevel1->akunLevel2 as $akunLevel2) {
                foreach ($akunLevel2->akunLevel3 as $akunLevel3) {
                    $saldoAkun = 0;
                    foreach ($akunLevel3->accounts as $account) {
                        $saldo = KeuanganUtil::sumSaldo($account, (int) $bulan);
                        if ($account->kode == '3.2.02.01') {
                            $saldo = KeuanganUtil::saldoLabaRugi($tahun, $bulan);
                        }
                        $saldoAkun += $saldo;
                    }
                    $saldoAkunLevel1[$akunLevel1->id] += $saldoAkun;
                    $groupRows[] = [
                        $akunLevel3->kode.'.',
                        $akunLevel3->nama,
                        (float) $saldoAkun,
                    ];
                }
            }

            if ($akunLevel1->id == 2 || $akunLevel1->id == 3) {
                $totalLiabilitasEkuitas += $saldoAkunLevel1[$akunLevel1->id];
            }

            $excelGroups[] = [
                'title' => $akunLevel1->kode.'. '.$akunLevel1->nama,
                'headers' => $headers,
                'rows' => $groupRows,
                'subtotals' => [
                    ['', 'Jumlah '.$akunLevel1->nama, (float) $saldoAkunLevel1[$akunLevel1->id]],
                ],
            ];
        }

        $excelGroups[] = [
            'title' => '',
            'headers' => $headers,
            'rows' => [],
            'subtotals' => [
                ['', 'Jumlah Liabilitas + Ekuitas', (float) $totalLiabilitasEkuitas],
            ],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            [],
            $excelGroups,
            'laporan-neraca.xlsx',
            [3],
            [14, 48, 18]
        );
    }

    public function calk(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $akunLevel1s = AkunLevel1::with([
            'akunLevel2.akunLevel3.accounts' => function ($query) use ($business) {
                $query->where('business_id', $business->id);
            },
            'akunLevel2.akunLevel3.accounts.balance' => function ($query) use ($business, $tahun) {
                $query->where('business_id', $business->id)->where('tahun', $tahun);
            },
        ])->where('id', '<=', '3')->get();

        $headers = ['Kelompok', 'Kode Akun', 'Nama Akun', 'Keterangan / Catatan', 'Saldo'];
        $rows = [];
        foreach ($akunLevel1s as $a1) {
            foreach ($a1->akunLevel2 as $a2) {
                foreach ($a2->akunLevel3 as $a3) {
                    foreach ($a3->accounts as $acc) {
                        $saldo = (float) KeuanganUtil::sumSaldo($acc, (int) $bulan);
                        $rows[] = [
                            $a1->nama,
                            $acc->kode,
                            $acc->nama,
                            $acc->keterangan ?? '-',
                            $saldo,
                        ];
                    }
                }
            }
        }

        return $this->buildExcel(
            'Catatan Atas Laporan Keuangan (CALK)',
            $this->periodeSubtitle($tahun, $bulan),
            $headers,
            $rows,
            [],
            'laporan-calk.xlsx',
            [5],
            [22, 16, 32, 44, 18]
        );
    }

    public function labaRugi(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $result = KeuanganUtil::labaRugi($tahun, $bulan);
        $labaRugi = $result['groups'];
        $metrics = $result['metrics'];

        $title = 'Laporan Laba Rugi';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $headers = ['Kode', 'Nama Akun', 'S/D Bln Lalu', 'Bln Ini', 'S/D Bln Ini'];
        $excelGroups = [];

        foreach ($labaRugi as $index => $lr) {
            $rows = [];
            if (! empty($lr['kode'])) {
                foreach ($lr['kode'] as $kode) {
                    $rows[] = [
                        $kode['kode'] ?? '',
                        $kode['nama'] ?? '',
                        (float) ($kode['saldo_sd_lalu'] ?? 0),
                        (float) ($kode['saldo_bulan_ini'] ?? 0),
                        (float) ($kode['saldo_sd_ini'] ?? 0),
                    ];
                }
            }

            $footerLabel = null;
            if ($index == 0) {
                $footerLabel = 'Total Pendapatan';
            } elseif ($index == 1) {
                $footerLabel = 'LABA KOTOR';
            } elseif ($index == 2) {
                $footerLabel = 'Total Beban';
            } elseif ($index == 3) {
                $footerLabel = 'Laba Bersih';
            }

            $subtotals = [];
            if ($footerLabel) {
                $subtotals[] = [
                    '', $footerLabel,
                    (float) ($lr['total_sd_lalu'] ?? 0),
                    (float) ($lr['total_bulan_ini'] ?? 0),
                    (float) ($lr['total_sd_ini'] ?? 0),
                ];
            }

            if ($index == 2) {
                $subtotals[] = [
                    '', 'Laba Sebelum Pajak',
                    (float) ($lr['total_sd_lalu'] ?? 0),
                    (float) ($lr['total_bulan_ini'] ?? 0),
                    (float) ($lr['total_sd_ini'] ?? 0),
                ];
            }

            $excelGroups[] = [
                'title' => $lr['nama'] ?? '-',
                'headers' => $headers,
                'rows' => $rows,
                'subtotals' => $subtotals,
            ];
        }

        $excelGroups[] = [
            'title' => 'METRICS',
            'headers' => ['Kode', 'Nama Akun', '', '', 'Nilai'],
            'rows' => [
                ['', 'Margin Kotor (%)', '', '', (float) ($metrics['margin_kotor'] ?? 0)],
                ['', 'Margin Bersih (%)', '', '', (float) ($metrics['margin_bersih'] ?? 0)],
            ],
            'subtotals' => [],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            [],
            $excelGroups,
            'laporan-laba-rugi.xlsx',
            [3, 4, 5],
            [16, 40, 18, 18, 18]
        );
    }

    public function arusKas(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');
        $bulanLalu = max((int) $bulan - 1, 0);

        $arusKas = KeuanganUtil::arusKas($tahun, $bulan);
        $saldoKas = (float) KeuanganUtil::saldoKas($tahun, $bulanLalu);

        $title = 'Laporan Arus Kas';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $headers = ['', 'Nama Akun', 'Saldo'];
        $excelGroups = [];

        foreach ($arusKas as $index => $ak) {
            $rows = [];
            $subtotals = [];

            if ($ak['header']) {
                $rows[] = [
                    $index + 1,
                    $ak['header']->nama_akun ?? '-',
                    $index == 0 ? (float) $saldoKas : '',
                ];
            }

            $grandTotal = [];
            foreach ($ak['groups'] as $indexGroup => $group) {
                if ($group['subheader']) {
                    $rows[] = ['', $group['subheader']->nama_akun ?? '', ''];
                }

                $total = 0;
                foreach ($group['items'] as $item) {
                    $rows[] = ['', $item->nama_akun ?? '-', (float) $item->total];
                    $total += (float) $item->total;
                }

                $titleJumlah = $ak['header']->nama_akun ?? '';
                if ($group['subheader']) {
                    $titleJumlah = $group['subheader']->nama_akun ?? '';
                }

                if (strtolower($titleJumlah) != 'pengeluaran') {
                    $grandTotal[$indexGroup] = $total;
                    $rows[] = ['', 'Jumlah '.$titleJumlah, (float) $total];
                }
            }

            if ($index > 0) {
                $totalBawah = 0;
                foreach ($grandTotal as $indexGrandTotal => $jumlahBawah) {
                    if ($indexGrandTotal == 0) {
                        $totalBawah += $jumlahBawah;
                    } else {
                        $totalBawah -= $jumlahBawah;
                    }
                }

                $label = '';
                if ($index == 1) {
                    $label = 'Kas Bersih yang diperoleh dari aktivitas Operasi (A-B-C)';
                } elseif ($index == 2) {
                    $label = 'Kas Bersih yang diperoleh dari aktivitas Investasi (A-B)';
                } elseif ($index == 3) {
                    $label = 'Kas Bersih yang diperoleh dari aktivitas Pendanaan (A-B)';
                }
                $subtotals[] = ['', $label, (float) $totalBawah];
            }

            $excelGroups[] = [
                'title' => $ak['header'] ? $ak['header']->nama_akun : '',
                'headers' => $headers,
                'rows' => $rows,
                'subtotals' => $subtotals,
            ];
        }

        $totalArusKas = 0;
        foreach ($excelGroups as $g) {
            foreach ($g['subtotals'] as $st) {
                if (is_numeric($st[2])) {
                    $totalArusKas += (float) $st[2];
                }
            }
        }

        $finalGroup = [
            'title' => '',
            'headers' => $headers,
            'rows' => [],
            'subtotals' => [
                ['', 'Kenaikan (Penurunan) Kas', (float) $totalArusKas],
                ['', 'SALDO AKHIR KAS SETARA KAS', (float) $totalArusKas + $saldoKas],
            ],
        ];
        $excelGroups[] = $finalGroup;

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            [],
            $excelGroups,
            'laporan-arus-kas.xlsx',
            [3],
            [8, 50, 22]
        );
    }

    public function asetTetapInventaris(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $tgl_kondisi = Carbon::createFromDate((int) $tahun, $bulan == '-' ? 12 : (int) $bulan, 1)->endOfMonth()->format('Y-m-d');

        $inventarisGroups = Inventory::where([
            ['jenis', '1'],
            ['status', '!=', '0'],
            ['tanggal_beli', '<=', $tgl_kondisi],
            ['harga_satuan', '>', '0'],
        ])->whereNotNull('tanggal_beli')
          ->whereIn('kategori', [1, 2, 3, 4])
          ->orderBy('kategori', 'ASC')->orderBy('tanggal_beli', 'ASC')
          ->get()->groupBy('kategori');

        $kategoriNaman = [
            1 => 'Tanah',
            2 => 'Bangunan',
            3 => 'Kendaraan',
            4 => 'Peralatan',
        ];

        $headers = ['Kategori', 'Nama Barang', 'Tanggal Beli', 'Tanggal Validasi', 'Status', 'Jumlah', 'Harga Satuan', 'Nilai Perolehan', 'Nilai Buku'];
        $rows = [];
        foreach ($inventarisGroups as $kategori => $items) {
            $namaKategori = $kategoriNaman[$kategori] ?? 'Kategori '.$kategori;
            foreach ($items as $inv) {
                $nilaiBuku = 0;
                try {
                    $nilaiBuku = (float) InventarisUtil::nilaiBuku($tgl_kondisi, $inv);
                } catch (\Throwable $e) {
                    $nilaiBuku = (float) $inv->harga_satuan;
                }
                $rows[] = [
                    $namaKategori,
                    $inv->nama_barang ?? '-',
                    $inv->tanggal_beli ? Carbon::parse($inv->tanggal_beli)->format('d/m/Y') : '-',
                    $inv->tanggal_validasi ? Carbon::parse($inv->tanggal_validasi)->format('d/m/Y') : '-',
                    ucfirst($inv->status ?? '-'),
                    (int) ($inv->jumlah ?? 1),
                    (float) $inv->harga_satuan,
                    (float) $inv->harga_satuan * (int) ($inv->jumlah ?? 1),
                    $nilaiBuku,
                ];
            }
        }

        return $this->buildExcel(
            'Aset Tetap dan Inventaris',
            $this->periodeSubtitle($tahun, $bulan),
            $headers,
            $rows,
            [],
            'laporan-aset-tetap-inventaris.xlsx',
            [7, 8, 9],
            [14, 36, 14, 14, 12, 10, 16, 18, 18]
        );
    }

    public function penjualanProduk(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';

        $query = SaleDetail::with(['sale.customer', 'product.unit'])
            ->whereHas('sale', function ($q) use ($business, $tahun, $bulan, $hari) {
                $q->where('business_id', $business->id);
                if ($bulan != '-') {
                    $q->whereYear('tanggal_transaksi', $tahun)->whereMonth('tanggal_transaksi', $bulan);
                } else {
                    $q->whereYear('tanggal_transaksi', $tahun);
                }
                if ($hari != '-') $q->whereDay('tanggal_transaksi', $hari);
            });

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_starts_with($data['sub_laporan'], 'prod:')) {
                $query->where('product_id', str_replace('prod:', '', $data['sub_laporan']));
            } elseif (str_starts_with($data['sub_laporan'], 'cus:')) {
                $cusId = str_replace('cus:', '', $data['sub_laporan']);
                $query->whereHas('sale', function ($q) use ($cusId) { $q->where('customer_id', $cusId); });
            }
        }

        $sales = $query->orderBy('id', 'desc')->get();
        $total = $sales->sum('subtotal');

        $headers = ['No', 'Product ID', 'Produk', 'Pelanggan', 'No Faktur', 'Tanggal', 'Qty', 'Harga Satuan', 'Subtotal'];
        $rows = [];
        foreach ($sales as $i => $row) {
            $rows[] = [
                $i + 1,
                $row->product_id,
                $row->product->nama_produk ?? '-',
                $row->sale->customer->nama_pelanggan ?? 'Guest',
                $row->sale->no_invoice ?? '-',
                Carbon::parse($row->sale->tanggal_transaksi)->format('d/m/Y H:i'),
                (float) $row->jumlah,
                (float) $row->harga_satuan,
                (float) $row->subtotal,
            ];
        }
        $totalsRow = ['', '', '', '', '', '', '', 'Total:', (float) $total];

        return $this->buildExcel(
            'Laporan Penjualan Produk',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            $headers,
            $rows,
            $totalsRow,
            'laporan-penjualan-produk.xlsx',
            [7, 8, 9]
        );
    }

    public function pembelianProduk(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';

        $query = PurchaseDetail::with(['purchase.supplier', 'product.unit'])
            ->whereHas('purchase', function ($q) use ($business, $tahun, $bulan, $hari) {
                $q->where('business_id', $business->id);
                if ($bulan != '-') {
                    $q->whereYear('tanggal_pembelian', $tahun)->whereMonth('tanggal_pembelian', $bulan);
                } else {
                    $q->whereYear('tanggal_pembelian', $tahun);
                }
                if ($hari != '-') $q->whereDay('tanggal_pembelian', $hari);
            });

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_starts_with($data['sub_laporan'], 'prod:')) {
                $query->where('product_id', str_replace('prod:', '', $data['sub_laporan']));
            } elseif (str_starts_with($data['sub_laporan'], 'sup:')) {
                $supId = str_replace('sup:', '', $data['sub_laporan']);
                $query->whereHas('purchase', function ($q) use ($supId) { $q->where('supplier_id', $supId); });
            }
        }

        $purchases = $query->orderBy('id', 'desc')->get();
        $total = $purchases->sum('subtotal');

        $headers = ['No', 'Product ID', 'Produk', 'Supplier', 'No Pembelian', 'Tanggal', 'Qty', 'Harga Satuan', 'Subtotal'];
        $rows = [];
        foreach ($purchases as $i => $row) {
            $rows[] = [
                $i + 1,
                $row->product_id,
                $row->product->nama_produk ?? '-',
                $row->purchase->supplier->nama_supplier ?? '-',
                $row->purchase->no_pembelian ?? '-',
                Carbon::parse($row->purchase->tanggal_pembelian)->format('d/m/Y H:i'),
                (float) $row->jumlah,
                (float) $row->harga_satuan,
                (float) $row->subtotal,
            ];
        }
        $totalsRow = ['', '', '', '', '', '', '', 'Total:', (float) $total];

        return $this->buildExcel(
            'Laporan Pembelian Produk',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            $headers,
            $rows,
            $totalsRow,
            'laporan-pembelian-produk.xlsx',
            [7, 8, 9]
        );
    }

    public function produkTerlaris(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = SaleDetail::select(
            'product_id',
            DB::raw('SUM(jumlah) as total_terjual'),
            DB::raw('SUM(subtotal) as total_revenue'),
            DB::raw('SUM(profit) as total_profit')
        )
            ->whereHas('sale', function ($q) use ($tahun, $bulan) {
                $q->whereYear('tanggal_transaksi', $tahun);
                if ($bulan != '-') $q->whereMonth('tanggal_transaksi', $bulan);
            })
            ->when(isset($data['sub_laporan']) && str_starts_with($data['sub_laporan'], 'cat:'), function ($q) use ($data) {
                $catId = str_replace('cat:', '', $data['sub_laporan']);
                $q->whereHas('product', function ($sq) use ($catId) { $sq->where('category_id', $catId); });
            })
            ->groupBy('product_id')->orderByDesc('total_terjual')->limit(20)
            ->with('product.category')->get();

        $headers = ['No', 'Produk', 'Kategori', 'Qty Terjual', 'Pendapatan', 'Profit'];
        $rows = [];
        $sumQty = 0; $sumRev = 0; $sumProf = 0;
        foreach ($query as $i => $item) {
            $rows[] = [
                $i + 1,
                $item->product->nama_produk ?? '-',
                $item->product->category->nama_kategori ?? '-',
                (float) $item->total_terjual,
                (float) $item->total_revenue,
                (float) $item->total_profit,
            ];
            $sumQty += (float) $item->total_terjual;
            $sumRev += (float) $item->total_revenue;
            $sumProf += (float) $item->total_profit;
        }
        $totalsRow = ['', '', 'Total', $sumQty, $sumRev, $sumProf];

        return $this->buildExcel(
            'Laporan Produk Terlaris',
            $this->periodeSubtitle($tahun, $bulan).' (Top 20)',
            $headers,
            $rows,
            $totalsRow,
            'laporan-produk-terlaris.xlsx',
            [4, 5, 6],
            [5, 36, 22, 14, 18, 18]
        );
    }

    public function piutang(array $data)
    {
        $sales = Sale::with('customer')
            ->where('jumlah_utang', '>', 0)
            ->orderBy('tanggal_transaksi', 'asc')->get();

        $grouped = $sales->groupBy('customer_id')->map(function ($items) {
            return [
                'customer' => $items->first()->customer,
                'total_piutang' => $items->sum('jumlah_utang'),
                'jumlah_invoice' => $items->count(),
                'items' => $items,
            ];
        })->sortByDesc('total_piutang');

        $totalPiutang = $sales->sum('jumlah_utang');

        $title = 'Laporan Piutang (Customer)';
        $subtitle = 'Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y');

        $summaryRow = [
            ['Total Piutang: Rp '.number_format($totalPiutang, 2, '.', ','), '', '', '', '', ''],
        ];

        $tableHeaders = ['No. Invoice', 'Tanggal', 'Total', 'Dibayar', 'Sisa Piutang', 'Umur (Hari)'];
        $excelGroups = [];
        foreach ($grouped as $group) {
            $rows = [];
            foreach ($group['items'] as $sale) {
                $umur = Carbon::parse($sale->tanggal_transaksi)->diffInDays(now());
                $rows[] = [
                    $sale->no_invoice ?? '-',
                    Carbon::parse($sale->tanggal_transaksi)->format('d/m/Y'),
                    (float) $sale->total,
                    (float) $sale->dibayar,
                    (float) $sale->jumlah_utang,
                    $umur.' hari',
                ];
            }
            $excelGroups[] = [
                'title' => ($group['customer']->nama_pelanggan ?? 'Guest').' ('.$group['jumlah_invoice'].' invoice)',
                'headers' => $tableHeaders,
                'rows' => $rows,
                'subtotals' => [
                    ['', '', '', 'Subtotal', (float) $group['total_piutang'], ''],
                ],
            ];
        }

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-piutang.xlsx',
            [3, 4, 5],
            [22, 14, 16, 16, 16, 14]
        );
    }

    public function hutang(array $data)
    {
        $purchases = Purchase::with('supplier')
            ->where('jumlah_utang', '>', 0)
            ->orderBy('tanggal_pembelian', 'asc')->get();

        $grouped = $purchases->groupBy('supplier_id')->map(function ($items) {
            return [
                'supplier' => $items->first()->supplier,
                'total_hutang' => $items->sum('jumlah_utang'),
                'jumlah_po' => $items->count(),
                'items' => $items,
            ];
        })->sortByDesc('total_hutang');

        $totalHutang = $purchases->sum('jumlah_utang');

        $title = 'Laporan Hutang (Supplier)';
        $subtitle = 'Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y');

        $summaryRow = [
            ['Total Hutang: Rp '.number_format($totalHutang, 2, '.', ','), '', '', '', '', ''],
        ];

        $tableHeaders = ['No. Pembelian', 'Tanggal', 'Total', 'Dibayar', 'Sisa Hutang', 'Umur (Hari)'];
        $excelGroups = [];
        foreach ($grouped as $group) {
            $rows = [];
            foreach ($group['items'] as $purchase) {
                $umur = Carbon::parse($purchase->tanggal_pembelian)->diffInDays(now());
                $rows[] = [
                    $purchase->no_pembelian ?? '-',
                    Carbon::parse($purchase->tanggal_pembelian)->format('d/m/Y'),
                    (float) $purchase->total,
                    (float) $purchase->dibayar,
                    (float) $purchase->jumlah_utang,
                    $umur.' hari',
                ];
            }
            $excelGroups[] = [
                'title' => ($group['supplier']->nama_supplier ?? '-').' ('.$group['jumlah_po'].' PO)',
                'headers' => $tableHeaders,
                'rows' => $rows,
                'subtotals' => [
                    ['', '', '', 'Subtotal', (float) $group['total_hutang'], ''],
                ],
            ];
        }

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-hutang.xlsx',
            [3, 4, 5],
            [22, 14, 16, 16, 16, 14]
        );
    }

    public function stokOpname(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = StockOpname::whereYear('tanggal_opname', $tahun)
            ->whereHas('details', function ($q) { $q->where('selisih', '!=', 0); })
            ->with(['details' => function ($q) {
                $q->where('selisih', '!=', 0)->with('product');
            }, 'user']);

        if ($bulan != '-') $query->whereMonth('tanggal_opname', $bulan);

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_starts_with($data['sub_laporan'], 'rak:')) {
                $rakId = str_replace('rak:', '', $data['sub_laporan']);
                $query->whereHas('details.product', function ($q) use ($rakId) { $q->where('shelf_id', $rakId); });
            } elseif (str_starts_with($data['sub_laporan'], 'cat:')) {
                $catId = str_replace('cat:', '', $data['sub_laporan']);
                $query->whereHas('details.product', function ($q) use ($catId) { $q->where('category_id', $catId); });
            }
        }

        $opnames = $query->orderBy('tanggal_opname', 'desc')->get();

        $title = 'Laporan Stok Opname';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $summaryRow = [];

        $tableHeaders = ['No', 'Produk', 'Stok Sistem', 'Stok Fisik', 'Selisih', 'Jenis', 'Nilai Selisih', 'Alasan'];
        $excelGroups = [];
        foreach ($opnames as $opname) {
            $rows = [];
            $detailNo = 0;
            foreach ($opname->details as $detail) {
                $detailNo++;
                $rows[] = [
                    $detailNo,
                    $detail->product->nama_produk ?? '-',
                    (float) ($detail->stok_sistem ?? 0),
                    (float) ($detail->stok_fisik ?? 0),
                    (float) ($detail->selisih ?? 0),
                    $detail->jenis_selisih ?? '-',
                    (float) ($detail->total_harga ?? 0),
                    $detail->alasan ?? '-',
                ];
            }
            $excelGroups[] = [
                'title' => ($opname->no_opname ?? '-').' | '.Carbon::parse($opname->tanggal_opname)->format('d/m/Y')
                    .' | Status: '.($opname->status ?? '-').' | Petugas: '.($opname->user->name ?? '-'),
                'headers' => $tableHeaders,
                'rows' => $rows,
                'subtotals' => [],
            ];
        }

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-stok-opname.xlsx',
            [3, 4, 5, 7],
            [5, 32, 14, 14, 12, 14, 16, 32]
        );
    }

    public function buktiStokOpname(array $data)
    {
        $business = view()->shared('business');
        $id = $data['id'] ?? null;
        if (! $id) {
            abort(404, 'ID Stock Opname tidak ditemukan');
        }

        $opname = StockOpname::with(['details' => function ($q) {
            $q->where('selisih', '!=', 0)->with('product');
        }, 'user', 'approvedBy'])->where('business_id', $business->id)->findOrFail($id);

        $headers = ['No', 'Produk', 'Stok Sistem', 'Stok Aktual', 'Selisih', 'Catatan'];
        $rows = [];
        foreach ($opname->details as $i => $d) {
            $rows[] = [
                $i + 1,
                $d->product->nama_produk ?? '-',
                (float) ($d->stok_sistem ?? 0),
                (float) ($d->stok_aktual ?? 0),
                (float) ($d->selisih ?? 0),
                $d->catatan ?? '-',
            ];
        }

        return $this->buildExcel(
            'Bukti Stock Opname '.$opname->no_opname,
            'Tanggal: '.Carbon::parse($opname->tanggal_opname)->format('d/m/Y').' | Status: '.strtoupper($opname->status ?? '-'),
            $headers,
            $rows,
            [],
            'bukti-so-'.$opname->no_opname.'.xlsx',
            [3, 4, 5],
            [5, 36, 14, 14, 12, 32]
        );
    }

    public function formStockOpname(array $data)
    {
        $business = view()->shared('business');
        $categoryId = $data['categoryId'] ?? null;
        $shelfId = $data['shelfId'] ?? null;
        $opnameId = $data['opnameId'] ?? null;

        $categoryName = '-';
        $shelfName = '-';
        $catatan = '-';

        $query = Product::where('business_id', auth()->user()->business_id)
            ->where('is_active', true);

        if ($opnameId) {
            $opname = StockOpname::find($opnameId);
            if ($opname) {
                $catatan = $opname->catatan ?: '-';
                $b = Business::find($opname->business_id);
                if ($b) {
                    $business = $b;
                    view()->share('business', $business);
                }
            }
            $query->whereIn('id', function ($q) use ($opnameId) {
                $q->select('product_id')->from('stock_opname_details')->where('stock_opname_id', $opnameId);
            });
        } else {
            if ($categoryId) {
                $query->where('category_id', $categoryId);
                $categoryName = \App\Models\Category::find($categoryId)?->nama_kategori ?: '-';
            }
            if ($shelfId) {
                $query->where('shelf_id', $shelfId);
                $shelfName = \App\Models\Shelves::find($shelfId)?->nama_rak ?: '-';
            }
        }

        $products = $query->orderBy('nama_produk')->get();
        $subtitleParts = ['Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y')];
        if ($categoryName !== '-') $subtitleParts[] = 'Kategori: '.$categoryName;
        if ($shelfName !== '-') $subtitleParts[] = 'Rak: '.$shelfName;

        $headers = ['No', 'Produk', 'Stok Sistem', 'Stok Aktual', 'Selisih', 'Catatan'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->nama_produk,
                (int) $p->stok_aktual,
                '',
                '',
                '',
            ];
        }

        return $this->buildExcel(
            'Form Stock Opname (Lembar Kerja)',
            implode(' | ', $subtitleParts),
            $headers,
            $rows,
            [],
            'form-stock-opname.xlsx',
            [],
            [5, 36, 16, 16, 12, 32]
        );
    }

    public function pembelian(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = Purchase::with('supplier')->whereYear('tanggal_pembelian', $tahun);
        if ($bulan != '-') $query->whereMonth('tanggal_pembelian', $bulan);
        if (isset($data['sub_laporan']) && str_starts_with($data['sub_laporan'], 'sup:')) {
            $supId = str_replace('sup:', '', $data['sub_laporan']);
            $query->where('supplier_id', $supId);
        }

        $purchases = $query->orderBy('tanggal_pembelian', 'desc')->get();

        $summary = [
            'total_po' => $purchases->count(),
            'total_pembelian' => $purchases->sum('total'),
            'total_dibayar' => $purchases->sum('dibayar'),
            'total_hutang' => $purchases->sum('jumlah_utang'),
        ];

        $title = 'Laporan Pembelian';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $summaryRow = [
            ['Jumlah PO', 'Total Pembelian', 'Total Dibayar', 'Total Hutang'],
            [
                $summary['total_po'],
                'Rp '.number_format($summary['total_pembelian'], 2, '.', ','),
                'Rp '.number_format($summary['total_dibayar'], 2, '.', ','),
                'Rp '.number_format($summary['total_hutang'], 2, '.', ','),
            ],
        ];

        $headers = ['No', 'No. Pembelian', 'Tanggal', 'Supplier', 'Pembayaran', 'Total', 'Hutang', 'Status'];
        $rows = [];
        foreach ($purchases as $i => $purchase) {
            $rows[] = [
                $i + 1,
                $purchase->no_pembelian ?? '-',
                Carbon::parse($purchase->tanggal_pembelian)->format('d/m/Y'),
                $purchase->supplier->nama_supplier ?? '-',
                ucfirst($purchase->jenis_pembayaran ?? '-'),
                (float) $purchase->total,
                (float) $purchase->jumlah_utang,
                $purchase->status ?? '-',
            ];
        }
        $totalsRow = ['', '', '', '', 'Total', (float) $purchases->sum('total'), (float) $purchases->sum('jumlah_utang'), ''];

        $excelGroups = [
            [
                'title' => 'Rincian Pembelian',
                'headers' => $headers,
                'rows' => $rows,
                'subtotals' => [$totalsRow],
            ],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-pembelian.xlsx',
            [6, 7],
            [5, 22, 14, 28, 14, 16, 16, 14]
        );
    }

    public function marginProduk(array $data)
    {
        $business = view()->shared('business');
        $products = Product::where('business_id', $business->id)
            ->where('is_active', true)->where('harga_jual', '>', 0)
            ->with('category')->get()
            ->map(function ($p) {
                $p->margin_rp = $p->harga_jual - $p->biaya_rata_rata;
                $p->margin_pct = $p->harga_jual > 0 ? (($p->harga_jual - $p->biaya_rata_rata) / $p->harga_jual) * 100 : 0;
                return $p;
            })->sortByDesc('margin_pct');

        $headers = ['No', 'Produk', 'Kategori', 'Harga Jual', 'Biaya Rata-rata', 'Margin (Rp)', 'Margin (%)'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->nama_produk,
                $p->category->nama_kategori ?? '-',
                (float) $p->harga_jual,
                (float) $p->biaya_rata_rata,
                (float) $p->margin_rp,
                (float) $p->margin_pct,
            ];
        }

        return $this->buildExcel(
            'Laporan Margin & Profitabilitas Produk',
            'Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y'),
            $headers,
            $rows,
            [],
            'laporan-margin-produk.xlsx',
            [4, 5, 6, 7],
            [5, 36, 22, 16, 18, 16, 14]
        );
    }

    public function customerTerbaik(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $query = Sale::select(
            'customer_id',
            DB::raw('COUNT(*) as jumlah_transaksi'),
            DB::raw('SUM(total) as total_belanja'),
            DB::raw('AVG(total) as rata_rata')
        )
            ->where('business_id', $business->id)
            ->whereYear('tanggal_transaksi', $tahun);
        if ($bulan != '-') $query->whereMonth('tanggal_transaksi', $bulan);

        $customers = $query->groupBy('customer_id')->orderByDesc('total_belanja')->limit(20)
            ->with('customer')->get();

        $headers = ['No', 'Pelanggan', 'Jumlah Transaksi', 'Total Belanja', 'Rata-rata'];
        $rows = [];
        foreach ($customers as $i => $c) {
            $rows[] = [
                $i + 1,
                $c->customer->nama_pelanggan ?? 'Guest',
                (int) $c->jumlah_transaksi,
                (float) $c->total_belanja,
                (float) $c->rata_rata,
            ];
        }

        return $this->buildExcel(
            'Laporan Customer Terbaik',
            $this->periodeSubtitle($tahun, $bulan).' (Top 20)',
            $headers,
            $rows,
            [],
            'laporan-customer-terbaik.xlsx',
            [4, 5],
            [5, 32, 18, 18, 18]
        );
    }

    public function inventoryTurnover(array $data)
    {
        $business = view()->shared('business');
        $products = Product::where('business_id', $business->id)
            ->with('category')->where('is_active', true)->where('stok_aktual', '>', 0)
            ->get()
            ->map(function ($p) {
                $terjual30 = SaleDetail::where('product_id', $p->id)
                    ->whereHas('sale', function ($q) {
                        $q->where('tanggal_transaksi', '>=', Carbon::now()->subDays(30));
                    })->sum('jumlah');
                $p->terjual_30hari = $terjual30;
                $avgDailySales = $terjual30 / 30;
                $p->days_in_inventory = $avgDailySales > 0 ? round($p->stok_aktual / $avgDailySales) : null;
                $p->turnover_ratio = $p->stok_aktual > 0 && $terjual30 > 0 ? round($terjual30 / $p->stok_aktual, 2) : 0;
                $p->nilai_stok = $p->stok_aktual * $p->biaya_rata_rata;
                return $p;
            })->sortByDesc('turnover_ratio');

        $headers = ['No', 'Produk', 'Kategori', 'Stok', 'Terjual 30hr', 'Turnover Ratio', 'Days in Inv', 'Nilai Stok'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->nama_produk,
                $p->category->nama_kategori ?? '-',
                (int) $p->stok_aktual,
                (int) $p->terjual_30hari,
                (float) $p->turnover_ratio,
                $p->days_in_inventory !== null ? (int) $p->days_in_inventory : '-',
                (float) $p->nilai_stok,
            ];
        }

        return $this->buildExcel(
            'Laporan Inventory Turnover',
            '30 Hari Terakhir | Per Tanggal: '.Carbon::now()->isoFormat('D MMMM Y'),
            $headers,
            $rows,
            [],
            'laporan-inventory-turnover.xlsx',
            [6, 8],
            [5, 32, 22, 12, 14, 16, 14, 18]
        );
    }

    public function retur(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? date('m');

        $salesReturnQuery = SalesReturn::with(['sale.customer', 'user'])->whereYear('tanggal_return', $tahun);
        $purchaseReturnQuery = PurchasesReturn::with(['purchase.supplier', 'user'])->whereYear('tanggal_return', $tahun);
        if ($bulan != '-') {
            $salesReturnQuery->whereMonth('tanggal_return', $bulan);
            $purchaseReturnQuery->whereMonth('tanggal_return', $bulan);
        }
        $salesReturns = $salesReturnQuery->orderBy('tanggal_return', 'desc')->get();
        $purchaseReturns = $purchaseReturnQuery->orderBy('tanggal_return', 'desc')->get();

        $title = 'Laporan Retur';
        $subtitle = $this->periodeSubtitle($tahun, $bulan);

        $summaryRow = [];

        $salesHeaders = ['No', 'No. Return', 'Tanggal', 'No. Invoice', 'Customer', 'Nilai Return', 'Alasan', 'Status'];
        $salesRows = [];
        foreach ($salesReturns as $index => $sr) {
            $salesRows[] = [
                $index + 1,
                $sr->no_return ?? '-',
                Carbon::parse($sr->tanggal_return)->format('d/m/Y'),
                $sr->sale->no_invoice ?? '-',
                $sr->sale->customer->nama_pelanggan ?? 'Guest',
                (float) $sr->total_return,
                $sr->alasan_return ?? '-',
                $sr->status ?? '-',
            ];
        }

        $purchaseHeaders = ['No', 'No. Return', 'Tanggal', 'No. Pembelian', 'Supplier', 'Nilai Return', 'Alasan', 'Status'];
        $purchaseRows = [];
        foreach ($purchaseReturns as $index => $pr) {
            $purchaseRows[] = [
                $index + 1,
                $pr->no_return ?? '-',
                Carbon::parse($pr->tanggal_return)->format('d/m/Y'),
                $pr->purchase->no_pembelian ?? '-',
                $pr->purchase->supplier->nama_supplier ?? '-',
                (float) $pr->total_return,
                $pr->alasan_return ?? '-',
                $pr->status ?? '-',
            ];
        }

        $excelGroups = [];
        $excelGroups[] = [
            'title' => 'A. Retur Penjualan (dari Customer)',
            'headers' => $salesHeaders,
            'rows' => $salesRows,
            'subtotals' => !empty($salesRows) ? [['', '', '', '', 'Total Retur Penjualan', (float) $salesReturns->sum('total_return'), '', '']] : [],
        ];
        $excelGroups[] = [
            'title' => 'B. Retur Pembelian (ke Supplier)',
            'headers' => $purchaseHeaders,
            'rows' => $purchaseRows,
            'subtotals' => !empty($purchaseRows) ? [['', '', '', '', 'Total Retur Pembelian', (float) $purchaseReturns->sum('total_return'), '', '']] : [],
        ];

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-retur.xlsx',
            [6],
            [5, 18, 14, 22, 24, 18, 32, 14]
        );
    }

    public function cashierReport(array $data)
    {
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';
        $userId = $data['sub_laporan'] ?? '';

        $query = cashDrawer::with(['user', 'business'])->whereYear('tanggal_buka', $tahun);
        if ($bulan != '-') $query->whereMonth('tanggal_buka', $bulan);
        if ($hari != '-') $query->whereDay('tanggal_buka', $hari);
        if ($userId != '') $query->where('user_id', $userId);

        $sessions = $query->orderBy('tanggal_buka', 'desc')->get();

        foreach ($sessions as $session) {
            $session->sales_items = SaleDetail::select(
                'product_id',
                DB::raw('SUM(jumlah) as total_qty'),
                DB::raw('SUM(subtotal) as total_amount')
            )
            ->whereHas('sale', function ($q) use ($session) {
                $q->where('user_id', $session->user_id)
                  ->where('created_at', '>=', $session->tanggal_buka);
                if ($session->tanggal_tutup) {
                    $q->where('created_at', '<=', $session->tanggal_tutup);
                }
            })
            ->groupBy('product_id')
            ->with('product')
            ->get();
        }

        $title = 'Laporan Kasir';
        $subtitle = $this->periodeSubtitle($tahun, $bulan, $hari);

        $summaryRow = [];

        $excelGroups = [];
        foreach ($sessions as $session) {
            $sessionTitle = 'Kasir: '.($session->user->nama_lengkap ?? ($session->user->name ?? '-'))
                .' | Buka: '.Carbon::parse($session->tanggal_buka)->format('d/m/Y H:i')
                .' | Tutup: '.($session->tanggal_tutup ? Carbon::parse($session->tanggal_tutup)->format('d/m/Y H:i') : 'SEKARANG');

            $itemHeaders = ['Produk', 'Qty', 'Total Penjualan'];
            $itemRows = [];
            foreach ($session->sales_items as $item) {
                $itemRows[] = [
                    $item->product->nama_produk ?? '-',
                    (int) $item->total_qty,
                    (float) $item->total_amount,
                ];
            }

            $selisih = $session->selisih ?? ((float)($session->saldo_akhir ?? 0) - (float)($session->saldo_akhir_aplikasi ?? 0));

            $excelGroups[] = [
                'title' => $sessionTitle,
                'headers' => $itemHeaders,
                'rows' => $itemRows,
                'subtotals' => [
                    [
                        'Saldo Awal: Rp '.number_format($session->saldo_awal ?? 0, 2, '.', ','),
                        'Saldo Akhir (App): Rp '.number_format($session->saldo_akhir_aplikasi ?? 0, 2, '.', ','),
                        'Saldo Akhir (Manual): Rp '.number_format($session->saldo_akhir ?? 0, 2, '.', ','),
                    ],
                    [
                        '',
                        'Selisih:',
                        'Rp '.number_format($selisih, 2, '.', ','),
                    ],
                ],
            ];
        }

        return $this->buildGroupedExcel(
            $title,
            $subtitle,
            $summaryRow,
            $excelGroups,
            'laporan-kasir.xlsx',
            [3],
            [50, 12, 22]
        );
    }

    public function cover(array $data)
    {
        $business = Business::with('owner')->find(auth()->user()?->business_id) ?? Business::with('owner')->first();
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';

        $periode = (string) $tahun;
        if ($bulan != '-') {
            try {
                $periode = Carbon::createFromDate((int) $tahun, (int) $bulan, 1)->isoFormat('MMMM').' '.$tahun;
            } catch (\Throwable $e) {
                $periode = $bulan.' '.$tahun;
            }
        }

        $headers = ['Item', 'Keterangan'];
        $rows = [
            ['Nama Usaha', $business?->nama_usaha ?? '-'],
            ['Alamat', $business?->alamat ?? '-'],
            ['No Telp', $business?->no_telp ?? '-'],
            ['Email', $business?->email ?? '-'],
            ['Periode Laporan', $periode],
            ['Tanggal Cetak', Carbon::now()->isoFormat('D MMMM Y')],
        ];

        return $this->buildExcel(
            'Halaman Sampul (Cover)',
            'Laporan Keuangan',
            $headers,
            $rows,
            [],
            'cover-laporan.xlsx',
            [],
            [22, 60]
        );
    }

    public function laporanStok(array $data)
    {
        $business = view()->shared('business');
        $tahun = $data['tahun'] ?? date('Y');
        $bulan = $data['bulan'] ?? '-';
        $hari = $data['periode'] ?? '-';

        $endDate = Carbon::createFromDate((int) $tahun, $bulan == '-' ? 12 : (int) $bulan, 1)->endOfMonth();
        if ($hari != '-') {
            $endDate = Carbon::createFromDate((int) $tahun, $bulan == '-' ? 12 : (int) $bulan, (int) $hari);
        }
        $startDate = Carbon::createFromDate((int) $tahun, $bulan == '-' ? 1 : (int) $bulan, 1)->startOfMonth();

        $query = Product::with(['category', 'unit', 'shelf'])
            ->where('business_id', $business->id)->where('is_active', true);

        if (isset($data['sub_laporan']) && $data['sub_laporan'] != '') {
            if (str_starts_with($data['sub_laporan'], 'cat:')) {
                $query->where('category_id', str_replace('cat:', '', $data['sub_laporan']));
            } elseif (str_starts_with($data['sub_laporan'], 'rak:')) {
                $query->where('shelf_id', str_replace('rak:', '', $data['sub_laporan']));
            }
        }

        $products = $query->orderBy('nama_produk')->get()
            ->map(function ($p) use ($startDate, $endDate) {
                $masuk = $p->stockMovements()
                    ->whereIn('jenis_perubahan', ['masuk', 'pembelian', 'retur_pembelian', 'koreksi_tambah'])
                    ->whereBetween('tanggal_perubahan_stok', [$startDate, $endDate])
                    ->sum('jumlah_perubahan');
                $keluar = $p->stockMovements()
                    ->whereIn('jenis_perubahan', ['keluar', 'penjualan', 'retur_penjualan', 'koreksi_kurang', 'rusak'])
                    ->whereBetween('tanggal_perubahan_stok', [$startDate, $endDate])
                    ->sum('jumlah_perubahan');
                $p->stok_awal_periode = $p->stok_aktual - ($masuk - $keluar);
                $p->stok_masuk = (int) $masuk;
                $p->stok_keluar = (int) $keluar;
                $p->stok_akhir = $p->stok_aktual;
                $p->nilai_stok = $p->stok_aktual * $p->biaya_rata_rata;
                return $p;
            });

        $headers = ['No', 'SKU', 'Nama Produk', 'Kategori', 'Satuan', 'Rak', 'Stok Awal', 'Masuk', 'Keluar', 'Stok Akhir', 'HPP', 'Nilai Stok'];
        $rows = [];
        foreach ($products as $i => $p) {
            $rows[] = [
                $i + 1,
                $p->sku ?? '-',
                $p->nama_produk,
                $p->category->nama_kategori ?? '-',
                $p->unit->nama_satuan ?? '-',
                $p->shelf->nama_rak ?? '-',
                (int) $p->stok_awal_periode,
                (int) $p->stok_masuk,
                (int) $p->stok_keluar,
                (int) $p->stok_akhir,
                (float) $p->biaya_rata_rata,
                (float) $p->nilai_stok,
            ];
        }
        $totalsRow = ['', '', '', '', '', 'Total', '', '', '', (int) $products->sum('stok_akhir'), '', (float) $products->sum('nilai_stok')];

        return $this->buildExcel(
            'Laporan Stok',
            $this->periodeSubtitle($tahun, $bulan, $hari),
            $headers,
            $rows,
            $totalsRow,
            'laporan-stok.xlsx',
            [11, 12],
            [5, 14, 32, 22, 12, 14, 14, 12, 12, 14, 16, 18]
        );
    }
}
