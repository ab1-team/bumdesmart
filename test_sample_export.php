<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

function buildSampleExcel(string $title, string $subtitle, array $summaryRow, array $groups, string $filename, array $columnWidths = [])
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr($title, 0, 31));

    $maxCols = 1;
    foreach ($groups as $g) {
        $maxCols = max($maxCols, count($g['headers']));
    }
    $lastCol = Coordinate::stringFromColumnIndex($maxCols);

    // Title
    $sheet->mergeCells("A1:{$lastCol}1");
    $sheet->setCellValue('A1', $title);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Subtitle
    $sheet->mergeCells("A2:{$lastCol}2");
    $sheet->setCellValue('A2', $subtitle);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $rowIndex = 3;

    // Summary
    if (!empty($summaryRow)) {
        foreach ($summaryRow as $sRow) {
            for ($i = 0; $i < $maxCols; $i++) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue("{$col}{$rowIndex}", $sRow[$i] ?? '');
            }
            $sheet->getStyle("A{$rowIndex}:{$lastCol}{$rowIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
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

        // Section header
        if ($groupTitle) {
            $sheet->mergeCells("A{$rowIndex}:{$grpLastCol}{$rowIndex}");
            $sheet->setCellValue("A{$rowIndex}", $groupTitle);
            $sheet->getStyle("A{$rowIndex}:{$grpLastCol}{$rowIndex}")->applyFromArray($sectionHeaderStyle);
            $rowIndex++;
        }

        // Table header
        $headerRowIdx = $rowIndex;
        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}{$headerRowIdx}", $h);
        }
        $sheet->getStyle("A{$headerRowIdx}:{$grpLastCol}{$headerRowIdx}")->applyFromArray($headerStyle);
        $rowIndex++;

        // Data rows
        foreach ($rows as $r) {
            for ($i = 0; $i < $colCount; $i++) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue("{$col}{$rowIndex}", $r[$i] ?? '');
            }
            $rowIndex++;
        }

        // Borders for data
        $dataEndRow = $rowIndex - 1;
        if (!empty($rows)) {
            $sheet->getStyle("A{$headerRowIdx}:{$grpLastCol}{$dataEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // Subtotals
        if (!empty($subtotals)) {
            foreach ($subtotals as $st) {
                for ($i = 0; $i < $colCount; $i++) {
                    $col = Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue("{$col}{$rowIndex}", $st[$i] ?? '');
                }
                $sheet->getStyle("A{$rowIndex}:{$grpLastCol}{$rowIndex}")->applyFromArray($subtotalStyle);
                $rowIndex++;
            }
        }

        $rowIndex++;
    }

    // Column widths
    foreach ($columnWidths as $idx => $w) {
        $col = Coordinate::stringFromColumnIndex((int) $idx + 1);
        $sheet->getColumnDimension($col)->setWidth($w);
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    echo "OK: $filename\n";
}

function buildSimpleExcel(string $title, string $subtitle, array $headers, array $rows, array $totalsRow, string $filename, array $columnWidths = [])
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr($title, 0, 31));

    $colCount = count($headers);
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
        for ($i = 0; $i < $colCount; $i++) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}{$rowIndex}", $r[$i] ?? '');
        }
        $rowIndex++;
    }

    if (!empty($rows)) {
        $sheet->getStyle("A{$headerRow}:{$lastCol}".($rowIndex - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    if (!empty($totalsRow)) {
        for ($i = 0; $i < $colCount; $i++) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}{$rowIndex}", $totalsRow[$i] ?? '');
        }
        $totalStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];
        $sheet->getStyle("A{$rowIndex}:{$lastCol}{$rowIndex}")->applyFromArray($totalStyle);
    }

    foreach ($columnWidths as $idx => $w) {
        $col = Coordinate::stringFromColumnIndex((int) $idx + 1);
        $sheet->getColumnDimension($col)->setWidth($w);
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    echo "OK: $filename\n";
}

$dir = __DIR__.'/storage/app/samples';
if (!is_dir($dir)) mkdir($dir, 0777, true);

