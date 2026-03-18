<?php $embedMode = trim((string)($_GET['embed'] ?? '')) === '1'; ?>
<?php if (!$embedMode): ?>
<div class="card">
    <h2 style="margin-top:0;">Modul Rawat Jalan</h2>
    <?php if (!empty($msgDetail)): ?>
        <p class="pill" style="border-color:<?= ($msg === 'ok') ? '#cde6de' : '#f4b4b4' ?>;background:<?= ($msg === 'ok') ? '#edf8f4' : '#ffecec' ?>;color:<?= ($msg === 'ok') ? '#0f5132' : '#8b1b1b' ?>;">
            <?= htmlspecialchars((string)$msgDetail, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="muted">Gagal mengambil data: <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="get" class="row" style="margin:10px 0 12px;">
        <input type="hidden" name="page" value="rawatjalan">
        <div class="field">
            <label>Dari Tanggal</label>
            <input type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>Sampai Tanggal</label>
            <input type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>Cari (No Rawat/RM/Nama)</label>
            <input type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="contoh: nama pasien">
        </div>
        <div class="field">
            <label>Poli</label>
            <select name="kd_poli" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                <option value="">Semua Poli</option>
                <?php foreach ($poliList as $p): ?>
                    <option value="<?= htmlspecialchars((string)$p['kd_poli'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string)$kdPoli === (string)$p['kd_poli']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$p['nm_poli'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Filter</button>
    </form>
</div>

<div class="card" style="margin-top:12px;">
    <style>
        .aksi-wrap { position: relative; display: inline-block; }
        .aksi-trigger {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid #cfe0eb;
            background: #f3f9fc; color: #1f4f69; cursor: pointer; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .aksi-trigger:hover { background: #e8f3f9; }
        .aksi-menu {
            position: absolute; right: 0; top: calc(100% + 6px); z-index: 40;
            background: #fff; border: 1px solid #d7deea; border-radius: 10px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .18);
            padding: 8px; display: none; grid-template-columns: repeat(4, 36px); gap: 8px;
            width: max-content;
        }
        .aksi-wrap.open .aksi-menu { display: grid; }
        .icon-action-btn {
            width: 36px; height: 36px; border-radius: 8px; border: 1px solid #d7e4ed;
            background: #f7fbfd; color: #1f4f69; text-decoration: none; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; position: relative;
            padding: 0;
        }
        .icon-action-btn:hover { background: #ebf4fa; }
        .icon-action-btn[disabled] { opacity: .55; cursor: not-allowed; }
        .icon-action-btn::after {
            content: attr(data-label);
            position: absolute; left: 50%; transform: translateX(-50%);
            bottom: calc(100% + 6px);
            background: #0f2f41; color: #fff; border-radius: 6px;
            padding: 4px 8px; font-size: 11px; white-space: nowrap;
            opacity: 0; pointer-events: none; transition: opacity .12s ease;
        }
        .icon-action-btn:hover::after, .icon-action-btn:focus::after { opacity: 1; }
        .icon-action-btn svg { width: 18px; height: 18px; display: block; }
        .service-modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 80;
            background: rgba(8, 25, 38, .48);
            backdrop-filter: blur(2px);
        }
        .service-modal-panel {
            position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%);
            width: min(1240px, 97vw); height: min(92vh, 900px);
            background: #fff; border: 1px solid #cfe0ec; border-radius: 14px;
            box-shadow: 0 26px 42px rgba(11, 42, 61, .28);
            display: flex; flex-direction: column; overflow: hidden;
            animation: modalIn .16s ease-out;
        }
        .service-modal-head {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 12px; border-bottom: 1px solid #dce9f1;
            background: linear-gradient(90deg, #eef7fb 0%, #f5fbf8 100%);
        }
        .service-modal-title { color: #16455f; font-size: 15px; letter-spacing: .2px; }
        .service-modal-close {
            background: #fff; color: #334155; border: 1px solid #cfe0ec;
            border-radius: 8px; padding: 8px 12px;
        }
        .service-modal-body { flex: 1; overflow: hidden; background: #fbfeff; }
        .service-modal-placeholder {
            margin: 14px; border: 1px dashed #bfd7e6; border-radius: 10px;
            background: #f2f9fd; color: #2a4c61; padding: 16px;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: translate(-50%, calc(-50% + 12px)); }
            to { opacity: 1; transform: translate(-50%, -50%); }
        }
    </style>
    <h3 style="margin-top:0;">Daftar Kunjungan Rawat Jalan</h3>
    <table>
        <thead>
            <tr>
                <th>No Rawat</th>
                <th>Tanggal</th>
                <th>Pasien</th>
                <th>Poli</th>
                <th>Dokter</th>
                <th>Penjamin</th>
                <th>Status Bayar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$r['no_rawat'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['tgl_registrasi'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$r['jam_reg'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['nm_poli'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['nm_dokter'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($r['png_jawab'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($r['status_bayar'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="aksi-wrap">
                            <button type="button" class="aksi-trigger" aria-label="Aksi pasien" title="Aksi">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="5" r="1.6"></circle><circle cx="12" cy="12" r="1.6"></circle><circle cx="12" cy="19" r="1.6"></circle>
                                </svg>
                            </button>
                            <div class="aksi-menu">
                                <button type="button" class="icon-action-btn open-service-modal" data-label="Tindakan" data-service="tindakan" data-url="?page=rawatjalan&embed=1&service_only=1&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&kd_poli=<?= urlencode((string)$kdPoli) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=tindakan">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20l6-6"></path><path d="M14 4l6 6"></path><path d="M8 8l8 8"></path></svg>
                                </button>
                                <button type="button" class="icon-action-btn open-service-modal" data-label="Resep Dokter" data-service="resep" data-url="?page=rawatjalan&embed=1&service_only=1&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&kd_poli=<?= urlencode((string)$kdPoli) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=resep">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4h12v16H6z"></path><path d="M9 9h6"></path><path d="M9 13h6"></path></svg>
                                </button>
                                <button type="button" class="icon-action-btn open-service-modal" data-label="Pemeriksaan" data-service="pemeriksaan" data-url="?page=rawatjalan&embed=1&service_only=1&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&kd_poli=<?= urlencode((string)$kdPoli) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=pemeriksaan">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"></path><path d="M12 4v16"></path></svg>
                                </button>
                                <button type="button" class="icon-action-btn open-service-modal" data-label="Permintaan Lab" data-service="lab" data-url="?page=rawatjalan&embed=1&service_only=1&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&kd_poli=<?= urlencode((string)$kdPoli) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=lab">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3v5l-4 7a4 4 0 0 0 3.5 6h7A4 4 0 0 0 19 15l-4-7V3"></path></svg>
                                </button>
                                <button type="button" class="icon-action-btn open-service-modal" data-label="Permintaan Radiologi" data-service="radiologi" data-url="?page=rawatjalan&embed=1&service_only=1&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&kd_poli=<?= urlencode((string)$kdPoli) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=radiologi">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"></rect><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                                <button type="button" class="icon-action-btn open-service-modal" data-label="Tindakan Operasi" data-service="operasi" data-url="?page=rawatjalan&embed=1&service_only=1&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&kd_poli=<?= urlencode((string)$kdPoli) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=operasi">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21l6-6"></path><path d="M15 3l6 6"></path><path d="M8 8l8 8"></path><path d="M14 10l2-2"></path></svg>
                                </button>
                                <a class="icon-action-btn" data-label="Bridging BPJS" href="?page=bridging-bpjs&auto=1&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&no_rawat=<?= urlencode((string)$r['no_rawat']) ?>" onclick="event.stopPropagation();">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16v10H4z"></path><path d="M8 11h8"></path><path d="M8 15h5"></path></svg>
                                </a>
                                <a class="icon-action-btn" data-label="Buat SEP" href="?page=vclaim-bpjs&no_rawat=<?= urlencode((string)$r['no_rawat']) ?>" onclick="event.stopPropagation();">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"></path><path d="M5 12h14"></path><rect x="4" y="4" width="16" height="16" rx="2"></rect></svg>
                                </a>
                                <button type="button" class="icon-action-btn open-service-modal" data-label="Kamar Inap" data-service="kamarinap" data-url="?page=rawatjalan&embed=1&service_only=1&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&kd_poli=<?= urlencode((string)$kdPoli) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1&focus=kamarinap">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="10" width="18" height="7"></rect><path d="M6 10V7h4v3"></path></svg>
                                </button>
                                <button type="button" class="icon-action-btn open-service-modal" data-label="Berkas RM" data-service="berkasrm" data-url="?page=berkas-rm&embed=1&no_rawat=<?= urlencode((string)$r['no_rawat']) ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3h6l5 5v13H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path><path d="M14 3v6h6"></path><path d="M10 13h6"></path><path d="M10 17h4"></path></svg>
                                </button>
                                <button type="button" class="icon-action-btn open-service-modal" data-label="Billing" data-service="billing" data-url="?page=billing-ralan&mode=ralan&embed=1&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$r['no_rawat']) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4h12v16l-3-2-3 2-3-2-3 2z"></path><path d="M9 9h6"></path><path d="M9 13h6"></path></svg>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div id="serviceModalOverlay" class="service-modal-overlay">
    <div id="serviceModalPanel" class="service-modal-panel">
        <div class="service-modal-head">
            <strong id="serviceModalTitle" class="service-modal-title">Layanan</strong>
            <button type="button" id="serviceModalClose" class="service-modal-close">Tutup</button>
        </div>
        <div id="serviceModalBody" class="service-modal-body"></div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($detailNoRawat) && !empty($openModal)): ?>
    <?php
    $focus = trim((string)($_GET['focus'] ?? ''));
    $serviceOnly = trim((string)($_GET['service_only'] ?? '')) === '1';
    $closeUrl = '?page=rawatjalan&from=' . urlencode((string)$from) . '&to=' . urlencode((string)$to) . '&q=' . urlencode((string)$q) . '&kd_poli=' . urlencode((string)$kdPoli);
    ?>
    <style>
        .modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); z-index: 60; }
        .modal-panel {
            position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%);
            width: min(1180px, 96vw); max-height: 92vh; overflow: auto;
            background: #fff; border: 1px solid #d7deea; border-radius: 12px; padding: 16px;
            box-shadow: 0 24px 40px rgba(15, 23, 42, .25); z-index: 61;
        }
        .modal-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 10px; }
        .modal-close {
            border: 1px solid #d7deea; border-radius: 8px; padding: 8px 10px; text-decoration: none; color: #1f2937; background: #fff;
        }
    </style>
    <?php if (!$embedMode): ?>
        <a class="modal-backdrop" href="<?= htmlspecialchars($closeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Tutup modal"></a>
        <div class="modal-panel">
    <?php else: ?>
        <div class="card" style="margin:0;">
    <?php endif; ?>
        <div class="modal-head">
            <h3 style="margin:0;">Detail Rawat Jalan: <?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?></h3>
            <?php if (!$embedMode): ?>
                <a class="modal-close" href="<?= htmlspecialchars($closeUrl, ENT_QUOTES, 'UTF-8') ?>">Tutup</a>
            <?php endif; ?>
        </div>
        <?php if (!empty($detailError)): ?>
            <p class="muted">Gagal mengambil detail: <?= htmlspecialchars((string)$detailError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif (!empty($detail)): ?>
            <div class="row" style="margin-bottom:10px;">
                <span class="pill">Pasien: <?= htmlspecialchars((string)$detail['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">No RM: <?= htmlspecialchars((string)$detail['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Poli: <?= htmlspecialchars((string)$detail['nm_poli'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Dokter: <?= htmlspecialchars((string)$detail['nm_dokter'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Penjamin: <?= htmlspecialchars((string)($detail['png_jawab'] ?? $detail['kd_pj'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php if (!$serviceOnly): ?>
            <div class="cards">
                <div class="card"><div class="muted">Total Tindakan Dokter</div><div class="badge"><?= count($detailDr) ?></div></div>
                <div class="card"><div class="muted">Total Pemeriksaan</div><div class="badge"><?= count($detailPemeriksaan) ?></div></div>
                <div class="card"><div class="muted">Total Resep</div><div class="badge"><?= count($detailResep) ?></div></div>
                <div class="card"><div class="muted">Permintaan Lab</div><div class="badge"><?= count($detailLab) ?></div></div>
                <div class="card"><div class="muted">Permintaan Radiologi</div><div class="badge"><?= count($detailRad) ?></div></div>
            </div>
            <?php endif; ?>

            <h4 style="margin:14px 0 8px;">Input Layanan Rawat Jalan</h4>
            <div class="cards">
                <?php if (!$serviceOnly || $focus === 'tindakan' || $focus === ''): ?>
                <div class="card" id="section-tindakan">
                    <div class="muted" style="margin-bottom:8px;">Input Tindakan</div>
                    <form method="post">
                        <input type="hidden" name="action" value="input_tindakan">
                        <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="field">
                            <label>Cari Tindakan (kode/nama, min. 3 huruf)</label>
                            <input type="text" id="tindakan_search" placeholder="contoh: pasang infus">
                        </div>
                        <div class="field">
                            <label>Jenis Tindakan</label>
                            <select id="tindakan_select" data-no-rawat="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>" name="kd_jenis_prw" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:280px;" required>
                                <option value="">- pilih tindakan -</option>
                            </select>
                        </div>
                        <button type="submit">Simpan Tindakan</button>
                    </form>
                    <div style="margin-top:10px;">
                        <div class="muted" style="margin-bottom:6px;">Riwayat Tindakan</div>
                        <table>
                            <thead><tr><th>Tanggal</th><th>Jam</th><th>Tindakan</th><th class="num">Biaya</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ($detailDr as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$d['tgl_perawatan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$d['jam_rawat'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$d['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="num"><?= number_format((float)$d['biaya_rawat'], 0, ',', '.') ?></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Hapus tindakan ini?');">
                                            <input type="hidden" name="action" value="hapus_tindakan">
                                            <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="kd_jenis_prw" value="<?= htmlspecialchars((string)($d['kd_jenis_prw'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="tgl_perawatan" value="<?= htmlspecialchars((string)$d['tgl_perawatan'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="jam_rawat" value="<?= htmlspecialchars((string)$d['jam_rawat'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" style="background:#b91c1c;">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$serviceOnly || $focus === 'resep' || $focus === ''): ?>
                <?php
                $resepObatUrl = '?page=rawatjalan&ajax=search_obat';
                include __DIR__ . '/partials/rawat_resep.php';
                ?>
                <?php endif; ?>

                <?php if (!$serviceOnly || $focus === 'pemeriksaan' || $focus === ''): ?>
                <div class="card" id="section-pemeriksaan">
                    <div class="muted" style="margin-bottom:8px;">Input Hasil Pemeriksaan</div>
                    <form method="post" id="pemeriksaan_ralan_form">
                        <input type="hidden" name="action" value="input_pemeriksaan" id="pemeriksaan_ralan_action">
                        <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="old_tgl_perawatan" value="" id="pemeriksaan_ralan_old_tgl">
                        <input type="hidden" name="old_jam_rawat" value="" id="pemeriksaan_ralan_old_jam">
                        <div class="row" style="justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <strong id="pemeriksaan_ralan_mode">Mode input baru</strong>
                            <button type="button" id="pemeriksaan_ralan_cancel" style="display:none;background:#64748b;">Batal Edit</button>
                        </div>
                        <div class="field"><label>Keluhan</label><textarea name="keluhan" id="pemeriksaan_ralan_keluhan" rows="2" required></textarea></div>
                        <div class="field"><label>Pemeriksaan Fisik</label><textarea name="pemeriksaan" id="pemeriksaan_ralan_pemeriksaan" rows="2" required></textarea></div>
                        <div class="field"><label>Tensi</label><input type="text" name="tensi" id="pemeriksaan_ralan_tensi" value="-"></div>
                        <div class="field"><label>Suhu</label><input type="text" name="suhu_tubuh" id="pemeriksaan_ralan_suhu_tubuh"></div>
                        <div class="field"><label>Nadi</label><input type="text" name="nadi" id="pemeriksaan_ralan_nadi"></div>
                        <div class="field"><label>Respirasi</label><input type="text" name="respirasi" id="pemeriksaan_ralan_respirasi"></div>
                        <div class="field"><label>Tinggi Badan</label><input type="text" name="tinggi" id="pemeriksaan_ralan_tinggi"></div>
                        <div class="field"><label>Berat Badan</label><input type="text" name="berat" id="pemeriksaan_ralan_berat"></div>
                        <div class="field"><label>SpO2</label><input type="text" name="spo2" id="pemeriksaan_ralan_spo2"></div>
                        <div class="field"><label>GCS</label><input type="text" name="gcs" id="pemeriksaan_ralan_gcs"></div>
                        <div class="field">
                            <label>Kesadaran</label>
                            <select name="kesadaran" id="pemeriksaan_ralan_kesadaran" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                                <?php foreach (['Compos Mentis','Somnolence','Sopor','Coma','Alert','Confusion','Voice','Pain','Unresponsive','Apatis','Delirium'] as $kes): ?>
                                    <option value="<?= htmlspecialchars($kes, ENT_QUOTES, 'UTF-8') ?>" <?= $kes === 'Compos Mentis' ? 'selected' : '' ?>><?= htmlspecialchars($kes, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field"><label>Alergi</label><input type="text" name="alergi" id="pemeriksaan_ralan_alergi"></div>
                        <div class="field"><label>Lingkar Perut</label><input type="text" name="lingkar_perut" id="pemeriksaan_ralan_lingkar_perut"></div>
                        <div class="field"><label>Penilaian</label><textarea name="penilaian" id="pemeriksaan_ralan_penilaian" rows="2"></textarea></div>
                        <div class="field"><label>Rencana Tindak Lanjut</label><textarea name="rtl" id="pemeriksaan_ralan_rtl" rows="2"></textarea></div>
                        <div class="field"><label>Instruksi</label><textarea name="instruksi" id="pemeriksaan_ralan_instruksi" rows="2"></textarea></div>
                        <div class="field"><label>Evaluasi</label><textarea name="evaluasi" id="pemeriksaan_ralan_evaluasi" rows="2"></textarea></div>
                        <button type="submit" id="pemeriksaan_ralan_submit">Simpan Pemeriksaan</button>
                    </form>
                    <div style="margin-top:10px;">
                        <div class="muted" style="margin-bottom:6px;">Riwayat Pemeriksaan</div>
                        <table>
                            <thead><tr><th>Tanggal</th><th>Jam</th><th>Keluhan</th><th>Pemeriksaan</th><th>TTV</th><th>Penilaian/RTL</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ($detailPemeriksaan as $pm): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$pm['tgl_perawatan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$pm['jam_rawat'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$pm['keluhan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$pm['pemeriksaan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($pm['tensi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string)($pm['suhu_tubuh'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string)($pm['spo2'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$pm['penilaian'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string)$pm['rtl'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <button type="button" class="edit-pemeriksaan-ralan" data-row='<?= htmlspecialchars(json_encode($pm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, "UTF-8") ?>'>Edit</button>
                                        <form method="post" onsubmit="return confirm('Hapus pemeriksaan ini?');">
                                            <input type="hidden" name="action" value="hapus_pemeriksaan">
                                            <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="tgl_perawatan" value="<?= htmlspecialchars((string)$pm['tgl_perawatan'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="jam_rawat" value="<?= htmlspecialchars((string)$pm['jam_rawat'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" style="background:#b91c1c;">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$serviceOnly || $focus === 'lab' || $focus === ''): ?>
                <div class="card" id="section-lab">
                    <div class="muted" style="margin-bottom:8px;">Permintaan Lab</div>
                    <form method="post">
                        <input type="hidden" name="action" value="input_lab">
                        <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="field"><label>Diagnosa Klinis</label><input type="text" name="diagnosa_klinis_lab" value="-"></div>
                        <div class="field"><label>Informasi Tambahan</label><input type="text" name="informasi_tambahan_lab" value="-"></div>
                        <div class="field">
                            <label>Cari Pemeriksaan Lab (kode/nama, min. 3 huruf)</label>
                            <input type="text" id="lab_search" placeholder="contoh: hematologi">
                        </div>
                        <div class="field">
                            <label>Pemeriksaan Lab</label>
                            <select id="lab_select" data-no-rawat="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>" name="lab_items[]" multiple size="7" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:300px;">
                            </select>
                        </div>
                        <button type="submit">Simpan Permintaan Lab</button>
                    </form>
                    <div style="margin-top:10px;">
                        <div class="muted" style="margin-bottom:6px;">Riwayat Permintaan Lab</div>
                        <table>
                            <thead><tr><th>No Order</th><th>Tanggal</th><th>Jam</th><th>Item</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ($detailLab as $lb): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$lb['noorder'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$lb['tgl_permintaan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$lb['jam_permintaan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$lb['item_lab'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Hapus permintaan lab ini?');">
                                            <input type="hidden" name="action" value="hapus_lab">
                                            <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="noorder" value="<?= htmlspecialchars((string)$lb['noorder'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" style="background:#b91c1c;">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$serviceOnly || $focus === 'radiologi' || $focus === ''): ?>
                <div class="card" id="section-radiologi">
                    <div class="muted" style="margin-bottom:8px;">Permintaan Radiologi</div>
                    <form method="post">
                        <input type="hidden" name="action" value="input_radiologi">
                        <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="field"><label>Diagnosa Klinis</label><input type="text" name="diagnosa_klinis_rad" value="-"></div>
                        <div class="field"><label>Informasi Tambahan</label><input type="text" name="informasi_tambahan_rad" value="-"></div>
                        <div class="field">
                            <label>Cari Pemeriksaan Radiologi (kode/nama, min. 3 huruf)</label>
                            <input type="text" id="rad_search" placeholder="contoh: thorax">
                        </div>
                        <div class="field">
                            <label>Pemeriksaan Radiologi</label>
                            <select id="rad_select" data-no-rawat="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>" name="radiologi_items[]" multiple size="7" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:300px;">
                            </select>
                        </div>
                        <button type="submit">Simpan Permintaan Radiologi</button>
                    </form>
                    <div style="margin-top:10px;">
                        <div class="muted" style="margin-bottom:6px;">Riwayat Permintaan Radiologi</div>
                        <table>
                            <thead><tr><th>No Order</th><th>Tanggal</th><th>Jam</th><th>Item</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ($detailRad as $rd): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$rd['noorder'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$rd['tgl_permintaan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$rd['jam_permintaan'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$rd['item_radiologi'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Hapus permintaan radiologi ini?');">
                                            <input type="hidden" name="action" value="hapus_radiologi">
                                            <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="noorder" value="<?= htmlspecialchars((string)$rd['noorder'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" style="background:#b91c1c;">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$serviceOnly || $focus === 'operasi' || $focus === ''): ?>
                <div class="card" id="section-operasi">
                    <div class="muted" style="margin-bottom:8px;">Input Tindakan Operasi</div>
                    <form method="post">
                        <input type="hidden" name="action" value="input_operasi">
                        <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="field">
                            <label>Cari Paket Operasi (kode/nama, min. 3 huruf)</label>
                            <input type="text" id="operasi_search" placeholder="contoh: operasi katarak">
                        </div>
                        <div class="field">
                            <label>Paket Operasi</label>
                            <select id="operasi_select" data-no-rawat="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>" name="kode_paket" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:320px;" required>
                                <option value="">- pilih paket operasi -</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Jenis Anastesi</label>
                            <input type="text" name="jenis_anasthesi" value="-" placeholder="contoh: Spinal / General">
                        </div>
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
                    <div style="margin-top:10px;">
                        <div class="muted" style="margin-bottom:6px;">Riwayat Tindakan Operasi</div>
                        <table>
                            <thead><tr><th>Tanggal Operasi</th><th>Kode</th><th>Paket</th><th class="num">Biaya</th><th>Aksi</th></tr></thead>
                            <tbody>
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
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($serviceOnly && $focus === 'kamarinap'): ?>
                <div class="card" id="section-kamarinap">
                    <div class="muted" style="margin-bottom:8px;">Pindah ke Rawat Inap</div>
                    <?php if (!empty($isAlreadyRanap)): ?>
                        <p class="pill" style="border-color:#f4b4b4;background:#ffecec;color:#8b1b1b;">Pasien ini sudah memiliki rawat inap aktif.</p>
                    <?php endif; ?>
                    <form method="post" class="row">
                        <input type="hidden" name="action" value="input_kamarinap">
                        <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="field">
                            <label>Cari Kamar</label>
                            <input type="text" id="kamar_inap_search" placeholder="ketik kode kamar / bangsal / kelas">
                        </div>
                        <div class="field">
                            <label>Kamar</label>
                            <select id="kamar_inap_select" name="kd_kamar" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:320px;" required>
                                <option value="">- pilih kamar kosong -</option>
                                <?php foreach (($kamarInapOptions ?? []) as $km): ?>
                                    <option value="<?= htmlspecialchars((string)$km['kd_kamar'], ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars((string)$km['kd_kamar'] . ' ' . (string)$km['nm_bangsal'] . ' ' . (string)$km['kelas'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string)$km['kd_kamar'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string)$km['nm_bangsal'], ENT_QUOTES, 'UTF-8') ?> | Kelas <?= htmlspecialchars((string)$km['kelas'], ENT_QUOTES, 'UTF-8') ?> | <?= number_format((float)($km['trf_kamar'] ?? 0), 0, ',', '.') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Tanggal Masuk</label>
                            <input type="date" name="tgl_masuk" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="field">
                            <label>Jam Masuk</label>
                            <input type="time" name="jam_masuk" step="1" value="<?= date('H:i:s') ?>" required>
                        </div>
                        <div class="field" style="min-width:360px;">
                            <label>Diagnosa Awal</label>
                            <input type="text" name="diagnosa_awal" placeholder="diagnosa awal masuk ranap" required>
                        </div>
                        <button type="submit" <?= !empty($isAlreadyRanap) ? 'disabled' : '' ?>>Simpan Kamar Inap</button>
                    </form>
                    <div style="margin-top:10px;">
                        <div class="muted" style="margin-bottom:6px;">Riwayat Kamar Inap</div>
                        <table>
                            <thead><tr><th>Tanggal</th><th>Jam</th><th>Kamar</th><th>Bangsal</th><th>Diagnosa Awal</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach (($detailKamarInap ?? []) as $ki): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$ki['tgl_masuk'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$ki['jam_masuk'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$ki['kd_kamar'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($ki['nm_bangsal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)($ki['kelas'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>)</td>
                                    <td><?= htmlspecialchars((string)($ki['diagnosa_awal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($ki['stts_pulang'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<script>
    (function () {
        var focusSection = <?= json_encode($focus ?? '', JSON_UNESCAPED_UNICODE) ?>;
        var obatSearchUrl = '?page=rawatjalan&ajax=search_obat';
        var tindakanSearchUrl = '?page=rawatjalan&ajax=search_tindakan';
        var labSearchUrl = '?page=rawatjalan&ajax=search_lab';
        var radSearchUrl = '?page=rawatjalan&ajax=search_rad';
        var operasiSearchUrl = '?page=rawatjalan&ajax=search_operasi';

        function normalize(str) {
            return (str || '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function debounce(fn, wait) {
            var timer = null;
            return function () {
                var args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fn.apply(null, args);
                }, wait);
            };
        }

        function fillObatOptions(select, rows, keepValue) {
            if (!select) return;
            select.innerHTML = '';
            var def = document.createElement('option');
            def.value = '';
            def.textContent = '- pilih obat -';
            select.appendChild(def);

            rows.forEach(function (ob) {
                var opt = document.createElement('option');
                var kode = String(ob.kode_brng || '');
                var nama = String(ob.nama_brng || '');
                var kandungan = String(ob.kandungan_obat || '-');
                var ralan = Number(ob.ralan || 0).toLocaleString('id-ID');
                opt.value = kode;
                opt.textContent = kode + ' | ' + nama + ' | ' + kandungan + ' (' + ralan + ')';
                if (keepValue && keepValue === kode) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
        }

        function fillSimpleOptions(select, rows, keepValues, isMultiple) {
            if (!select) return;
            select.innerHTML = '';
            if (!isMultiple) {
                var def = document.createElement('option');
                def.value = '';
                def.textContent = '- pilih tindakan -';
                select.appendChild(def);
            }
            rows.forEach(function (item) {
                var opt = document.createElement('option');
                var kode = String(item.kd_jenis_prw || '');
                var nama = String(item.nm_perawatan || '');
                var biaya = Number(item.total_byr || item.total_byrdr || 0);
                if (!biaya) {
                    biaya = Number(item.material || 0) + Number(item.bhp || 0) + Number(item.tarif_tindakandr || 0) + Number(item.kso || 0) + Number(item.menejemen || 0);
                }
                if (!biaya) {
                    biaya = Number(item.operator1 || 0) + Number(item.operator2 || 0) + Number(item.operator3 || 0)
                        + Number(item.asisten_operator1 || 0) + Number(item.asisten_operator2 || 0) + Number(item.asisten_operator3 || 0)
                        + Number(item.instrumen || 0) + Number(item.dokter_anak || 0) + Number(item.perawaat_resusitas || 0)
                        + Number(item.dokter_anestesi || 0) + Number(item.asisten_anestesi || 0) + Number(item.asisten_anestesi2 || 0)
                        + Number(item.bidan || 0) + Number(item.bidan2 || 0) + Number(item.bidan3 || 0) + Number(item.perawat_luar || 0)
                        + Number(item.sewa_ok || 0) + Number(item.alat || 0) + Number(item.akomodasi || 0) + Number(item.bagian_rs || 0)
                        + Number(item.omloop || 0) + Number(item.omloop2 || 0) + Number(item.omloop3 || 0) + Number(item.omloop4 || 0)
                        + Number(item.omloop5 || 0) + Number(item.sarpras || 0) + Number(item.dokter_pjanak || 0) + Number(item.dokter_umum || 0);
                }
                opt.value = kode;
                opt.textContent = kode + ' | ' + nama + ' (' + biaya.toLocaleString('id-ID') + ')';
                if (isMultiple) {
                    if (keepValues && keepValues[kode]) opt.selected = true;
                } else if (keepValues && keepValues === kode) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
        }

        function bindAjaxLookupInput(input, select, url, isMultiple, noRawat) {
            if (!input || !select) return;
            var doFetch = debounce(function (query) {
                var q = String(query || '').trim();
                if (q.length < 3) return;
                var keep = isMultiple ? {} : (select.value || '');
                if (isMultiple) {
                    Array.prototype.forEach.call(select.selectedOptions || [], function (opt) {
                        keep[opt.value] = true;
                    });
                }
                var fullUrl = url + '&q=' + encodeURIComponent(q);
                if (noRawat) fullUrl += '&no_rawat=' + encodeURIComponent(noRawat);
                fetch(fullUrl, { credentials: 'same-origin' })
                    .then(function (res) { return res.json(); })
                    .then(function (json) {
                        if (!json || !json.ok || !Array.isArray(json.data)) return;
                        fillSimpleOptions(select, json.data, keep, isMultiple);
                    });
            }, 200);
            input.addEventListener('input', function () {
                doFetch(input.value);
            });
        }

        function bindLocalFilterInput(input, select) {
            if (!input || !select) return;
            var original = Array.prototype.map.call(select.options, function (opt) {
                return {
                    value: opt.value,
                    text: opt.text,
                    search: opt.getAttribute('data-search') || (opt.value + ' ' + opt.text)
                };
            });
            input.addEventListener('input', function () {
                var q = normalize(input.value || '');
                var selected = select.value || '';
                var list = q.length < 1 ? original : original.filter(function (it) {
                    return it.value === '' || normalize(it.search).indexOf(q) !== -1;
                });
                select.innerHTML = '';
                list.forEach(function (it) {
                    var opt = document.createElement('option');
                    opt.value = it.value;
                    opt.textContent = it.text;
                    if (it.search) opt.setAttribute('data-search', it.search);
                    if (selected && selected === it.value) opt.selected = true;
                    select.appendChild(opt);
                });
            });
        }

        function bindObatAjaxInput(input, select) {
            if (!input || !select) return;
            var doFetch = debounce(function (query) {
                var q = String(query || '').trim();
                if (q.length < 3) return;
                var currentSelected = select.value || '';
                fetch(obatSearchUrl + '&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                    .then(function (res) { return res.json(); })
                    .then(function (json) {
                        if (!json || !json.ok || !Array.isArray(json.data)) return;
                        fillObatOptions(select, json.data, currentSelected);
                    });
            }, 200);
            input.addEventListener('input', function () {
                doFetch(input.value);
            });
        }

        function bindResepRow(row) {
            if (!row) return;
            var searchInput = row.querySelector('.obat_search_input');
            var select = row.querySelector('.obat_select_row');
            bindObatAjaxInput(searchInput, select);
            var removeBtn = row.querySelector('.remove_resep_item');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    var container = document.getElementById('resep_items');
                    if (!container) return;
                    if (container.querySelectorAll('.resep-row').length <= 1) {
                        if (searchInput) searchInput.value = '';
                        if (select) select.value = '';
                        var qty = row.querySelector('input[name="jml[]"]');
                        var aturan = row.querySelector('input[name="aturan_pakai[]"]');
                        if (qty) qty.value = '1';
                        if (aturan) aturan.value = '3 x 1';
                        if (select) fillObatOptions(select, [], '');
                        return;
                    }
                    row.remove();
                });
            }
        }

        var tindakanSearchInput = document.getElementById('tindakan_search');
        var tindakanSelect = document.getElementById('tindakan_select');
        var labSearchInput = document.getElementById('lab_search');
        var labSelect = document.getElementById('lab_select');
        var radSearchInput = document.getElementById('rad_search');
        var radSelect = document.getElementById('rad_select');
        var operasiSearchInput = document.getElementById('operasi_search');
        var operasiSelect = document.getElementById('operasi_select');
        bindAjaxLookupInput(tindakanSearchInput, tindakanSelect, tindakanSearchUrl, false, tindakanSelect ? tindakanSelect.getAttribute('data-no-rawat') : '');
        bindAjaxLookupInput(labSearchInput, labSelect, labSearchUrl, true, labSelect ? labSelect.getAttribute('data-no-rawat') : '');
        bindAjaxLookupInput(radSearchInput, radSelect, radSearchUrl, true, radSelect ? radSelect.getAttribute('data-no-rawat') : '');
        bindAjaxLookupInput(operasiSearchInput, operasiSelect, operasiSearchUrl, false, operasiSelect ? operasiSelect.getAttribute('data-no-rawat') : '');
        bindLocalFilterInput(document.getElementById('kamar_inap_search'), document.getElementById('kamar_inap_select'));

        var resepContainer = document.getElementById('resep_items');
        if (resepContainer) {
            var firstRow = resepContainer.querySelector('.resep-row');
            bindResepRow(firstRow);

            var addBtn = document.getElementById('add_resep_item');
            if (addBtn && firstRow) {
                addBtn.addEventListener('click', function () {
                    var clone = firstRow.cloneNode(true);
                    var select = clone.querySelector('.obat_select_row');
                    var qty = clone.querySelector('input[name="jml[]"]');
                    var aturan = clone.querySelector('input[name="aturan_pakai[]"]');
                    if (select) select.value = '';
                    if (qty) qty.value = '1';
                    if (aturan) aturan.value = '3 x 1';
                    resepContainer.appendChild(clone);
                    bindResepRow(clone);
                });
            }
        }

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
        var serviceTitles = {
            tindakan: 'Tindakan',
            resep: 'Resep Dokter',
            pemeriksaan: 'Pemeriksaan',
            lab: 'Permintaan Lab',
            radiologi: 'Permintaan Radiologi',
            operasi: 'Tindakan Operasi',
            kamarinap: 'Kamar Inap',
            berkasrm: 'Berkas Rekam Medik',
            billing: 'Billing'
        };
        var serviceOverlay = document.getElementById('serviceModalOverlay');
        var serviceBody = document.getElementById('serviceModalBody');
        var serviceTitle = document.getElementById('serviceModalTitle');
        var serviceClose = document.getElementById('serviceModalClose');
        function closeServiceModal() {
            if (!serviceOverlay) return;
            serviceOverlay.style.display = 'none';
            if (serviceBody) serviceBody.innerHTML = '';
        }
        if (serviceClose) {
            serviceClose.addEventListener('click', closeServiceModal);
        }
        if (serviceOverlay) {
            serviceOverlay.addEventListener('click', function (e) {
                if (e.target === serviceOverlay) closeServiceModal();
            });
        }
        document.querySelectorAll('.open-service-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var service = btn.getAttribute('data-service') || '';
                var url = btn.getAttribute('data-url') || '';
                if (!serviceOverlay || !serviceBody) return;
                serviceTitle.textContent = serviceTitles[service] || 'Layanan';
                serviceBody.innerHTML = '<iframe src="' + url + '" style="width:100%;height:100%;border:0;" loading="lazy"></iframe>';
                serviceOverlay.style.display = 'block';
            });
        });
        function bindRalanPemeriksaanEditor() {
            var form = document.getElementById('pemeriksaan_ralan_form');
            if (!form) return;
            var actionInput = document.getElementById('pemeriksaan_ralan_action');
            var oldTgl = document.getElementById('pemeriksaan_ralan_old_tgl');
            var oldJam = document.getElementById('pemeriksaan_ralan_old_jam');
            var modeLabel = document.getElementById('pemeriksaan_ralan_mode');
            var submitBtn = document.getElementById('pemeriksaan_ralan_submit');
            var cancelBtn = document.getElementById('pemeriksaan_ralan_cancel');
            var defaults = {
                tensi: '-', kesadaran: 'Compos Mentis'
            };
            function setValue(id, value) {
                var el = document.getElementById(id);
                if (el) el.value = value || '';
            }
            function resetForm() {
                form.reset();
                actionInput.value = 'input_pemeriksaan';
                oldTgl.value = '';
                oldJam.value = '';
                setValue('pemeriksaan_ralan_tensi', defaults.tensi);
                setValue('pemeriksaan_ralan_kesadaran', defaults.kesadaran);
                modeLabel.textContent = 'Mode input baru';
                submitBtn.textContent = 'Simpan Pemeriksaan';
                cancelBtn.style.display = 'none';
            }
            document.querySelectorAll('.edit-pemeriksaan-ralan').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var row = {};
                    try { row = JSON.parse(btn.getAttribute('data-row') || '{}'); } catch (e) {}
                    actionInput.value = 'update_pemeriksaan';
                    oldTgl.value = row.tgl_perawatan || '';
                    oldJam.value = row.jam_rawat || '';
                    setValue('pemeriksaan_ralan_keluhan', row.keluhan || '');
                    setValue('pemeriksaan_ralan_pemeriksaan', row.pemeriksaan || '');
                    setValue('pemeriksaan_ralan_tensi', row.tensi || '-');
                    setValue('pemeriksaan_ralan_suhu_tubuh', row.suhu_tubuh || '');
                    setValue('pemeriksaan_ralan_nadi', row.nadi || '');
                    setValue('pemeriksaan_ralan_respirasi', row.respirasi || '');
                    setValue('pemeriksaan_ralan_tinggi', row.tinggi || '');
                    setValue('pemeriksaan_ralan_berat', row.berat || '');
                    setValue('pemeriksaan_ralan_spo2', row.spo2 || '');
                    setValue('pemeriksaan_ralan_gcs', row.gcs || '');
                    setValue('pemeriksaan_ralan_kesadaran', row.kesadaran || 'Compos Mentis');
                    setValue('pemeriksaan_ralan_alergi', row.alergi || '');
                    setValue('pemeriksaan_ralan_lingkar_perut', row.lingkar_perut || '');
                    setValue('pemeriksaan_ralan_penilaian', row.penilaian || '');
                    setValue('pemeriksaan_ralan_rtl', row.rtl || '');
                    setValue('pemeriksaan_ralan_instruksi', row.instruksi || '');
                    setValue('pemeriksaan_ralan_evaluasi', row.evaluasi || '');
                    modeLabel.textContent = 'Mode edit: ' + (row.tgl_perawatan || '') + ' ' + (row.jam_rawat || '');
                    submitBtn.textContent = 'Simpan Update';
                    cancelBtn.style.display = 'inline-block';
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
            cancelBtn.addEventListener('click', resetForm);
            resetForm();
        }
        bindRalanPemeriksaanEditor();
        if (focusSection) {
            var target = document.getElementById('section-' + focusSection);
            if (target && typeof target.scrollIntoView === 'function') {
                setTimeout(function () {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 120);
            }
        }
    })();
</script>
