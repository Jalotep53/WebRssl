<div class="card">
    <h2 style="margin-top:0;">Katalog Menu SIMRS</h2>
    <p class="muted">Total item terpetakan: <strong><?= (int)$total ?></strong></p>
    <form method="get" class="row" style="margin:10px 0 14px;">
        <input type="hidden" name="page" value="menu-catalog">
        <div class="field">
            <label>Cari menu / permission / button</label>
            <input type="text" name="q" value="<?= htmlspecialchars((string)$query, ENT_QUOTES, 'UTF-8') ?>" placeholder="contoh: bpjs, billing, obat">
        </div>
        <button type="submit">Cari</button>
    </form>
</div>

<?php foreach ($groups as $group => $items): ?>
    <?php if (empty($items)) { continue; } ?>
    <div class="card" style="margin-top:12px;">
        <h3 style="margin-top:0;"><?= htmlspecialchars((string)$group, ENT_QUOTES, 'UTF-8') ?> <span class="muted">(<?= count($items) ?>)</span></h3>
        <table>
            <thead>
                <tr><th>Label Menu</th><th>Permission</th><th>Komponen</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $m): ?>
                    <tr>
                        <td><a href="<?= htmlspecialchars((string)($m['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($m['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></td>
                        <td><code><?= htmlspecialchars((string)($m['permission'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><code><?= htmlspecialchars((string)($m['button'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= !empty($m['implemented']) ? 'Implementasi Ada' : 'Belum' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