// ============================================================
// 1. PENJUALAN HARIAN
// ============================================================
buildSampleExcel(
    'Laporan Penjualan Harian',
    'Periode: Juni 2026',
    [
        ['Rangkuman', '', ''],
        ['Total Penjualan', 'Jumlah Transaksi', 'Rata-rata'],
        ['Rp 15.500.000,00', '25', 'Rp 620.000,00'],
    ],
    [
        [
            'title' => 'Cash',
            'headers' => ['No', 'No. Invoice', 'Waktu', 'Pelanggan', 'Pembayaran', 'Inisial', 'Nominal', 'Status'],
            'rows' => [
                [1, 'INV-2026-001', '2026-06-22 09:15', 'Budi Santoso', 'Cash', 'FN', 500000, 'Paid'],
                [2, 'INV-2026-003', '2026-06-22 10:30', 'Siti Aminah', 'Cash', 'FN', 250000, 'Paid'],
                [3, 'INV-2026-005', '2026-06-22 14:00', 'Guest', 'Cash', 'AD', 750000, 'Paid'],
            ],
            'subtotals' => [
                ['', '', '', '', '', 'Total Cash', 1500000, ''],
            ],
        ],
        [
            'title' => 'Transfer/Qris',
            'headers' => ['No', 'No. Invoice', 'Waktu', 'Pelanggan', 'Pembayaran', 'Inisial', 'Nominal', 'Status'],
            'rows' => [
                [1, 'INV-2026-002', '2026-06-22 09:45', 'Rina Wati', 'Transfer', 'FN', 1200000, 'Paid'],
                [2, 'INV-2026-006', '2026-06-22 15:20', 'Ahmad Fauzi', 'Qris', 'AD', 800000, 'Paid'],
            ],
            'subtotals' => [
                ['', '', '', '', '', 'Total Transfer/Qris', 2000000, ''],
            ],
        ],
        [
            'title' => 'Piutang',
            'headers' => ['No', 'No. Invoice', 'Waktu', 'Pelanggan', 'Pembayaran', 'Inisial', 'Nominal', 'Status'],
            'rows' => [
                [1, 'INV-2026-004', '2026-06-22 11:00', 'PT Maju Jaya', 'Piutang', 'FN', 5000000, 'Pending'],
            ],
            'subtotals' => [
                ['', '', '', '', '', 'Total Piutang', 5000000, ''],
            ],
        ],
    ],
    $dir.'/01-penjualan-harian.xlsx',
    [5, 22, 20, 22, 14, 10, 16, 12]
);

// ============================================================
// 2. PIUTANG
// ============================================================
buildSampleExcel(
    'Laporan Piutang (Customer)',
    'Per Tanggal: 22 Juni 2026',
    [
        ['Total Piutang: Rp 12.500.000,00', '', '', '', '', ''],
    ],
    [
        [
            'title' => 'PT Maju Jaya (3 invoice)',
            'headers' => ['No. Invoice', 'Tanggal', 'Total', 'Dibayar', 'Sisa Piutang', 'Umur (Hari)'],
            'rows' => [
                ['INV-2026-004', '15/06/2026', 5000000, 0, 5000000, '7 hari'],
                ['INV-2026-010', '10/06/2026', 3500000, 1000000, 2500000, '12 hari'],
                ['INV-2026-012', '01/06/2026', 8000000, 3000000, 5000000, '21 hari'],
            ],
            'subtotals' => [
                ['', '', '', 'Subtotal', 12500000, ''],
            ],
        ],
        [
            'title' => 'Budi Santoso (1 invoice)',
            'headers' => ['No. Invoice', 'Tanggal', 'Total', 'Dibayar', 'Sisa Piutang', 'Umur (Hari)'],
            'rows' => [
                ['INV-2026-020', '05/05/2026', 2000000, 0, 2000000, '48 hari'],
            ],
            'subtotals' => [
                ['', '', '', 'Subtotal', 2000000, ''],
            ],
        ],
    ],
    $dir.'/02-piutang.xlsx',
    [22, 14, 16, 16, 16, 14]
);

// ============================================================
// 3. HUTANG
// ============================================================
buildSampleExcel(
    'Laporan Hutang (Supplier)',
    'Per Tanggal: 22 Juni 2026',
    [
        ['Total Hutang: Rp 8.000.000,00', '', '', '', '', ''],
    ],
    [
        [
            'title' => 'PT Supplier Utama (2 PO)',
            'headers' => ['No. Pembelian', 'Tanggal', 'Total', 'Dibayar', 'Sisa Hutang', 'Umur (Hari)'],
            'rows' => [
                ['PO-2026-001', '01/06/2026', 5000000, 2000000, 3000000, '21 hari'],
                ['PO-2026-005', '15/06/2026', 7000000, 2000000, 5000000, '7 hari'],
            ],
            'subtotals' => [
                ['', '', '', 'Subtotal', 8000000, ''],
            ],
        ],
    ],
    $dir.'/03-hutang.xlsx',
    [22, 14, 16, 16, 16, 14]
);

