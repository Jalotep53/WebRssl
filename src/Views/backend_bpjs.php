<div class="grid cols-2">
    <div class="card">
        <h3 style="margin-top:0;">Konfigurasi BPJS dari Khanza Java</h3>
        <table>
            <tbody>
                <tr><th>Status</th><td><span class="pill"><?= !empty($bpjsConfig['available']) ? 'Siap Digunakan' : 'Belum Lengkap' ?></span></td></tr>
                <tr><th>VClaim URL</th><td><?= htmlspecialchars((string)($bpjsConfig['url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Cons ID</th><td><?= htmlspecialchars((string)($bpjsConfig['consid'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Secret Key</th><td><?= htmlspecialchars((string)($bpjsConfig['secret'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>User Key</th><td><?= htmlspecialchars((string)($bpjsConfig['userkey'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <tr><th>Source</th><td><?= htmlspecialchars((string)($bpjsConfig['source'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3 style="margin-top:0;">Endpoint Lain Terkait BPJS</h3>
        <table>
            <thead><tr><th>Key</th><th>Nilai</th></tr></thead>
            <tbody>
            <?php foreach ((array)($bpjsExtras['entries'] ?? []) as $key => $value): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:14px;">
    <h3 style="margin-top:0;">Quick Actions</h3>
    <div class="list-links">
        <a href="../?page=bridging-bpjs">Buka modul Bridging BPJS</a>
        <a href="../?page=vclaim-bpjs">Buka modul VClaim BPJS</a>
        <a href="../?page=registrasi">Buka Registrasi untuk cek kepesertaan</a>
    </div>
</div>
