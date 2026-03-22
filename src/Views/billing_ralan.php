<?php $embedMode = trim((string)($_GET['embed'] ?? '')) === '1'; ?>
<?php if (!$embedMode): ?>
<div class="card">
    <h2 style="margin-top:0;"><?= (($mode ?? 'ralan') === 'ranap') ? 'Billing Rawat Inap' : 'Billing Rawat Jalan' ?></h2>
    <?php if (!empty($msgDetail)): ?>
        <p class="pill" style="border-color:<?= ($msg === 'ok') ? '#cde6de' : '#f4b4b4' ?>;background:<?= ($msg === 'ok') ? '#edf8f4' : '#ffecec' ?>;color:<?= ($msg === 'ok') ? '#0f5132' : '#8b1b1b' ?>;">
            <?= htmlspecialchars((string)$msgDetail, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="muted">Gagal mengambil data: <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="get" class="row" style="margin:10px 0 12px;">
        <input type="hidden" name="page" value="billing-ralan">
        <input type="hidden" name="mode" value="<?= htmlspecialchars((string)($mode ?? 'ralan'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="field">
            <label>Dari Tanggal</label>
            <input type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>Sampai Tanggal</label>
            <input type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>Status Bayar</label>
            <select name="status_bayar" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                <option value="semua" <?= ($statusBayar === 'semua') ? 'selected' : '' ?>>Semua</option>
                <option value="belum" <?= ($statusBayar === 'belum') ? 'selected' : '' ?>>Belum Bayar</option>
                <option value="sudah" <?= ($statusBayar === 'sudah') ? 'selected' : '' ?>>Sudah Bayar</option>
            </select>
        </div>
        <div class="field">
            <label>Cari (No Rawat/RM/Nama/No Nota)</label>
            <input type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="ketik kata kunci">
        </div>
        <button type="submit">Filter</button>
    </form>
</div>

<div class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;"><?= (($mode ?? 'ralan') === 'ranap') ? 'Daftar Billing Rawat Inap' : 'Daftar Billing Rawat Jalan' ?></h3>
    <table>
        <thead>
            <tr>
                <th>No Rawat</th>
                <th>Tanggal</th>
                <th>No RM</th>
                <th>Pasien</th>
                <th>Poli</th>
                <th>Dokter</th>
                <th>Jenis Bayar</th>
                <th>No Nota</th>
                <th>Status Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="9" class="muted">Tidak ada data billing rawat jalan</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <a href="?page=billing-ralan&mode=<?= urlencode((string)($mode ?? 'ralan')) ?>&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&status_bayar=<?= urlencode((string)$statusBayar) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>&open=1">
                                <?= htmlspecialchars((string)$r['no_rawat'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars((string)$r['tgl_registrasi'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$r['jam_reg'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['nm_poli'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['nm_dokter'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['png_jawab'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['no_nota'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="pill" style="<?= ((string)$r['status_bayar'] === 'Sudah Bayar') ? 'border-color:#cde6de;background:#edf8f4;color:#0f5132;' : 'border-color:#f4dcb4;background:#fff7e8;color:#8a5a00;' ?>">
                                <?= htmlspecialchars((string)$r['status_bayar'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($detailNoRawat) && !empty($openModal)): ?>
    <?php $closeUrl = '?page=billing-ralan&mode=' . urlencode((string)($mode ?? 'ralan')) . '&from=' . urlencode((string)$from) . '&to=' . urlencode((string)$to) . '&q=' . urlencode((string)$q) . '&status_bayar=' . urlencode((string)$statusBayar); ?>
    <style>
        .modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); z-index: 60; }
        .modal-panel {
            position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%);
            width: min(1240px, 96vw); max-height: 92vh; overflow: auto;
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
            <h3 style="margin:0;">Detail Billing: <?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?></h3>
            <?php if (!$embedMode): ?>
            <a class="modal-close" href="<?= htmlspecialchars($closeUrl, ENT_QUOTES, 'UTF-8') ?>">Tutup</a>
            <?php endif; ?>
        </div>
        <?php if (!empty($detailError)): ?>
            <p class="muted">Gagal mengambil detail: <?= htmlspecialchars((string)$detailError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif (!empty($detail)): ?>
            <?php
            $ppnTotalDisplay = 0.0;
            foreach (($detailPembayaran ?? []) as $dpp) {
                $ppnTotalDisplay += (float)($dpp['besarppn'] ?? 0);
            }
            $totalTagihanDisplay = (float)($komponen['grand_total'] ?? 0);
            $grandTotalFinal = $totalTagihanDisplay + $ppnTotalDisplay;
            ?>
            <div class="row" style="margin-bottom:10px;">
                <span class="pill">Pasien: <?= htmlspecialchars((string)$detail['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">No RM: <?= htmlspecialchars((string)$detail['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Poli: <?= htmlspecialchars((string)$detail['nm_poli'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Dokter: <?= htmlspecialchars((string)$detail['nm_dokter'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">No Nota: <?= htmlspecialchars((string)($detail['no_nota'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Status: <?= htmlspecialchars((string)$detail['status_bayar'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="card" style="margin-top:10px;">
                <div class="muted">Grand Total Billing</div>
                <div class="badge" id="billing-grand-total"><?= number_format($grandTotalFinal, 0, ',', '.') ?></div>
                <div class="muted" style="margin-top:6px;">
                    Tagihan dasar <span id="billing-base-total"><?= number_format($totalTagihanDisplay, 0, ',', '.') ?></span>,
                    PPN <span id="billing-ppn-total"><?= number_format($ppnTotalDisplay, 0, ',', '.') ?></span>,
                    tambahan biaya <?= number_format((float)$komponen['tambahan'], 0, ',', '.') ?>,
                    dan potongan <?= number_format((float)$komponen['pengurangan'], 0, ',', '.') ?>.
                </div>
            </div>

            <div class="row" style="margin-top:12px;">
                <a href="?page=billing-ralan&mode=<?= urlencode((string)($mode ?? 'ralan')) ?>&detail=<?= urlencode((string)$detailNoRawat) ?>&print=1&v=<?= urlencode((string)time()) ?>" target="_blank" style="display:inline-block;padding:10px 12px;border-radius:8px;background:#0f766e;color:#fff;text-decoration:none;">
                    Cetak Rincian
                </a>
                <?php if ((string)$detail['status_bayar'] !== 'Sudah Bayar'): ?>
                    <form method="post">
                        <?php
                        $totalPembayaranExisting = 0.0;
                        foreach (($detailPembayaran ?? []) as $dp0) {
                            $totalPembayaranExisting += (float)($dp0['besar_bayar'] ?? 0) + (float)($dp0['besarppn'] ?? 0);
                        }
                        $sisaPiutangDefault = max(0.0, $grandTotalFinal - $totalPembayaranExisting);
                        ?>
                        <input type="hidden" name="action" value="set_sudah_bayar">
                        <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="status_bayar" value="<?= htmlspecialchars((string)$statusBayar, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($embedMode): ?><input type="hidden" name="embed" value="1"><?php endif; ?>
                        <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                            <div style="flex:1 1 520px;min-width:420px;">
                                <div id="akun_bayar_items">
                                    <div class="akun-row" style="border:1px solid #d7deea;border-radius:8px;padding:10px;margin-bottom:8px;">
                                        <div class="field">
                                            <label>Akun Bayar</label>
                                            <select name="nama_bayar[]" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:260px;" required>
                                                <option value="">- pilih akun bayar -</option>
                                                <?php foreach ($akunBayarList as $ab): ?>
                                                    <option value="<?= htmlspecialchars((string)$ab['nama_bayar'], ENT_QUOTES, 'UTF-8') ?>" data-is-piutang="<?= (stripos((string)$ab['nama_bayar'], 'piutang') !== false) ? '1' : '0' ?>" data-ppn="<?= htmlspecialchars((string)number_format((float)$ab['ppn'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars((string)$ab['nama_bayar'], ENT_QUOTES, 'UTF-8') ?> (PPN <?= number_format((float)$ab['ppn'], 2, ',', '.') ?>%)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label>Nominal Bayar</label>
                                            <input type="number" step="0.01" min="0" name="besar_bayar[]" value="<?= htmlspecialchars((string)number_format((float)$grandTotalFinal, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                        <div class="field">
                                            <label>&nbsp;</label>
                                            <button type="button" class="remove_akun_item" style="background:#b91c1c;">Hapus</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div style="flex:1 1 520px;min-width:420px;">
                                <div id="akun_piutang_items">
                                    <div class="akun-piutang-row" style="border:1px solid #d7deea;border-radius:8px;padding:10px;margin-bottom:8px;">
                                        <div class="field">
                                            <label>Akun Pembayaran Piutang</label>
                                            <select name="nama_piutang[]" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:260px;">
                                                <option value="">- pilih akun piutang -</option>
                                                <?php foreach (($akunPiutangList ?? []) as $api): ?>
                                                    <option value="<?= htmlspecialchars((string)$api['nama_bayar'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars((string)$api['nama_bayar'], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label>Nominal Piutang</label>
                                            <input type="number" step="0.01" min="0" name="total_piutang[]" value="<?= htmlspecialchars((string)number_format((float)$sisaPiutangDefault, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="field">
                                            <label>Kd PJ</label>
                                            <input type="text" name="kd_pj_piutang[]" value="<?= htmlspecialchars((string)($detail['kd_pj'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="3">
                                        </div>
                                        <div class="field">
                                            <label>Tgl Tempo</label>
                                            <input type="date" name="tgl_tempo[]" value="<?= htmlspecialchars((string)date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit">Simpan Nota & Tandai Sudah Bayar</button>
                    </form>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="set_belum_bayar">
                        <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="status_bayar" value="<?= htmlspecialchars((string)$statusBayar, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($embedMode): ?><input type="hidden" name="embed" value="1"><?php endif; ?>
                        <button type="submit" style="background:#b91c1c;">Batalkan Status Bayar</button>
                    </form>
                <?php endif; ?>
            </div>

            <h4 style="margin:14px 0 8px;">Rincian Pembayaran (Akun Bayar)</h4>
            <table>
                <thead>
                    <tr>
                        <th>Akun Bayar</th>
                        <th>Kode Rekening</th>
                        <th class="num">PPN (%)</th>
                        <th class="num">Nominal PPN</th>
                        <th class="num">Nominal Bayar</th>
                        <th class="num">Total Final</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detailPembayaran)): ?>
                        <tr><td colspan="6" class="muted">Belum ada detail akun bayar</td></tr>
                    <?php else: ?>
                        <?php $detailPembayaranTotal = 0.0; ?>
                        <?php foreach ($detailPembayaran as $dp): ?>
                            <?php $detailPembayaranFinal = (float)$dp['besar_bayar'] + (float)$dp['besarppn']; ?>
                            <?php $detailPembayaranTotal += $detailPembayaranFinal; ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$dp['nama_bayar'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($dp['kd_rek'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="num"><?= number_format((float)($dp['ppn'] ?? 0), 2, ',', '.') ?></td>
                                <td class="num"><?= number_format((float)$dp['besarppn'], 0, ',', '.') ?></td>
                                <td class="num"><?= number_format((float)$dp['besar_bayar'], 0, ',', '.') ?></td>
                                <td class="num"><?= number_format($detailPembayaranFinal, 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="5" style="text-align:right;font-weight:700;">Total Pembayaran Final</td>
                            <td class="num" style="font-weight:700;"><?= number_format($detailPembayaranTotal, 0, ',', '.') ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h4 style="margin:14px 0 8px;">Rincian Pembayaran Piutang</h4>
            <table>
                <thead>
                    <tr>
                        <th>Akun Piutang</th>
                        <th>Kode Rekening</th>
                        <th>Kd PJ</th>
                        <th class="num">Total Piutang</th>
                        <th class="num">Sisa Piutang</th>
                        <th>Tgl Tempo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detailPiutang)): ?>
                        <tr><td colspan="6" class="muted">Belum ada detail piutang</td></tr>
                    <?php else: ?>
                        <?php foreach ($detailPiutang as $pi): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$pi['nama_bayar'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($pi['kd_rek'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($pi['kd_pj'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="num"><?= number_format((float)($pi['totalpiutang'] ?? 0), 0, ',', '.') ?></td>
                                <td class="num"><?= number_format((float)($pi['sisapiutang'] ?? 0), 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars((string)($pi['tgltempo'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h4 style="margin:14px 0 8px;">Rincian Obat / BMHP / Gas Medis</h4>
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th class="num">Jumlah</th>
                        <th class="num">Tambahan</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $obatGroups = [
                        'Obat' => [],
                        'BMHP' => [],
                        'Gas Medis' => [],
                    ];
                    foreach (($obatDetail ?? []) as $od) {
                        $kat = (string)($od['kategori'] ?? 'Obat');
                        if (!isset($obatGroups[$kat])) {
                            $kat = 'Obat';
                        }
                        $obatGroups[$kat][] = $od;
                    }
                    ?>
                    <?php if (empty($obatDetail)): ?>
                        <tr><td colspan="5" class="muted">Belum ada rincian obat</td></tr>
                    <?php else: ?>
                        <?php foreach ($obatGroups as $groupName => $groupRows): ?>
                            <tr>
                                <td colspan="5" style="background:#f5f8fb;font-weight:700;"><?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <?php if (empty($groupRows)): ?>
                                <tr><td colspan="5" class="muted">Tidak ada item <?= htmlspecialchars(strtolower($groupName), ENT_QUOTES, 'UTF-8') ?></td></tr>
                            <?php else: ?>
                                <?php $groupTotal = 0.0; ?>
                                <?php foreach ($groupRows as $od): ?>
                                    <?php $groupTotal += (float)$od['total_item']; ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)$od['kode_brng'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)$od['nama_brng'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="num"><?= number_format((float)$od['jml'], 2, ',', '.') ?></td>
                                        <td class="num"><?= number_format((float)$od['tambahan'], 0, ',', '.') ?></td>
                                        <td class="num"><?= number_format((float)$od['total_item'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="4" style="text-align:right;font-weight:700;">Total <?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="num" style="font-weight:700;"><?= number_format($groupTotal, 0, ',', '.') ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h4 style="margin:14px 0 8px;">Rincian Tabel Billing</h4>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No</th>
                        <th>Perawatan</th>
                        <th>Status</th>
                        <th class="num">Biaya</th>
                        <th class="num">Jumlah</th>
                        <th class="num">Tambahan</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($billingRows)): ?>
                        <tr><td colspan="8" class="muted">Belum ada data pada tabel billing</td></tr>
                    <?php else: ?>
                        <?php foreach ($billingRows as $b): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$b['tgl_byr'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)$b['no'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)$b['nm_perawatan'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)$b['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="num"><?= number_format((float)$b['biaya'], 0, ',', '.') ?></td>
                                <td class="num"><?= number_format((float)$b['jumlah'], 2, ',', '.') ?></td>
                                <td class="num"><?= number_format((float)$b['tambahan'], 0, ',', '.') ?></td>
                                <td class="num"><?= number_format((float)$b['totalbiaya'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>
<script>
    (function () {
        var container = document.getElementById('akun_bayar_items');
        var piutangContainer = document.getElementById('akun_piutang_items');
        var baseTotalNode = document.getElementById('billing-base-total');
        var ppnTotalNode = document.getElementById('billing-ppn-total');
        var grandTotalNode = document.getElementById('billing-grand-total');
        if (!container) return;
        var firstRow = container.querySelector('.akun-row');
        var baseTotal = parseFloat('<?= htmlspecialchars((string)number_format((float)$totalTagihanDisplay, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>') || 0;
        function formatIdr(value) {
            return (value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
        }
        function computePaymentState() {
            var nominalBayar = 0;
            var totalPpn = 0;
            container.querySelectorAll('.akun-row').forEach(function (row) {
                var sel = row.querySelector('select[name="nama_bayar[]"]');
                var nom = row.querySelector('input[name="besar_bayar[]"]');
                var nominal = parseFloat(((nom && nom.value) || '').replace(',', '.'));
                if (isNaN(nominal)) nominal = 0;
                nominalBayar += nominal;
                if (sel) {
                    var opt = sel.options[sel.selectedIndex];
                    var ppn = parseFloat((opt && opt.getAttribute('data-ppn')) || '0');
                    if (!isNaN(ppn) && nominal > 0) {
                        totalPpn += (nominal * ppn) / 100;
                    }
                }
            });
            return {
                nominalBayar: nominalBayar,
                totalPpn: totalPpn,
                grandTotal: baseTotal + totalPpn,
                totalBayarFinal: nominalBayar + totalPpn
            };
        }
        function syncSummary() {
            var state = computePaymentState();
            if (ppnTotalNode) {
                ppnTotalNode.textContent = formatIdr(state.totalPpn);
            }
            if (grandTotalNode) {
                grandTotalNode.textContent = formatIdr(state.grandTotal);
            }
            if (baseTotalNode) {
                baseTotalNode.textContent = formatIdr(baseTotal);
            }
            return state;
        }
        function syncSisaPiutang() {
            if (!piutangContainer) return;
            var state = syncSummary();
            var sisa = Math.max(0, state.grandTotal - state.totalBayarFinal);
            var piutangNominal = piutangContainer.querySelector('input[name="total_piutang[]"]');
            if (piutangNominal) {
                piutangNominal.value = sisa.toFixed(2);
            }
        }
        function bindRow(row) {
            if (!row) return;
            var sel = row.querySelector('select[name="nama_bayar[]"]');
            var nom = row.querySelector('input[name="besar_bayar[]"]');
            function syncPiutangMode() {
                if (!sel || !nom) return;
                var opt = sel.options[sel.selectedIndex];
                var isPiutang = opt && opt.getAttribute('data-is-piutang') === '1';
                var defaultTotal = '<?= htmlspecialchars((string)number_format((float)$totalTagihanDisplay, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>';
                if (isPiutang) {
                    nom.value = defaultTotal;
                    nom.readOnly = true;
                } else {
                    nom.readOnly = false;
                }
                syncSisaPiutang();
            }
            if (sel) {
                sel.addEventListener('change', syncPiutangMode);
                syncPiutangMode();
            }
            if (nom) {
                nom.addEventListener('input', syncSisaPiutang);
            }
            var rm = row.querySelector('.remove_akun_item');
            if (rm) {
                rm.addEventListener('click', function () {
                    var rows = container.querySelectorAll('.akun-row');
                    if (rows.length <= 1) {
                        if (sel) sel.value = '';
                        if (nom) nom.value = '';
                        if (nom) nom.readOnly = false;
                        syncSisaPiutang();
                        return;
                    }
                    row.remove();
                    syncSisaPiutang();
                });
            }
        }
        bindRow(firstRow);
        syncSummary();
        syncSisaPiutang();
    })();
</script>