// ============================================================
// 4. PEMBELIAN
// ============================================================
buildSampleExcel(
    'Laporan Pembelian',
    'Periode: Juni 2026',
    [
        ['Jumlah PO', 'Total Pembelian', 'Total Dibayar', 'Total Hutang'],
        [5, 'Rp 25.000.000,00', 'Rp 17.000.000,00', 'Rp 8.000.000,00'],
    ],
    [
        [
            'title' => 'Rincian Pembelian',
            'headers' => ['No', 'No. Pembelian', 'Tanggal', 'Supplier', 'Pembayaran', 'Total', 'Hutang', 'Status'],
            'rows' => [
                [1, 'PO-2026-005', '15/06/2026', 'PT Supplier Utama', 'Transfer', 7000000, 5000000, 'partial'],
                [2, 'PO-2026-004', '10/06/2026', 'CV Sejahtera', 'Tunai', 3500000, 0, 'paid'],
                [3, 'PO-2026-001', '01/06/2026', 'PT Supplier Utama', 'Transfer', 5000000, 3000000, 'partial'],
            ],
            'subtotals' => [
                ['', '', '', '', 'Total', 15500000, 8000000, ''],
            ],
        ],
    ],
    $dir.'/04-pembelian.xlsx',
    [5, 22, 14, 28, 14, 16, 16, 14]
);

// ============================================================
// 5. JURNAL TRANSAKSI
// ============================================================
buildSimpleExcel(
    'Jurnal Transaksi',
    'Periode: Juni 2026',
    ['No', 'Tanggal', 'Ref ID.', 'Kode Akun', 'Keterangan', 'Debit', 'Kredit', 'Ins'],
    [
        [1, '2026-06-01', 'TRX001', '1.1.01', 'Kas', 5000000, 0, 'FN'],
        ['', '', '', '4.1.01', 'Pendapatan Penjualan', 0, 5000000, ''],
        [2, '2026-06-02', 'TRX002', '1.1.01', 'Kas', 1200000, 0, 'AD'],
        ['', '', '', '4.1.01', 'Pendapatan Penjualan', 0, 1200000, ''],
        [3, '2026-06-03', 'TRX003', '5.1.01', 'Beban Operasional', 800000, 0, 'FN'],
        ['', '', '', '1.1.01', 'Kas', 0, 800000, ''],
    ],
    ['', '', '', '', 'Total', 7000000, 7000000, ''],
    $dir.'/05-jurnal-transaksi.xlsx',
    [5, 14, 12, 14, 36, 16, 16, 5]
);

// ============================================================
// 6. STOK MINIMUM
// ============================================================
buildSimpleExcel(
    'Laporan Stok Minimum',
    'Periode: Juni 2026',
    ['No', 'Kode Produk', 'Nama Produk', 'Stok Saat Ini', 'Stok Minimum', 'Defisit', 'Saran Order'],
    [
        [1, 'PRD-001', 'Beras Premium 5kg', 2, 10, 8, 18],
        [2, 'PRD-015', 'Minyak Goreng 2L', 5, 15, 10, 25],
        [3, 'PRD-022', 'Gula Pasir 1kg', 8, 20, 12, 32],
    ],
    [],
    $dir.'/06-stok-minimum.xlsx',
    [5, 14, 32, 14, 14, 14, 14]
);

// ============================================================
// 7. PENJUALAN PRODUK
// ============================================================
buildSimpleExcel(
    'Laporan Penjualan Produk',
    'Periode: Juni 2026',
    ['No', 'Product ID', 'Produk', 'Satuan', 'Nama Pelanggan', 'Nomor Faktur', 'Tanggal', 'Kuantitas', 'Harga Jual Satuan', 'Sub Total'],
    [
        [1, 101, 'Beras Premium 5kg', 'Sak', 'Budi Santoso', 'INV-2026-001', '22/06/2026 09:15', 2, 65000, 130000],
        [2, 102, 'Minyak Goreng 2L', 'Botol', 'Siti Aminah', 'INV-2026-003', '22/06/2026 10:30', 3, 28000, 84000],
        [3, 103, 'Gula Pasir 1kg', 'Kg', 'Guest', 'INV-2026-005', '22/06/2026 14:00', 5, 14000, 70000],
    ],
    ['', '', '', '', '', '', '', '', 'Total:', 284000],
    $dir.'/07-penjualan-produk.xlsx',
    [5, 12, 28, 10, 22, 22, 18, 12, 18, 16]
);

