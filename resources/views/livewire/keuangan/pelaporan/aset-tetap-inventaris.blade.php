@php
    use App\Utils\Tanggal;
    use App\Utils\InventarisUtil;

    $kategoriNaman = [
        1 => 'Tanah',
        2 => 'Gedung',
        3 => 'Kendaraan dan Mesin produksi',
        4 => 'Peralatan umum/ Inventaris',
    ];
@endphp

@extends('layouts.pdf')

@section('content')
    @foreach ($inventarisGroups as $kategori => $items)
        @php
            $nama_kategori = $kategoriNaman[$kategori] ?? 'Kategori ' . $kategori;

            $t_unit = 0;
            $t_harga = 0;
            $t_penyusutan = 0;
            $t_akum_susut = 0;
            $t_nilai_buku = 0;

            $j_unit = 0;
            $j_harga = 0;
            $j_penyusutan = 0;
            $j_akum_susut = 0;
            $j_nilai_buku = 0;

            $no = 1;
        @endphp

        @if (!$loop->first)
            <div style="page-break-before: always;"></div>
        @endif

        <div style="text-align: center; margin-bottom: 15px;">
            <div style="font-size: 16px;">
                <b>Daftar {{ $nama_kategori }}</b>
            </div>
        </div>

        <table border="0" width="100%" cellspacing="0" cellpadding="0"
            style="font-size: 10px; table-layout: fixed; border-collapse: collapse;">
            <thead>
                <tr style="background: rgb(232, 232, 232)">
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" rowspan="2" width="2%">No
                    </th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" rowspan="2" width="7%">Tgl
                        Beli</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" rowspan="2" width="18%">Nama
                        Barang</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" rowspan="2" width="2%">Id
                    </th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" rowspan="2" width="5%">
                        Kondisi</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" rowspan="2" width="3%">Unit
                    </th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" rowspan="2" width="8%">
                        Harga Satuan</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" rowspan="2" width="9%">
                        Harga Perolehan</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" rowspan="2" width="4%">Umur
                        Eko.</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" rowspan="2" width="8%">
                        Satuan Susut</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" colspan="2" width="12%">
                        Tahun Ini</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" colspan="2" width="13%">s.d.
                        Tahun Ini</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" rowspan="2" width="9%">
                        Nilai Buku</th>
                </tr>
                <tr style="background: rgb(232, 232, 232)">
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" width="3%">Umur</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" width="9%">Biaya</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" width="3%">Umur</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: center;" width="10%">Biaya</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $inv)
                    @php
                        $nama_barang = $inv->nama_barang;
                        $warna = '#000000';
                        $is_valid = true;

                        if (strtolower($inv->status) != 'baik' && $tgl_kondisi >= $inv->tanggal_validasi) {
                            $tglValStr = \Carbon\Carbon::parse($inv->tanggal_validasi)->isoFormat('DD/MM/YYYY');
                            $nama_barang .= ' (' . $inv->status . ' ' . $tglValStr . ')';
                            $warna = '#FF0000';
                            $is_valid = false;
                        }

                        $statusListInvalid = ['dijual', 'jual', 'hilang', 'dihapus', 'hapus'];
                        $is_status_invalid = in_array(strtolower($inv->status), $statusListInvalid);
                    @endphp

                    <tr style="color: {{ $warna }};">
                        @if ($kategori == 1)
                            {{-- Golongan Tanah/Bangunan tanpa penyusutan bulanan --}}
                            @php
                                $t_unit += $inv->jumlah;
                                $t_harga += $inv->harga_satuan * $inv->jumlah;

                                $nilai_buku = $inv->harga_satuan * $inv->jumlah;
                                if ($is_status_invalid) {
                                    $nilai_buku = 0;
                                }

                                if ($is_status_invalid && $tgl_kondisi >= $inv->tanggal_validasi) {
                                    $j_unit += $inv->jumlah;
                                    $j_harga += $inv->harga_satuan * $inv->jumlah;
                                    $j_nilai_buku += $nilai_buku;
                                } else {
                                    $t_nilai_buku += $nilai_buku;
                                }
                            @endphp
                            <td style="border: 1px solid #000; padding: 3px;" align="center">{{ $no++ }}</td>
                            <td style="border: 1px solid #000; padding: 3px;" align="center">
                                {{ \Carbon\Carbon::parse($inv->tanggal_beli)->format('d/m/Y') }}</td>
                            <td style="border: 1px solid #000; padding: 3px;">{{ $nama_barang }}</td>
                            <td style="border: 1px solid #000; padding: 3px;" align="center">{{ $inv->id }}</td>
                            <td style="border: 1px solid #000; padding: 3px;" align="center">{{ ucfirst($inv->status) }}
                            </td>
                            <td style="border: 1px solid #000; padding: 3px;" align="center">{{ $inv->jumlah }}</td>
                            <td style="border: 1px solid #000; padding: 3px;" align="right">
                                {{ number_format($inv->harga_satuan, 2, '.', ',') }}</td>
                            <td style="border: 1px solid #000; padding: 3px;" align="right">
                                {{ number_format($inv->harga_satuan * $inv->jumlah, 2, '.', ',') }}</td>
                            <td style="border: 1px solid #000; padding: 3px;" colspan="6"></td>
                            <td style="border: 1px solid #000; padding: 3px;" align="right">
                                {{ number_format($nilai_buku, 2, '.', ',') }}</td>
                        @else
                            {{-- Golongan dengan Penyusutan --}}
                            @php
                                $satuan_susut =
                                    $inv->harga_satuan <= 0
                                        ? 0
                                        : round(($inv->harga_satuan * $inv->jumlah) / $inv->umur_ekonomis, 2);
                                $pakai_lalu = InventarisUtil::bulan($inv->tanggal_beli, $tahun - 1 . '-12-31');
                                $nilai_buku = InventarisUtil::nilaiBuku($tgl_kondisi, $inv);

                                if (strtolower($inv->status) != 'baik' && $tgl_kondisi >= $inv->tanggal_validasi) {
                                    $umur = InventarisUtil::bulan($inv->tanggal_beli, $inv->tanggal_validasi);
                                } else {
                                    $umur = InventarisUtil::bulan($inv->tanggal_beli, $tgl_kondisi);
                                }

                                $_satuan_susut = $satuan_susut;
                                if ($umur >= $inv->umur_ekonomis) {
                                    $harga = $inv->harga_satuan * $inv->jumlah;
                                    $_susut = $satuan_susut * ($inv->umur_ekonomis - 1);
                                    $satuan_susut = $harga - $_susut - 1;
                                }

                                $susut = $satuan_susut * $umur;
                                if ($umur >= $inv->umur_ekonomis && $inv->harga_satuan * $inv->jumlah > 0) {
                                    $akum_umur = $inv->umur_ekonomis;
                                    $_akum_susut = $inv->harga_satuan * $inv->jumlah;
                                    $akum_susut = $_akum_susut - 1;
                                    $nilai_buku = 1;
                                } else {
                                    $akum_umur = $umur;
                                    $akum_susut = $susut;
                                    if ($nilai_buku < 0) {
                                        $nilai_buku = 1;
                                    }
                                }

                                $umur_pakai = $akum_umur - $pakai_lalu;
                                $penyusutan = $satuan_susut * $umur_pakai;

                                if ($is_status_invalid && $tgl_kondisi >= $inv->tanggal_validasi) {
                                    $akum_susut = $inv->harga_satuan * $inv->jumlah;
                                    $nilai_buku = 0;
                                    $penyusutan = 0;
                                    $umur_pakai = 0;
                                }

                                if (strtolower($inv->status) == 'rusak' && $tgl_kondisi >= $inv->tanggal_validasi) {
                                    $akum_susut = $inv->harga_satuan * $inv->jumlah - 1;
                                    $nilai_buku = 1;
                                    $penyusutan = 0;
                                    $umur_pakai = 0;
                                }

                                if (!($umur_pakai >= 0 && $inv->harga_satuan * $inv->jumlah > 0)) {
                                    $umur_pakai = 0;
                                    $penyusutan = 0;
                                }

                                if ($akum_umur == $inv->umur_ekonomis && $umur_pakai > 0) {
                                    $penyusutan = $_satuan_susut * ($umur_pakai - 1) + $satuan_susut;
                                }

                                $t_unit += $inv->jumlah;
                                $t_harga += $inv->harga_satuan * $inv->jumlah;
                                $t_penyusutan += $penyusutan;
                                $t_akum_susut += $akum_susut;
                                $t_nilai_buku += $nilai_buku;

                                $tahun_validasi = $inv->tanggal_validasi
                                    ? (int) substr($inv->tanggal_validasi, 0, 4)
                                    : 0;
                            @endphp

                            @if ($is_status_invalid && $tgl_kondisi >= $inv->tanggal_validasi && $tahun_validasi < $tahun && $tahun_validasi > 0)
                                @php
                                    $j_unit += $inv->jumlah;
                                    $j_harga += $inv->harga_satuan * $inv->jumlah;
                                    $j_penyusutan += $penyusutan;
                                    $j_akum_susut += $akum_susut;
                                    $j_nilai_buku += $nilai_buku;
                                @endphp
                            @else
                                <td style="border: 1px solid #000; padding: 3px;" align="center">{{ $no++ }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="center">
                                    {{ \Carbon\Carbon::parse($inv->tanggal_beli)->format('d/m/Y') }}</td>
                                <td style="border: 1px solid #000; padding: 3px;">{{ $nama_barang }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="center">{{ $inv->id }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="center">
                                    {{ ucfirst($inv->status) }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="center">{{ $inv->jumlah }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="right">
                                    {{ number_format($inv->harga_satuan, 2, '.', ',') }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="right">
                                    {{ number_format($inv->harga_satuan * $inv->jumlah, 2, '.', ',') }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="center">
                                    {{ $inv->umur_ekonomis }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="right">
                                    {{ number_format($_satuan_susut, 2, '.', ',') }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="center">{{ $umur_pakai }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="right">
                                    {{ number_format($penyusutan, 2, '.', ',') }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="center">{{ $akum_umur }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="right">
                                    {{ number_format($akum_susut, 2, '.', ',') }}</td>
                                <td style="border: 1px solid #000; padding: 3px;" align="right">
                                    {{ number_format($nilai_buku, 2, '.', ',') }}</td>
                            @endif
                        @endif
                    </tr>
                @endforeach

                @if ($kategori != 1)
                    <tr>
                        <td style="border: 1px solid #000; padding: 3px;" height="15" colspan="5">
                            Jumlah Daftar {{ $nama_kategori }} (Hapus, Hilang, Jual) s.d. Tahun {{ $tahun - 1 }}
                        </td>
                        <td style="border: 1px solid #000; padding: 3px;" align="center">{{ $j_unit }}</td>
                        <td style="border: 1px solid #000; padding: 3px;">&nbsp;</td>
                        <td style="border: 1px solid #000; padding: 3px;" align="right">{{ number_format($j_harga, 2, '.', ',') }}
                        </td>
                        <td style="border: 1px solid #000; padding: 3px;">&nbsp;</td>
                        <td style="border: 1px solid #000; padding: 3px;">&nbsp;</td>
                        <td style="border: 1px solid #000; padding: 3px;" align="right" colspan="2">
                            {{ number_format($j_penyusutan, 2, '.', ',') }}</td>
                        <td style="border: 1px solid #000; padding: 3px;" align="right" colspan="2">
                            {{ number_format($j_akum_susut, 2, '.', ',') }}</td>
                        <td style="border: 1px solid #000; padding: 3px;" align="right">
                            {{ number_format($j_nilai_buku, 2, '.', ',') }}</td>
                    </tr>
                @endif

                <tr>
                    <td style="border: 1px solid #000; padding: 3px;" colspan="5" height="15">
                        <b>Jumlah</b>
                    </td>
                    <td style="border: 1px solid #000; padding: 3px;" align="center">
                        <b>{{ number_format($t_unit, 0) }}</b>
                    </td>
                    <td style="border: 1px solid #000; padding: 3px;">&nbsp;</td>
                    <td style="border: 1px solid #000; padding: 3px;" align="right">
                        <b>{{ number_format($t_harga, 2, '.', ',') }}</b>
                    </td>
                    @if ($kategori == 1)
                        <td style="border: 1px solid #000; padding: 3px;" colspan="6">&nbsp;</td>
                    @else
                        <td style="border: 1px solid #000; padding: 3px;">&nbsp;</td>
                        <td style="border: 1px solid #000; padding: 3px;">&nbsp;</td>
                        <td style="border: 1px solid #000; padding: 3px;">&nbsp;</td>
                        <td style="border: 1px solid #000; padding: 3px;" align="right">
                            <b>{{ number_format($t_penyusutan, 2, '.', ',') }}</b>
                        </td>
                        <td style="border: 1px solid #000; padding: 3px;">&nbsp;</td>
                        <td style="border: 1px solid #000; padding: 3px;" align="right">
                            <b>{{ number_format($t_akum_susut, 2, '.', ',') }}</b>
                        </td>
                    @endif
                    <td style="border: 1px solid #000; padding: 3px;" align="right">
                        <b>{{ number_format($t_nilai_buku, 2, '.', ',') }}</b>
                    </td>
                </tr>
            </tbody>
        </table>
    @endforeach
@endsection
