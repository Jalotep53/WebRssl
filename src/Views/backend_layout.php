<?php
declare(strict_types=1);

$currentModule = (string)($currentModule ?? 'dashboard');
$backendModules = is_array($backendModules ?? null) ? $backendModules : [];
$settingRs = app_settings();
$appName = trim((string)($settingRs['nama_instansi'] ?? 'SIMRS Web'));
if ($appName === '') {
    $appName = 'SIMRS Web';
}
$authUser = is_array($authUser ?? null) ? $authUser : [];
$modulePageMap = [
    'dashboard' => 'backend-admin',
    'modules' => 'backend-admin',
    'config' => 'konfigurasi',
    'bridging-bpjs' => 'bridging-bpjs',
    'bridging-satusehat' => 'backend-admin',
    'rbac' => 'backend-admin',
    'monitoring' => 'tracker',
];
$backendModules = array_values(array_filter($backendModules, static function (array $item) use ($modulePageMap, $authUser): bool {
    $key = (string)($item['key'] ?? '');
    $targetPage = $modulePageMap[$key] ?? 'backend-admin';
    return \WebBaru\Services\RbacService::canAccessPage($targetPage, $authUser);
}));
$canCatalog = \WebBaru\Services\RbacService::canAccessPage('menu-catalog', $authUser);
$canKonfig = \WebBaru\Services\RbacService::canAccessPage('konfigurasi', $authUser);
$canTracker = \WebBaru\Services\RbacService::canAccessPage('tracker', $authUser);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string)($title ?? 'Backend Admin'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="../?page=app-icon">
    <style>
        :root {
            --bg: #eef4f8;
            --panel: #ffffff;
            --line: #d5e1e9;
            --text: #1d2d38;
            --muted: #607381;
            --primary: #124f6b;
            --primary-2: #0c3b50;
            --accent: #d98b2b;
            --nav: #0f2430;
            --nav-line: rgba(255,255,255,.08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 100% 0%, rgba(217,139,43,.12), transparent 28%),
                radial-gradient(circle at 0% 0%, rgba(18,79,107,.12), transparent 36%),
                var(--bg);
        }
        .shell { min-height: 100vh; display: grid; grid-template-columns: 270px 1fr; }
        .sidebar {
            background: linear-gradient(180deg, #0d202b 0%, #112f3f 100%);
            color: #edf7fd;
            border-right: 1px solid rgba(255,255,255,.08);
            padding: 18px 16px;
        }
        .brand {
            display: block;
            text-decoration: none;
            color: inherit;
            margin-bottom: 18px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--nav-line);
        }
        .brand strong { display: block; font-size: 17px; }
        .brand small { color: rgba(255,255,255,.72); }
        .userbox {
            padding: 12px;
            border: 1px solid var(--nav-line);
            border-radius: 12px;
            background: rgba(255,255,255,.05);
            margin-bottom: 16px;
        }
        .userbox .name { font-weight: 600; }
        .userbox .role { color: rgba(255,255,255,.72); font-size: 12px; }
        .nav { display: grid; gap: 8px; }
        .nav a {
            display: block;
            text-decoration: none;
            color: #e9f4fa;
            border: 1px solid transparent;
            border-radius: 12px;
            padding: 10px 12px;
            background: rgba(255,255,255,.03);
        }
        .nav a:hover, .nav a.active {
            background: rgba(217,139,43,.14);
            border-color: rgba(217,139,43,.35);
        }
        .nav a span { display: block; color: rgba(255,255,255,.66); font-size: 12px; margin-top: 2px; }
        .sidebar-footer {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--nav-line);
            display: grid;
            gap: 8px;
        }
        .sidebar-footer a {
            color: #d8edf8;
            text-decoration: none;
            font-size: 13px;
        }
        .content { padding: 18px; }
        .topbar {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 16px;
        }
        .topbar h1 { margin: 0; font-size: 24px; }
        .topbar .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .topbar .actions a {
            text-decoration: none;
            color: #fff;
            background: var(--primary);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
        }
        .topbar .actions a.secondary { background: #64748b; }
        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 24px rgba(13, 42, 58, .06);
        }
        .grid { display: grid; gap: 14px; }
        .grid.cols-2 { grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
        .grid.cols-3 { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .muted { color: var(--muted); }
        .stat { font-size: 28px; font-weight: 700; color: var(--primary); }
        .pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid #d5e5ef;
            background: #f5fafc;
            color: #23495f;
            font-size: 12px;
        }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { border-bottom: 1px solid var(--line); padding: 8px; text-align: left; vertical-align: top; }
        th { background: #edf5fa; }
        .list-links { display: grid; gap: 8px; }
        .list-links a {
            display: block;
            text-decoration: none;
            color: var(--primary);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            background: #fbfeff;
        }
        @media (max-width: 980px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { border-right: 0; border-bottom: 1px solid rgba(255,255,255,.08); }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a href="./" class="brand">
            <strong>Backend Admin</strong>
            <small><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></small>
        </a>
        <div class="userbox">
            <div class="name"><?= htmlspecialchars((string)($authUser['nama'] ?? $authUser['kode'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="role">Role: <?= htmlspecialchars((string)($authUser['role'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <nav class="nav">
            <?php foreach ($backendModules as $item): ?>
                <a href="?module=<?= urlencode((string)$item['key']) ?>" class="<?= $currentModule === (string)$item['key'] ? 'active' : '' ?>">
                    <?= htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8') ?>
                    <span><?= htmlspecialchars((string)$item['desc'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="../">Kembali ke WebBaru</a>
            <a href="../?page=logout">Logout</a>
        </div>
    </aside>
    <main class="content">
        <div class="topbar">
            <div>
                <h1><?= htmlspecialchars((string)($title ?? 'Backend Admin'), ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="muted">Akses admin melalui <code>/WebBaru/backend</code></div>
            </div>
            <div class="actions">
                <?php if ($canCatalog): ?><a href="../?page=menu-catalog" class="secondary">Katalog Menu</a><?php endif; ?>
                <?php if ($canKonfig): ?><a href="../?page=konfigurasi" class="secondary">Konfigurasi Legacy</a><?php endif; ?>
                <?php if ($canTracker): ?><a href="../?page=tracker">Tracker</a><?php endif; ?>
            </div>
        </div>
        <?php require $backendContentFile; ?>
    </main>
</div>
</body>
</html>



