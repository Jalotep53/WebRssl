<?php
declare(strict_types=1);

$fmt = static function (float $value): string {
    return number_format($value, 0, ',', '.');
};
$pick = static function (array $source, array $keys, string $fallback = ''): string {
    foreach ($keys as $key) {
        if (array_key_exists($key, $source)) {
            $val = trim((string)$source[$key]);
            if ($val !== '') {
                return $val;
            }
        }
    }
    return $fallback;
};
$detail = is_array($detail ?? null) ? $detail : [];
$settingRs = is_array($settingRs ?? null) ? $settingRs : [];
$billingRows = is_array($billingRows ?? null) ? $billingRows : [];
$obatDetail = is_array($obatDetail ?? null) ? $obatDetail : [];
$detailPembayaran = is_array($detailPembayaran ?? null) ? $detailPembayaran : [];
$komponen = is_array($komponen ?? null) ? $komponen : [];
$templateMode = trim((string)($templateMode ?? 'rajal'));
$tindakanRows = is_array($tindakanRows ?? null) ? $tindakanRows : [];
$labRows = is_array($labRows ?? null) ? $labRows : [];
$radRows = is_array($radRows ?? null) ? $radRows : [];
$resepRows = is_array($resepRows ?? null) ? $resepRows : [];
$dokterRows = is_array($dokterRows ?? null) ? $dokterRows : [];
$kamarRows = is_array($kamarRows ?? null) ? $kamarRows : [];
$gabungInfo = is_array($gabungInfo ?? null) ? $gabungInfo : null;
$bayiNoRawat = trim((string)($bayiNoRawat ?? ''));
$bayiBillingRows = is_array($bayiBillingRows ?? null) ? $bayiBillingRows : [];
$bayiTotal = (float)($bayiTotal ?? 0);
$labDetailItems = is_array($labDetailItems ?? null) ? $labDetailItems : [];
$radDetailItems = is_array($radDetailItems ?? null) ? $radDetailItems : [];
$resepDetailItems = is_array($resepDetailItems ?? null) ? $resepDetailItems : [];
$tambahanRows = is_array($tambahanRows ?? null) ? $tambahanRows : [];
$penguranganRows = is_array($penguranganRows ?? null) ? $penguranganRows : [];

$registrasi = (float)($komponen['registrasi'] ?? 0);
$ruangRawat = (float)($komponen['ruang'] ?? 0);
$tindakanDokter = (float)($komponen['tindakan_dokter'] ?? 0);
$tindakanPerawat = (float)($komponen['tindakan_perawat'] ?? 0);
$tindakanDrPr = (float)($komponen['tindakan_drpr'] ?? 0);
$laboratorium = (float)($komponen['lab'] ?? 0);
$radiologi = (float)($komponen['radiologi'] ?? 0);
$obatTotal = (float)($komponen['obat'] ?? 0);
$bmhpTotal = (float)($komponen['bmhp'] ?? 0);
$gasMedisTotal = (float)($komponen['gas_medis'] ?? 0);
$tambahanBiaya = (float)($komponen['tambahan'] ?? 0);
$penguranganBiaya = (float)($komponen['pengurangan'] ?? 0);
$totalTagihan = (float)($komponen['grand_total'] ?? 0);
$ppnTotal = 0.0;
foreach ($billingRows as $br) {
    $nm = strtolower((string)($br['nm_perawatan'] ?? ''));
    if (strpos($nm, 'ppn') !== false) {
        $ppnTotal += (float)($br['totalbiaya'] ?? 0);
    }
}
$tagihanPpn = $totalTagihan + $ppnTotal;
$dibayar = 0.0;
foreach ($detailPembayaran as $dp) {
    $dibayar += (float)($dp['besar_bayar'] ?? 0);
}
$sisaPiutang = max(0.0, $tagihanPpn - $dibayar);

$nmPasien = (string)($detail['nm_pasien'] ?? '-');
$umur = trim((string)($detail['umurdaftar'] ?? ''));
$sttsUmur = trim((string)($detail['sttsumur'] ?? ''));
$nmPasienDisplay = $nmPasien;
if ($umur !== '' && $sttsUmur !== '') {
    $nmPasienDisplay .= ' (' . $umur . $sttsUmur . ')';
}
$alamatPasien = (string)($detail['alamat'] ?? '-');

