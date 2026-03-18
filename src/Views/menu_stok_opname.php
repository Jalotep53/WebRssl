<div class="card">
    <h2 style="margin-top:0;">Stok Opname</h2>
    <p class="muted">CRUD stok opname dengan perhitungan selisih otomatis.</p>

    <?php if (!empty($msg)): ?>
        <p class="pill" style="margin:8px 0;border-color:<?= ($msgType ?? 'ok') === 'error' ? '#f1c3c3' : '#b7e3d1' ?>;background:<?= ($msgType ?? 'ok') === 'error' ? '#fff1f1' : '#eafaf2' ?>;color:<?= ($msgType ?? 'ok') === 'error' ? '#8e2424' : '#145a32' ?>;">
            <?= htmlspecialchars((string)$msg, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if (empty($opnameTableReady)): ?>
        <p class="pill" style="border-color:#f4d7b4;background:#fff7ec;color:#8a4b12;">Tabel opname belum ditemukan di database aktif.</p>
    <?php else: ?>
        <form method="get" class="row" style="margin-top:10px;">
            <input type="hidden" name="page" value="menu-stok-opname">
            <div class="field">
                <label>Cari (Kode/Nama/Batch/Faktur)</label>
                <input type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="contoh: BR0001 / batch / faktur">
            </div>
            <button type="submit">Cari</button>
        </form>

        <?php if (!empty($error)): ?>
            <p class="muted">Gagal mengambil data: <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php $isEdit = !empty($editRow); ?>
        <form method="post" class="row" style="margin-top:14px;padding:10px;border:1px solid #d7e6ef;border-radius:10px;">
            <input type="hidden" name="action" value="<?= $isEdit ? 'opname_update' : 'opname_create' ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="old_kode_brng" value="<?= htmlspecialchars((string)$editRow['kode_brng'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="old_tanggal" value="<?= htmlspecialchars((string)$editRow['tanggal'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="old_kd_bangsal" value="<?= htmlspecialchars((string)$editRow['kd_bangsal'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="old_no_batch" value="<?= htmlspecialchars((string)$editRow['no_batch'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="old_no_faktur" value="<?= htmlspecialchars((string)$editRow['no_faktur'], ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <div class="field"><label>Kode Barang</label><input type="text" name="kode_brng" required value="<?= htmlspecialchars((string)($editRow['kode_brng'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label>Tanggal</label><input type="date" name="tanggal" required value="<?= htmlspecialchars((string)($editRow['tanggal'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label>Kode Bangsal</label><input type="text" name="kd_bangsal" required value="<?= htmlspecialchars((string)($editRow['kd_bangsal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label>No Batch</label><input type="text" name="no_batch" value="<?= htmlspecialchars((string)($editRow['no_batch'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label>No Faktur</label><input type="text" name="no_faktur" value="<?= htmlspecialchars((string)($editRow['no_faktur'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label>Harga Beli</label><input type="number" step="0.01" name="h_beli" value="<?= htmlspecialchars((string)($editRow['h_beli'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label>Stok Sistem</label><input type="number" step="0.01" name="stok" value="<?= htmlspecialchars((string)($editRow['stok'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label>Stok Real</label><input type="number" step="0.01" name="real" value="<?= htmlspecialchars((string)($editRow['real'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label>Keterangan</label><input type="text" name="keterangan" value="<?= htmlspecialchars((string)($editRow['keterangan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"></div>
            <button type="submit"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Opname' ?></button>
            <?php if ($isEdit): ?>
                <a href="?page=menu-stok-opname" style="display:inline-block;padding:10px 14px;border-radius:8px;border:1px solid #d7e6ef;background:#fff;color:#1d2b36;text-decoration:none;">Batal Edit</a>
            <?php endif; ?>
        </form>

        <div style="overflow:auto;margin-top:10px;">
            <table>
                <thead>
                <tr>
                    <th>Kode</th><th>Nama Barang</th><th>Tanggal</th><th>Bangsal</th><th>Batch</th><th>Faktur</th>
                    <th class="num">Stok</th><th class="num">Real</th><th class="num">Selisih</th><th class="num">Lebih</th><th>Keterangan</th><th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="12" class="muted">Tidak ada data opname.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($r['kode_brng'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['nama_brng'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['tanggal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['kd_bangsal'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['no_batch'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['no_faktur'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="num"><?= number_format((float)($r['stok'] ?? 0), 2, ',', '.') ?></td>
                            <td class="num"><?= number_format((float)($r['real'] ?? 0), 2, ',', '.') ?></td>
                            <td class="num"><?= number_format((float)($r['selisih'] ?? 0), 2, ',', '.') ?></td>
                            <td class="num"><?= number_format((float)($r['lebih'] ?? 0), 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars((string)($r['keterangan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="white-space:nowrap;">
                                <a href="?page=menu-stok-opname&ek=<?= urlencode((string)$r['kode_brng']) ?>&et=<?= urlencode((string)$r['tanggal']) ?>&eb=<?= urlencode((string)$r['kd_bangsal']) ?>&enb=<?= urlencode((string)$r['no_batch']) ?>&enf=<?= urlencode((string)$r['no_faktur']) ?>&q=<?= urlencode((string)$q) ?>" style="display:inline-block;padding:6px 10px;border:1px solid #d7e6ef;border-radius:8px;background:#fff;color:#1d2b36;text-decoration:none;">Edit</a>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Hapus data opname ini?');">
                                    <input type="hidden" name="action" value="opname_delete">
                                    <input type="hidden" name="kode_brng" value="<?= htmlspecialchars((string)$r['kode_brng'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="tanggal" value="<?= htmlspecialchars((string)$r['tanggal'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="kd_bangsal" value="<?= htmlspecialchars((string)$r['kd_bangsal'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="no_batch" value="<?= htmlspecialchars((string)$r['no_batch'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="no_faktur" value="<?= htmlspecialchars((string)$r['no_faktur'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" style="padding:6px 10px;background:#b53a3a;">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
