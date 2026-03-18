<?php if (!$embedMode): ?>
<div class="card">
    <h2 style="margin-top:0;">Berkas Rekam Medik</h2>
<?php else: ?>
<div class="card" style="margin:0;">
<?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="pill" style="border-color:#f4b4b4;background:#ffecec;color:#8b1b1b;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php elseif (!empty($visit)): ?>
        <?php
        $modeLabel = (strtoupper((string)($visit['status_lanjut'] ?? '')) === 'RANAP') ? 'Rawat Inap' : 'Rawat Jalan';
        $filledCount = 0;
        foreach ($forms as $form) {
            if (!empty($form['exists'])) {
                $filledCount++;
            }
        }
        ?>
        <div class="row" style="margin-bottom:10px;">
            <span class="pill">No Rawat: <?= htmlspecialchars((string)$visit['no_rawat'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="pill">No RM: <?= htmlspecialchars((string)$visit['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="pill">Pasien: <?= htmlspecialchars((string)$visit['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="pill">Modul: <?= htmlspecialchars($modeLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <div class="cards" style="margin-bottom:12px;">
            <div class="card">
                <div class="muted">Form Terisi</div>
                <div class="badge"><?= $filledCount ?></div>
            </div>
            <div class="card">
                <div class="muted">Total Jenis Form</div>
                <div class="badge"><?= count($forms) ?></div>
            </div>
            <div class="card">
                <div class="muted">Keterangan</div>
                <div style="font-weight:600;color:#1f4f69;">Daftar menyesuaikan <?= htmlspecialchars($modeLabel, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <?php foreach ($sections as $sectionTitle => $items): ?>
            <h4 style="margin:14px 0 8px;"><?= htmlspecialchars((string)$sectionTitle, ENT_QUOTES, 'UTF-8') ?></h4>
            <table>
                <thead>
                    <tr>
                        <th>Berkas Rekam Medik</th>
                        <th>Tabel</th>
                        <th class="num">Jumlah</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $current = null;
                        foreach ($forms as $form) {
                            if (($form['key'] ?? '') === ($item['key'] ?? '')) {
                                $current = $form;
                                break;
                            }
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($item['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><code><?= htmlspecialchars((string)($item['table'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td class="num"><?= (int)($current['count'] ?? 0) ?></td>
                            <td>
                                <span class="pill" style="<?= !empty($current['exists']) ? 'border-color:#cde6de;background:#edf8f4;color:#0f5132;' : 'border-color:#f4dcb4;background:#fff7e8;color:#8a5a00;' ?>">
                                    <?= !empty($current['exists']) ? 'Sudah Ada' : 'Belum Ada' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
