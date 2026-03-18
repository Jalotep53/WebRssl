<div class="grid cols-2">
    <div class="card">
        <h3 style="margin-top:0;">Statistik Package Java</h3>
        <table>
            <thead><tr><th>Package</th><th>Jumlah</th></tr></thead>
            <tbody>
            <?php foreach ((array)($packageStats ?? []) as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($row['package'] ?? $row['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($row['count'] ?? $row['total'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Ringkasan Modul Admin</h3>
        <?php foreach ((array)($adminMenuGroups ?? []) as $groupName => $items): ?>
            <div style="margin-bottom:10px;">
                <strong><?= htmlspecialchars((string)$groupName, ENT_QUOTES, 'UTF-8') ?></strong>
                <div class="muted"><?= count($items) ?> menu</div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
