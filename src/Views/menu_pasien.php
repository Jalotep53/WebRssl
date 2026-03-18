<div class="card">
    <h2 style="margin-top:0;">Data Master Pasien</h2>

    <?php if (!empty($msg)): ?>
        <p class="pill" style="margin:8px 0;border-color:<?= ($msgType ?? 'ok') === 'error' ? '#f1c3c3' : '#b7e3d1' ?>;background:<?= ($msgType ?? 'ok') === 'error' ? '#fff1f1' : '#eafaf2' ?>;color:<?= ($msgType ?? 'ok') === 'error' ? '#8e2424' : '#145a32' ?>;">
            <?= htmlspecialchars((string)$msg, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="get" class="row" style="margin-top:10px;">
        <input type="hidden" name="page" value="menu-pasien">
        <div class="field">
            <label>Cari (No RM / Nama / NIK / No Peserta)</label>
            <input type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="contoh: 000123 / BUDI / 3174...">
        </div>
        <button type="submit">Cari</button>
    </form>

    <?php if (!empty($error)): ?>
        <p class="muted">Gagal mengambil data: <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php $isEdit = !empty($editRow); ?>
    <form method="post" class="row" style="margin-top:14px;padding:10px;border:1px solid #d7e6ef;border-radius:10px;">
        <input type="hidden" name="action" value="<?= $isEdit ? 'pasien_update' : 'pasien_create' ?>">
        <div class="field"><label>No RM</label><input type="text" name="no_rkm_medis" required value="<?= htmlspecialchars((string)($editRow['no_rkm_medis'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isEdit ? 'readonly' : '' ?>></div>
        <div class="field"><label>Nama Pasien</label><input type="text" name="nm_pasien" required value="<?= htmlspecialchars((string)($editRow['nm_pasien'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>JK</label><select name="jk" style="border:1px solid #d7e6ef;border-radius:8px;padding:9px 10px;min-width:120px;"><option value="L" <?= (($editRow['jk'] ?? 'L') === 'L') ? 'selected' : '' ?>>L</option><option value="P" <?= (($editRow['jk'] ?? 'L') === 'P') ? 'selected' : '' ?>>P</option></select></div>
        <div class="field"><label>Tgl Lahir</label><input type="date" name="tgl_lahir" required value="<?= htmlspecialchars((string)($editRow['tgl_lahir'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>Tempat Lahir</label><input type="text" name="tmp_lahir" value="<?= htmlspecialchars((string)($editRow['tmp_lahir'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>NIK/KTP</label><input type="text" name="no_ktp" value="<?= htmlspecialchars((string)($editRow['no_ktp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>No Peserta</label><input type="text" name="no_peserta" value="<?= htmlspecialchars((string)($editRow['no_peserta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>No Telp</label><input type="text" name="no_tlp" value="<?= htmlspecialchars((string)($editRow['no_tlp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>Ibu Kandung</label><input type="text" name="nm_ibu" value="<?= htmlspecialchars((string)($editRow['nm_ibu'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="field"><label>Cara Bayar</label>
            <select name="kd_pj" required style="border:1px solid #d7e6ef;border-radius:8px;padding:9px 10px;min-width:240px;">
                <option value="">Pilih Cara Bayar</option>
                <?php foreach (($penjab ?? []) as $pj): ?>
                    <option value="<?= htmlspecialchars((string)$pj['kd_pj'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string)($editRow['kd_pj'] ?? '') === (string)$pj['kd_pj']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$pj['kd_pj'] . ' - ' . (string)$pj['png_jawab'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="min-width:420px;"><label>Alamat</label><input type="text" name="alamat" value="<?= htmlspecialchars((string)($editRow['alamat'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>" style="min-width:420px;"></div>
        <button type="submit"><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Pasien' ?></button>
        <?php if ($isEdit): ?>
            <a href="?page=menu-pasien" style="display:inline-block;padding:10px 14px;border-radius:8px;border:1px solid #d7e6ef;background:#fff;color:#1d2b36;text-decoration:none;">Batal Edit</a>
        <?php endif; ?>
    </form>

    <table style="margin-top:10px;">
        <thead>
        <tr>
            <th>No RM</th><th>Nama</th><th>JK</th><th>Tgl Lahir</th><th>NIK/KTP</th><th>No Peserta</th><th>No Telp</th><th>Alamat</th><th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($rows ?? []) as $r): ?>
            <tr>
                <td><?= htmlspecialchars((string)($r['no_rkm_medis'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($r['nm_pasien'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($r['jk'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($r['tgl_lahir'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($r['no_ktp'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($r['no_peserta'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($r['no_tlp'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($r['alamat'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td style="white-space:nowrap;">
                    <a href="?page=menu-pasien&edit=<?= urlencode((string)$r['no_rkm_medis']) ?>&q=<?= urlencode((string)$q) ?>" style="display:inline-block;padding:6px 10px;border:1px solid #d7e6ef;border-radius:8px;background:#fff;color:#1d2b36;text-decoration:none;">Edit</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Hapus pasien ini?');">
                        <input type="hidden" name="action" value="pasien_delete">
                        <input type="hidden" name="no_rkm_medis" value="<?= htmlspecialchars((string)$r['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" style="padding:6px 10px;background:#b53a3a;">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
