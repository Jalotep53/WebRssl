<div class="card">
    <h2 style="margin-top:0;">Modul Farmasi - Daftar Resep</h2>
    <?php if (!empty($msgDetail)): ?>
        <p class="pill" style="border-color:<?= ($msg === 'ok') ? '#cde6de' : '#f4b4b4' ?>;background:<?= ($msg === 'ok') ? '#edf8f4' : '#ffecec' ?>;color:<?= ($msg === 'ok') ? '#0f5132' : '#8b1b1b' ?>;">
            <?= htmlspecialchars((string)$msgDetail, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="muted">Gagal mengambil data: <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="get" class="row" style="margin:10px 0 12px;">
        <input type="hidden" name="page" value="farmasi">
        <div class="field">
            <label>Dari Tanggal</label>
            <input type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>Sampai Tanggal</label>
            <input type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>Status</label>
            <select name="status" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                <option value="belum" <?= ($status === 'belum') ? 'selected' : '' ?>>Belum Terlayani</option>
                <option value="sudah" <?= ($status === 'sudah') ? 'selected' : '' ?>>Sudah Terlayani</option>
                <option value="semua" <?= ($status === 'semua') ? 'selected' : '' ?>>Semua</option>
            </select>
        </div>
        <div class="field">
            <label>Status Rawat</label>
            <select name="stts_rawat" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                <option value="semua" <?= ($sttsRawat === 'semua') ? 'selected' : '' ?>>Semua</option>
                <option value="ralan" <?= ($sttsRawat === 'ralan') ? 'selected' : '' ?>>Rawat Jalan</option>
                <option value="ranap" <?= ($sttsRawat === 'ranap') ? 'selected' : '' ?>>Rawat Inap</option>
            </select>
        </div>
        <div class="field">
            <label>Cari (No Resep/No Rawat/RM/Pasien/Dokter)</label>
            <input type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="ketik minimal 3 huruf">
        </div>
        <button type="submit">Filter</button>
    </form>
</div>

<div class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;">Daftar Permintaan Resep</h3>
    <table>
        <thead>
            <tr>
                <th>No Resep</th>
                <th>Tgl/Jam Resep</th>
                <th>No Rawat</th>
                <th>No RM</th>
                <th>Pasien</th>
                <th>Dokter</th>
                <th>Poli/Unit</th>
                <th>Jenis Bayar</th>
                <th>Status Rawat</th>
                <th>Status Layanan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="10" class="muted">Tidak ada data resep</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <a href="?page=farmasi&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&status=<?= urlencode((string)$status) ?>&stts_rawat=<?= urlencode((string)$sttsRawat) ?>&detail=<?= urlencode((string)$r['no_resep']) ?>&open=1">
                                <?= htmlspecialchars((string)$r['no_resep'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars((string)$r['tgl_peresepan'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$r['jam_peresepan'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['no_rawat'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['nm_dokter'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['nm_poli'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['png_jawab'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(strtoupper((string)$r['status']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="pill" style="<?= ((string)$r['status_layanan'] === 'Sudah Terlayani') ? 'border-color:#cde6de;background:#edf8f4;color:#0f5132;' : 'border-color:#f4dcb4;background:#fff7e8;color:#8a5a00;' ?>">
                                <?= htmlspecialchars((string)$r['status_layanan'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($detailNoResep) && !empty($openModal)): ?>
    <?php $closeUrl = '?page=farmasi&from=' . urlencode((string)$from) . '&to=' . urlencode((string)$to) . '&q=' . urlencode((string)$q) . '&status=' . urlencode((string)$status) . '&stts_rawat=' . urlencode((string)$sttsRawat); ?>
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
    <a class="modal-backdrop" href="<?= htmlspecialchars($closeUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Tutup modal"></a>
    <div class="modal-panel">
        <div class="modal-head">
            <h3 style="margin:0;">Detail Resep: <?= htmlspecialchars((string)$detailNoResep, ENT_QUOTES, 'UTF-8') ?></h3>
            <a class="modal-close" href="<?= htmlspecialchars($closeUrl, ENT_QUOTES, 'UTF-8') ?>">Tutup</a>
        </div>
        <?php if (!empty($detailError)): ?>
            <p class="muted">Gagal mengambil detail: <?= htmlspecialchars((string)$detailError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif (!empty($detail)): ?>
            <div class="row" style="margin-bottom:10px;">
                <span class="pill">No Rawat: <?= htmlspecialchars((string)$detail['no_rawat'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">No RM: <?= htmlspecialchars((string)$detail['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Pasien: <?= htmlspecialchars((string)$detail['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Dokter: <?= htmlspecialchars((string)$detail['nm_dokter'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Poli: <?= htmlspecialchars((string)$detail['nm_poli'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="row" style="margin-bottom:12px;">
                <span class="pill">Status: <?= htmlspecialchars((string)$detail['status_layanan'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Validasi: <?= htmlspecialchars((string)($detail['tgl_validasi'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)($detail['jam_validasi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Penyerahan: <?= htmlspecialchars((string)($detail['tgl_penyerahan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)($detail['jam_penyerahan'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <?php if ((string)$detail['status_layanan'] !== 'Sudah Terlayani'): ?>
                <form method="post" style="margin-bottom:14px;">
                    <input type="hidden" name="action" value="validasi_resep">
                    <input type="hidden" name="no_resep" value="<?= htmlspecialchars((string)$detailNoResep, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="status" value="<?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="stts_rawat" value="<?= htmlspecialchars((string)$sttsRawat, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit">Validasi Resep</button>
                </form>
            <?php endif; ?>

            <h4 style="margin:10px 0 8px;">Item Resep</h4>
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th class="num">Jumlah</th>
                        <th>Satuan</th>
                        <th>Aturan Pakai</th>
                        <th class="num">Stok</th>
                        <th class="num">Harga Ralan</th>
                        <th class="num">Subtotal</th>
                        <?php if ((string)($detail['status_layanan'] ?? '') !== 'Sudah Terlayani'): ?>
                            <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detailItems)): ?>
                        <tr><td colspan="<?= ((string)($detail['status_layanan'] ?? '') !== 'Sudah Terlayani') ? '10' : '9' ?>" class="muted">Tidak ada item resep</td></tr>
                    <?php else: ?>
                        <?php $totalResep = 0.0; ?>
                        <?php foreach ($detailItems as $it): ?>
                            <?php $subtotalItem = (float)$it['jml'] * (float)$it['ralan']; ?>
                            <?php $totalResep += $subtotalItem; ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$it['kode_brng'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)$it['nama_brng'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)$it['kategori'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="num"><?= number_format((float)$it['jml'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars((string)$it['kode_sat'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)$it['aturan_pakai'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="num"><?= number_format((float)$it['stok_total'], 2, ',', '.') ?></td>
                                <td class="num"><?= number_format((float)$it['ralan'], 0, ',', '.') ?></td>
                                <td class="num"><?= number_format($subtotalItem, 0, ',', '.') ?></td>
                                <?php if ((string)$detail['status_layanan'] !== 'Sudah Terlayani'): ?>
                                    <td>
                                        <form method="post" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:6px;">
                                            <input type="hidden" name="action" value="update_resep_item">
                                            <input type="hidden" name="no_resep" value="<?= htmlspecialchars((string)$detailNoResep, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="kode_brng" value="<?= htmlspecialchars((string)$it['kode_brng'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="status" value="<?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="stts_rawat" value="<?= htmlspecialchars((string)$sttsRawat, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="number" step="0.01" min="0.01" name="jml" value="<?= htmlspecialchars((string)number_format((float)$it['jml'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" style="width:88px;">
                                            <input type="text" name="aturan_pakai" value="<?= htmlspecialchars((string)$it['aturan_pakai'], ENT_QUOTES, 'UTF-8') ?>" style="width:160px;">
                                            <button type="submit" style="padding:6px 10px;">Ubah</button>
                                        </form>
                                        <form method="post" style="margin-top:6px;" onsubmit="return confirm('Hapus item resep ini?');">
                                            <input type="hidden" name="action" value="delete_resep_item">
                                            <input type="hidden" name="no_resep" value="<?= htmlspecialchars((string)$detailNoResep, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="kode_brng" value="<?= htmlspecialchars((string)$it['kode_brng'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="status" value="<?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="stts_rawat" value="<?= htmlspecialchars((string)$sttsRawat, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" style="background:#b91c1c;padding:6px 10px;">Hapus</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="<?= ((string)($detail['status_layanan'] ?? '') !== 'Sudah Terlayani') ? '8' : '7' ?>" style="text-align:right;font-weight:700;">Total Resep</td>
                            <td class="num" style="font-weight:700;"><?= number_format($totalResep, 0, ',', '.') ?></td>
                            <?php if ((string)($detail['status_layanan'] ?? '') !== 'Sudah Terlayani'): ?>
                                <td></td>
                            <?php endif; ?>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>