// ============================================================
// 8. KASIR
// ============================================================
buildSampleExcel(
    'Laporan Kasir',
    'Periode: Juni 2026',
    [],
    [
        [
            'title' => 'Kasir: Fandi | Buka: 22/06/2026 08:00 | Tutup: 22/06/2026 16:00',
            'headers' => ['Produk', 'Qty', 'Total Penjualan'],
            'rows' => [
                ['Beras Premium 5kg', 5, 325000],
                ['Minyak Goreng 2L', 8, 224000],
                ['Gula Pasir 1kg', 12, 168000],
            ],
            'subtotals' => [
                ['Saldo Awal: Rp 500.000,00', 'Saldo Akhir (App): Rp 1.217.000,00', 'Saldo Akhir (Manual): Rp 1.220.000,00'],
                ['', 'Selisih:', 'Rp 3.000,00'],
            ],
        ],
        [
            'title' => 'Kasir: Anisa | Buka: 22/06/2026 08:00 | Tutup: 22/06/2026 16:00',
            'headers' => ['Produk', 'Qty', 'Total Penjualan'],
            'rows' => [
                ['Beras Premium 5kg', 3, 195000],
                ['Tepung Terigu 1kg', 10, 120000],
            ],
            'subtotals' => [
                ['Saldo Awal: Rp 300.000,00', 'Saldo Akhir (App): Rp 615.000,00', 'Saldo Akhir (Manual): Rp 615.000,00'],
                ['', 'Selisih:', 'Rp 0,00'],
            ],
        ],
    ],
    $dir.'/08-kasir.xlsx',
    [50, 12, 22]
);

// ============================================================
// 9. RETUR
// ============================================================
buildSampleExcel(
    'Laporan Retur',
    'Periode: Juni 2026',
    [],
    [
        [
            'title' => 'A. Retur Penjualan (dari Customer)',
            'headers' => ['No', 'No. Return', 'Tanggal', 'No. Invoice', 'Customer', 'Nilai Return', 'Alasan', 'Status'],
            'rows' => [
                [1, 'RET-001', '20/06/2026', 'INV-2026-001', 'Budi Santoso', 65000, 'Baru rusak', 'approved'],
                [2, 'RET-002', '21/06/2026', 'INV-2026-003', 'Siti Aminah', 28000, 'Salah kirim', 'pending'],
            ],
            'subtotals' => [
                ['', '', '', '', 'Total Retur Penjualan', 93000, '', ''],
            ],
        ],
        [
            'title' => 'B. Retur Pembelian (ke Supplier)',
            'headers' => ['No', 'No. Return', 'Tanggal', 'No. Pembelian', 'Supplier', 'Nilai Return', 'Alasan', 'Status'],
            'rows' => [
                [1, 'RET-P-001', '18/06/2026', 'PO-2026-001', 'PT Supplier Utama', 150000, 'Kualitas buruk', 'approved'],
            ],
            'subtotals' => [
                ['', '', '', '', 'Total Retur Pembelian', 150000, '', ''],
            ],
        ],
    ],
    $dir.'/09-retur.xlsx',
    [5, 18, 14, 22, 24, 18, 32, 14]
);

// ============================================================
// 10. LAPORAN STOK
// ============================================================
buildSimpleExcel(
    'Laporan Stok',
    'Periode: Juni 2026',
    ['No', 'SKU', 'Nama Produk', 'Kategori', 'Satuan', 'Rak', 'Stok Awal', 'Masuk', 'Keluar', 'Stok Akhir', 'HPP', 'Nilai Stok'],
    [
        [1, 'PRD-001', 'Beras Premium 5kg', 'Sembako', 'Sak', 'A1', 50, 100, 80, 70, 58000, 4060000],
        [2, 'PRD-002', 'Minyak Goreng 2L', 'Sembako', 'Botol', 'A2', 30, 50, 45, 35, 25000, 875000],
        [3, 'PRD-003', 'Gula Pasir 1kg', 'Sembako', 'Kg', 'A1', 100, 80, 90, 90, 12000, 1080000],
    ],
    ['', '', '', '', '', 'Total', '', '', '', 195, '', 6015000],
    $dir.'/10-laporan-stok.xlsx',
    [5, 14, 32, 16, 10, 10, 12, 10, 10, 12, 14, 18]
);

echo "\n=== Semua sample berhasil dibuat di: $dir ===\n";
