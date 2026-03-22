<?php $embedMode = trim((string)($_GET['embed'] ?? '')) === '1'; ?>
<?php $serviceOnly = trim((string)($_GET['service_only'] ?? '')) === '1'; ?>
<?php $focus = trim((string)($_GET['focus'] ?? '')); ?>
<?php if (!$embedMode): ?>
<div class="card">
    <h2 style="margin-top:0;">Modul Rawat Inap</h2>
    <?php if (!empty($msgDetail)): ?>
        <p class="pill" style="border-color:<?= (($msg ?? '') === 'ok') ? '#cde6de' : '#f4b4b4' ?>;background:<?= (($msg ?? '') === 'ok') ? '#edf8f4' : '#ffecec' ?>;color:<?= (($msg ?? '') === 'ok') ? '#0f5132' : '#8b1b1b' ?>;">
            <?= htmlspecialchars((string)$msgDetail, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="muted">Gagal mengambil data: <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="get" class="row" style="margin:10px 0 12px;">
        <input type="hidden" name="page" value="rawatinap">
        <div class="field">
            <label>Status Pasien</label>
            <select name="status" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                <option value="aktif" <?= (($status ?? 'aktif') === 'aktif') ? 'selected' : '' ?>>Masih Dirawat</option>
                <option value="semua" <?= (($status ?? 'aktif') === 'semua') ? 'selected' : '' ?>>Semua (berdasarkan tanggal)</option>
            </select>
        </div>
        <div class="field">
            <label>Dari Tanggal Masuk</label>
            <input type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>" <?= (($status ?? 'aktif') === 'aktif') ? 'disabled' : '' ?>>
        </div>
        <div class="field">
            <label>Sampai Tanggal Masuk</label>
            <input type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>" <?= (($status ?? 'aktif') === 'aktif') ? 'disabled' : '' ?>>
        </div>
        <div class="field">
            <label>Cari (No Rawat/RM/Nama/Bangsal/Kamar)</label>
            <input type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit">Filter</button>
    </form>
</div>

<div class="card" style="margin-top:12px;">
    <style>
        .card, table, tbody, tr, td { overflow: visible !important; }
        .aksi-wrap { position: relative; display: inline-block; }
        .aksi-trigger {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid #cfe0eb;
            background: #f3f9fc; color: #1f4f69; cursor: pointer; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .aksi-menu {
            position: absolute; right: 0; top: calc(100% + 6px); z-index: 40;
            background: #fff; border: 1px solid #d7deea; border-radius: 10px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .18);
            padding: 10px; display: none; grid-template-columns: repeat(4, 42px); gap: 8px; width: max-content; max-width: min(260px, calc(100vw - 32px));
            overflow: visible;
        }
        .aksi-wrap.open .aksi-menu { display: grid; }
        .icon-action-btn {
            width: 42px; height: 42px; min-height: 42px; border-radius: 10px; border: 1px solid #d7e4ed;
            background: #f7fbfd; color: #1f4f69; text-decoration: none; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; position: relative; padding: 0;
        }
        .icon-action-btn::after {
            content: attr(data-label); position: absolute; left: 50%; transform: translateX(-50%);
            bottom: calc(100% + 6px); background: #0f2f41; color: #fff; border-radius: 6px;
            padding: 4px 8px; font-size: 11px; white-space: nowrap; opacity: 0; pointer-events: none;
        }
        .icon-action-btn:hover::after { opacity: 1; }
        .icon-action-btn svg { width: 18px; height: 18px; display: block; flex: 0 0 18px; }
        .service-modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 80;
            background: rgba(8, 25, 38, .48); backdrop-filter: blur(2px);
        }
        .service-modal-panel {
            position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%);
            width: min(1240px, 97vw); height: min(92vh, 900px);
            background: #fff; border: 1px solid #cfe0ec; border-radius: 14px;
            box-shadow: 0 26px 42px rgba(11, 42, 61, .28);
            display: flex; flex-direction: column; overflow: hidden;
        }
        .service-modal-head {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 12px; border-bottom: 1px solid #dce9f1;
            background: linear-gradient(90deg, #eef7fb 0%, #f5fbf8 100%);
        }
        .service-modal-body { flex: 1; overflow: hidden; background: #fbfeff; }
    </style>
    <h3 style="margin-top:0;">Daftar Pasien Rawat Inap</h3>
    <table>
        <thead>
        <tr>
            <th>No Rawat</th><th>Masuk</th><th>Pasien</th><th>Bangsal/Kamar</th><th>Dokter</th><th>Penjamin</th><th>Status Bayar</th><th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td>
                    <?= htmlspecialchars((string)($r['no_rawat_display'] ?? $r['no_rawat']), ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($r['is_gabung'])): ?><br><span class="pill">Kamar Gabung</span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string)$r['tgl_masuk'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$r['jam_masuk'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?php if (!empty($r['is_gabung'])): ?>
                        <div><strong>Ibu:</strong> <?= htmlspecialchars((string)($r['nm_ibu'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> <span class="muted">(<?= htmlspecialchars((string)($r['no_rawat_ibu'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>)</span></div>
                        <div><strong>Bayi:</strong> <?= htmlspecialchars((string)($r['nm_bayi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> <span class="muted">(<?= htmlspecialchars((string)($r['no_rawat_bayi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>)</span></div>
                    <?php else: ?>
                        <?= htmlspecialchars((string)($r['nm_pasien_display'] ?? $r['nm_pasien']), ENT_QUOTES, 'UTF-8') ?><br>
                        <span class="muted"><?= htmlspecialchars((string)$r['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string)($r['nm_bangsal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br><span class="muted"><?= htmlspecialchars((string)($r['kd_kamar'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string)($r['kelas'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= htmlspecialchars((string)($r['nm_dokter'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($r['png_jawab'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($r['status_bayar'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <div class="aksi-wrap">
                        <button type="button" class="aksi-trigger" title="Aksi">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1.6"></circle><circle cx="12" cy="12" r="1.6"></circle><circle cx="12" cy="19" r="1.6"></circle></svg>
                        </button>
                        <div class="aksi-menu">
                            <button type="button" class="icon-action-btn open-service-modal" data-label="Tindakan" data-service="tindakan" data-url="?page=rawatinap&embed=1&service_only=1&status=<?= urlencode((string)($status ?? 'aktif')) ?>&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=tindakan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20l6-6"></path><path d="M14 4l6 6"></path><path d="M8 8l8 8"></path></svg></button>
                            <button type="button" class="icon-action-btn open-service-modal" data-label="Resep Dokter" data-service="resep" data-url="?page=rawatinap&embed=1&service_only=1&status=<?= urlencode((string)($status ?? 'aktif')) ?>&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=resep"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4h12v16H6z"></path><path d="M9 9h6"></path><path d="M9 13h6"></path></svg></button>
                            <button type="button" class="icon-action-btn open-service-modal" data-label="Pemeriksaan" data-service="pemeriksaan" data-url="?page=rawatinap&embed=1&service_only=1&status=<?= urlencode((string)($status ?? 'aktif')) ?>&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=pemeriksaan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"></path><path d="M12 4v16"></path></svg></button>
                            <button type="button" class="icon-action-btn open-service-modal" data-label="Permintaan Lab" data-service="lab" data-url="?page=rawatinap&embed=1&service_only=1&status=<?= urlencode((string)($status ?? 'aktif')) ?>&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=lab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3v5l-4 7a4 4 0 0 0 3.5 6h7A4 4 0 0 0 19 15l-4-7V3"></path></svg></button>
                            <button type="button" class="icon-action-btn open-service-modal" data-label="Permintaan Radiologi" data-service="radiologi" data-url="?page=rawatinap&embed=1&service_only=1&status=<?= urlencode((string)($status ?? 'aktif')) ?>&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=radiologi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"></rect><circle cx="12" cy="12" r="3"></circle></svg></button>
                            <button type="button" class="icon-action-btn open-service-modal" data-label="Tindakan Operasi" data-service="operasi" data-url="?page=rawatinap&embed=1&service_only=1&status=<?= urlencode((string)($status ?? 'aktif')) ?>&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=operasi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21l6-6"></path><path d="M15 3l6 6"></path><path d="M8 8l8 8"></path><path d="M14 10l2-2"></path></svg></button>
                            <a class="icon-action-btn" data-label="Bridging BPJS" href="?page=bridging-bpjs&auto=1&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&no_rawat=<?= urlencode((string)$r['no_rawat']) ?>" onclick="event.stopPropagation();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16v10H4z"></path><path d="M8 11h8"></path><path d="M8 15h5"></path></svg></a>
                            <a class="icon-action-btn" data-label="Buat SEP" href="?page=vclaim-bpjs&no_rawat=<?= urlencode((string)$r['no_rawat']) ?>" onclick="event.stopPropagation();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"></path><path d="M5 12h14"></path><rect x="4" y="4" width="16" height="16" rx="2"></rect></svg></a>
                            <button type="button" class="icon-action-btn open-service-modal" data-label="Kamar Inap" data-service="kamarinap" data-url="?page=rawatinap&embed=1&service_only=1&status=<?= urlencode((string)($status ?? 'aktif')) ?>&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=kamarinap"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="10" width="18" height="7"></rect><path d="M6 10V7h4v3"></path></svg></button>
                            <?php if (!empty($r['is_gabung']) && !empty($r['no_rawat_bayi'])): ?>
                                <button type="button" class="icon-action-btn open-service-modal" data-label="Aksi Bayi" data-service="bayi" data-url="?page=rawatinap&embed=1&service_only=1&status=<?= urlencode((string)($status ?? 'aktif')) ?>&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&detail=<?= urlencode((string)$r['no_rawat_bayi']) ?>&open=1&focus=tindakan">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3"></circle><path d="M7 20a5 5 0 0 1 10 0"></path></svg>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="icon-action-btn open-service-modal" data-label="Berkas RM" data-service="berkasrm" data-url="?page=berkas-rm&embed=1&no_rawat=<?= urlencode((string)$r['no_rawat']) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3h6l5 5v13H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path><path d="M14 3v6h6"></path><path d="M10 13h6"></path><path d="M10 17h4"></path></svg></button>
                            <button type="button" class="icon-action-btn open-service-modal" data-label="Billing" data-service="billing" data-url="?page=billing-ralan&mode=ranap&embed=1&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$r['no_rawat']) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4h12v16l-3-2-3 2-3-2-3 2z"></path><path d="M9 9h6"></path><path d="M9 13h6"></path></svg></button>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($detailNoRawat) && !empty($openModal)): ?>
    <?php $closeUrl = '?page=rawatinap&status=' . urlencode((string)($status ?? 'aktif')) . '&from=' . urlencode((string)$from) . '&to=' . urlencode((string)$to) . '&q=' . urlencode((string)$q); ?>
    <?php if (!$embedMode): ?>
        <a style="position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:60;" href="<?= htmlspecialchars($closeUrl, ENT_QUOTES, 'UTF-8') ?>"></a>
        <div style="position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);width:min(1180px,96vw);max-height:92vh;overflow:auto;background:#fff;border:1px solid #d7deea;border-radius:12px;padding:16px;box-shadow:0 24px 40px rgba(15,23,42,.25);z-index:61;">
    <?php else: ?>
        <div class="card" style="margin:0;">
    <?php endif; ?>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;">
            <h3 style="margin:0;">Detail Rawat Inap: <?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?></h3>
            <?php if (!$embedMode): ?><a href="<?= htmlspecialchars($closeUrl, ENT_QUOTES, 'UTF-8') ?>" style="border:1px solid #d7deea;border-radius:8px;padding:8px 10px;text-decoration:none;color:#1f2937;background:#fff;">Tutup</a><?php endif; ?>
        </div>
        <?php if (!empty($detailError)): ?>
            <p class="muted">Gagal mengambil detail: <?= htmlspecialchars((string)$detailError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif (!empty($detail)): ?>
            <div class="row" style="margin-bottom:10px;">
                <span class="pill">Pasien: <?= htmlspecialchars((string)$detail['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">No RM: <?= htmlspecialchars((string)$detail['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Dokter: <?= htmlspecialchars((string)($detail['nm_dokter'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Penjamin: <?= htmlspecialchars((string)($detail['png_jawab'] ?? $detail['kd_pj'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <?php if (!$serviceOnly || $focus === 'kamarinap' || $focus === ''): ?>
            <h4 id="section-kamarinap" style="margin:14px 0 8px;">Riwayat Kamar Inap</h4>
            <table><thead><tr><th>Bangsal</th><th>Kamar</th><th>Kelas</th><th>Masuk</th><th>Keluar</th><th>Status Pulang</th></tr></thead><tbody>
                <?php foreach ($riwayatKamar as $rk): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($rk['nm_bangsal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($rk['kd_kamar'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($rk['kelas'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$rk['tgl_masuk'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$rk['jam_masuk'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$rk['tgl_keluar'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$rk['jam_keluar'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$rk['stts_pulang'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>

            <?php if (!$serviceOnly || $focus === 'tindakan' || $focus === ''): ?>
            <h4 id="section-tindakan" style="margin:14px 0 8px;">Tindakan Ranap (Dokter/Perawat)</h4>
            <form method="post" class="row" style="margin-bottom:8px;">
                <input type="hidden" name="action" value="input_tindakan">
                <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                <div class="field">
                    <label>Cari Tindakan (kode/nama)</label>
                    <input type="text" id="ranap_tindakan_search" placeholder="contoh: pasang infus">
                </div>
                <div class="field">
                    <label>Tindakan</label>
                    <select id="ranap_tindakan_select" name="kd_jenis_prw" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:320px;" required>
                        <option value="">- pilih tindakan -</option>
                        <?php foreach (($tindakanOptions ?? []) as $op): ?>
                            <option value="<?= htmlspecialchars((string)$op['kd_jenis_prw'], ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars((string)$op['kd_jenis_prw'] . ' ' . (string)$op['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)$op['kd_jenis_prw'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string)$op['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Simpan Tindakan</button>
            </form>
            <table><thead><tr><th>Tanggal</th><th>Jam</th><th>Jenis</th><th>Tindakan</th><th class="num">Biaya</th><th>Aksi</th></tr></thead><tbody>
                <?php foreach ($detailDr as $d): ?><tr><td><?= htmlspecialchars((string)$d['tgl_perawatan'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$d['jam_rawat'], ENT_QUOTES, 'UTF-8') ?></td><td>Dokter</td><td><?= htmlspecialchars((string)$d['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?></td><td class="num"><?= number_format((float)$d['biaya_rawat'], 0, ',', '.') ?></td><td><form method="post" onsubmit="return confirm('Hapus tindakan ini?');"><input type="hidden" name="action" value="hapus_tindakan"><input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="kd_jenis_prw" value="<?= htmlspecialchars((string)$d['kd_jenis_prw'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="tgl_perawatan" value="<?= htmlspecialchars((string)$d['tgl_perawatan'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="jam_rawat" value="<?= htmlspecialchars((string)$d['jam_rawat'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit" style="background:#b91c1c;">Hapus</button></form></td></tr><?php endforeach; ?>
                <?php foreach ($detailPr as $d): ?><tr><td><?= htmlspecialchars((string)$d['tgl_perawatan'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$d['jam_rawat'], ENT_QUOTES, 'UTF-8') ?></td><td>Perawat</td><td><?= htmlspecialchars((string)$d['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?></td><td class="num"><?= number_format((float)$d['biaya_rawat'], 0, ',', '.') ?></td></tr><?php endforeach; ?>
                <?php foreach ($detailDrpr as $d): ?><tr><td><?= htmlspecialchars((string)$d['tgl_perawatan'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$d['jam_rawat'], ENT_QUOTES, 'UTF-8') ?></td><td>Dokter+Perawat</td><td><?= htmlspecialchars((string)$d['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?></td><td class="num"><?= number_format((float)$d['biaya_rawat'], 0, ',', '.') ?></td></tr><?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>

            <?php if (!$serviceOnly || $focus === 'operasi' || $focus === ''): ?>
            <h4 id="section-operasi" style="margin:14px 0 8px;">Tindakan Operasi</h4>
            <form method="post" class="row" style="margin-bottom:8px;">
                <input type="hidden" name="action" value="input_operasi">
                <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                <div class="field">
                    <label>Cari Paket Operasi (kode/nama)</label>
                    <input type="text" id="ranap_operasi_search" placeholder="contoh: laparatomi">
                </div>
                <div class="field">
                    <label>Paket Operasi</label>
                    <select id="ranap_operasi_select" name="kode_paket" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:320px;" required>
                        <option value="">- pilih paket operasi -</option>
                        <?php foreach (($operasiOptions ?? []) as $op): ?>
                            <option value="<?= htmlspecialchars((string)$op['kode_paket'], ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars((string)$op['kode_paket'] . ' ' . (string)$op['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)$op['kode_paket'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string)$op['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label>Jenis Anastesi</label><input type="text" name="jenis_anasthesi" value="-" placeholder="contoh: Spinal / General"></div>
                <div class="field">
                    <label>Kategori Operasi</label>
                    <select name="kategori_operasi" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                        <option value="-">-</option>
                        <option value="Khusus">Khusus</option>
                        <option value="Besar">Besar</option>
                        <option value="Sedang">Sedang</option>
                        <option value="Kecil">Kecil</option>
                        <option value="Elektive">Elektive</option>
                        <option value="Emergency">Emergency</option>
                    </select>
                </div>
                <button type="submit">Simpan Operasi</button>
            </form>
            <table><thead><tr><th>Tanggal Operasi</th><th>Kode</th><th>Paket</th><th class="num">Biaya</th><th>Aksi</th></tr></thead><tbody>
                <?php foreach (($detailOperasi ?? []) as $op): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$op['tgl_operasi'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$op['kode_paket'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$op['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="num"><?= number_format((float)($op['total_biaya'] ?? 0), 0, ',', '.') ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Hapus tindakan operasi ini?');">
                                <input type="hidden" name="action" value="hapus_operasi">
                                <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="kode_paket" value="<?= htmlspecialchars((string)$op['kode_paket'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="tgl_operasi" value="<?= htmlspecialchars((string)$op['tgl_operasi'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" style="background:#b91c1c;">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>

            <?php if (!$serviceOnly || $focus === 'pemeriksaan' || $focus === ''): ?>
            <h4 id="section-pemeriksaan" style="margin:14px 0 8px;">Hasil Pemeriksaan</h4>
            <form method="post" class="row" style="margin-bottom:8px;" id="pemeriksaan_ranap_form">
                <input type="hidden" name="action" value="input_pemeriksaan" id="pemeriksaan_ranap_action">
                <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="old_tgl_perawatan" value="" id="pemeriksaan_ranap_old_tgl">
                <input type="hidden" name="old_jam_rawat" value="" id="pemeriksaan_ranap_old_jam">
                <div style="flex-basis:100%;display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <strong id="pemeriksaan_ranap_mode">Mode input baru</strong>
                    <button type="button" id="pemeriksaan_ranap_cancel" style="display:none;background:#64748b;">Batal Edit</button>
                </div>
                <div class="field"><label>Keluhan</label><textarea name="keluhan" id="pemeriksaan_ranap_keluhan" rows="2" required></textarea></div>
                <div class="field"><label>Pemeriksaan</label><textarea name="pemeriksaan" id="pemeriksaan_ranap_pemeriksaan" rows="2" required></textarea></div>
                <div class="field"><label>Tensi</label><input type="text" name="tensi" id="pemeriksaan_ranap_tensi" value="-"></div>
                <div class="field"><label>Suhu Tubuh</label><input type="text" name="suhu_tubuh" id="pemeriksaan_ranap_suhu_tubuh"></div>
                <div class="field"><label>Nadi</label><input type="text" name="nadi" id="pemeriksaan_ranap_nadi"></div>
                <div class="field"><label>Respirasi</label><input type="text" name="respirasi" id="pemeriksaan_ranap_respirasi"></div>
                <div class="field"><label>Tinggi Badan</label><input type="text" name="tinggi" id="pemeriksaan_ranap_tinggi"></div>
                <div class="field"><label>Berat Badan</label><input type="text" name="berat" id="pemeriksaan_ranap_berat"></div>
                <div class="field"><label>SpO2</label><input type="text" name="spo2" id="pemeriksaan_ranap_spo2"></div>
                <div class="field"><label>GCS</label><input type="text" name="gcs" id="pemeriksaan_ranap_gcs"></div>
                <div class="field">
                    <label>Kesadaran</label>
                    <select name="kesadaran" id="pemeriksaan_ranap_kesadaran" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                        <?php foreach (['Compos Mentis','Somnolence','Sopor','Coma','Alert','Confusion','Voice','Pain','Unresponsive','Apatis','Delirium'] as $kes): ?>
                            <option value="<?= htmlspecialchars($kes, ENT_QUOTES, 'UTF-8') ?>" <?= $kes === 'Compos Mentis' ? 'selected' : '' ?>><?= htmlspecialchars($kes, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label>Alergi</label><input type="text" name="alergi" id="pemeriksaan_ranap_alergi"></div>
                <div class="field"><label>Penilaian</label><textarea name="penilaian" id="pemeriksaan_ranap_penilaian" rows="2"></textarea></div>
                <div class="field"><label>RTL</label><textarea name="rtl" id="pemeriksaan_ranap_rtl" rows="2"></textarea></div>
                <div class="field"><label>Instruksi</label><textarea name="instruksi" id="pemeriksaan_ranap_instruksi" rows="2"></textarea></div>
                <div class="field"><label>Evaluasi</label><textarea name="evaluasi" id="pemeriksaan_ranap_evaluasi" rows="2"></textarea></div>
                <button type="submit" id="pemeriksaan_ranap_submit">Simpan Pemeriksaan</button>
            </form>
            <table><thead><tr><th>Tanggal</th><th>Jam</th><th>Keluhan</th><th>Pemeriksaan</th><th>TTV</th><th>Penilaian/RTL</th><th>Aksi</th></tr></thead><tbody>
                <?php foreach ($detailPemeriksaan as $pm): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$pm['tgl_perawatan'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$pm['jam_rawat'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$pm['keluhan'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$pm['pemeriksaan'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($pm['tensi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string)($pm['suhu_tubuh'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string)($pm['spo2'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$pm['penilaian'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string)$pm['rtl'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button type="button" class="edit-pemeriksaan-ranap" data-row='<?= htmlspecialchars(json_encode($pm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, "UTF-8") ?>'>Edit</button>
                            <form method="post" onsubmit="return confirm('Hapus pemeriksaan ini?');"><input type="hidden" name="action" value="hapus_pemeriksaan"><input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="tgl_perawatan" value="<?= htmlspecialchars((string)$pm['tgl_perawatan'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="jam_rawat" value="<?= htmlspecialchars((string)$pm['jam_rawat'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit" style="background:#b91c1c;">Hapus</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>

            <?php if (!$serviceOnly || $focus === 'resep' || $focus === ''): ?>
            <?php
            $resepObatUrl = '?page=rawatinap&ajax=search_obat';
            include __DIR__ . '/partials/rawat_resep.php';
            ?>
            <?php endif; ?>

            <?php if (!$serviceOnly || $focus === 'lab' || $focus === ''): ?>
            <h4 id="section-lab" style="margin:14px 0 8px;">Permintaan Lab</h4>
            <form method="post" class="row" style="margin-bottom:8px;">
                <input type="hidden" name="action" value="input_lab">
                <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                <div class="field">
                    <label>Cari Item Lab (kode/nama)</label>
                    <input type="text" id="ranap_lab_search" placeholder="contoh: hematologi">
                </div>
                <div class="field">
                    <label>Item Lab</label>
                    <select id="ranap_lab_select" name="lab_items[]" multiple size="5" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:320px;">
                        <?php foreach (($labOptions ?? []) as $op): ?>
                            <option value="<?= htmlspecialchars((string)$op['kd_jenis_prw'], ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars((string)$op['kd_jenis_prw'] . ' ' . (string)$op['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$op['kd_jenis_prw'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string)$op['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Simpan Permintaan Lab</button>
            </form>
            <table><thead><tr><th>No Order</th><th>Tanggal</th><th>Jam</th><th>Item</th><th>Aksi</th></tr></thead><tbody>
                <?php foreach ($detailLab as $lb): ?><tr><td><?= htmlspecialchars((string)$lb['noorder'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$lb['tgl_permintaan'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$lb['jam_permintaan'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$lb['item_lab'], ENT_QUOTES, 'UTF-8') ?></td><td><form method="post" onsubmit="return confirm('Hapus permintaan lab ini?');"><input type="hidden" name="action" value="hapus_lab"><input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="noorder" value="<?= htmlspecialchars((string)$lb['noorder'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit" style="background:#b91c1c;">Hapus</button></form></td></tr><?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>

            <?php if (!$serviceOnly || $focus === 'radiologi' || $focus === ''): ?>
            <h4 id="section-radiologi" style="margin:14px 0 8px;">Permintaan Radiologi</h4>
            <form method="post" class="row" style="margin-bottom:8px;">
                <input type="hidden" name="action" value="input_radiologi">
                <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                <div class="field">
                    <label>Cari Item Radiologi (kode/nama)</label>
                    <input type="text" id="ranap_rad_search" placeholder="contoh: thorax">
                </div>
                <div class="field">
                    <label>Item Radiologi</label>
                    <select id="ranap_rad_select" name="radiologi_items[]" multiple size="5" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:320px;">
                        <?php foreach (($radOptions ?? []) as $op): ?>
                            <option value="<?= htmlspecialchars((string)$op['kd_jenis_prw'], ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars((string)$op['kd_jenis_prw'] . ' ' . (string)$op['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$op['kd_jenis_prw'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string)$op['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Simpan Permintaan Radiologi</button>
            </form>
            <table><thead><tr><th>No Order</th><th>Tanggal</th><th>Jam</th><th>Item</th><th>Aksi</th></tr></thead><tbody>
                <?php foreach ($detailRad as $rd): ?><tr><td><?= htmlspecialchars((string)$rd['noorder'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$rd['tgl_permintaan'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$rd['jam_permintaan'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$rd['item_radiologi'], ENT_QUOTES, 'UTF-8') ?></td><td><form method="post" onsubmit="return confirm('Hapus permintaan radiologi ini?');"><input type="hidden" name="action" value="hapus_radiologi"><input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="noorder" value="<?= htmlspecialchars((string)$rd['noorder'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit" style="background:#b91c1c;">Hapus</button></form></td></tr><?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>

            <?php if (!$serviceOnly): ?>
            <h4 id="section-billing" style="margin:14px 0 8px;">Billing</h4>
            <table><thead><tr><th>Tanggal</th><th>No</th><th>Perawatan</th><th>Status</th><th class="num">Total</th></tr></thead><tbody>
                <?php foreach ($detailBilling as $bl): ?><tr><td><?= htmlspecialchars((string)$bl['tgl_byr'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$bl['no'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$bl['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$bl['status'], ENT_QUOTES, 'UTF-8') ?></td><td class="num"><?= number_format((float)$bl['totalbiaya'], 0, ',', '.') ?></td></tr><?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!$embedMode): ?>
<div id="serviceModalOverlay" class="service-modal-overlay">
    <div class="service-modal-panel">
        <div class="service-modal-head">
            <strong id="serviceModalTitle">Layanan</strong>
            <button type="button" id="serviceModalClose" style="background:#fff;color:#334155;border:1px solid #cfe0ec;border-radius:8px;padding:8px 12px;">Tutup</button>
        </div>
        <div id="serviceModalBody" class="service-modal-body"></div>
    </div>
</div>
<?php endif; ?>

<script>
    (function () {
        function normalize(str) {
            return (str || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').replace(/\s+/g, ' ').trim();
        }
        function bindSearchInputToSelect(input, select) {
            if (!input || !select) return;
            var original = Array.prototype.map.call(select.options, function (opt) {
                return {
                    value: opt.value,
                    text: opt.text,
                    search: opt.getAttribute('data-search') || (opt.value + ' ' + opt.text)
                };
            });
            function render(query) {
                var q = normalize(query);
                var keep = {};
                Array.prototype.forEach.call(select.selectedOptions || [], function (opt) { keep[opt.value] = true; });
                var list = q.length < 1 ? original : original.filter(function (it) {
                    return it.value === '' || normalize(it.search).indexOf(q) !== -1;
                });
                select.innerHTML = '';
                list.forEach(function (it) {
                    var opt = document.createElement('option');
                    opt.value = it.value;
                    opt.textContent = it.text;
                    if (it.search) opt.setAttribute('data-search', it.search);
                    if (keep[it.value]) opt.selected = true;
                    select.appendChild(opt);
                });
            }
            input.addEventListener('input', function () {
                render(input.value || '');
            });
        }
        bindSearchInputToSelect(document.getElementById('ranap_tindakan_search'), document.getElementById('ranap_tindakan_select'));
        bindSearchInputToSelect(document.getElementById('ranap_operasi_search'), document.getElementById('ranap_operasi_select'));
        bindSearchInputToSelect(document.getElementById('ranap_lab_search'), document.getElementById('ranap_lab_select'));
        bindSearchInputToSelect(document.getElementById('ranap_rad_search'), document.getElementById('ranap_rad_select'));

        function bindRanapPemeriksaanEditor() {
            var form = document.getElementById('pemeriksaan_ranap_form');
            if (!form) return;
            var actionInput = document.getElementById('pemeriksaan_ranap_action');
            var oldTgl = document.getElementById('pemeriksaan_ranap_old_tgl');
            var oldJam = document.getElementById('pemeriksaan_ranap_old_jam');
            var modeLabel = document.getElementById('pemeriksaan_ranap_mode');
            var submitBtn = document.getElementById('pemeriksaan_ranap_submit');
            var cancelBtn = document.getElementById('pemeriksaan_ranap_cancel');
            function setValue(id, value) {
                var el = document.getElementById(id);
                if (el) el.value = value || '';
            }
            function resetForm() {
                form.reset();
                actionInput.value = 'input_pemeriksaan';
                oldTgl.value = '';
                oldJam.value = '';
                setValue('pemeriksaan_ranap_tensi', '-');
                setValue('pemeriksaan_ranap_kesadaran', 'Compos Mentis');
                modeLabel.textContent = 'Mode input baru';
                submitBtn.textContent = 'Simpan Pemeriksaan';
                cancelBtn.style.display = 'none';
            }
            document.querySelectorAll('.edit-pemeriksaan-ranap').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var row = {};
                    try { row = JSON.parse(btn.getAttribute('data-row') || '{}'); } catch (e) {}
                    actionInput.value = 'update_pemeriksaan';
                    oldTgl.value = row.tgl_perawatan || '';
                    oldJam.value = row.jam_rawat || '';
                    setValue('pemeriksaan_ranap_keluhan', row.keluhan || '');
                    setValue('pemeriksaan_ranap_pemeriksaan', row.pemeriksaan || '');
                    setValue('pemeriksaan_ranap_tensi', row.tensi || '-');
                    setValue('pemeriksaan_ranap_suhu_tubuh', row.suhu_tubuh || '');
                    setValue('pemeriksaan_ranap_nadi', row.nadi || '');
                    setValue('pemeriksaan_ranap_respirasi', row.respirasi || '');
                    setValue('pemeriksaan_ranap_tinggi', row.tinggi || '');
                    setValue('pemeriksaan_ranap_berat', row.berat || '');
                    setValue('pemeriksaan_ranap_spo2', row.spo2 || '');
                    setValue('pemeriksaan_ranap_gcs', row.gcs || '');
                    setValue('pemeriksaan_ranap_kesadaran', row.kesadaran || 'Compos Mentis');
                    setValue('pemeriksaan_ranap_alergi', row.alergi || '');
                    setValue('pemeriksaan_ranap_penilaian', row.penilaian || '');
                    setValue('pemeriksaan_ranap_rtl', row.rtl || '');
                    setValue('pemeriksaan_ranap_instruksi', row.instruksi || '');
                    setValue('pemeriksaan_ranap_evaluasi', row.evaluasi || '');
                    modeLabel.textContent = 'Mode edit: ' + (row.tgl_perawatan || '') + ' ' + (row.jam_rawat || '');
                    submitBtn.textContent = 'Simpan Update';
                    cancelBtn.style.display = 'inline-block';
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
            cancelBtn.addEventListener('click', resetForm);
            resetForm();
        }
        bindRanapPemeriksaanEditor();
        var actionWraps = document.querySelectorAll('.aksi-wrap');
        actionWraps.forEach(function (wrap) {
            var trigger = wrap.querySelector('.aksi-trigger');
            if (!trigger) return;
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                actionWraps.forEach(function (w) { if (w !== wrap) w.classList.remove('open'); });
                wrap.classList.toggle('open');
            });
        });
        document.addEventListener('click', function () {
            actionWraps.forEach(function (w) { w.classList.remove('open'); });
        });
        document.querySelectorAll('.icon-action-btn.not-ready').forEach(function (btn) {
            btn.addEventListener('click', function () { alert('Fitur ini belum aktif di modul web.'); });
        });

        var serviceTitles = { tindakan:'Tindakan', operasi:'Tindakan Operasi', resep:'Resep Dokter', pemeriksaan:'Pemeriksaan', lab:'Permintaan Lab', radiologi:'Permintaan Radiologi', kamarinap:'Kamar Inap', bayi:'Aksi Bayi', berkasrm:'Berkas Rekam Medik', billing:'Billing' };
        var serviceOverlay = document.getElementById('serviceModalOverlay');
        var serviceBody = document.getElementById('serviceModalBody');
        var serviceTitle = document.getElementById('serviceModalTitle');
        var serviceClose = document.getElementById('serviceModalClose');
        function closeServiceModal() {
            if (!serviceOverlay) return;
            serviceOverlay.style.display = 'none';
            if (serviceBody) serviceBody.innerHTML = '';
        }
        if (serviceClose) serviceClose.addEventListener('click', closeServiceModal);
        if (serviceOverlay) serviceOverlay.addEventListener('click', function (e) { if (e.target === serviceOverlay) closeServiceModal(); });
        document.querySelectorAll('.open-service-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!serviceOverlay || !serviceBody) return;
                var service = btn.getAttribute('data-service') || '';
                var url = btn.getAttribute('data-url') || '';
                serviceTitle.textContent = serviceTitles[service] || 'Layanan';
                serviceBody.innerHTML = '<iframe src="' + url + '" style="width:100%;height:100%;border:0;" loading="lazy"></iframe>';
                serviceOverlay.style.display = 'block';
            });
        });
    })();
</script>

