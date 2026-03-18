<?php
$visit = is_array($visitContext['visit'] ?? null) ? $visitContext['visit'] : [];
$diag = is_array($visitContext['diagnosa'] ?? null) ? $visitContext['diagnosa'] : [];
$existingSep = is_array($visitContext['existing_sep'] ?? null) ? $visitContext['existing_sep'] : [];
$latestSkdp = is_array($visitContext['latest_skdp'] ?? null) ? $visitContext['latest_skdp'] : [];
$latestSpri = is_array($visitContext['latest_spri'] ?? null) ? $visitContext['latest_spri'] : [];
$latestRanapSep = is_array($visitContext['latest_ranap_sep'] ?? null) ? $visitContext['latest_ranap_sep'] : [];
$ranapSepOptions = array_values(array_filter((array)($visitContext['ranap_sep_options'] ?? []), static fn($row) => is_array($row)));
$createSepData = is_array($createSepData ?? null) ? $createSepData : [];
$viewMode = in_array((string)($viewMode ?? 'sep'), ['sep','rujukan','surat'], true) ? (string)$viewMode : 'sep';
$sepContext = (string)($createSepData['sep_context'] ?? $visitContext['sep_context'] ?? 'ralan');
$isRanap = $sepContext === 'ranap';
$isIgd = $sepContext === 'igd';
$showRalanSepType = !$isRanap && !$isIgd;
$ralanSepJenis = $showRalanSepType ? (string)($createSepData['ralan_sep_jenis'] ?? 'kontrol_berulang') : '';
$showSepKunjungan = !$isRanap && !$isIgd;
$showSkdpField = !$isRanap && !$isIgd && $ralanSepJenis !== 'rujukan_pertama';
$showSpriField = $isRanap;
$rujukanButtonTitle = $ralanSepJenis === 'post_opname' ? 'Pilih SEP Ranap' : 'Pilih Rujukan';
$ralanSepInfo = match ($ralanSepJenis) {
    'post_opname' => 'Kontrol post opname memakai asal rujukan RS. No rujukan diambil dari SEP rawat inap sebelumnya, sedangkan SKDP tetap bisa diisi bila ada.',
    'rujukan_pertama' => 'Rujukan pertama hanya memakai surat rujukan dari faskes 1 dan tidak memakai SKDP.',
    default => 'Kontrol berulang memakai rujukan dari faskes 1 dan surat kontrol/SKDP.',
};
$baseParams = ['page' => 'vclaim-bpjs', 'no_rawat' => (string)($createNoRawat ?? ''), 'no_sep' => (string)($createNoSep ?? '')];
if ($showRalanSepType && $ralanSepJenis !== '') {
    $baseParams['ralan_sep_jenis'] = $ralanSepJenis;
}
$sepUrl = '?' . http_build_query(array_filter($baseParams + ['view' => 'sep'], static fn($v) => $v !== ''));
$rujukanUrl = '?' . http_build_query(array_filter($baseParams + ['view' => 'rujukan'], static fn($v) => $v !== ''));
$suratUrl = '?' . http_build_query(array_filter($baseParams + ['view' => 'surat'], static fn($v) => $v !== ''));
?>
<div class="card">
    <h2 style="margin-top:0;">VClaim BPJS</h2>
    <p class="muted">Form Buat SEP disesuaikan dengan konteks kunjungan pasien: rawat jalan, gawat darurat, atau rawat inap.</p>
    <?php if (!empty($createNoRawat) || !empty($createNoSep)): ?>
        <p class="pill">
            Konteks
            <?php if (!empty($createNoRawat)): ?> | No Rawat: <?= htmlspecialchars((string)$createNoRawat, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
            <?php if (!empty($createNoSep)): ?> | No SEP: <?= htmlspecialchars((string)$createNoSep, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
            | Mode: <?= htmlspecialchars((string)($createSepData['sep_context_label'] ?? 'Rawat Jalan'), ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($visitError)): ?>
        <p class="pill" style="border-color:#f4b4b4;background:#ffecec;color:#8b1b1b;"><?= htmlspecialchars((string)$visitError, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:12px;">
    <div class="vclaim-mode-tabs">
        <a href="<?= htmlspecialchars((string)$sepUrl, ENT_QUOTES, 'UTF-8') ?>" class="<?= $viewMode === 'sep' ? 'active' : '' ?>">Create SEP</a>
        <a href="<?= htmlspecialchars((string)$rujukanUrl, ENT_QUOTES, 'UTF-8') ?>" class="<?= $viewMode === 'rujukan' ? 'active' : '' ?>">Create Rujukan</a>
        <a href="<?= htmlspecialchars((string)$suratUrl, ENT_QUOTES, 'UTF-8') ?>" class="<?= $viewMode === 'surat' ? 'active' : '' ?>">Create Surat Kontrol</a>
    </div>
</div>
<?php if (!empty($visit)): ?>
<div class="vclaim-summary" style="margin-top:12px;">
    <div class="card"><div class="muted">Pasien</div><strong><?= htmlspecialchars((string)($visit['nm_pasien'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong><div class="muted">RM <?= htmlspecialchars((string)($visit['no_rkm_medis'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
    <div class="card"><div class="muted"><?= $isRanap ? 'Bangsal/Kamar' : 'Poli RS' ?></div><strong><?= htmlspecialchars((string)($isRanap ? ($createSepData['nm_kamar_aktif'] ?? '-') : ($visit['nm_poli'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></strong><div class="muted">BPJS <?= htmlspecialchars((string)($createSepData['nm_poli_tujuan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
    <div class="card"><div class="muted">DPJP RS</div><strong><?= htmlspecialchars((string)($visit['nm_dokter'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong><div class="muted">BPJS <?= htmlspecialchars((string)($createSepData['nm_dpjp'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
    <div class="card"><div class="muted">Diagnosa Utama</div><strong><?= htmlspecialchars((string)($createSepData['diag_awal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong><div class="muted"><?= htmlspecialchars((string)($createSepData['nm_diag_awal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
</div>
<?php endif; ?>

<div class="card" style="margin-top:12px;">
    <style>
        .vclaim-mode-tabs { display:flex; gap:10px; flex-wrap:wrap; }
        .vclaim-mode-tabs a {
            text-decoration:none;
            padding:10px 14px;
            border-radius:12px;
            border:1px solid #d2e0ea;
            background:#f6fbfe;
            color:#285268;
            font-weight:600;
        }
        .vclaim-mode-tabs a.active {
            background:#e6f1f8;
            border-color:#8ab6d6;
            color:#10384d;
        }
        .vclaim-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; }
        .vclaim-summary .card { border-radius:14px; }
        .vclaim-form { display:grid; gap:16px; }
        .vclaim-form > h3 { margin:0; }
        .vclaim-lead { margin:0 0 4px; color:#5f7788; }
        .vclaim-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:14px; }
        .vclaim-grid .field {
            margin:0;
            padding:12px;
            border:1px solid #d8e5ee;
            border-radius:14px;
            background:#fbfdff;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
        }
        .vclaim-grid .field.full { grid-column:1 / -1; }
        .vclaim-grid .field label {
            display:block;
            font-size:12px;
            font-weight:700;
            color:#37556a;
            margin-bottom:8px;
            letter-spacing:.02em;
            text-transform:uppercase;
        }
        .vclaim-grid input,
        .vclaim-grid select,
        .vclaim-grid textarea {
            width:100%;
            border:1px solid #c9d8e2;
            border-radius:10px;
            padding:10px 12px;
            font:inherit;
            background:#fff;
            color:#15384c;
        }
        .vclaim-grid input[readonly] {
            background:#eef5f9;
            color:#496275;
        }
        .vclaim-inline {
            display:flex;
            gap:8px;
            align-items:center;
        }
        .vclaim-inline input { flex:1; }
        .vclaim-inline button {
            min-width:42px;
            height:42px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius:10px;
        }
        .vclaim-inline button.secondary,
        .vclaim-inline button[type="button"] {
            border:1px solid #8bb6cc;
            background:linear-gradient(180deg,#eff8fd 0%,#d9eef8 100%);
            color:#0f4560;
            box-shadow:0 4px 10px rgba(15,69,96,.12);
            font-size:16px;
            font-weight:700;
            cursor:pointer;
            flex:0 0 42px;
        }
        .vclaim-inline button.secondary:hover,
        .vclaim-inline button[type="button"]:hover {
            background:linear-gradient(180deg,#e2f2fb 0%,#cae7f5 100%);
            border-color:#6ca1bd;
        }
        .vclaim-inline button.secondary:focus-visible,
        .vclaim-inline button[type="button"]:focus-visible {
            outline:2px solid #7aaeca;
            outline-offset:2px;
        }
        .vclaim-inline button span {
            line-height:1;
        }
        .vclaim-section {
            margin-top:0;
            padding:16px;
            border:1px solid #d8e5ee;
            border-radius:16px;
            background:linear-gradient(180deg,#ffffff 0%,#f9fcfe 100%);
        }
        .vclaim-section-title {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            margin-bottom:12px;
            padding-bottom:10px;
            border-bottom:1px solid #e2edf4;
        }
        .vclaim-section-title strong {
            display:block;
            font-size:15px;
            color:#14384d;
        }
        .vclaim-section-title span {
            display:block;
            font-size:12px;
            color:#688091;
            margin-top:2px;
        }
        .vclaim-submit {
            display:flex;
            justify-content:flex-end;
            padding-top:4px;
        }
        .vclaim-submit button {
            min-width:220px;
            padding:12px 18px;
            border-radius:12px;
            font-weight:700;
            box-shadow:0 10px 22px rgba(18,79,107,.12);
        }
        @media (max-width: 720px) {
            .vclaim-submit button { width:100%; min-width:0; }
        }
    </style>

    <form method="post" class="vclaim-form">
        <input type="hidden" name="action" value="create_sep">
        <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)($createSepData['no_rawat'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="sep_context" value="<?= htmlspecialchars((string)($createSepData['sep_context'] ?? 'ralan'), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="jns_pelayanan" value="<?= htmlspecialchars((string)($createSepData['jns_pelayanan'] ?? '2'), ENT_QUOTES, 'UTF-8') ?>">

        <div class="vclaim-section">
            <div class="vclaim-section-title"><div><strong>Peserta & Pelayanan</strong><span>Identitas utama peserta BPJS dan layanan SEP.</span></div></div>
            <div class="vclaim-grid">
                <div class="field"><label>No Kartu</label><input type="text" name="no_kartu" value="<?= htmlspecialchars((string)($createSepData['no_kartu'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
                <div class="field"><label>Tanggal SEP</label><input type="date" name="tgl_sep" value="<?= htmlspecialchars((string)($createSepData['tgl_sep'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>" required></div>
                <div class="field"><label>Kode PPK Pelayanan</label><input type="text" name="ppk_pelayanan" value="<?= htmlspecialchars((string)($createSepData['ppk_pelayanan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
                <div class="field"><label>Nama PPK Pelayanan</label><input type="text" name="nm_ppk_pelayanan" value="<?= htmlspecialchars((string)($createSepData['nm_ppk_pelayanan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                <div class="field"><label>Jenis SEP</label><input type="text" value="<?= htmlspecialchars((string)($createSepData['sep_context_label'] ?? 'Rawat Jalan'), ENT_QUOTES, 'UTF-8') ?>" readonly></div>
                <div class="field"><label>No MR</label><input type="text" name="no_mr" value="<?= htmlspecialchars((string)($createSepData['no_mr'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
                <?php if ($showRalanSepType): ?>
                    <div class="field"><label>Jenis Kunjungan Rawat Jalan</label><select id="ralan-sep-jenis" name="ralan_sep_jenis"><option value="kontrol_berulang" <?= $ralanSepJenis === 'kontrol_berulang' ? 'selected' : '' ?>>Kontrol Berulang</option><option value="post_opname" <?= $ralanSepJenis === 'post_opname' ? 'selected' : '' ?>>Kontrol Post Opname</option><option value="rujukan_pertama" <?= $ralanSepJenis === 'rujukan_pertama' ? 'selected' : '' ?>>Rujukan Pertama</option></select></div>
                    <div class="field full"><p id="ralan-sep-info" class="pill" style="margin:0;border-color:#d7e4ed;background:#f7fbfd;color:#355468;"><?= htmlspecialchars($ralanSepInfo, ENT_QUOTES, 'UTF-8') ?></p></div>
                <?php endif; ?>
                <div class="field"><label>Kelas Hak</label><select name="kls_rawat_hak"><option value="1" <?= (($createSepData['kls_rawat_hak'] ?? '') === '1') ? 'selected' : '' ?>>Kelas 1</option><option value="2" <?= (($createSepData['kls_rawat_hak'] ?? '') === '2') ? 'selected' : '' ?>>Kelas 2</option><option value="3" <?= (($createSepData['kls_rawat_hak'] ?? '') === '3') ? 'selected' : '' ?>>Kelas 3</option></select></div>
                <?php if ($isRanap): ?>
                    <div class="field"><label>Kelas Naik</label><select name="kls_rawat_naik"><option value="">-</option><option value="1" <?= (($createSepData['kls_rawat_naik'] ?? '') === '1') ? 'selected' : '' ?>>VVIP</option><option value="2" <?= (($createSepData['kls_rawat_naik'] ?? '') === '2') ? 'selected' : '' ?>>VIP</option><option value="3" <?= (($createSepData['kls_rawat_naik'] ?? '') === '3') ? 'selected' : '' ?>>Kelas 1</option><option value="4" <?= (($createSepData['kls_rawat_naik'] ?? '') === '4') ? 'selected' : '' ?>>Kelas 2</option><option value="5" <?= (($createSepData['kls_rawat_naik'] ?? '') === '5') ? 'selected' : '' ?>>Kelas 3</option><option value="6" <?= (($createSepData['kls_rawat_naik'] ?? '') === '6') ? 'selected' : '' ?>>ICCU</option><option value="7" <?= (($createSepData['kls_rawat_naik'] ?? '') === '7') ? 'selected' : '' ?>>ICU</option><option value="8" <?= (($createSepData['kls_rawat_naik'] ?? '') === '8') ? 'selected' : '' ?>>Kelas Khusus</option></select></div>
                    <div class="field"><label>Pembiayaan</label><select name="pembiayaan"><option value="">-</option><option value="1" <?= (($createSepData['pembiayaan'] ?? '') === '1') ? 'selected' : '' ?>>Pribadi</option><option value="2" <?= (($createSepData['pembiayaan'] ?? '') === '2') ? 'selected' : '' ?>>Pemberi Kerja</option><option value="3" <?= (($createSepData['pembiayaan'] ?? '') === '3') ? 'selected' : '' ?>>Asuransi Kesehatan Tambahan</option></select></div>
                    <div class="field full"><label>Penanggung Jawab Naik Kelas</label><input type="text" name="penanggung_jawab" value="<?= htmlspecialchars((string)($createSepData['penanggung_jawab'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="vclaim-section">
            <div class="vclaim-section-title"><div><strong>Rujukan, Diagnosa, Tujuan, DPJP</strong><span>Data rujukan, diagnosa awal, poli tujuan, dan dokter penanggung jawab.</span></div></div>
            <div class="vclaim-grid">
                <div class="field"><label>Asal Rujukan</label><select name="asal_rujukan"><option value="1" <?= (($createSepData['asal_rujukan'] ?? '') === '1') ? 'selected' : '' ?>>1. Faskes 1</option><option value="2" <?= (($createSepData['asal_rujukan'] ?? '') === '2') ? 'selected' : '' ?>>2. Faskes 2 (RS)</option></select></div>
                <div class="field"><label>Tanggal Rujukan</label><input type="date" name="tgl_rujukan" value="<?= htmlspecialchars((string)($createSepData['tgl_rujukan'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>" required></div>
                <div class="field">
                    <label>No Rujukan</label>
                    <div class="vclaim-inline" style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center;width:100%;">
                        <input type="text" id="sep-no-rujukan" name="no_rujukan" style="min-width:0;width:100%;" value="<?= htmlspecialchars((string)($createSepData['no_rujukan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <button type="button" id="btn-pilih-rujukan" class="secondary lookup-btn" style="display:inline-flex;align-items:center;justify-content:center;width:42px;min-width:42px;height:42px;padding:0;border:1px solid #8bb6cc;background:#d9eef8;color:#0f4560;font-weight:700;white-space:nowrap;" title="<?= htmlspecialchars($rujukanButtonTitle, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($rujukanButtonTitle, ENT_QUOTES, 'UTF-8') ?>" data-no-kartu="<?= htmlspecialchars((string)($createSepData['no_kartu'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-no-rawat="<?= htmlspecialchars((string)($createNoRawat ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-default-skdp="<?= htmlspecialchars((string)($createSepData['latest_skdp_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-ranap-sep-no="<?= htmlspecialchars((string)($createSepData['latest_ranap_sep_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-ranap-sep-tgl="<?= htmlspecialchars((string)($createSepData['latest_ranap_sep_tgl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-ranap-ppk="<?= htmlspecialchars((string)($createSepData['ppk_pelayanan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-ranap-nm-ppk="<?= htmlspecialchars((string)($createSepData['nm_ppk_pelayanan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><span aria-hidden="true">&#128269;</span></button>
                    </div>
                </div>
                <div class="field"><label>Kode PPK Rujukan</label><input type="text" id="sep-ppk-rujukan" name="ppk_rujukan" value="<?= htmlspecialchars((string)($createSepData['ppk_rujukan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
                <div class="field full"><label>Nama PPK Rujukan</label><input type="text" id="sep-nm-ppk-rujukan" name="nm_ppk_rujukan" value="<?= htmlspecialchars((string)($createSepData['nm_ppk_rujukan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                <div class="field">
                    <label>Diagnosa Awal</label>
                    <div class="vclaim-inline" style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center;width:100%;">
                        <input type="text" id="sep-diag-awal" name="diag_awal" style="min-width:0;width:100%;" value="<?= htmlspecialchars((string)($createSepData['diag_awal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <button type="button" id="btn-pilih-diagnosa" class="secondary lookup-btn" style="display:inline-flex;align-items:center;justify-content:center;width:42px;min-width:42px;height:42px;padding:0;border:1px solid #8bb6cc;background:#d9eef8;color:#0f4560;font-weight:700;white-space:nowrap;" title="Pilih Diagnosa" aria-label="Pilih Diagnosa"><span aria-hidden="true">&#128269;</span></button>
                    </div>
                </div>
                <div class="field full"><label>Nama Diagnosa</label><input type="text" id="sep-nm-diag-awal" name="nm_diag_awal" value="<?= htmlspecialchars((string)($createSepData['nm_diag_awal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" readonly></div>
                <div class="field"><label>Kode <?= htmlspecialchars((string)($createSepData['poli_field_label'] ?? 'Poli Tujuan BPJS'), ENT_QUOTES, 'UTF-8') ?></label><input type="text" name="poli_tujuan" value="<?= htmlspecialchars((string)($createSepData['poli_tujuan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
                <div class="field"><label>Nama <?= htmlspecialchars((string)($createSepData['poli_field_label'] ?? 'Poli Tujuan BPJS'), ENT_QUOTES, 'UTF-8') ?></label><input type="text" name="nm_poli_tujuan" value="<?= htmlspecialchars((string)($createSepData['nm_poli_tujuan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                <?php if (!$isRanap && !$isIgd): ?>
                    <div class="field" id="skdp-field"<?= $showSkdpField ? '' : ' style="display:none;"' ?>>
                        <label>No Surat SKDP</label>
                        <div class="vclaim-inline" style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center;width:100%;">
                            <input type="text" id="sep-no-surat" name="no_surat" style="min-width:0;width:100%;" value="<?= htmlspecialchars((string)($createSepData['no_surat'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" id="btn-pilih-skdp" class="secondary lookup-btn" style="display:inline-flex;align-items:center;justify-content:center;width:42px;min-width:42px;height:42px;padding:0;border:1px solid #8bb6cc;background:#d9eef8;color:#0f4560;font-weight:700;white-space:nowrap;" title="Pilih Surat Kontrol" aria-label="Pilih Surat Kontrol" data-no-kartu="<?= htmlspecialchars((string)($createSepData['no_kartu'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-no-rawat="<?= htmlspecialchars((string)($createNoRawat ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-bulan="<?= htmlspecialchars((string)date('m', strtotime((string)($createSepData['tgl_sep'] ?? date('Y-m-d')))), ENT_QUOTES, 'UTF-8') ?>" data-tahun="<?= htmlspecialchars((string)date('Y', strtotime((string)($createSepData['tgl_sep'] ?? date('Y-m-d')))), ENT_QUOTES, 'UTF-8') ?>"><span aria-hidden="true">&#128269;</span></button>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($showSpriField): ?>
                    <div class="field">
                        <label>No SPRI</label>
                        <div class="vclaim-inline" style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center;width:100%;">
                            <input type="text" id="sep-no-surat" name="no_surat" style="min-width:0;width:100%;" value="<?= htmlspecialchars((string)($createSepData['no_surat'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" id="btn-pilih-spri" class="secondary lookup-btn" style="display:inline-flex;align-items:center;justify-content:center;width:42px;min-width:42px;height:42px;padding:0;border:1px solid #8bb6cc;background:#d9eef8;color:#0f4560;font-weight:700;white-space:nowrap;" title="Pilih SPRI" aria-label="Pilih SPRI" data-no-kartu="<?= htmlspecialchars((string)($createSepData['no_kartu'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-no-rawat="<?= htmlspecialchars((string)($createNoRawat ?? ''), ENT_QUOTES, 'UTF-8') ?>"><span aria-hidden="true">&#128269;</span></button>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="field"><label>Kode DPJP BPJS</label><input type="text" name="kode_dpjp" value="<?= htmlspecialchars((string)($createSepData['kode_dpjp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                <div class="field"><label>Nama DPJP BPJS</label><input type="text" name="nm_dpjp" value="<?= htmlspecialchars((string)($createSepData['nm_dpjp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                <?php if (!$isRanap): ?>
                    <div class="field"><label>Kode DPJP Layan</label><input type="text" name="dpjp_layan" value="<?= htmlspecialchars((string)($createSepData['dpjp_layan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>Nama DPJP Layan</label><input type="text" name="nm_dpjp_layan" value="<?= htmlspecialchars((string)($createSepData['nm_dpjp_layan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                <?php endif; ?>
                <div class="field full"><label>Catatan</label><input type="text" name="catatan" value="<?= htmlspecialchars((string)($createSepData['catatan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"></div>
            </div>
        </div>

        <?php if ($showSepKunjungan): ?>
            <div class="vclaim-section">
                <div class="vclaim-section-title"><div><strong>Kunjungan, Penunjang, dan Jaminan</strong><span>Tujuan kunjungan, penunjang, cob, dan data kecelakaan bila diperlukan.</span></div></div>
                <div class="vclaim-grid">
                    <div class="field"><label>Tujuan Kunjungan</label><select name="tujuan_kunj"><option value="0" <?= (($createSepData['tujuan_kunj'] ?? '') === '0') ? 'selected' : '' ?>>Normal</option><option value="1" <?= (($createSepData['tujuan_kunj'] ?? '') === '1') ? 'selected' : '' ?>>Prosedur</option><option value="2" <?= (($createSepData['tujuan_kunj'] ?? '') === '2') ? 'selected' : '' ?>>Konsul Dokter</option></select></div>
                    <div class="field"><label>Flag Procedure</label><select name="flag_procedure"><option value="">-</option><option value="0" <?= (($createSepData['flag_procedure'] ?? '') === '0') ? 'selected' : '' ?>>Prosedur Tidak Berkelanjutan</option><option value="1" <?= (($createSepData['flag_procedure'] ?? '') === '1') ? 'selected' : '' ?>>Prosedur dan Terapi Berkelanjutan</option></select></div>
                    <div class="field"><label>Kode Penunjang</label><select name="kd_penunjang"><option value="">-</option><?php $penunjangMap = ['1' => '1. Radioterapi', '2' => '2. Kemoterapi', '3' => '3. Rehabilitasi Medik', '4' => '4. Rehabilitasi Psikososial', '5' => '5. Transfusi Darah', '6' => '6. Pelayanan Gigi', '7' => '7. Laboratorium', '8' => '8. USG', '9' => '9. Farmasi', '10' => '10. Lain-lain', '11' => '11. MRI', '12' => '12. Hemodialisa']; foreach ($penunjangMap as $code => $label): ?><option value="<?= $code ?>" <?= (($createSepData['kd_penunjang'] ?? '') === $code) ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                    <div class="field"><label>Assessment Pelayanan</label><select name="assesment_pel"><option value="">-</option><option value="1" <?= (($createSepData['assesment_pel'] ?? '') === '1') ? 'selected' : '' ?>>Poli Spesialis Tidak Tersedia pada Hari Sebelumnya</option><option value="2" <?= (($createSepData['assesment_pel'] ?? '') === '2') ? 'selected' : '' ?>>Jam Poli Telah Berakhir pada Hari Sebelumnya</option><option value="3" <?= (($createSepData['assesment_pel'] ?? '') === '3') ? 'selected' : '' ?>>Dokter Spesialis yang Dimaksud Tidak Praktek pada Hari Sebelumnya</option><option value="4" <?= (($createSepData['assesment_pel'] ?? '') === '4') ? 'selected' : '' ?>>Atas Instruksi RS</option><option value="5" <?= (($createSepData['assesment_pel'] ?? '') === '5') ? 'selected' : '' ?>>Tujuan Kontrol</option></select></div>
                    <div class="field"><label>Eksekutif</label><select name="eksekutif"><option value="0" <?= (($createSepData['eksekutif'] ?? '') === '0') ? 'selected' : '' ?>>0. Tidak</option><option value="1" <?= (($createSepData['eksekutif'] ?? '') === '1') ? 'selected' : '' ?>>1. Ya</option></select></div>
                    <div class="field"><label>COB</label><select name="cob"><option value="0" <?= (($createSepData['cob'] ?? '') === '0') ? 'selected' : '' ?>>0. Tidak</option><option value="1" <?= (($createSepData['cob'] ?? '') === '1') ? 'selected' : '' ?>>1. Ya</option></select></div>
                    <div class="field"><label>Katarak</label><select name="katarak"><option value="0" <?= (($createSepData['katarak'] ?? '') === '0') ? 'selected' : '' ?>>0. Tidak</option><option value="1" <?= (($createSepData['katarak'] ?? '') === '1') ? 'selected' : '' ?>>1. Ya</option></select></div>
                    <div class="field"><label>No Telp</label><input type="text" name="no_telp" value="<?= htmlspecialchars((string)($createSepData['no_telp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
                    <div class="field"><label>User</label><input type="text" name="user" value="<?= htmlspecialchars((string)($createSepData['user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
                    <div class="field"><label>Laka Lantas</label><select name="laka_lantas"><option value="0" <?= (($createSepData['laka_lantas'] ?? '') === '0') ? 'selected' : '' ?>>0. Bukan Kasus Kecelakaan</option><option value="1" <?= (($createSepData['laka_lantas'] ?? '') === '1') ? 'selected' : '' ?>>1. KLL dan Bukan Kecelakaan Kerja</option><option value="2" <?= (($createSepData['laka_lantas'] ?? '') === '2') ? 'selected' : '' ?>>2. Kecelakaan Kerja</option><option value="3" <?= (($createSepData['laka_lantas'] ?? '') === '3') ? 'selected' : '' ?>>3. Kecelakaan Lalu Lintas</option></select></div>
                    <div class="field"><label>No LP</label><input type="text" name="no_lp" value="<?= htmlspecialchars((string)($createSepData['no_lp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>Penjamin KLL</label><select name="penjamin"><option value="">-</option><option value="1" <?= (($createSepData['penjamin'] ?? '') === '1') ? 'selected' : '' ?>>1. Jasa Raharja PT</option><option value="2" <?= (($createSepData['penjamin'] ?? '') === '2') ? 'selected' : '' ?>>2. BPJS Ketenagakerjaan</option><option value="3" <?= (($createSepData['penjamin'] ?? '') === '3') ? 'selected' : '' ?>>3. TASPEN PT</option><option value="4" <?= (($createSepData['penjamin'] ?? '') === '4') ? 'selected' : '' ?>>4. ASABRI PT</option></select></div>
                    <div class="field"><label>Tgl KKL</label><input type="date" name="tgl_kkl" value="<?= htmlspecialchars((string)($createSepData['tgl_kkl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>Suplesi</label><select name="suplesi"><option value="0" <?= (($createSepData['suplesi'] ?? '') === '0') ? 'selected' : '' ?>>0. Tidak</option><option value="1" <?= (($createSepData['suplesi'] ?? '') === '1') ? 'selected' : '' ?>>1. Ya</option></select></div>
                    <div class="field"><label>No SEP Suplesi</label><input type="text" name="no_sep_suplesi" value="<?= htmlspecialchars((string)($createSepData['no_sep_suplesi'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field full"><label>Keterangan KLL</label><input type="text" name="keterangan_kkl" value="<?= htmlspecialchars((string)($createSepData['keterangan_kkl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>KD Propinsi</label><input type="text" name="kd_propinsi" value="<?= htmlspecialchars((string)($createSepData['kd_propinsi'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>NM Propinsi</label><input type="text" name="nm_propinsi" value="<?= htmlspecialchars((string)($createSepData['nm_propinsi'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>KD Kabupaten</label><input type="text" name="kd_kabupaten" value="<?= htmlspecialchars((string)($createSepData['kd_kabupaten'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>NM Kabupaten</label><input type="text" name="nm_kabupaten" value="<?= htmlspecialchars((string)($createSepData['nm_kabupaten'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>KD Kecamatan</label><input type="text" name="kd_kecamatan" value="<?= htmlspecialchars((string)($createSepData['kd_kecamatan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>NM Kecamatan</label><input type="text" name="nm_kecamatan" value="<?= htmlspecialchars((string)($createSepData['nm_kecamatan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                </div>
            </div>
        <?php else: ?>
            <div class="vclaim-section">
                <p class="pill" style="border-color:#d7e4ed;background:#f7fbfd;color:#355468;">Field tujuan kunjungan, penunjang, dan surat kontrol tidak dipakai untuk konteks <?= htmlspecialchars((string)($createSepData['sep_context_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>.</p>
            </div>
            <div class="vclaim-section">
                <div class="vclaim-grid">
                    <div class="field"><label>Eksekutif</label><select name="eksekutif"><option value="0" <?= (($createSepData['eksekutif'] ?? '') === '0') ? 'selected' : '' ?>>0. Tidak</option><option value="1" <?= (($createSepData['eksekutif'] ?? '') === '1') ? 'selected' : '' ?>>1. Ya</option></select></div>
                    <div class="field"><label>COB</label><select name="cob"><option value="0" <?= (($createSepData['cob'] ?? '') === '0') ? 'selected' : '' ?>>0. Tidak</option><option value="1" <?= (($createSepData['cob'] ?? '') === '1') ? 'selected' : '' ?>>1. Ya</option></select></div>
                    <div class="field"><label>Katarak</label><select name="katarak"><option value="0" <?= (($createSepData['katarak'] ?? '') === '0') ? 'selected' : '' ?>>0. Tidak</option><option value="1" <?= (($createSepData['katarak'] ?? '') === '1') ? 'selected' : '' ?>>1. Ya</option></select></div>
                    <div class="field"><label>No Telp</label><input type="text" name="no_telp" value="<?= htmlspecialchars((string)($createSepData['no_telp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
                    <div class="field"><label>User</label><input type="text" name="user" value="<?= htmlspecialchars((string)($createSepData['user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
                    <div class="field"><label>Laka Lantas</label><select name="laka_lantas"><option value="0" <?= (($createSepData['laka_lantas'] ?? '') === '0') ? 'selected' : '' ?>>0. Bukan Kasus Kecelakaan</option><option value="1" <?= (($createSepData['laka_lantas'] ?? '') === '1') ? 'selected' : '' ?>>1. KLL dan Bukan Kecelakaan Kerja</option><option value="2" <?= (($createSepData['laka_lantas'] ?? '') === '2') ? 'selected' : '' ?>>2. Kecelakaan Kerja</option><option value="3" <?= (($createSepData['laka_lantas'] ?? '') === '3') ? 'selected' : '' ?>>3. Kecelakaan Lalu Lintas</option></select></div>
                    <div class="field"><label>No LP</label><input type="text" name="no_lp" value="<?= htmlspecialchars((string)($createSepData['no_lp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>Penjamin KLL</label><select name="penjamin"><option value="">-</option><option value="1" <?= (($createSepData['penjamin'] ?? '') === '1') ? 'selected' : '' ?>>1. Jasa Raharja PT</option><option value="2" <?= (($createSepData['penjamin'] ?? '') === '2') ? 'selected' : '' ?>>2. BPJS Ketenagakerjaan</option><option value="3" <?= (($createSepData['penjamin'] ?? '') === '3') ? 'selected' : '' ?>>3. TASPEN PT</option><option value="4" <?= (($createSepData['penjamin'] ?? '') === '4') ? 'selected' : '' ?>>4. ASABRI PT</option></select></div>
                    <div class="field"><label>Tgl KKL</label><input type="date" name="tgl_kkl" value="<?= htmlspecialchars((string)($createSepData['tgl_kkl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>Suplesi</label><select name="suplesi"><option value="0" <?= (($createSepData['suplesi'] ?? '') === '0') ? 'selected' : '' ?>>0. Tidak</option><option value="1" <?= (($createSepData['suplesi'] ?? '') === '1') ? 'selected' : '' ?>>1. Ya</option></select></div>
                    <div class="field"><label>No SEP Suplesi</label><input type="text" name="no_sep_suplesi" value="<?= htmlspecialchars((string)($createSepData['no_sep_suplesi'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field full"><label>Keterangan KLL</label><input type="text" name="keterangan_kkl" value="<?= htmlspecialchars((string)($createSepData['keterangan_kkl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>KD Propinsi</label><input type="text" name="kd_propinsi" value="<?= htmlspecialchars((string)($createSepData['kd_propinsi'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>NM Propinsi</label><input type="text" name="nm_propinsi" value="<?= htmlspecialchars((string)($createSepData['nm_propinsi'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>KD Kabupaten</label><input type="text" name="kd_kabupaten" value="<?= htmlspecialchars((string)($createSepData['kd_kabupaten'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>NM Kabupaten</label><input type="text" name="nm_kabupaten" value="<?= htmlspecialchars((string)($createSepData['nm_kabupaten'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>KD Kecamatan</label><input type="text" name="kd_kecamatan" value="<?= htmlspecialchars((string)($createSepData['kd_kecamatan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                    <div class="field"><label>NM Kecamatan</label><input type="text" name="nm_kecamatan" value="<?= htmlspecialchars((string)($createSepData['nm_kecamatan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="vclaim-submit"><button type="submit">Simpan Buat SEP</button></div>
    </form>

    <?php if (is_array($createSepResult)): ?>
        <div style="margin-top:12px;">
            <p class="pill" style="border-color:<?= !empty($createSepResult['ok']) ? '#cde6de' : '#f4b4b4' ?>;background:<?= !empty($createSepResult['ok']) ? '#edf8f4' : '#ffecec' ?>;color:<?= !empty($createSepResult['ok']) ? '#0f5132' : '#8b1b1b' ?>;">
                <?= htmlspecialchars((string)($createSepResult['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($createSepResult['persist_error'])): ?> | Simpan lokal gagal: <?= htmlspecialchars((string)$createSepResult['persist_error'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
            </p>
            <details style="margin-top:8px;">
                <summary>Lihat respon create SEP</summary>
                <pre style="background:#f7fbfd;border:1px solid #d7e4ed;border-radius:8px;padding:10px;overflow:auto;max-height:260px;"><?= htmlspecialchars((string)json_encode($createSepResult['data'] ?? $createSepResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
            </details>
        </div>
    <?php endif; ?>

    <?php if (is_array($createSepResult) && !empty($createSepResult['ok'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('appAlertModal');
        const target = document.getElementById('sep-terakhir-card');
        const doScroll = () => {
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
            }
        };

        const waitCloseAndScroll = () => {
            if (!modal) {
                doScroll();
                return;
            }
            const observer = new MutationObserver(() => {
                if (!modal.classList.contains('app-alert-open')) {
                    observer.disconnect();
                    doScroll();
                }
            });
            observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
        };

        waitCloseAndScroll();
        const noSep = <?= json_encode((string)($createSepData['no_sep'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const msg = 'SEP berhasil dibuat' + (noSep ? ': ' + noSep : '');
        window.alert(msg);
    });
    </script>
    <?php endif; ?>
</div>
<?php if (!empty($existingSep)): ?>
<div id="sep-terakhir-card" class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;">SEP Terakhir di SIMRS</h3>
    <table>
        <tbody>
            <tr><th>No SEP</th><td><?= htmlspecialchars((string)($existingSep['no_sep'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Tgl SEP</th><td><?= htmlspecialchars((string)($existingSep['tglsep'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>No Rujukan</th><td><?= htmlspecialchars((string)($existingSep['no_rujukan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Diagnosa</th><td><?= htmlspecialchars((string)($existingSep['diagawal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)($existingSep['nmdiagnosaawal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Poli Tujuan</th><td><?= htmlspecialchars((string)($existingSep['kdpolitujuan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)($existingSep['nmpolitujuan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
        </tbody>
    </table>
    <p style="margin:10px 0 0;"><a href="?page=sep-print&no_sep=<?= urlencode((string)($existingSep['no_sep'] ?? '')) ?>">Cetak SEP terakhir</a></p>
</div>
<?php endif; ?>

<div class="cards" style="margin-top:12px;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));<?= $viewMode === 'sep' ? 'display:none;' : '' ?>">
    <div class="card" style="<?= $viewMode !== 'surat' ? 'display:none;' : '' ?>">
        <h3 style="margin-top:0;">Buat Surat Kontrol</h3>
        <form method="post" class="vclaim-form">
            <input type="hidden" name="action" value="create_surat">
            <div class="field"><label>No SEP</label><input type="text" name="cs_no_sep" value="<?= htmlspecialchars((string)($createSuratData['no_sep'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label>Kode Dokter BPJS</label><input type="text" name="cs_kode_dokter" value="<?= htmlspecialchars((string)($createSuratData['kode_dokter'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label>Poli Kontrol (kode BPJS)</label><input type="text" name="cs_poli_kontrol" value="<?= htmlspecialchars((string)($createSuratData['poli_kontrol'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label>Tanggal Rencana Kontrol</label><input type="date" name="cs_tgl_rencana" value="<?= htmlspecialchars((string)($createSuratData['tgl_rencana'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label>User</label><input type="text" name="cs_user" value="<?= htmlspecialchars((string)($createSuratData['user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <button type="submit">Simpan Surat Kontrol</button>
        </form>
        <?php if (is_array($createSuratResult)): ?>
            <div style="margin-top:10px;">
                <p class="pill" style="border-color:<?= !empty($createSuratResult['ok']) ? '#cde6de' : '#f4b4b4' ?>;background:<?= !empty($createSuratResult['ok']) ? '#edf8f4' : '#ffecec' ?>;color:<?= !empty($createSuratResult['ok']) ? '#0f5132' : '#8b1b1b' ?>;">
                    <?= htmlspecialchars((string)($createSuratResult['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="<?= $viewMode !== 'rujukan' ? 'display:none;' : '' ?>">
        <h3 style="margin-top:0;">Buat Rujukan</h3>
        <form method="post" class="vclaim-form">
            <input type="hidden" name="action" value="create_rujukan">
            <div class="field"><label>No SEP</label><input type="text" name="cr_no_sep" value="<?= htmlspecialchars((string)($createRujukanData['no_sep'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label>Tgl Rujukan</label><input type="date" name="cr_tgl_rujukan" value="<?= htmlspecialchars((string)($createRujukanData['tgl_rujukan'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label>Tgl Rencana Kunjungan</label><input type="date" name="cr_tgl_rencana" value="<?= htmlspecialchars((string)($createRujukanData['tgl_rencana'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label>PPK Dirujuk (kode BPJS)</label><input type="text" name="cr_ppk_dirujuk" value="<?= htmlspecialchars((string)($createRujukanData['ppk_dirujuk'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label>Jenis Pelayanan</label><select name="cr_jns_pelayanan"><option value="2" <?= (($createRujukanData['jns_pelayanan'] ?? '2') === '2') ? 'selected' : '' ?>>Rawat Jalan</option><option value="1" <?= (($createRujukanData['jns_pelayanan'] ?? '') === '1') ? 'selected' : '' ?>>Rawat Inap</option></select></div>
            <div class="field"><label>Catatan</label><input type="text" name="cr_catatan" value="<?= htmlspecialchars((string)($createRujukanData['catatan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label>Diagnosa Rujukan</label><input type="text" name="cr_diag_rujukan" value="<?= htmlspecialchars((string)($createRujukanData['diag_rujukan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label>Tipe Rujukan</label><select name="cr_tipe_rujukan"><option value="0" <?= (($createRujukanData['tipe_rujukan'] ?? '0') === '0') ? 'selected' : '' ?>>Penuh</option><option value="1" <?= (($createRujukanData['tipe_rujukan'] ?? '') === '1') ? 'selected' : '' ?>>Partial</option><option value="2" <?= (($createRujukanData['tipe_rujukan'] ?? '') === '2') ? 'selected' : '' ?>>Rujuk Balik</option></select></div>
            <div class="field"><label>Poli Rujukan</label><input type="text" name="cr_poli_rujukan" value="<?= htmlspecialchars((string)($createRujukanData['poli_rujukan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="field"><label>User</label><input type="text" name="cr_user" value="<?= htmlspecialchars((string)($createRujukanData['user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <button type="submit">Simpan Rujukan</button>
        </form>
        <?php if (is_array($createRujukanResult)): ?>
            <div style="margin-top:10px;">
                <p class="pill" style="border-color:<?= !empty($createRujukanResult['ok']) ? '#cde6de' : '#f4b4b4' ?>;background:<?= !empty($createRujukanResult['ok']) ? '#edf8f4' : '#ffecec' ?>;color:<?= !empty($createRujukanResult['ok']) ? '#0f5132' : '#8b1b1b' ?>;">
                    <?= htmlspecialchars((string)($createRujukanResult['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="ranap-sep-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:999;align-items:center;justify-content:center;padding:20px;">
    <div style="width:min(980px,100%);max-height:85vh;overflow:auto;background:#fff;border-radius:14px;border:1px solid #d7e4ed;box-shadow:0 20px 50px rgba(15,23,42,.18);padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
            <div>
                <h3 style="margin:0;">Pilih SEP Rawat Inap</h3>
                <div class="muted">Dipakai untuk kontrol post opname sebagai nomor rujukan dari SEP rawat inap sebelumnya.</div>
            </div>
            <button type="button" id="ranap-sep-close" class="secondary">Tutup</button>
        </div>
        <div style="margin-top:12px;overflow:auto;">
            <table>
                <thead>
                    <tr><th>No SEP</th><th>Tgl SEP</th><th>Tgl Pulang</th><th>Poli</th><th>Diagnosa</th><th>Aksi</th></tr>
                </thead>
                <tbody id="ranap-sep-list">
                    <?php if (empty($ranapSepOptions)): ?>
                        <tr><td colspan="6" class="muted">Belum ada SEP rawat inap tersimpan untuk pasien ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ranapSepOptions as $row): ?>
                            <?php
                            $rowNoSep = trim((string)($row['no_sep'] ?? ''));
                            $rowTglSep = trim((string)($row['tglsep'] ?? ''));
                            $rowTglPulang = trim((string)($row['tglpulang'] ?? ''));
                            $rowPoli = trim((string)($row['nmpolitujuan'] ?? $row['kdpolitujuan'] ?? '-'));
                            $rowDiag = trim((string)($row['diagawal'] ?? '')) . ((trim((string)($row['nmdiagnosaawal'] ?? '')) !== '') ? ' - ' . trim((string)($row['nmdiagnosaawal'] ?? '')) : '');
                            $rowPpk = trim((string)($row['kdppkpelayanan'] ?? $createSepData['ppk_pelayanan'] ?? ''));
                            $rowNmPpk = trim((string)($row['nmppkpelayanan'] ?? $createSepData['nm_ppk_pelayanan'] ?? ''));
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($rowNoSep, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($rowTglSep !== '' ? $rowTglSep : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(($rowTglPulang !== '' && $rowTglPulang !== '0000-00-00 00:00:00') ? $rowTglPulang : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($rowPoli !== '' ? $rowPoli : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($rowDiag !== '' ? $rowDiag : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><button type="button" class="secondary ranap-sep-pilih" title="Pilih SEP Ranap" aria-label="Pilih SEP Ranap" style="display:inline-flex;align-items:center;justify-content:center;width:42px;min-width:42px;height:42px;padding:0;border:1px solid #8bb6cc;background:#d9eef8;color:#0f4560;font-weight:700;white-space:nowrap;" data-no-rujukan="<?= htmlspecialchars($rowNoSep, ENT_QUOTES, 'UTF-8') ?>" data-tgl-rujukan="<?= htmlspecialchars($rowTglSep, ENT_QUOTES, 'UTF-8') ?>" data-ppk-rujukan="<?= htmlspecialchars($rowPpk, ENT_QUOTES, 'UTF-8') ?>" data-nm-ppk-rujukan="<?= htmlspecialchars($rowNmPpk, ENT_QUOTES, 'UTF-8') ?>"><span aria-hidden="true">&#128269;</span></button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div id="spri-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:999;align-items:center;justify-content:center;padding:20px;">
    <div style="width:min(920px,100%);max-height:85vh;overflow:auto;background:#fff;border-radius:14px;border:1px solid #d7e4ed;box-shadow:0 20px 50px rgba(15,23,42,.18);padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
            <div>
                <h3 style="margin:0;">Pilih SPRI BPJS</h3>
                <div class="muted">Daftar SPRI diambil dari data bridging yang tersimpan untuk pasien/rawat ini.</div>
            </div>
            <button type="button" id="spri-close" class="secondary">Tutup</button>
        </div>
        <p id="spri-status" class="pill" style="margin-top:12px;display:none;"></p>
        <div style="margin-top:12px;overflow:auto;">
            <table>
                <thead>
                    <tr><th>No SPRI</th><th>Tgl Surat</th><th>Tgl Rencana</th><th>Poli</th><th>Dokter</th><th>Aksi</th></tr>
                </thead>
                <tbody id="spri-list">
                    <tr><td colspan="6" class="muted">Belum ada data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div id="skdp-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:999;align-items:center;justify-content:center;padding:20px;">
    <div style="width:min(920px,100%);max-height:85vh;overflow:auto;background:#fff;border-radius:14px;border:1px solid #d7e4ed;box-shadow:0 20px 50px rgba(15,23,42,.18);padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
            <div>
                <h3 style="margin:0;">Pilih Surat Kontrol BPJS</h3>
                <div class="muted">Daftar diambil dari server BPJS sesuai pasien yang sedang dipilih.</div>
            </div>
            <button type="button" id="skdp-close" class="secondary">Tutup</button>
        </div>
        <div class="vclaim-grid" style="margin-top:14px;">
            <div class="field"><label>Bulan</label><input type="number" id="skdp-bulan" min="1" max="12"></div>
            <div class="field"><label>Tahun</label><input type="number" id="skdp-tahun" min="2000" max="2100"></div>
            <div class="field"><label>Filter</label><select id="skdp-filter"><option value="2">Tanggal Rencana Kontrol</option><option value="1">Tanggal Entri</option></select></div>
            <div class="field" style="display:flex;align-items:flex-end;"><button type="button" id="skdp-load">Ambil Daftar</button></div>
        </div>
        <p id="skdp-status" class="pill" style="margin-top:12px;display:none;"></p>
        <div style="margin-top:12px;overflow:auto;">
            <table>
                <thead>
                    <tr><th>No Surat</th><th>No SEP</th><th>Tgl Terbit</th><th>Tgl Kontrol</th><th>Poli</th><th>Dokter</th><th>Aksi</th></tr>
                </thead>
                <tbody id="skdp-list">
                    <tr><td colspan="7" class="muted">Belum ada data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const skdpBtn = document.getElementById('btn-pilih-skdp');
    const skdpModal = document.getElementById('skdp-modal');
    const spriBtn = document.getElementById('btn-pilih-spri');
    const spriModal = document.getElementById('spri-modal');
    const rujukanBtn = document.getElementById('btn-pilih-rujukan');
    const rujukanModal = document.getElementById('rujukan-modal');
    const ranapSepModal = document.getElementById('ranap-sep-modal');
    const ranapSepClose = document.getElementById('ranap-sep-close');
    const ralanTypeEl = document.getElementById('ralan-sep-jenis');
    const ralanInfoEl = document.getElementById('ralan-sep-info');
    const asalRujukanEl = document.querySelector('select[name="asal_rujukan"]');
    const noRujukanEl = document.getElementById('sep-no-rujukan');
    const ppkRujukanEl = document.getElementById('sep-ppk-rujukan');
    const nmPpkRujukanEl = document.getElementById('sep-nm-ppk-rujukan');
    const diagAwalEl = document.getElementById('sep-diag-awal');
    const nmDiagAwalEl = document.getElementById('sep-nm-diag-awal');
    const diagBtn = document.getElementById('btn-pilih-diagnosa');
    const diagModal = document.getElementById('diagnosa-modal');
    const tglRujukanEl = document.querySelector('input[name="tgl_rujukan"]');
    const noSuratEl = document.getElementById('sep-no-surat');
    const skdpFieldEl = document.getElementById('skdp-field');
    const kodeDpjpEl = document.querySelector('input[name="kode_dpjp"]');
    const poliTujuanEl = document.querySelector('input[name="poli_tujuan"]');

    const setPillStatus = (element, message, error = false) => {
        if (!element) return;
        element.style.display = 'block';
        element.textContent = message;
        element.style.borderColor = error ? '#f4b4b4' : '#cde6de';
        element.style.background = error ? '#ffecec' : '#edf8f4';
        element.style.color = error ? '#8b1b1b' : '#0f5132';
    };

    const ralanTypeConfig = {
        kontrol_berulang: {
            asal: '1',
            showSkdp: true,
            title: 'Pilih Rujukan',
            note: 'Kontrol berulang memakai rujukan dari faskes 1 dan surat kontrol/SKDP.',
        },
        post_opname: {
            asal: '2',
            showSkdp: true,
            title: 'Pilih SEP Ranap',
            note: 'Kontrol post opname memakai asal rujukan RS. No rujukan diambil dari SEP rawat inap sebelumnya, sedangkan SKDP tetap bisa diisi bila ada.',
        },
        rujukan_pertama: {
            asal: '1',
            showSkdp: false,
            title: 'Pilih Rujukan',
            note: 'Rujukan pertama hanya memakai surat rujukan dari faskes 1 dan tidak memakai SKDP.',
        },
    };

    const applyRalanType = () => {
        if (!ralanTypeEl) return;
        const cfg = ralanTypeConfig[ralanTypeEl.value] || ralanTypeConfig.kontrol_berulang;
        if (asalRujukanEl) asalRujukanEl.value = cfg.asal;
        if (ralanInfoEl) ralanInfoEl.textContent = cfg.note;
        if (skdpFieldEl) skdpFieldEl.style.display = cfg.showSkdp ? '' : 'none';
        if (rujukanBtn) {
            rujukanBtn.title = cfg.title;
            rujukanBtn.setAttribute('aria-label', cfg.title);
        }
        if (!cfg.showSkdp && noSuratEl) {
            noSuratEl.value = '';
        }
        if (ralanTypeEl.value === 'post_opname' && rujukanBtn) {
            if (noRujukanEl && !noRujukanEl.value) noRujukanEl.value = rujukanBtn.dataset.ranapSepNo || '';
            if (tglRujukanEl && !tglRujukanEl.value) tglRujukanEl.value = rujukanBtn.dataset.ranapSepTgl || tglRujukanEl.value;
            if (ppkRujukanEl && !ppkRujukanEl.value) ppkRujukanEl.value = rujukanBtn.dataset.ranapPpk || '';
            if (nmPpkRujukanEl && !nmPpkRujukanEl.value) nmPpkRujukanEl.value = rujukanBtn.dataset.ranapNmPpk || '';
            if (noSuratEl && !noSuratEl.value) noSuratEl.value = rujukanBtn.dataset.defaultSkdp || '';
        } else if (ralanTypeEl.value === 'kontrol_berulang' && noSuratEl && !noSuratEl.value && rujukanBtn) {
            noSuratEl.value = rujukanBtn.dataset.defaultSkdp || '';
        }
    };

    if (ralanTypeEl) {
        ralanTypeEl.addEventListener('change', applyRalanType);
        applyRalanType();
    }

    if (ranapSepModal) {
        ranapSepModal.querySelectorAll('.ranap-sep-pilih').forEach((pickBtn) => {
            pickBtn.addEventListener('click', () => {
                if (noRujukanEl) noRujukanEl.value = pickBtn.dataset.noRujukan || '';
                if (tglRujukanEl) tglRujukanEl.value = pickBtn.dataset.tglRujukan || '';
                if (ppkRujukanEl) ppkRujukanEl.value = pickBtn.dataset.ppkRujukan || '';
                if (nmPpkRujukanEl) nmPpkRujukanEl.value = pickBtn.dataset.nmPpkRujukan || '';
                ranapSepModal.style.display = 'none';
            });
        });
        if (ranapSepClose) {
            ranapSepClose.addEventListener('click', () => {
                ranapSepModal.style.display = 'none';
            });
        }
        ranapSepModal.addEventListener('click', (event) => {
            if (event.target === ranapSepModal) ranapSepModal.style.display = 'none';
        });
    }

    if (spriBtn && spriModal) {
        const closeBtn = document.getElementById('spri-close');
        const statusEl = document.getElementById('spri-status');
        const listEl = document.getElementById('spri-list');

        const renderRows = (rows) => {
            if (!listEl) return;
            if (!Array.isArray(rows) || rows.length === 0) {
                listEl.innerHTML = '<tr><td colspan="6" class="muted">Data SPRI tidak ditemukan.</td></tr>';
                return;
            }
            listEl.innerHTML = rows.map((row) => {
                const noSurat = row.no_surat || '';
                const tglTerbit = row.tgl_terbit || '-';
                const tglKontrol = row.tgl_kontrol || '-';
                const poli = [row.kode_poli || '', row.nama_poli || ''].filter(Boolean).join(' - ') || '-';
                const dokter = [row.kode_dokter || '', row.nama_dokter || ''].filter(Boolean).join(' - ') || '-';
                return `<tr>
                    <td>${noSurat}</td>
                    <td>${tglTerbit}</td>
                    <td>${tglKontrol}</td>
                    <td>${poli}</td>
                    <td>${dokter}</td>
                    <td><button type="button" class="secondary spri-pilih" title="Pilih SPRI" aria-label="Pilih SPRI" style="display:inline-flex;align-items:center;justify-content:center;width:42px;min-width:42px;height:42px;padding:0;border:1px solid #8bb6cc;background:#d9eef8;color:#0f4560;font-weight:700;white-space:nowrap;" data-no-surat="${noSurat}" data-kode-dokter="${row.kode_dokter || ''}" data-kode-poli="${row.kode_poli || ''}"><span aria-hidden="true">&#128269;</span></button></td>
                </tr>`;
            }).join('');
            listEl.querySelectorAll('.spri-pilih').forEach((pickBtn) => {
                pickBtn.addEventListener('click', () => {
                    if (noSuratEl) noSuratEl.value = pickBtn.dataset.noSurat || '';
                    if (kodeDpjpEl && pickBtn.dataset.kodeDokter) kodeDpjpEl.value = pickBtn.dataset.kodeDokter || '';
                    if (poliTujuanEl && !poliTujuanEl.value && pickBtn.dataset.kodePoli) poliTujuanEl.value = pickBtn.dataset.kodePoli || '';
                    spriModal.style.display = 'none';
                });
            });
        };

        const loadRows = async () => {
            const noKartu = spriBtn.dataset.noKartu || '';
            const noRawat = spriBtn.dataset.noRawat || '';
            setPillStatus(statusEl, 'Mengambil daftar SPRI...');
            if (listEl) listEl.innerHTML = '<tr><td colspan="6" class="muted">Memuat...</td></tr>';
            const url = new URL(window.location.href);
            url.searchParams.set('page', 'vclaim-bpjs');
            url.searchParams.set('ajax', 'spri-list');
            if (noRawat) url.searchParams.set('no_rawat', noRawat);
            if (noKartu) url.searchParams.set('no_kartu', noKartu);
            try {
                const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                setPillStatus(statusEl, data.message || (data.ok ? 'Data SPRI ditemukan.' : 'Gagal mengambil data.'), !data.ok);
                renderRows(data.data || []);
            } catch (error) {
                setPillStatus(statusEl, 'Gagal memuat daftar SPRI.', true);
                if (listEl) listEl.innerHTML = '<tr><td colspan="6" class="muted">Gagal memuat data.</td></tr>';
            }
        };

        spriBtn.addEventListener('click', () => {
            spriModal.style.display = 'flex';
            loadRows();
        });
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                spriModal.style.display = 'none';
            });
        }
        spriModal.addEventListener('click', (event) => {
            if (event.target === spriModal) spriModal.style.display = 'none';
        });
    }

    if (skdpBtn && skdpModal) {
        const closeBtn = document.getElementById('skdp-close');
        const loadBtn = document.getElementById('skdp-load');
        const statusEl = document.getElementById('skdp-status');
        const listEl = document.getElementById('skdp-list');
        const bulanEl = document.getElementById('skdp-bulan');
        const tahunEl = document.getElementById('skdp-tahun');
        const filterEl = document.getElementById('skdp-filter');
        if (bulanEl) bulanEl.value = skdpBtn.dataset.bulan || String(new Date().getMonth() + 1);
        if (tahunEl) tahunEl.value = skdpBtn.dataset.tahun || String(new Date().getFullYear());

        const renderRows = (rows) => {
            if (!listEl) return;
            if (!Array.isArray(rows) || rows.length === 0) {
                listEl.innerHTML = '<tr><td colspan="7" class="muted">Data surat kontrol tidak ditemukan.</td></tr>';
                return;
            }
            listEl.innerHTML = rows.map((row) => {
                const noSurat = row.no_surat || '';
                const noSep = row.no_sep || '';
                const tglTerbit = row.tgl_terbit || '-';
                const tglKontrol = row.tgl_kontrol || '-';
                const poli = [row.kode_poli || '', row.nama_poli || ''].filter(Boolean).join(' - ') || '-';
                const dokter = [row.kode_dokter || '', row.nama_dokter || ''].filter(Boolean).join(' - ') || '-';
                return `<tr>
                    <td>${noSurat}</td>
                    <td>${noSep || '-'}</td>
                    <td>${tglTerbit}</td>
                    <td>${tglKontrol}</td>
                    <td>${poli}</td>
                    <td>${dokter}</td>
                    <td><button type="button" class="secondary skdp-pilih" title="Pilih Surat Kontrol" aria-label="Pilih Surat Kontrol" style="display:inline-flex;align-items:center;justify-content:center;width:42px;min-width:42px;height:42px;padding:0;border:1px solid #8bb6cc;background:#d9eef8;color:#0f4560;font-weight:700;white-space:nowrap;" data-no-surat="${noSurat}" data-kode-dokter="${row.kode_dokter || ''}" data-kode-poli="${row.kode_poli || ''}"><span aria-hidden="true">&#128269;</span></button></td>
                </tr>`;
            }).join('');
            listEl.querySelectorAll('.skdp-pilih').forEach((pickBtn) => {
                pickBtn.addEventListener('click', () => {
                    if (noSuratEl) noSuratEl.value = pickBtn.dataset.noSurat || '';
                    if (kodeDpjpEl && pickBtn.dataset.kodeDokter) kodeDpjpEl.value = pickBtn.dataset.kodeDokter || '';
                    if (poliTujuanEl && !poliTujuanEl.value && pickBtn.dataset.kodePoli) poliTujuanEl.value = pickBtn.dataset.kodePoli || '';
                    skdpModal.style.display = 'none';
                });
            });
        };

        const loadRows = async () => {
            const noKartu = skdpBtn.dataset.noKartu || '';
            const noRawat = skdpBtn.dataset.noRawat || '';
            if (!noKartu) {
                setPillStatus(statusEl, 'No kartu pasien belum tersedia.', true);
                return;
            }
            setPillStatus(statusEl, 'Mengambil daftar surat kontrol dari BPJS...');
            if (listEl) listEl.innerHTML = '<tr><td colspan="7" class="muted">Memuat...</td></tr>';
            const url = new URL(window.location.href);
            url.searchParams.set('page', 'vclaim-bpjs');
            url.searchParams.set('ajax', 'surat-kontrol-list');
            if (noRawat) url.searchParams.set('no_rawat', noRawat);
            url.searchParams.set('no_kartu', noKartu);
            url.searchParams.set('bulan', bulanEl ? (bulanEl.value || '') : '');
            url.searchParams.set('tahun', tahunEl ? (tahunEl.value || '') : '');
            url.searchParams.set('filter', filterEl ? (filterEl.value || '2') : '2');
            try {
                const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                setPillStatus(statusEl, data.message || (data.ok ? 'Data surat kontrol ditemukan.' : 'Gagal mengambil data.'), !data.ok);
                renderRows(data.data || []);
            } catch (error) {
                setPillStatus(statusEl, 'Gagal menghubungi server BPJS dari modul web.', true);
                if (listEl) listEl.innerHTML = '<tr><td colspan="7" class="muted">Gagal memuat data.</td></tr>';
            }
        };

        skdpBtn.addEventListener('click', () => {
            skdpModal.style.display = 'flex';
            loadRows();
        });
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                skdpModal.style.display = 'none';
            });
        }
        if (loadBtn) loadBtn.addEventListener('click', loadRows);
        skdpModal.addEventListener('click', (event) => {
            if (event.target === skdpModal) skdpModal.style.display = 'none';
        });
    }

    if (diagBtn && diagModal) {
        const closeBtn = document.getElementById('diagnosa-close');
        const loadBtn = document.getElementById('diagnosa-load');
        const statusEl = document.getElementById('diagnosa-status');
        const listEl = document.getElementById('diagnosa-list');
        const keywordEl = document.getElementById('diagnosa-keyword');

        const renderRows = (rows) => {
            if (!listEl) return;
            if (!Array.isArray(rows) || rows.length === 0) {
                listEl.innerHTML = '<tr><td colspan="3" class="muted">Data diagnosa tidak ditemukan.</td></tr>';
                return;
            }
            listEl.innerHTML = rows.map((row) => {
                const kode = row.kode || '';
                const nama = row.nama || '';
                return `<tr>
                    <td>${kode || '-'}</td>
                    <td>${nama || '-'}</td>
                    <td><button type="button" class="secondary diagnosa-pilih" title="Pilih Diagnosa" aria-label="Pilih Diagnosa" style="display:inline-flex;align-items:center;justify-content:center;width:42px;min-width:42px;height:42px;padding:0;border:1px solid #8bb6cc;background:#d9eef8;color:#0f4560;font-weight:700;white-space:nowrap;" data-kode="${kode}" data-nama="${nama}"><span aria-hidden="true">&#128269;</span></button></td>
                </tr>`;
            }).join('');

            listEl.querySelectorAll('.diagnosa-pilih').forEach((pickBtn) => {
                pickBtn.addEventListener('click', () => {
                    if (diagAwalEl) diagAwalEl.value = pickBtn.dataset.kode || '';
                    if (nmDiagAwalEl) nmDiagAwalEl.value = pickBtn.dataset.nama || '';
                    diagModal.style.display = 'none';
                });
            });
        };

        const loadRows = async () => {
            const keyword = keywordEl ? (keywordEl.value || '').trim() : '';
            if (!keyword) {
                setPillStatus(statusEl, 'Isi kata kunci diagnosa terlebih dahulu.', true);
                if (listEl) listEl.innerHTML = '<tr><td colspan="3" class="muted">Belum ada data.</td></tr>';
                return;
            }

            setPillStatus(statusEl, 'Mengambil referensi diagnosa dari BPJS...');
            if (listEl) listEl.innerHTML = '<tr><td colspan="3" class="muted">Memuat...</td></tr>';

            const url = new URL(window.location.href);
            url.searchParams.set('page', 'vclaim-bpjs');
            url.searchParams.set('ajax', 'diag-list');
            url.searchParams.set('q', keyword);
            try {
                const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                setPillStatus(statusEl, data.message || (data.ok ? 'Data diagnosa ditemukan.' : 'Gagal mengambil data.'), !data.ok);
                renderRows(data.data || []);
            } catch (error) {
                setPillStatus(statusEl, 'Gagal menghubungi server BPJS dari modul web.', true);
                if (listEl) listEl.innerHTML = '<tr><td colspan="3" class="muted">Gagal memuat data.</td></tr>';
            }
        };

        diagBtn.addEventListener('click', () => {
            if (keywordEl && !keywordEl.value) {
                keywordEl.value = (nmDiagAwalEl && nmDiagAwalEl.value) ? nmDiagAwalEl.value : ((diagAwalEl && diagAwalEl.value) ? diagAwalEl.value : '');
            }
            diagModal.style.display = 'flex';
            loadRows();
        });

        if (loadBtn) loadBtn.addEventListener('click', loadRows);
        if (keywordEl) {
            keywordEl.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    loadRows();
                }
            });
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                diagModal.style.display = 'none';
            });
        }
        diagModal.addEventListener('click', (event) => {
            if (event.target === diagModal) diagModal.style.display = 'none';
        });
    }
    if (rujukanBtn && rujukanModal) {
        const closeBtn = document.getElementById('rujukan-close');
        const loadBtn = document.getElementById('rujukan-load');
        const statusEl = document.getElementById('rujukan-status');
        const listEl = document.getElementById('rujukan-list');
        const asalEl = document.getElementById('rujukan-asal');

        const renderRows = (rows) => {
            if (!listEl) return;
            if (!Array.isArray(rows) || rows.length === 0) {
                listEl.innerHTML = '<tr><td colspan="5" class="muted">Data rujukan tidak ditemukan.</td></tr>';
                return;
            }
            listEl.innerHTML = rows.map((row) => {
                const noRujukan = row.no_rujukan || '';
                const tglRujukan = row.tgl_rujukan || '-';
                const ppk = [row.ppk_rujukan || '', row.nm_ppk_rujukan || ''].filter(Boolean).join(' - ') || '-';
                const diag = [row.diag_awal || '', row.nm_diag_awal || ''].filter(Boolean).join(' - ') || '-';
                return `<tr>
                    <td>${noRujukan}</td>
                    <td>${tglRujukan}</td>
                    <td>${ppk}</td>
                    <td>${diag}</td>
                    <td><button type="button" class="secondary rujukan-pilih" title="Pilih Rujukan" aria-label="Pilih Rujukan" style="display:inline-flex;align-items:center;justify-content:center;width:42px;min-width:42px;height:42px;padding:0;border:1px solid #8bb6cc;background:#d9eef8;color:#0f4560;font-weight:700;white-space:nowrap;" data-no-rujukan="${row.no_rujukan || ''}" data-tgl-rujukan="${row.tgl_rujukan || ''}" data-ppk-rujukan="${row.ppk_rujukan || ''}" data-nm-ppk-rujukan="${row.nm_ppk_rujukan || ''}" data-diag-awal="${row.diag_awal || ''}" data-nm-diag-awal="${row.nm_diag_awal || ''}"><span aria-hidden="true">&#128269;</span></button></td>
                </tr>`;
            }).join('');
            listEl.querySelectorAll('.rujukan-pilih').forEach((pickBtn) => {
                pickBtn.addEventListener('click', () => {
                    if (noRujukanEl) noRujukanEl.value = pickBtn.dataset.noRujukan || '';
                    if (tglRujukanEl) tglRujukanEl.value = pickBtn.dataset.tglRujukan || '';
                    if (ppkRujukanEl) ppkRujukanEl.value = pickBtn.dataset.ppkRujukan || '';
                    if (nmPpkRujukanEl) nmPpkRujukanEl.value = pickBtn.dataset.nmPpkRujukan || '';
                    if (diagAwalEl) diagAwalEl.value = pickBtn.dataset.diagAwal || '';
                    if (nmDiagAwalEl) nmDiagAwalEl.value = pickBtn.dataset.nmDiagAwal || '';
                    rujukanModal.style.display = 'none';
                });
            });
        };

        const loadRows = async () => {
            const noKartu = rujukanBtn.dataset.noKartu || '';
            const noRawat = rujukanBtn.dataset.noRawat || '';
            if (asalEl && asalRujukanEl) {
                asalEl.value = asalRujukanEl.value === '2' ? 'rs' : 'faskes1';
            }
            if (!noKartu) {
                setPillStatus(statusEl, 'No kartu pasien belum tersedia.', true);
                return;
            }
            setPillStatus(statusEl, 'Mengambil daftar rujukan dari BPJS...');
            if (listEl) listEl.innerHTML = '<tr><td colspan="5" class="muted">Memuat...</td></tr>';
            const url = new URL(window.location.href);
            url.searchParams.set('page', 'vclaim-bpjs');
            url.searchParams.set('ajax', 'rujukan-list');
            if (noRawat) url.searchParams.set('no_rawat', noRawat);
            url.searchParams.set('no_kartu', noKartu);
            url.searchParams.set('asal', asalEl ? (asalEl.value || 'faskes1') : 'faskes1');
            try {
                const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                setPillStatus(statusEl, data.message || (data.ok ? 'Data rujukan ditemukan.' : 'Gagal mengambil data.'), !data.ok);
                renderRows(data.data || []);
            } catch (error) {
                setPillStatus(statusEl, 'Gagal menghubungi server BPJS dari modul web.', true);
                if (listEl) listEl.innerHTML = '<tr><td colspan="5" class="muted">Gagal memuat data.</td></tr>';
            }
        };

        rujukanBtn.addEventListener('click', () => {
            if (ralanTypeEl && ralanTypeEl.value === 'post_opname' && ranapSepModal) {
                ranapSepModal.style.display = 'flex';
                return;
            }
            rujukanModal.style.display = 'flex';
            loadRows();
        });
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                rujukanModal.style.display = 'none';
            });
        }
        if (loadBtn) loadBtn.addEventListener('click', loadRows);
        rujukanModal.addEventListener('click', (event) => {
            if (event.target === rujukanModal) rujukanModal.style.display = 'none';
        });
    }
});
</script>

<div id="rujukan-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:999;align-items:center;justify-content:center;padding:20px;">
    <div style="width:min(980px,100%);max-height:85vh;overflow:auto;background:#fff;border-radius:14px;border:1px solid #d7e4ed;box-shadow:0 20px 50px rgba(15,23,42,.18);padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
            <div>
                <h3 style="margin:0;">Pilih Rujukan BPJS</h3>
                <div class="muted">Daftar rujukan diambil dari server BPJS sesuai pasien yang sedang dipilih.</div>
            </div>
            <button type="button" id="rujukan-close" class="secondary">Tutup</button>
        </div>
        <div class="vclaim-grid" style="margin-top:14px;">
            <div class="field"><label>Asal Rujukan</label><select id="rujukan-asal"><option value="faskes1">Faskes 1</option><option value="rs">Faskes 2 (RS)</option></select></div>
            <div class="field" style="display:flex;align-items:flex-end;"><button type="button" id="rujukan-load">Ambil Daftar</button></div>
        </div>
        <p id="rujukan-status" class="pill" style="margin-top:12px;display:none;"></p>
        <div style="margin-top:12px;overflow:auto;">
            <table>
                <thead>
                    <tr><th>No Rujukan</th><th>Tgl Rujukan</th><th>PPK Rujukan</th><th>Diagnosa</th><th>Aksi</th></tr>
                </thead>
                <tbody id="rujukan-list">
                    <tr><td colspan="5" class="muted">Belum ada data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div id="diagnosa-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:999;align-items:center;justify-content:center;padding:20px;">
    <div style="width:min(900px,100%);max-height:85vh;overflow:auto;background:#fff;border-radius:14px;border:1px solid #d7e4ed;box-shadow:0 20px 50px rgba(15,23,42,.18);padding:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
            <div>
                <h3 style="margin:0;">Pilih Diagnosa BPJS</h3>
                <div class="muted">Cari berdasarkan kode ICD-10 atau nama diagnosa seperti pada referensi VClaim.</div>
            </div>
            <button type="button" id="diagnosa-close" class="secondary">Tutup</button>
        </div>
        <div class="vclaim-grid" style="margin-top:14px;grid-template-columns:minmax(0,1fr) auto;">
            <div class="field"><label>Kata Kunci</label><input type="text" id="diagnosa-keyword" placeholder="Contoh: A09 atau demam"></div>
            <div class="field" style="display:flex;align-items:flex-end;"><button type="button" id="diagnosa-load">Cari Diagnosa</button></div>
        </div>
        <p id="diagnosa-status" class="pill" style="margin-top:12px;display:none;"></p>
        <div style="margin-top:12px;overflow:auto;">
            <table>
                <thead>
                    <tr><th>Kode</th><th>Nama Diagnosa</th><th>Aksi</th></tr>
                </thead>
                <tbody id="diagnosa-list">
                    <tr><td colspan="3" class="muted">Belum ada data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


