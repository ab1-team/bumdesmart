<div>
    <div class="no-print alert alert-info mb-3 mx-3 mt-3" style="font-size: 11px;">
        <strong>💡 Tips Presisi:</strong> Pastikan pengaturan <strong>Scale/Skala</strong> pada jendela cetak diatur ke <strong>"100%"</strong> atau <strong>"Actual Size"</strong> (bukan "Fit to Page") agar ukuran stiker pas dengan kertas T&J.
    </div>

    <button onclick="window.print()" class="no-print btn btn-primary btn-sm shadow-sm" style="position: fixed; top: 10px; right: 10px; z-index: 9999;">
        <span class="material-symbols-outlined">print</span> Cetak Label
    </button>

    <div class="label-container size-{{ $size }}">
        @foreach ($products as $product)
            @for ($i = 0; $i < $qty; $i++)
                @if (str_starts_with($size, 'shelf_'))
                    <div class="shelf-tag-item">
                        <div class="shelf-tag-main">
                            <div class="shelf-tag-name">{{ $product->nama_produk }}</div>
                            <div class="shelf-tag-price-wrapper">
                                <span class="shelf-tag-currency">Rp</span>
                                <span class="shelf-tag-price">{{ number_format($product->harga_jual, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="shelf-tag-side">
                            <div class="shelf-tag-barcode-wrapper">
                                @if ($type == 'barcode')
                                    <svg class="barcode" 
                                         jsbarcode-value="{{ $product->barcode ?: $product->sku }}"
                                         jsbarcode-format="CODE128"
                                         jsbarcode-width="1"
                                         jsbarcode-height="20"
                                         jsbarcode-fontSize="10"
                                         jsbarcode-margin="0">
                                    </svg>
                                @else
                                    <div class="qrcode" data-value="{{ $product->barcode ?: $product->sku }}" data-size="40"></div>
                                @endif
                            </div>
                            <div class="shelf-tag-info">
                                <div class="shelf-tag-sku">{{ $product->sku }}</div>
                                <div class="shelf-tag-unit">{{ $product->unit->nama_satuan }}</div>
                            </div>
                        </div>
                    </div>
                @else
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
                @endif
            @endfor
        @endforeach
    </div>

    <style>
        body { margin: 0; padding: 0; background: #fff; }
        
        .label-container {
            margin: 0 auto;
            display: grid;
            justify-content: center;
            align-content: start;
            padding: 0;
            background: #fff;
        }

        .label-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px dashed #ccc;
            box-sizing: border-box;
            padding: 1mm;
            text-align: center;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .product-name {
            font-size: 7pt;
            font-weight: 700;
            line-height: 1.1;
            max-height: 2.2em;
            overflow: hidden;
            margin-bottom: 0.5mm;
            color: #000;
        }

        .product-price {
            font-size: 9pt;
            font-weight: 900;
            margin-top: 0.5mm;
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

        /* --- Precise T&J / Kojiko Size Definitions --- */

        /* No. 107 (18x50mm) - 3 Columns */
        .size-107 { 
            grid-template-columns: repeat(3, 50mm); 
            column-gap: 2.5mm;
            row-gap: 5mm;
            padding: 5mm 3mm;
            width: 165mm; 
        }
        .size-107 .label-item { width: 50mm; height: 18mm; }
        .size-107 .barcode-wrapper svg { height: 25px !important; }

        /* No. 108 (18x38mm) - 4 Columns */
        .size-108 { 
            grid-template-columns: repeat(4, 38mm); 
            column-gap: 2.5mm;
            row-gap: 5mm;
            padding: 5mm 3mm;
            width: 165mm; 
        }
        .size-108 .label-item { width: 38mm; height: 18.5mm; }
        .size-108 .product-name { font-size: 6pt; }
        .size-108 .barcode-wrapper svg { height: 22px !important; }

        /* No. 103 (32x64mm) - 2 Columns */
        .size-103 { 
            grid-template-columns: repeat(2, 64mm); 
            column-gap: 3.5mm;
            row-gap: 5mm;
            padding: 8mm 4mm;
            width: 165mm; 
        }
        .size-103 .label-item { width: 64mm; height: 32mm; padding: 2mm; }
        .size-103 .product-name { font-size: 9pt; }
        .size-103 .product-price { font-size: 12pt; }
        .size-103 .barcode-wrapper svg { height: 45px !important; }

        /* No. 121 (38x75mm) - 2 Columns */
        .size-121 { 
            grid-template-columns: repeat(2, 75mm); 
            column-gap: 3.5mm;
            row-gap: 5mm;
            padding: 8mm 4mm;
            width: 165mm; 
        }
        .size-121 .label-item { width: 75mm; height: 38mm; padding: 2mm; }
        .size-121 .product-name { font-size: 10pt; }
        .size-121 .product-price { font-size: 14pt; }
        .size-121 .barcode-wrapper svg { height: 55px !important; }

        /* No. 123 (12x30mm) - 6 Columns */
        .size-123 { 
            grid-template-columns: repeat(6, 30mm); 
            gap: 1mm;
            padding: 5mm 1mm;
            width: 190mm; 
        }
        .size-123 .label-item { width: 30mm; height: 12mm; padding: 0.5mm; }
        .size-123 .product-name { font-size: 5pt; }
        .size-123 .product-price { font-size: 6pt; }
        .size-123 .barcode-wrapper svg { height: 14px !important; }

        /* A4 Bulk (3 columns x 9 rows) */
        .size-A4_3_9 { 
            grid-template-columns: repeat(3, 63mm); 
            gap: 2mm;
            padding: 10mm;
            width: 210mm; 
        }
        .size-A4_3_9 .label-item { width: 63mm; height: 30mm; padding: 2mm; }
        .size-A4_3_9 .barcode-wrapper svg { height: 40px !important; }

        /* --- Shelf Tag Design --- */
        .shelf-tag-item {
            display: flex;
            background: white;
            border: 1px solid #000;
            box-sizing: border-box;
            padding: 2mm;
            overflow: hidden;
            page-break-inside: avoid;
            position: relative;
        }

        .shelf-tag-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding-right: 2mm;
            border-right: 1px dashed #ccc;
        }

        .shelf-tag-name {
            font-size: 11pt;
            font-weight: 800;
            line-height: 1.1;
            color: #000;
            text-transform: uppercase;
        }

        .shelf-tag-price-wrapper {
            margin-top: auto;
            display: flex;
            align-items: baseline;
        }

        .shelf-tag-currency {
            font-size: 10pt;
            font-weight: 700;
            margin-right: 1mm;
        }

        .shelf-tag-price {
            font-size: 24pt;
            font-weight: 900;
            color: #000;
        }

        .shelf-tag-side {
            width: 30mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding-left: 1mm;
        }

        .shelf-tag-barcode-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .shelf-tag-info {
            width: 100%;
            text-align: right;
            font-size: 7pt;
            color: #444;
        }

        .shelf-tag-sku {
            font-family: monospace;
        }

        .shelf-tag-unit {
            font-weight: bold;
            font-size: 8pt;
        }

        /* --- Shelf Tag Sizes --- */
        .size-shelf_80_40 {
            grid-template-columns: repeat(2, 80mm);
            column-gap: 5mm;
            row-gap: 5mm;
            padding: 10mm;
            width: 180mm;
        }
        .size-shelf_80_40 .shelf-tag-item { width: 80mm; height: 40mm; }

        .size-shelf_90_55 {
            grid-template-columns: repeat(2, 90mm);
            column-gap: 5mm;
            row-gap: 5mm;
            padding: 10mm;
            width: 200mm;
        }
        .size-shelf_90_55 .shelf-tag-item { width: 90mm; height: 55mm; }
        .size-shelf_90_55 .shelf-tag-price { font-size: 32pt; }
        .size-shelf_90_55 .shelf-tag-name { font-size: 14pt; }

        @media print {
            @page {
                size: auto;
                margin: 0;
            }
            html, body {
                height: auto;
                margin: 0;
                padding: 0;
                background: white;
            }
            .no-print { display: none !important; }
            .label-item { border: none !important; }
            .label-container { 
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
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
                let size = parseInt(el.dataset.size) || 60;
                new QRCode(el, {
                    text: el.dataset.value,
                    width: size,
                    height: size,
                    colorDark : "#000000",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            });
        });
    </script>
</div>
