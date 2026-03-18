<div class="card">
    <h2 style="margin-top:0;">Data Master Obat</h2>

    <?php if (!empty($msg)): ?>
        <p class="pill" style="margin:8px 0;border-color:<?= ($msgType ?? 'ok') === 'error' ? '#f1c3c3' : '#b7e3d1' ?>;background:<?= ($msgType ?? 'ok') === 'error' ? '#fff1f1' : '#eafaf2' ?>;color:<?= ($msgType ?? 'ok') === 'error' ? '#8e2424' : '#145a32' ?>;">
            <?= htmlspecialchars((string)$msg, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="get" class="row" style="margin-top:10px;">
        <input type="hidden" name="page" value="menu-obat">
        <div class="field">
            <label>Cari (Kode / Nama Obat)</label>
            <input type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="contoh: BR0001 / Paracetamol">
        </div>
        <button type="submit">Cari</button>
    </form>

    <?php if (!empty($error)): ?>
        <p class="muted">Gagal mengambil data: <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php $isEdit = !empty($editRow); ?>
    <form method="post" class="row" style="margin-top:14px;padding:10px;border:1px solid #d7e6ef;border-radius:10px;">
        <input type="hidden" name="action" value="<?= $isEdit ? 'obat_update' : 'obat_create' ?>">
        <div class="field"><label>Kode Obat</label><input type="text" name="kode_brng" required value="<?= htmlspecialchars((string)($editRow['kode_brng'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isEdit ? 'readonly' : '' ?>></div>
        <div class="field" style="min-width:380px;"><label>Nama Obat</label><input type="text" name="nama_brng" required value="<?= htmlspecialchars((string)($editRow['nama_brng'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="min-width:380px;"></div>
        <div class="field"><label>Satuan</label><input type="text" name="kode_sat" required value="<?= htmlspecialchars((string)($editRow['kode_sat'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>Harga Beli</label><input type="number" step="0.01" name="h_beli" value="<?= htmlspecialchars((string)($editRow['h_beli'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>Harga Ralan</label><input type="number" step="0.01" name="ralan" value="<?= htmlspecialchars((string)($editRow['ralan'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>Stok Minimal</label><input type="number" step="0.01" name="stokminimal" value="<?= htmlspecialchars((string)($editRow['stokminimal'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>Letak</label><input type="text" name="letak_barang" value="<?= htmlspecialchars((string)($editRow['letak_barang'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>Status</label><select name="status" style="border:1px solid #d7e6ef;border-radius:8px;padding:9px 10px;min-width:120px;"><option value="1" <?= (($editRow['status'] ?? '1') === '1') ? 'selected' : '' ?>>Aktif</option><option value="0" <?= (($editRow['status'] ?? '1') === '0') ? 'selected' : '' ?>>Nonaktif</option></select></div>
        <button type="submit"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Obat' ?></button>
        <?php if ($isEdit): ?>
            <a href="?page=menu-obat" style="display:inline-block;padding:10px 14px;border-radius:8px;border:1px solid #d7e6ef;background:#fff;color:#1d2b36;text-decoration:none;">Batal Edit</a>
        <?php endif; ?>
    </form>

    <table style="margin-top:10px;">
        <thead>
        <tr>
            <th>Kode</th><th>Nama Obat</th><th>Satuan</th><th class="num">Harga Beli</th><th class="num">Harga Ralan</th><th class="num">Total Stok</th><th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($rows ?? []) as $r): ?>
            <tr>
                <td><?= htmlspecialchars((string)($r['kode_brng'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($r['nama_brng'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($r['kode_sat'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="num"><?= number_format((float)($r['h_beli'] ?? 0), 0, ',', '.') ?></td>
                <td class="num"><?= number_format((float)($r['ralan'] ?? 0), 0, ',', '.') ?></td>
                <td class="num"><?= number_format((float)($r['total_stok'] ?? 0), 0, ',', '.') ?></td>
                <td style="white-space:nowrap;">
                    <a href="?page=menu-obat&edit=<?= urlencode((string)$r['kode_brng']) ?>&q=<?= urlencode((string)$q) ?>" style="display:inline-block;padding:6px 10px;border:1px solid #d7e6ef;border-radius:8px;background:#fff;color:#1d2b36;text-decoration:none;">Edit</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Hapus obat ini?');">
                        <input type="hidden" name="action" value="obat_delete">
                        <input type="hidden" name="kode_brng" value="<?= htmlspecialchars((string)$r['kode_brng'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" style="padding:6px 10px;background:#b53a3a;">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
