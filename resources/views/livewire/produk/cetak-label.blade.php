<div class="p-4">
    <div class="no-print mb-4 card shadow-sm">
        <div class="card-body d-flex align-items-center justify-content-between">
            <div>
                <h3 class="mb-1">Pratinjau Cetak Label</h3>
                <p class="text-muted mb-0">Pastikan ukuran kertas dan margin pada pengaturan printer sudah sesuai.</p>
            </div>
            <button onclick="window.print()" class="btn btn-primary btn-lg px-4">
                <span class="material-symbols-outlined me-2">print</span>
                Cetak Sekarang
            </button>
        </div>
    </div>

    <div class="label-container size-{{ $size }}">
        @foreach ($products as $product)
            @for ($i = 0; $i < $qty; $i++)
                <div class="label-item">
                    @if ($showName)
                        <div class="product-name">{{ $product->nama_produk }}</div>
                    @endif
                    
                    <div class="barcode-wrapper">
                        @if ($type == 'barcode')
                            <svg class="barcode" 
                                 jsbarcode-value="{{ $product->barcode ?: $product->sku }}"
                                 jsbarcode-format="CODE128"
                                 jsbarcode-width="1.2"
                                 jsbarcode-height="35"
                                 jsbarcode-fontSize="12"
                                 jsbarcode-margin="0">
                            </svg>
                        @else
                            <div class="qrcode" data-value="{{ $product->barcode ?: $product->sku }}"></div>
                        @endif
                    </div>

                    @if ($showPrice)
                        <div class="product-price">Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</div>
                    @endif
                </div>
            @endfor
        @endforeach
    </div>

    <style>
        /* Base Label Styles */
        .label-container {
            margin: 0 auto;
            display: grid;
            justify-content: center;
        }

        .label-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px dashed #ddd; /* Preview only */
            overflow: hidden;
            box-sizing: border-box;
            padding: 2mm;
            text-align: center;
        }

        .product-name {
            font-size: 7pt;
            font-weight: 700;
            line-height: 1.1;
            max-height: 2.2em;
            overflow: hidden;
            margin-bottom: 1mm;
            color: #000;
        }

        .product-price {
            font-size: 10pt;
            font-weight: 900;
            margin-top: 1mm;
            color: #000;
        }

        .barcode-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .barcode-wrapper svg {
            max-width: 100%;
        }

        /* Tom & Jerry No. 107 (18x50mm) - 3 Columns */
        .size-107 {
            grid-template-columns: repeat(3, 50mm);
            grid-auto-rows: 18mm;
            grid-column-gap: 3mm;
            grid-row-gap: 2mm;
            width: 156mm;
        }
        .size-107 .label-item {
            width: 50mm;
            height: 18mm;
        }

        /* Tom & Jerry No. 103 (32x64mm) - 2 Columns */
        .size-103 {
            grid-template-columns: repeat(2, 64mm);
            grid-auto-rows: 32mm;
            grid-column-gap: 5mm;
            grid-row-gap: 2mm;
            width: 133mm;
        }
        .size-103 .label-item {
            width: 64mm;
            height: 32mm;
        }

        /* A4 Bulk (3 columns x 9 rows) */
        .size-A4_3_9 {
            grid-template-columns: repeat(3, 1fr);
            grid-gap: 5mm;
            padding: 10mm;
            width: 210mm;
        }
        .size-A4_3_9 .label-item {
            height: 30mm;
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body { 
                margin: 0; 
                padding: 10mm 5mm; /* Adjust based on T&J Sheet Margin */
                background: white; 
            }
            .no-print { display: none !important; }
            .label-item { border: none !important; }
            .label-container { width: auto !important; }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Generate Barcodes
            if (typeof JsBarcode !== 'undefined') {
                JsBarcode(".barcode").init();
            }

            // Generate QRCodes
            document.querySelectorAll('.qrcode').forEach(el => {
                new QRCode(el, {
                    text: el.dataset.value,
                    width: 60,
                    height: 60,
                    colorDark : "#000000",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            });
        });
    </script>
</div>
