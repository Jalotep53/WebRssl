<div class="content-shell">
<div class="grid cols-3">
    <div class="card"><div class="muted">Total Menu Legacy</div><div class="stat"><?= (int)($summary['total_menus'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Modul Admin Terdeteksi</div><div class="stat"><?= (int)($summary['admin_menus'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Modul Satu Sehat</div><div class="stat"><?= (int)($summary['satu_sehat_menus'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Key Konfigurasi</div><div class="stat"><?= (int)($summary['config_keys'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Package Java</div><div class="stat"><?= (int)($summary['package_count'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Status BPJS / Satu Sehat</div><div style="margin-top:8px;"><span class="pill">BPJS: <?= !empty($bpjsConfig['available']) ? 'Siap' : 'Belum Lengkap' ?></span> <span class="pill">Satu Sehat: <?= !empty($satuSehatConfig['available']) ? 'Siap' : 'Belum Lengkap' ?></span></div></div>
</div>

<div class="grid cols-2" style="margin-top:14px;">
    <div class="card">
        <h3 style="margin-top:0;">Quick Access</h3>
        <div class="list-links">
            <?php foreach (($backendModules ?? []) as $item): ?>
                <a href="?module=<?= urlencode((string)$item['key']) ?>"><?= htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Ringkasan Integrasi Java</h3>
        <table>
            <tbody>
                <tr><th>BPJS VClaim URL</th><td><?= htmlspecialchars((string)($bpjsConfig['url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Sumber BPJS</th><td><?= htmlspecialchars((string)($bpjsConfig['source'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Auth Satu Sehat</th><td><?= htmlspecialchars((string)($satuSehatConfig['auth_url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>FHIR Satu Sehat</th><td><?= htmlspecialchars((string)($satuSehatConfig['fhir_url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Source Config</th><td><?= htmlspecialchars((string)($satuSehatConfig['source'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="grid cols-2" style="margin-top:14px;">
    <div class="card">
        <h3 style="margin-top:0;">Modul Admin Terkini</h3>
        <table>
            <thead><tr><th>Label</th><th>Permission</th><th>Button</th></tr></thead>
            <tbody>
            <?php foreach (($latestModules ?? []) as $item): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($item['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($item['permission'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($item['button'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Package Java Teratas</h3>
        <table>
            <thead><tr><th>Package</th><th>Jumlah</th></tr></thead>
            <tbody>
            <?php foreach (array_slice((array)($packageStats ?? []), 0, 12) as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($row['package'] ?? $row['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($row['count'] ?? $row['total'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