$obatGroups = ['Obat' => [], 'BMHP' => [], 'Gas Medis' => []];
foreach ($obatDetail as $od) {
    $kat = (string)($od['kategori'] ?? 'Obat');
    if (!isset($obatGroups[$kat])) {
        $kat = 'Obat';
    }
    $obatGroups[$kat][] = $od;
}

$petugasNama = trim((string)(($_SESSION['auth']['nama'] ?? $_SESSION['auth']['kode'] ?? 'Petugas')));
$penanggungJawab = trim((string)($detail['namakeluarga'] ?? ''));
if ($penanggungJawab === '') {
    $penanggungJawab = '.............';
}
$npwp = $pick($settingRs, ['npwp', 'NPWP'], '0822844373912700');
$pkp = $pick($settingRs, ['pkp', 'PKP'], 'S-104/PKP/KPP.260703/2024');
$instansi = $pick($settingRs, ['nama_instansi'], 'RUMAH SAKIT');
$yayasan = $pick($settingRs, ['yayasan', 'nama_yayasan'], '');
$alamatInstansi = $pick($settingRs, ['alamat_instansi'], '-');
$kontak = $pick($settingRs, ['kontak'], '-');
$email = $pick($settingRs, ['email'], '-');
$kabupaten = $pick($settingRs, ['kabupaten'], '');
$noRm = (string)($detail['no_rkm_medis'] ?? '-');
$noNota = (string)($detail['no_nota'] ?? '-');
$tanggalCetak = date('Y-m-d');
$tanggalJam = trim((string)($detail['tgl_nota'] ?? '')) . ' ' . trim((string)($detail['jam_nota'] ?? ''));
if (trim($tanggalJam) === '') {
    $tanggalJam = trim((string)($detail['tgl_registrasi'] ?? '')) . ' ' . trim((string)($detail['jam_reg'] ?? ''));
}
$judulBilling = $templateMode === 'rajal' ? 'Pernyataan Billing Pasien Rawat Jalan' : 'Pernyataan Billing Pasien Rawat Inap';
$periodeRanap = '';
if ($templateMode !== 'rajal') {
    $mulai = '';
    $akhir = '';
    if (!empty($kamarRows)) {
        $first = $kamarRows[0];
        $last = $kamarRows[count($kamarRows) - 1];
        $mulai = trim((string)($first['tgl_masuk'] ?? '')) . ' ' . trim((string)($first['jam_masuk'] ?? ''));
        $akhirTgl = trim((string)($last['tgl_keluar'] ?? ''));
        $akhirJam = trim((string)($last['jam_keluar'] ?? ''));
        if ($akhirTgl === '' || $akhirTgl === '0000-00-00') {
            $akhirTgl = trim((string)($detail['tgl_registrasi'] ?? date('Y-m-d')));
        }
        if ($akhirJam === '') {
            $akhirJam = '00:00:00';
        }
        $akhir = trim($akhirTgl . ' ' . $akhirJam);
    }
    if ($mulai === '') {
        $mulai = trim((string)($detail['tgl_registrasi'] ?? '')) . ' ' . trim((string)($detail['jam_reg'] ?? ''));
    }
    if ($akhir === '') {
        $akhir = trim((string)($detail['tgl_registrasi'] ?? date('Y-m-d'))) . ' 00:00:00';
    }
    $periodeRanap = trim($mulai) . ' s.d. ' . trim($akhir);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Cetak Billing Ralan', ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-button { display: none !important; }
        }
        body { margin: 0; padding: 12px; background: #fff; font-family: Tahoma, Arial, sans-serif; color: #000; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        .blk { text-align: center; background: #000; color: #fff; font-size: 18px; padding: 3px 6px; }
        .small { font-size: 12px; }
        .xsmall { font-size: 11px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .top { vertical-align: top; }
        .mb8 { margin-bottom: 8px; }
        .print-button { margin-bottom: 8px; }
        .info-box { margin-top: 6px; text-align: justify; }
        .identitas-pasien-wrap { margin-top: 0; }
        .content-mid-center {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4px 8px;
        }
        .content-mid-justify {
            min-height: 120px;
            display: flex;
            align-items: center;
            text-align: justify;
            padding: 6px 8px;
        }
    </style>
</head>
<body bgcolor="#ffffff">
<?php if (!empty($detailError)): ?>
    <p>Gagal mengambil detail: <?= htmlspecialchars((string)$detailError, ENT_QUOTES, 'UTF-8') ?></p>
<?php elseif (empty($detail)): ?>
    <p>Data billing tidak ditemukan.</p>
<?php else: ?>
    <div class="print-button"><button onclick="window.print()">Print</button></div>
    <script>window.onload = function () { window.print(); };</script>

    <table style="width:100%">
        <tr>
            <td width="50%" class="top" style="padding-right:6px;">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="22%" class="top"><img width="100" src="?page=app-logo" alt="Logo RS"></td>
                        <td width="78%" class="top">
                            <?php if ($yayasan !== ''): ?><div class="small"><b><?= htmlspecialchars($yayasan, ENT_QUOTES, 'UTF-8') ?></b></div><?php endif; ?>
                            <div style="font-size:22px;"><b><?= htmlspecialchars($instansi, ENT_QUOTES, 'UTF-8') ?></b></div>
                            <div class="small">
                                <?= htmlspecialchars($alamatInstansi, ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($kabupaten !== ''): ?>, <?= htmlspecialchars($kabupaten, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                <br>Hp: <?= htmlspecialchars($kontak, ENT_QUOTES, 'UTF-8') ?>, E-mail : <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </td>
                    </tr>
                </table>
                <div class="identitas-pasien-wrap">
                    <div class="small"><b>NPWP : <?= htmlspecialchars($npwp, ENT_QUOTES, 'UTF-8') ?></b></div>
                    <div class="small"><b>PKP&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= htmlspecialchars($pkp, ENT_QUOTES, 'UTF-8') ?></b></div>
                    <div class="small"><b><?= htmlspecialchars($noRm . '#' . $detailNoRawat, ENT_QUOTES, 'UTF-8') ?></b></div>
                    <div style="font-size:18px;"><b><?= htmlspecialchars($nmPasien, ENT_QUOTES, 'UTF-8') ?></b></div>
                    <div class="small"><b><?= htmlspecialchars($alamatPasien, ENT_QUOTES, 'UTF-8') ?></b></div>
                </div>
            </td>
            <td width="50%" class="top" style="padding-left:6px;">
                <div class="blk">Pesan-Informasi Penting</div>
                <div class="small content-mid-justify">
                    <div>
                        Terimakasih telah memilih <?= htmlspecialchars($instansi, ENT_QUOTES, 'UTF-8') ?> sebagai rumah sakit pilihan anda, semoga pelayanan kami memuaskan dan berkenan bagi para pengunjung dan keluarga pasien. Untuk pembayaran menggunakan kartu kredit dan kartu sejenis, silahkan hubungi bagian kasir untuk informasi lebih jelas.
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="top">
                <table style="width:100%;" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="50%" class="top" style="padding-right:6px;">
                            <div class="blk"><?= htmlspecialchars($judulBilling, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="content-mid-center">
                                <?php if ($templateMode === 'rajal'): ?>
                                    <div class="small"><b>Tanggal Pernyataan : <?= htmlspecialchars($tanggalCetak, ENT_QUOTES, 'UTF-8') ?></b></div>
                                <?php else: ?>
                                    <div class="small"><b>Periode Rawat Inap : <?= htmlspecialchars($periodeRanap, ENT_QUOTES, 'UTF-8') ?></b></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td width="50%" class="top" style="padding-left:6px;">
                            <div class="blk">Informasi Asuransi Penanggung Jawab</div>
                            <div class="small" style="text-align:justify; margin-top:6px;">
                                Nama Asuransi &nbsp; : &nbsp; <?= htmlspecialchars((string)($detail['png_jawab'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                                Asuransi ID # &nbsp;&nbsp;&nbsp; : &nbsp; <?= htmlspecialchars((string)($detail['no_peserta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br><br>
                                Silahkan hubungi costumer services Asuransi anda jika ada perubahan dan ketidakjelasan pada Asuransi anda
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="3"><div class="blk">Detail Dari Data yang Dibebankan</div></td>
        </tr>
    </table>

    <table bgcolor="#ffffff" align="left" border="0" cellspacing="0" cellpadding="0">
        <tr><td colspan="7">
            <table width="100%" bgcolor="#ffffff" align="left" border="0" cellspacing="0" cellpadding="0">
                <tr class="xsmall"><td width="30%">No.Nota</td><td width="40%" colspan="6">: <?= htmlspecialchars($noNota, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php if ($templateMode === 'rajal'): ?>
                    <tr class="xsmall"><td width="30%">Unit/Instansi</td><td width="40%" colspan="6">: FARMASI</td></tr>
                <?php else: ?>
                    <?php
                    $bangsalKamar = '-';
                    if (!empty($kamarRows)) {
                        $k = $kamarRows[0];
                        $bangsalKamar = trim((string)($k['kd_kamar'] ?? ''));
                        $nmBangsal = trim((string)($k['nm_bangsal'] ?? ''));
                        if ($nmBangsal !== '') {
                            $bangsalKamar .= ', ' . $nmBangsal;
                        }
                    }
                    ?>
                    <tr class="xsmall"><td width="30%">Bangsal/Kamar</td><td width="40%" colspan="6">: <?= htmlspecialchars($bangsalKamar, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php endif; ?>
                <tr class="xsmall"><td width="30%">Tanggal &amp; Jam</td><td width="40%" colspan="6">: <?= htmlspecialchars(trim($tanggalJam), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php if ($templateMode === 'ranap_gabung' && $gabungInfo): ?>
                    <tr class="xsmall"><td width="30%">No.R.M. Ibu</td><td width="40%" colspan="6">: <?= htmlspecialchars((string)($gabungInfo['rm_ibu'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr class="xsmall"><td width="30%">Nama Ibu</td><td width="40%" colspan="6">: <?= htmlspecialchars((string)($gabungInfo['nm_ibu'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr class="xsmall"><td width="30%">No.R.M. Bayi</td><td width="40%" colspan="6">: <?= htmlspecialchars((string)($gabungInfo['rm_bayi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr class="xsmall"><td width="30%">Nama Bayi</td><td width="40%" colspan="6">: <?= htmlspecialchars((string)($gabungInfo['nm_bayi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php else: ?>
                    <tr class="xsmall"><td width="30%">No.RM</td><td width="40%" colspan="6">: <?= htmlspecialchars($noRm, ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr class="xsmall"><td width="30%">Nama Pasien</td><td width="40%" colspan="6">: <?= htmlspecialchars($nmPasienDisplay, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php endif; ?>
                <tr class="xsmall"><td width="30%">Alamat Pasien</td><td width="40%" colspan="6">: <?= htmlspecialchars($alamatPasien, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php if (!empty($dokterRows)): ?>
                    <tr class="xsmall"><td width="30%">Dokter</td><td width="40%" colspan="6">:</td></tr>
                    <?php foreach ($dokterRows as $dr): ?>
                        <tr class="xsmall"><td width="30%">&nbsp;</td><td width="40%" colspan="6"><?= htmlspecialchars((string)($dr['nm_dokter'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="xsmall"><td width="30%">Dokter</td><td width="40%" colspan="6">:&nbsp;<?= htmlspecialchars((string)($detail['nm_dokter'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php endif; ?>
                <tr class="xsmall">
                    <td width="30%">Administrasi Rekam Medik</td>
                    <td width="55%" colspan="4">:</td><td width="1%"></td><td width="14%" class="right"><?= $fmt($registrasi) ?></td>
                </tr>
                <?php if ($templateMode !== 'rajal'): ?>
                <tr class="xsmall">
                    <td width="30%">Ruang</td>
                    <td width="55%" colspan="4">:</td><td width="1%"></td><td width="14%" class="right"><?= $fmt($ruangRawat) ?></td>
                </tr>
                <?php foreach ($kamarRows as $kr): ?>
                    <?php
                    $kamarNama = trim((string)($kr['kd_kamar'] ?? ''));
                    $bangsalNama = trim((string)($kr['nm_bangsal'] ?? ''));
                    if ($bangsalNama !== '') {
                        $kamarNama .= ', ' . $bangsalNama;
                    }
                    $lamaHari = (int)($kr['lama'] ?? 0);
                    if ($lamaHari <= 0) {
                        $tglMasuk = trim((string)($kr['tgl_masuk'] ?? ''));
                        $tglKeluar = trim((string)($kr['tgl_keluar'] ?? ''));
                        if ($tglMasuk !== '' && $tglKeluar !== '' && $tglKeluar !== '0000-00-00') {
                            try {
                                $d1 = new DateTime($tglMasuk);
                                $d2 = new DateTime($tglKeluar);
                                $lamaHari = (int)$d1->diff($d2)->days;
                                if ($lamaHari <= 0) {
                                    $lamaHari = 1;
                                }
                            } catch (Throwable $e) {
                                $lamaHari = 1;
                            }
                        } else {
                            $lamaHari = 1;
                        }
                    }
                    ?>
                    <tr class="xsmall">
                        <td width="30%">&nbsp;</td>
                        <td width="55%" colspan="4"><?= htmlspecialchars($kamarNama, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$lamaHari, ENT_QUOTES, 'UTF-8') ?> Hari)</td>
                        <td width="1%"></td>
                        <td width="14%" class="right"><?= $fmt((float)($kr['ttl_biaya'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                <tr class="xsmall top">
                    <td width="30%">Tindakan</td>
                    <td width="40%" colspan="6">
                        <table border="0" width="100%" cellspacing="0" cellpadding="0">
                            <?php if ($templateMode === 'ranap_gabung'): ?>
                                <?php
                                $ibuTindakanGroups = [
                                    'Dokter' => [],
                                    'Perawat' => [],
                                    'Dokter+Perawat' => [],
                                ];
                                foreach ($tindakanRows as $row) {
                                    $jenis = (string)($row['jenis'] ?? 'Dokter');
                                    if (!isset($ibuTindakanGroups[$jenis])) {
                                        $jenis = 'Dokter';
                                    }
                                    $ibuTindakanGroups[$jenis][] = $row;
                                }
                                ?>
                                <?php foreach ($ibuTindakanGroups as $jenis => $rowsJenis): ?>
                                    <?php if (empty($rowsJenis)) { continue; } ?>
                                    <tr class="xsmall">
                                        <td width="80%"><b><?= htmlspecialchars('Tindakan Ibu - ' . $jenis, ENT_QUOTES, 'UTF-8') ?></b></td>
                                        <td width="1%"></td>
                                        <td width="19%"></td>
                                    </tr>
                                    <?php $subtotalJenis = 0.0; ?>
                                    <?php foreach ($rowsJenis as $row): ?>
                                        <?php $subtotalJenis += (float)($row['biaya_rawat'] ?? 0); ?>
                                        <tr class="xsmall">
                                            <td width="80%"><?= htmlspecialchars((string)($row['nm_perawatan'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td width="1%">1</td>
                                            <td width="19%" class="right"><?= $fmt((float)($row['biaya_rawat'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="xsmall">
                                        <td width="80%"></td>
                                        <td width="1%"></td>
                                        <td width="19%" class="right"><b><?= $fmt($subtotalJenis) ?></b></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php foreach ($tindakanRows as $row): ?>
                                    <tr class="xsmall">
                                        <td width="80%"><?= htmlspecialchars((string)($row['nm_perawatan'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td width="1%"><?= htmlspecialchars((string)($row['jenis'] ?? '1'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td width="19%" class="right"><?= $fmt((float)($row['biaya_rawat'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="xsmall"><td width="80%"></td><td width="1%"></td><td width="19%" class="right"><b><?= $fmt($tindakanDokter + $tindakanPerawat + $tindakanDrPr) ?></b></td></tr>
                            <?php endif; ?>
                        </table>
                    </td>
                </tr>
                <tr class="xsmall top">
                    <td width="30%">Laboratorium</td>
                    <td width="40%" colspan="6">
                        <table border="0" width="100%" cellspacing="0" cellpadding="0">
                            <?php foreach ($labDetailItems as $li): ?>
                                <tr class="xsmall">
                                    <td width="80%"><?= htmlspecialchars((string)($li['nm_perawatan'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td width="1%">1</td>
                                    <td width="19%" class="right"><?= $fmt((float)($li['harga'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!empty($labDetailItems) || !empty($labRows)): ?><tr class="xsmall"><td width="80%"></td><td width="1%"></td><td width="19%" class="right"><b><?= $fmt($laboratorium) ?></b></td></tr><?php endif; ?>
                        </table>
                    </td>
                </tr>
                <tr class="xsmall top">
                    <td width="30%">Radiologi</td>
                    <td width="40%" colspan="6">
                        <table border="0" width="100%" cellspacing="0" cellpadding="0">
                            <?php foreach ($radDetailItems as $ri): ?>
                                <tr class="xsmall">
                                    <td width="80%"><?= htmlspecialchars((string)($ri['nm_perawatan'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td width="1%">1</td>
                                    <td width="19%" class="right"><?= $fmt((float)($ri['harga'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!empty($radDetailItems) || !empty($radRows)): ?><tr class="xsmall"><td width="80%"></td><td width="1%"></td><td width="19%" class="right"><b><?= $fmt($radiologi) ?></b></td></tr><?php endif; ?>
                        </table>
                    </td>
                </tr>
                <tr class="xsmall top">
                    <td width="30%">Resep</td>
                    <td width="40%" colspan="6">
                        <table border="0" width="100%" cellspacing="0" cellpadding="0">
                            <?php foreach ($resepDetailItems as $ri): ?>
                                <tr class="xsmall">
                                    <td width="80%"><?= htmlspecialchars((string)($ri['nama_brng'] ?? ''), ENT_QUOTES, 'UTF-8') ?><?= !empty($ri['aturan_pakai']) ? ' (' . htmlspecialchars((string)$ri['aturan_pakai'], ENT_QUOTES, 'UTF-8') . ')' : '' ?></td>
                                    <td width="1%"><?= number_format((float)($ri['jml'] ?? 0), 1, '.', '') ?></td>
                                    <td width="19%" class="right"><?= $fmt((float)($ri['total_harga'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                </tr>
                <?php if ($templateMode === 'ranap_gabung'): ?>
                <tr class="xsmall top">
                    <td width="30%">Biaya Perawatan Bayi</td>
                    <td width="40%" colspan="6">
                        <table border="0" width="100%" cellspacing="0" cellpadding="0">
                            <?php foreach ($bayiBillingRows as $br): ?>
                                <tr class="xsmall">
                                    <td width="80%"><?= htmlspecialchars((string)($br['nm_perawatan'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td width="1%"><?= htmlspecialchars((string)($br['jumlah'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td width="19%" class="right"><?= $fmt((float)($br['totalbiaya'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="xsmall"><td width="80%"></td><td width="1%"></td><td width="19%" class="right"><b><?= $fmt($bayiTotal) ?></b></td></tr>
                        </table>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach (['Obat' => $obatTotal, 'BMHP' => $bmhpTotal, 'Gas Medis' => $gasMedisTotal] as $label => $subtotal): ?>
                    <tr class="xsmall top">
                        <td width="30%"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></td>
                        <td width="40%" colspan="6">
                            <table border="0" width="100%" cellspacing="0" cellpadding="0">
                                <?php foreach ($obatGroups[$label] as $row): ?>
                                    <tr class="xsmall">
                                        <td width="80%"><?= htmlspecialchars((string)($row['nama_brng'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td width="1%"><?= number_format((float)($row['jml'] ?? 0), 1, '.', '') ?></td>
                                        <td width="19%" class="right"><?= $fmt((float)($row['total_item'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!empty($obatGroups[$label])): ?><tr class="xsmall"><td width="80%"></td><td width="1%"></td><td width="19%" class="right"><b><?= $fmt($subtotal) ?></b></td></tr><?php endif; ?>
                            </table>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="xsmall top">
                    <td width="30%">Tambahan Biaya</td>
                    <td width="40%" colspan="6">
                        <table border="0" width="100%" cellspacing="0" cellpadding="0">
                            <?php foreach ($tambahanRows as $tb): ?>
                                <tr class="xsmall">
                                    <td width="80%"><?= htmlspecialchars((string)($tb['nama_biaya'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td width="1%">1</td>
                                    <td width="19%" class="right"><?= $fmt((float)($tb['besar_biaya'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="xsmall"><td width="80%"></td><td width="1%"></td><td width="19%" class="right"><b><?= $fmt($tambahanBiaya) ?></b></td></tr>
                        </table>
                    </td>
                </tr>
                <tr class="xsmall top">
                    <td width="30%">Pengurangan Biaya</td>
                    <td width="40%" colspan="6">
                        <table border="0" width="100%" cellspacing="0" cellpadding="0">
                            <?php foreach ($penguranganRows as $pg): ?>
                                <tr class="xsmall">
                                    <td width="80%"><?= htmlspecialchars((string)($pg['nama_pengurangan'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td width="1%">1</td>
                                    <td width="19%" class="right"><?= $fmt((float)($pg['besar_pengurangan'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="xsmall"><td width="80%"></td><td width="1%"></td><td width="19%" class="right"><b><?= $fmt($penguranganBiaya) ?></b></td></tr>
                        </table>
                    </td>
                </tr>
                <tr class="xsmall"><td width="30%">TOTAL TAGIHAN</td><td width="55%" colspan="4"></td><td width="1%"></td><td width="14%" class="right"><b><?= $fmt($totalTagihan) ?></b></td></tr>
                <tr class="xsmall"><td width="30%">PPN</td><td width="55%" colspan="4"></td><td width="1%"></td><td width="14%" class="right"><b><?= $fmt($ppnTotal) ?></b></td></tr>
                <tr class="xsmall"><td width="30%">TAGIHAN + PPN</td><td width="55%" colspan="4"></td><td width="1%"></td><td width="14%" class="right"><b><?= $fmt($tagihanPpn) ?></b></td></tr>
                <tr class="xsmall"><td width="30%">EKSES</td><td width="55%" colspan="4"></td><td width="1%"></td><td width="14%" class="right"><b>0</b></td></tr>
                <tr class="xsmall"><td width="30%">SISA PIUTANG</td><td width="55%" colspan="4"></td><td width="1%"></td><td width="14%" class="right"><b><?= $fmt($sisaPiutang) ?></b></td></tr>
                <tr><td>&nbsp;</td></tr>
                <tr class="xsmall">
                    <td colspan="7" class="center"><b>* FASILITAS PPN JASA PELAYANAN KESEHATAN <br>DIBEBASKAN SESUAI DENGAN PASAL 16B UU HPP No.7 TAHUN 2021 TENTANG HARMONISASI PERATURAN PERPAJAKAN</b></td>
                </tr>
                <tr><td>&nbsp;</td></tr>
                <tr class="xsmall"><td colspan="7">
                    <table width="100%" bgcolor="#ffffff" align="left" border="0" cellspacing="0" cellpadding="0">
                        <tr class="xsmall">
                            <td width="50%" class="center">&nbsp;</td>
                            <td width="50%" class="center"><?= htmlspecialchars(($kabupaten !== '' ? $kabupaten : 'Kota') . ', ' . date('d-m-Y His'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr class="xsmall">
                            <td width="50%" class="center">Petugas</td>
                            <td width="50%" class="center">Penanggung Jawab Pasien</td>
                        </tr>
                        <tr class="xsmall">
                            <td width="50%" class="center"><br><br>( <?= htmlspecialchars($petugasNama, ENT_QUOTES, 'UTF-8') ?> )</td>
                            <td width="50%" class="center"><br><br>( <?= htmlspecialchars($penanggungJawab, ENT_QUOTES, 'UTF-8') ?> )</td>
                        </tr>
                    </table>
                </td></tr>
            </table>
        </td></tr>
    </table>
<?php endif; ?>
</body>
</html>
