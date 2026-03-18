<div class="grid cols-2">
    <div class="card">
        <h3 style="margin-top:0;">Modul Admin Legacy</h3>
        <p class="muted">Daftar ini diambil dari katalog menu Khanza dan difilter untuk area admin, setup, monitoring, dan integrasi.</p>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Quick Links</h3>
        <div class="list-links">
            <?php foreach (($quickLinks ?? []) as $link): ?>
                <a href="<?= htmlspecialchars((string)$link['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$link['label'], ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php foreach (($adminMenuGroups ?? []) as $groupName => $items): ?>
    <div class="card" style="margin-top:14px;">
        <h3 style="margin-top:0;"><?= htmlspecialchars((string)$groupName, ENT_QUOTES, 'UTF-8') ?> <span class="muted">(<?= count($items) ?>)</span></h3>
        <table>
            <thead><tr><th>Label</th><th>Permission</th><th>Button</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($item['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($item['permission'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($item['button'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
