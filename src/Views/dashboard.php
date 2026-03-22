<?php
$maxKunjungan = 1;
$maxPendapatan = 1.0;
foreach (($series7 ?? []) as $row) {
    $maxKunjungan = max($maxKunjungan, (int)($row['kunjungan'] ?? 0));
    $maxPendapatan = max($maxPendapatan, (float)($row['pendapatan'] ?? 0));
}
$maxPoli = 1;
foreach (($topPoli7Day ?? []) as $p) {
    $maxPoli = max($maxPoli, (int)($p['total'] ?? 0));
}
?>

<div class="content-shell">
<div class="card">
    <h2 style="margin-top:0;">Dashboard Operasional Harian</h2>
    <p class="muted">Ringkasan layanan harian untuk Registrasi, Rawat Jalan, Farmasi, dan Billing.</p>

    <h3 style="margin:14px 0 8px;">Ringkasan Hari Ini</h3>
    <div class="cards">
        <div class="card">
            <div class="muted">Total Kunjungan</div>
            <div class="badge"><?= (int)($summary['kunjungan_hari_ini'] ?? 0) ?></div>
        </div>
        <div class="card">
            <div class="muted">Pasien Baru</div>
            <div class="badge"><?= (int)($summary['pasien_baru_hari_ini'] ?? 0) ?></div>
        </div>
        <div class="card">
            <div class="muted">Resep Belum Validasi</div>
            <div class="badge"><?= (int)($summary['resep_belum_validasi'] ?? 0) ?></div>
        </div>
        <div class="card">
            <div class="muted">Billing Belum Lunas</div>
            <div class="badge"><?= (int)($summary['billing_belum_lunas'] ?? 0) ?></div>
        </div>
    </div>

</div>

<div class="card">
    <h3 style="margin-top:0;">Antrian Layanan</h3>
    <table>
        <thead>
        <tr>
            <th>Layanan</th>
            <th class="num">Menunggu</th>
            <th class="num">Proses</th>
            <th class="num">Selesai</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Registrasi</td>
            <td class="num"><?= (int)($queue['registrasi']['menunggu'] ?? 0) ?></td>
            <td class="num"><?= (int)($queue['registrasi']['proses'] ?? 0) ?></td>
            <td class="num"><?= (int)($queue['registrasi']['selesai'] ?? 0) ?></td>
        </tr>
        <tr>
            <td>Rawat Jalan</td>
            <td class="num"><?= (int)($queue['rawatjalan']['menunggu'] ?? 0) ?></td>
            <td class="num"><?= (int)($queue['rawatjalan']['proses'] ?? 0) ?></td>
            <td class="num"><?= (int)($queue['rawatjalan']['selesai'] ?? 0) ?></td>
        </tr>
        <tr>
            <td>Farmasi</td>
            <td class="num"><?= (int)($queue['farmasi']['menunggu'] ?? 0) ?></td>
            <td class="num"><?= (int)($queue['farmasi']['proses'] ?? 0) ?></td>
            <td class="num"><?= (int)($queue['farmasi']['selesai'] ?? 0) ?></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0;">Alert Prioritas</h3>
    <div class="row">
        <span class="pill">Resep Pending > 2 Jam: <strong><?= (int)($alerts['resep_pending_lama'] ?? 0) ?></strong></span>
        <span class="pill">Permintaan Lab Hari Ini: <strong><?= (int)($alerts['permintaan_lab_pending'] ?? 0) ?></strong></span>
        <span class="pill">Permintaan Radiologi Hari Ini: <strong><?= (int)($alerts['permintaan_rad_pending'] ?? 0) ?></strong></span>
        <span class="pill">Billing Belum Bayar: <strong><?= (int)($alerts['billing_belum_bayar'] ?? 0) ?></strong></span>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Grafik 7 Hari</h3>
    <div class="cards">
        <div class="card">
            <div class="muted" style="margin-bottom:8px;">Tren Kunjungan Rawat Jalan</div>
            <?php foreach (($series7 ?? []) as $r): ?>
                <?php $w = (int)round(((int)$r['kunjungan'] / $maxKunjungan) * 100); ?>
                <div style="display:grid;grid-template-columns:52px 1fr 60px;gap:8px;align-items:center;margin-bottom:6px;">
                    <div class="muted"><?= htmlspecialchars((string)$r['label'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="background:#e9f3f9;border-radius:8px;height:14px;overflow:hidden;">
                        <div style="height:100%;width:<?= $w ?>%;background:#0f6b8f;"></div>
                    </div>
                    <div class="num"><?= (int)$r['kunjungan'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="card">
            <div class="muted" style="margin-bottom:8px;">Tren Pendapatan Ralan</div>
            <?php foreach (($series7 ?? []) as $r): ?>
                <?php $w = (int)round(((float)$r['pendapatan'] / $maxPendapatan) * 100); ?>
                <div style="display:grid;grid-template-columns:52px 1fr 100px;gap:8px;align-items:center;margin-bottom:6px;">
                    <div class="muted"><?= htmlspecialchars((string)$r['label'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="background:#e8f8f7;border-radius:8px;height:14px;overflow:hidden;">
                        <div style="height:100%;width:<?= $w ?>%;background:#13a5a0;"></div>
                    </div>
                    <div class="num"><?= number_format((float)$r['pendapatan'], 0, ',', '.') ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card" style="margin-top:12px;">
        <div class="muted" style="margin-bottom:8px;">Top Poli (7 Hari Terakhir)</div>
        <?php foreach (($topPoli7Day ?? []) as $p): ?>
            <?php $w = (int)round(((int)$p['total'] / $maxPoli) * 100); ?>
            <div style="display:grid;grid-template-columns:minmax(180px,280px) 1fr 60px;gap:8px;align-items:center;margin-bottom:6px;">
                <div><?= htmlspecialchars((string)$p['nm_poli'], ENT_QUOTES, 'UTF-8') ?></div>
                <div style="background:#f1f7fb;border-radius:8px;height:14px;overflow:hidden;">
                    <div style="height:100%;width:<?= $w ?>%;background:#266f98;"></div>
                </div>
                <div class="num"><?= (int)$p['total'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Shortcut Aksi Cepat</h3>
    <div class="row">
        <a href="?page=registrasi" style="text-decoration:none;"><span class="pill">Daftarkan Pasien</span></a>
        <a href="?page=rawatjalan" style="text-decoration:none;"><span class="pill">Buka Rawat Jalan</span></a>
        <a href="?page=farmasi" style="text-decoration:none;"><span class="pill">Validasi Resep</span></a>
        <a href="?page=billing-ralan" style="text-decoration:none;"><span class="pill">Billing Ralan</span></a>
    </div>
</div>
</div>

