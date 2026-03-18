<style>
    .sep-wrap { background:#fff; border:1px solid #d4dde6; border-radius:10px; padding:12px; }
    .sep-tools { display:flex; justify-content:space-between; margin-bottom:10px; gap:8px; }
    .sep-tool-left, .sep-tool-right { display:flex; gap:8px; }
    .sep-paper {
        border:1px solid #000;
        padding:10px 12px;
        color:#000;
        font-family:Tahoma, Arial, sans-serif;
        font-size:11px;
        line-height:1.25;
    }
    .sep-head { display:flex; align-items:flex-start; gap:10px; margin-bottom:6px; }
    .sep-head img { width:88px; height:48px; object-fit:contain; }
    .sep-head h1 { margin:0; font-size:16px; letter-spacing:.4px; }
    .sep-title { text-align:center; font-weight:700; font-size:14px; margin:4px 0 10px; }
    .sep-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px 18px; }
    .sep-grid table { width:100%; border-collapse:collapse; }
    .sep-grid td { padding:1px 0; vertical-align:top; }
    .sep-grid td.k { width:110px; }
    .sep-grid td.c { width:10px; text-align:center; }
    .sep-notes { margin-top:8px; font-size:10px; }
    .sep-sign { margin-top:10px; display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .sep-sign .box { min-height:70px; }
    .sep-debug { margin:10px 0 12px; border:1px solid #d7e4ed; border-radius:10px; padding:10px 12px; background:#f7fbfd; }
    .sep-debug pre { margin:10px 0 0; padding:12px; border-radius:10px; background:#fff; border:1px solid #dbe5ee; overflow:auto; white-space:pre-wrap; word-break:break-word; }
    @media print {
        body * { visibility:hidden !important; }
        .sep-wrap, .sep-wrap * { visibility:visible !important; }
        .sep-wrap {
            position:absolute !important;
            left:0 !important;
            top:0 !important;
            width:100% !important;
            margin:0 !important;
            padding:0 !important;
            border:0 !important;
            box-shadow:none !important;
            border-radius:0 !important;
        }
        .sep-tools, .sep-debug { display:none !important; }
        .sep-paper { border:1px solid #000 !important; }
    }
</style>

<?php
$requestPayload = is_array($requestPayload ?? null) ? $requestPayload : null;
$payloadSep = is_array($requestPayload['request']['t_sep'] ?? null) ? $requestPayload['request']['t_sep'] : [];
$jsonPayload = $requestPayload === null
    ? ''
    : (json_encode($requestPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
$kelasCode = '';
if (is_array($payloadSep['klsRawat'] ?? null)) {
    $kelasCode = trim((string)($payloadSep['klsRawat']['klsRawatHak'] ?? ''));
} else {
    $kelasCode = trim((string)($payloadSep['klsRawat'] ?? ''));
}
$kelasNaikCode = is_array($payloadSep['klsRawat'] ?? null) ? trim((string)($payloadSep['klsRawat']['klsRawatNaik'] ?? '')) : '';
$kelasLabel = static function (string $code): string {
    return match ($code) {
        '1' => 'Kelas 1',
        '2' => 'Kelas 2',
        '3' => 'Kelas 3',
        '4' => 'Kelas 2',
        '5' => 'Kelas 3',
        '6' => 'ICCU',
        '7' => 'ICU',
        '8' => 'Kelas Khusus',
        default => ($code !== '' ? $code : '-'),
    };
};
?>

<div class="card">
    <h2 style="margin-top:0;">Cetak SEP</h2>
    <?php if (!empty($error)): ?>
        <p class="pill" style="border-color:#f4b4b4;background:#ffecec;color:#8b1b1b;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php elseif (!empty($row)): ?>
        <?php if (!empty($bpjsCheck) && is_array($bpjsCheck)): ?>
            <p class="pill" style="border-color:<?= !empty($bpjsCheck['ok']) ? '#cde6de' : '#f4b4b4' ?>;background:<?= !empty($bpjsCheck['ok']) ? '#edf8f4' : '#ffecec' ?>;color:<?= !empty($bpjsCheck['ok']) ? '#0f5132' : '#8b1b1b' ?>;">
                Status BPJS: <?= htmlspecialchars((string)($bpjsCheck['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            </p>
            <?php if (empty($bpjsCheck['ok'])): ?>
                <?php
                    $rawBpjs = $bpjsCheck['raw'] ?? null;
                    $rawBpjsText = is_array($rawBpjs)
                        ? (json_encode($rawBpjs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '')
                        : trim((string)$rawBpjs);
                ?>
                <details style="margin:10px 0 12px; border:1px solid #f4b4b4; border-radius:10px; padding:10px 12px; background:#fff7f7;">
                    <summary style="cursor:pointer; color:#8b1b1b; font-weight:600;">Detail Error BPJS / JSON Tidak Valid</summary>
                    <div style="margin-top:10px; font-size:13px; color:#5b1d1d;">
                        <div><strong>No. SEP:</strong> <?= htmlspecialchars((string)($row['no_sep'] ?? $noSep ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div><strong>HTTP Code:</strong> <?= htmlspecialchars((string)($bpjsCheck['http_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div><strong>Meta Code:</strong> <?= htmlspecialchars((string)($bpjsCheck['meta_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div><strong>Meta Message:</strong> <?= htmlspecialchars((string)($bpjsCheck['meta_message'] ?? $bpjsCheck['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="margin-top:8px;"><strong>Endpoint:</strong> <code>GET SEP/<?= htmlspecialchars((string)($row['no_sep'] ?? $noSep ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></div>
                        <div style="margin-top:8px;"><strong>Catatan:</strong> Halaman ini memverifikasi SEP dengan request <code>GET</code>, tetapi payload create SEP direkonstruksi dari data <code>bridging_sep</code> dan ditampilkan pada panel debug di bawah.</div>
                    </div>
                    <?php if ($rawBpjsText !== ''): ?>
                        <pre><?= htmlspecialchars($rawBpjsText, ENT_QUOTES, 'UTF-8') ?></pre>
                    <?php endif; ?>
                </details>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($jsonPayload !== ''): ?>
            <details class="sep-debug">
                <summary style="cursor:pointer; font-weight:600; color:#174761;">Payload JSON Request SEP BPJS</summary>
                <div style="margin-top:8px; font-size:12px; color:#4b6478;">Payload ini dibentuk ulang dari data SEP yang tersimpan di <code>bridging_sep</code> dan mengikuti schema request BPJS.</div>
                <pre><?= htmlspecialchars($jsonPayload, ENT_QUOTES, 'UTF-8') ?></pre>
            </details>
        <?php endif; ?>

        <?php
        $rujukan = is_array($payloadSep['rujukan'] ?? null) ? $payloadSep['rujukan'] : [];
        $poli = is_array($payloadSep['poli'] ?? null) ? $payloadSep['poli'] : [];
        $skdp = is_array($payloadSep['skdp'] ?? null) ? $payloadSep['skdp'] : [];
        $jenisRawat = ((string)($payloadSep['jnsPelayanan'] ?? $row['jnspelayanan'] ?? '') === '1') ? 'Rawat Inap' : 'Rawat Jalan';
        $kelasHak = $kelasLabel($kelasCode);
        $kelasNaik = $kelasNaikCode !== '' ? $kelasLabel($kelasNaikCode) : '-';
        $subSpesialis = (string)($row['nmpolitujuan'] ?? '-');
        $faskesPerujuk = (string)($row['nmppkrujukan'] ?? '-');
        $diagnosaAwal = trim(((string)($row['diagawal'] ?? '-')) . ' ' . ((string)($row['nmdiagnosaawal'] ?? '')));
        $penjamin = (string)($row['png_jawab'] ?? '-');
        $dokter = (string)($row['nmdpdjp'] ?? $row['nm_dokter'] ?? '-');
        $jnsKunjungan = match ((string)($row['tujuankunjungan'] ?? '0')) {
            '1' => 'Prosedur',
            '2' => 'Konsul Dokter',
            default => 'Normal',
        };
        $petugas = trim((string)($row['nama_petugas'] ?? '')) !== '' ? (string)$row['nama_petugas'] : (string)($row['user'] ?? '-');
        $asalRujukanLabel = match ((string)($rujukan['asalRujukan'] ?? '1')) {
            '2' => '2. Faskes 2 (RS)',
            default => '1. Faskes 1',
        };
        ?>
        <div class="sep-wrap">
            <div class="sep-tools">
                <div class="sep-tool-left">
                    <button type="button" onclick="history.back()">Kembali</button>
                </div>
                <div class="sep-tool-right">
                    <button type="button" onclick="window.print()">Print</button>
                </div>
            </div>
            <div class="sep-paper">
                <div class="sep-head">
                    <img src="logo_bpjs.png" alt="BPJS">
                    <div>
                        <h1>BPJS KESEHATAN</h1>
                        <div>Rujukan: <?= htmlspecialchars((string)($row['no_rujukan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
                <div class="sep-title">SURAT ELEGIBILITAS PESERTA</div>

                <div class="sep-grid">
                    <table>
                        <tr><td class="k">No. SEP</td><td class="c">:</td><td><?= htmlspecialchars((string)($row['no_sep'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Tgl. SEP</td><td class="c">:</td><td><?= htmlspecialchars((string)($payloadSep['tglSep'] ?? $row['tglsep'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">No. Kartu</td><td class="c">:</td><td><?= htmlspecialchars((string)($payloadSep['noKartu'] ?? $row['no_kartu'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">No. RM</td><td class="c">:</td><td><?= htmlspecialchars((string)($payloadSep['noMR'] ?? $row['nomr'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Nama Peserta</td><td class="c">:</td><td><?= htmlspecialchars((string)($row['nama_pasien'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Tgl. Lahir</td><td class="c">:</td><td><?= htmlspecialchars((string)($row['tanggal_lahir'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">No.Telepon</td><td class="c">:</td><td><?= htmlspecialchars((string)($payloadSep['noTelp'] ?? $row['notelep'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Faskes Rujuk</td><td class="c">:</td><td><?= htmlspecialchars($faskesPerujuk, ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Tgl. Rujukan</td><td class="c">:</td><td><?= htmlspecialchars((string)($rujukan['tglRujukan'] ?? $row['tglrujukan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Diagnosa Awal</td><td class="c">:</td><td><?= htmlspecialchars($diagnosaAwal !== '' ? $diagnosaAwal : '-', ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Catatan</td><td class="c">:</td><td><?= htmlspecialchars((string)($payloadSep['catatan'] ?? $row['catatan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    </table>
                    <table>
                        <tr><td class="k">Peserta</td><td class="c">:</td><td><?= htmlspecialchars((string)($row['peserta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Jns.Kunjungan</td><td class="c">:</td><td><?= htmlspecialchars($jnsKunjungan, ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Jns. Rawat</td><td class="c">:</td><td><?= htmlspecialchars($jenisRawat, ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Kls. Hak</td><td class="c">:</td><td><?= htmlspecialchars($kelasHak, ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Kls. Naik</td><td class="c">:</td><td><?= htmlspecialchars($kelasNaik, ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Penjamin</td><td class="c">:</td><td><?= htmlspecialchars($penjamin, ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Sub/Spesialis</td><td class="c">:</td><td><?= htmlspecialchars($subSpesialis, ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Kode Poli</td><td class="c">:</td><td><?= htmlspecialchars((string)($poli['tujuan'] ?? $row['kdpolitujuan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Asal Rujukan</td><td class="c">:</td><td><?= htmlspecialchars($asalRujukanLabel, ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">PPK Layanan</td><td class="c">:</td><td><?= htmlspecialchars((string)($payloadSep['ppkPelayanan'] ?? $row['kdppkpelayanan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Dokter DPJP</td><td class="c">:</td><td><?= htmlspecialchars($dokter, ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">Kode DPJP</td><td class="c">:</td><td><?= htmlspecialchars((string)($skdp['kodeDPJP'] ?? $row['kddpjp'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">No. Surat</td><td class="c">:</td><td><?= htmlspecialchars((string)($skdp['noSurat'] ?? $row['noskdp'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><td class="k">User SEP</td><td class="c">:</td><td><?= htmlspecialchars((string)($payloadSep['user'] ?? $row['user'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    </table>
                </div>

                <div class="sep-notes">
                    <div>*Saya menyetujui BPJS Kesehatan menggunakan informasi medis pasien jika diperlukan.</div>
                    <div>*SEP bukan sebagai bukti penjaminan peserta.</div>
                    <div>Cetakan ke 1</div>
                </div>

                <div class="sep-sign">
                    <div class="box">
                        Pasien/Keluarga Pasien
                        <div style="height:42px;"></div>
                        (.....................................)
                    </div>
                    <div class="box" style="text-align:right;">
                        Petugas
                        <div style="height:42px;"></div>
                        (<?= htmlspecialchars($petugas, ENT_QUOTES, 'UTF-8') ?>)
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
