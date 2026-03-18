<div class="grid cols-2">
    <div class="card">
        <h3 style="margin-top:0;">Konfigurasi Legacy</h3>
        <p class="muted">Ringkasan key konfigurasi hasil pembacaan dokumentasi dan konfigurasi aktif dari Khanza Java.</p>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Status Integrasi</h3>
        <div style="display:grid;gap:8px;">
            <span class="pill">BPJS: <?= !empty($bpjsConfig['available']) ? 'Konfigurasi Lengkap' : 'Konfigurasi Belum Lengkap' ?></span>
            <span class="pill">Satu Sehat: <?= !empty($satuSehatConfig['available']) ? 'Konfigurasi Lengkap' : 'Konfigurasi Belum Lengkap' ?></span>
        </div>
    </div>
</div>

<div class="grid cols-2" style="margin-top:14px;">
    <div class="card">
        <h3 style="margin-top:0;">BPJS VClaim Aktif</h3>
        <table>
            <tbody>
                <tr><th>URL</th><td><?= htmlspecialchars((string)($bpjsConfig['url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Cons ID</th><td><?= htmlspecialchars((string)($bpjsConfig['consid'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>User Key</th><td><?= htmlspecialchars((string)($bpjsConfig['userkey'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Source</th><td><?= htmlspecialchars((string)($bpjsConfig['source'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Satu Sehat Aktif</h3>
        <table>
            <tbody>
                <tr><th>Organization ID</th><td><?= htmlspecialchars((string)($satuSehatConfig['organization_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Auth URL</th><td><?= htmlspecialchars((string)($satuSehatConfig['auth_url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>FHIR URL</th><td><?= htmlspecialchars((string)($satuSehatConfig['fhir_url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Source</th><td><?= htmlspecialchars((string)($satuSehatConfig['source'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php foreach (($configGroups ?? []) as $groupName => $keys): ?>
    <div class="card" style="margin-top:14px;">
        <h3 style="margin-top:0;"><?= htmlspecialchars((string)$groupName, ENT_QUOTES, 'UTF-8') ?> <span class="muted">(<?= count($keys) ?>)</span></h3>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach ($keys as $key): ?>
                <span class="pill"><?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
