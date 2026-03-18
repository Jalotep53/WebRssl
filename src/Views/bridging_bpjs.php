<div class="card">
    <h2 style="margin-top:0;">Bridging BPJS - Daftar SEP</h2>
    <style>
        .bpjs-check-status {
            margin: 0 0 10px;
            border-radius: 8px;
            padding: 10px 12px;
            border: 1px solid #cfe0eb;
            background: #f5fbff;
            color: #15435a;
            font-size: 13px;
        }
        .bpjs-check-status.error {
            border-color: #f2b8b8;
            background: #fff2f2;
            color: #8f1d1d;
        }
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
            padding: 8px; display: none; grid-template-columns: repeat(4, 32px); gap: 8px;
            width: max-content;
        }
        .aksi-wrap.open .aksi-menu { display: grid; }
        .icon-action-btn {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid #d7e4ed;
            background: #f7fbfd; color: #1f4f69; text-decoration: none; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; position: relative;
            padding: 0;
        }
        .icon-action-btn:hover { background: #ebf4fa; }
        .icon-action-btn::after {
            content: attr(data-label);
            position: absolute; left: 50%; transform: translateX(-50%);
            bottom: calc(100% + 6px);
            background: #0f2f41; color: #fff; border-radius: 6px;
            padding: 4px 8px; font-size: 11px; white-space: nowrap;
            opacity: 0; pointer-events: none; transition: opacity .12s ease;
            z-index: 20;
        }
        .icon-action-btn:hover::after, .icon-action-btn:focus::after { opacity: 1; }
        .icon-action-btn svg { width: 16px; height: 16px; display: block; }
    </style>

    <?php if (!empty($bpjsCheck)): ?>
        <div class="bpjs-check-status <?= empty($bpjsCheck['ok']) ? 'error' : '' ?>">
            Cek SEP <?= htmlspecialchars((string)($bpjsCheck['target_sep'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:
            <?= htmlspecialchars((string)($bpjsCheck['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($bpjsCheck['meta_code']) || !empty($bpjsCheck['meta_message'])): ?>
                (code: <?= htmlspecialchars((string)($bpjsCheck['meta_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>,
                message: <?= htmlspecialchars((string)($bpjsCheck['meta_message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>)
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p class="muted">Gagal mengambil data SEP: <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="get" class="row" style="margin:10px 0 12px;">
        <input type="hidden" name="page" value="bridging-bpjs">
        <?php if (!empty($autoFromAction)): ?>
            <input type="hidden" name="auto" value="1">
        <?php endif; ?>
        <div class="field">
            <label>Dari Tanggal SEP</label>
            <input type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>Sampai Tanggal SEP</label>
            <input type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>No Rawat</label>
            <input type="text" name="no_rawat" value="<?= htmlspecialchars((string)$noRawat, ENT_QUOTES, 'UTF-8') ?>" placeholder="contoh: 2026/03/11/000026">
        </div>
        <div class="field">
            <label>No SEP</label>
            <input type="text" name="no_sep" value="<?= htmlspecialchars((string)$noSep, ENT_QUOTES, 'UTF-8') ?>" placeholder="cari no SEP">
        </div>
        <div class="field">
            <label>Jenis Rawat</label>
            <select name="jenis_rawat">
                <option value="">Semua</option>
                <option value="2" <?= ((string)($jenisRawat ?? '') === '2') ? 'selected' : '' ?>>Rawat Jalan</option>
                <option value="1" <?= ((string)($jenisRawat ?? '') === '1') ? 'selected' : '' ?>>Rawat Inap</option>
            </select>
        </div>
        <button type="submit">Filter</button>
    </form>
</div>

<div class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;">Daftar SEP Rawat Jalan & Rawat Inap</h3>
    <table>
        <thead>
            <tr>
                <th>Tgl SEP</th>
                <th>No SEP</th>
                <th>No Rawat</th>
                <th>Jenis Layanan</th>
                <th>Pasien</th>
                <th>No Kartu</th>
                <th>Diagnosa</th>
                <th>Poli Tujuan</th>
                <th>Penjamin</th>
                <th>Status Bayar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="11" class="muted">Tidak ada data SEP</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $rawatPage = ((string)($r['status_lanjut'] ?? '') === 'Ranap') ? 'rawatinap' : 'rawatjalan';
                    $params = [
                        'page' => 'bridging-bpjs',
                        'from' => (string)$from,
                        'to' => (string)$to,
                        'no_rawat' => (string)$noRawat,
                        'no_sep' => (string)$noSep,
                        'jenis_rawat' => (string)($jenisRawat ?? ''),
                        'action' => 'cek-sep',
                        'target_sep' => (string)($r['no_sep'] ?? ''),
                    ];
                    if (!empty($autoFromAction)) {
                        $params['auto'] = '1';
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($r['tglsep'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['no_sep'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['no_rawat'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                            $jenis = (string)($r['jnspelayanan'] ?? '');
                            echo htmlspecialchars($jenis === '1' ? 'Rawat Inap' : ($jenis === '2' ? 'Rawat Jalan' : '-'), ENT_QUOTES, 'UTF-8');
                            ?>
                        </td>
                        <td><?= htmlspecialchars((string)($r['nama_pasien'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['no_kartu'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['nmdiagnosaawal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['nmpolitujuan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['png_jawab'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['status_bayar'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div class="aksi-wrap">
                                <button type="button" class="aksi-trigger" aria-label="Aksi Bridging BPJS" title="Aksi">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="5" r="1.6"></circle><circle cx="12" cy="12" r="1.6"></circle><circle cx="12" cy="19" r="1.6"></circle>
                                    </svg>
                                </button>
                                <div class="aksi-menu">
                                    <a class="icon-action-btn" data-label="Cetak SEP"
                                       href="?page=sep-print&no_sep=<?= urlencode((string)($r['no_sep'] ?? '')) ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M6 9V4h12v5"></path><rect x="6" y="13" width="12" height="7"></rect><rect x="4" y="9" width="16" height="6"></rect>
                                        </svg>
                                    </a>
                                    <a class="icon-action-btn" data-label="Buat Surat Kontrol"
                                       href="?page=vclaim-bpjs&no_rawat=<?= urlencode((string)($r['no_rawat'] ?? '')) ?>&no_sep=<?= urlencode((string)($r['no_sep'] ?? '')) ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M6 4h12v16H6z"></path><path d="M9 9h6"></path><path d="M9 13h6"></path>
                                        </svg>
                                    </a>
                                    <a class="icon-action-btn" data-label="Buat Rujukan"
                                       href="?page=vclaim-bpjs&no_rawat=<?= urlencode((string)($r['no_rawat'] ?? '')) ?>&no_sep=<?= urlencode((string)($r['no_sep'] ?? '')) ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 12h13"></path><path d="M13 7l5 5-5 5"></path>
                                        </svg>
                                    </a>
                                    <a class="icon-action-btn" data-label="Cek SEP ke BPJS"
                                       href="?<?= htmlspecialchars(http_build_query($params), ENT_QUOTES, 'UTF-8') ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 12a8 8 0 1 1-2.34-5.66"></path>
                                            <polyline points="20 4 20 10 14 10"></polyline>
                                        </svg>
                                    </a>
                                    <a class="icon-action-btn" data-label="Buka Data Rawat"
                                       href="?page=<?= htmlspecialchars($rawatPage, ENT_QUOTES, 'UTF-8') ?>&q=<?= urlencode((string)($r['no_rawat'] ?? '')) ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 12h18"></path><path d="M12 3v18"></path>
                                        </svg>
                                    </a>
                                    <button type="button" class="icon-action-btn js-copy-sep" data-label="Copy No SEP"
                                            data-sep="<?= htmlspecialchars((string)($r['no_sep'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="9" y="9" width="11" height="11" rx="2"></rect>
                                            <rect x="4" y="4" width="11" height="11" rx="2"></rect>
                                        </svg>
                                    </button>
                                    <a class="icon-action-btn" data-label="Buka VClaim BPJS"
                                       href="?page=vclaim-bpjs">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="9"></circle><path d="M8 12h8"></path><path d="M12 8v8"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    (function () {
        document.querySelectorAll('.aksi-trigger').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var wrap = btn.closest('.aksi-wrap');
                if (!wrap) return;
                document.querySelectorAll('.aksi-wrap.open').forEach(function (w) {
                    if (w !== wrap) w.classList.remove('open');
                });
                wrap.classList.toggle('open');
            });
        });

        document.addEventListener('click', function () {
            document.querySelectorAll('.aksi-wrap.open').forEach(function (w) { w.classList.remove('open'); });
        });

        document.querySelectorAll('.js-copy-sep').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-sep') || '';
                if (!text) return;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        btn.setAttribute('data-label', 'No SEP tersalin');
                        setTimeout(function () { btn.setAttribute('data-label', 'Copy No SEP'); }, 1100);
                    });
                    return;
                }
                var temp = document.createElement('input');
                temp.value = text;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                btn.setAttribute('data-label', 'No SEP tersalin');
                setTimeout(function () { btn.setAttribute('data-label', 'Copy No SEP'); }, 1100);
            });
        });
    })();
</script>
