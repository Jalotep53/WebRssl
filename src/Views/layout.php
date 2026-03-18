<?php
declare(strict_types=1);

use WebBaru\Services\AuthService;
use WebBaru\Services\RbacService;

$auth = AuthService::user();
$isLoggedIn = AuthService::isLoggedIn();
$pageNow = (string)($_GET['page'] ?? '');
$embedMode = trim((string)($_GET['embed'] ?? '')) === '1';
$settingRs = app_settings();
$appName = trim((string)($settingRs['nama_instansi'] ?? 'SIMRS Web'));
if ($appName === '') {
    $appName = 'SIMRS Web';
}
$canPage = static fn(string $targetPage): bool => RbacService::canAccessPage($targetPage, $auth);
$menuQuick = [
    ['page' => 'registrasi', 'label' => 'Registrasi'],
    ['page' => 'rawatjalan', 'label' => 'Rawat Jalan'],
    ['page' => 'rawatinap', 'label' => 'Rawat Inap'],
    ['page' => 'farmasi', 'label' => 'Farmasi'],
];
$menuQuick = array_values(array_filter($menuQuick, static fn(array $item): bool => $canPage((string)$item['page'])));

$menuModul = [
    ['page' => 'menu-pasien', 'label' => 'Pasien'],
    ['page' => 'menu-obat', 'label' => 'Obat'],
    ['page' => 'menu-stok-opname', 'label' => 'Stok Opname'],
];
$menuModul = array_values(array_filter($menuModul, static fn(array $item): bool => $canPage((string)$item['page'])));
$showMenuDropdown = !empty($menuModul);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? $appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="?page=app-icon">
    <style>
        :root {
            --bg: #f3f8fb;
            --panel: #ffffff;
            --line: #d7e6ef;
            --text: #1d2b36;
            --muted: #5a6f7f;
            --primary: #0f6b8f;
            --primary-2: #0b5673;
            --accent: #13a5a0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 0% 0%, rgba(19, 165, 160, .12), transparent 45%),
                radial-gradient(circle at 100% 0%, rgba(15, 107, 143, .14), transparent 40%),
                var(--bg);
        }
        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background: linear-gradient(90deg, #0f6b8f 0%, #11799f 38%, #13a5a0 100%);
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,.25);
            box-shadow: 0 8px 20px rgba(7, 66, 89, .2);
        }
        .topbar-wrap {
            width: min(1400px, 96vw);
            margin: 0 auto;
            padding: 10px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 260px;
            text-decoration: none;
            color: inherit;
        }
        .brand img {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(255,255,255,.95);
            object-fit: contain;
            padding: 4px;
            border: 1px solid rgba(255,255,255,.4);
        }
        .brand-title {
            line-height: 1.2;
        }
        .brand-title strong {
            display: block;
            font-size: 15px;
            letter-spacing: .2px;
        }
        .brand-title small {
            font-size: 12px;
            opacity: .9;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .welcome {
            font-size: 13px;
            white-space: nowrap;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 999px;
            padding: 6px 12px;
        }
        .menu {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .menu a {
            color: #fff;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,.22);
            background: rgba(255,255,255,.08);
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 13px;
        }
        .menu a:hover,
        .menu a.active {
            background: #ffffff;
            color: #0f6b8f;
            border-color: #ffffff;
        }
        .topbar-wrap { align-items: flex-start; }
        .brand-panel { display:flex; flex-direction:column; align-items:flex-start; gap:7px; }
        .topbar-right { align-items:flex-start; }
        .brand-welcome { margin-left: 54px; }
        .menu .menu-trigger {
            color:#fff; text-decoration:none; border:1px solid rgba(255,255,255,.22);
            background:rgba(255,255,255,.08); border-radius:999px; padding:7px 12px; font-size:13px; display:inline-block;
        }
        .menu .menu-trigger.active { background:#ffffff; color:#0f6b8f; border-color:#ffffff; }
        .menu-dropdown { position:relative; }
        .menu-dropdown-list {
            position:absolute; right:0; top:calc(100% + 6px); min-width:190px; background:#fff; border:1px solid #d7deea;
            border-radius:10px; box-shadow:0 14px 28px rgba(15,23,42,.18); padding:6px; display:none; z-index:120;
        }
        .menu-dropdown-list a { display:block; border:0; border-radius:8px; color:#1f3847; background:transparent; padding:8px 10px; }
        .menu-dropdown-list a:hover, .menu-dropdown-list a.active { background:#edf5fa; color:#0f6b8f; }
        .menu-dropdown.open .menu-dropdown-list, .menu-dropdown:focus-within .menu-dropdown-list { display:block; }
        .main {
            width: min(1400px, 96vw);
            margin: 16px auto 24px;
            flex: 1;
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 8px 22px rgba(18, 75, 103, .06);
        }
        .muted { color: var(--muted); }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            border-bottom: 1px solid var(--line);
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #edf5fa;
            font-weight: 600;
        }
        .num { text-align: right; white-space: nowrap; }
        .pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 12px;
            border: 1px solid #c4e6e4;
            background: #e8f8f7;
            color: #115d6d;
        }
        .row { display: flex; gap: 12px; align-items: end; flex-wrap: wrap; }
        .field label { display: block; font-size: 13px; margin-bottom: 4px; }
        .field input {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 9px 10px;
            min-width: 280px;
        }
        button {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            background: var(--primary);
            color: #fff;
            cursor: pointer;
        }
        button:hover { background: var(--primary-2); }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 10px;
        }
        .badge {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.1;
        }
        .footer {
            background: #0f3244;
            color: #cae4f0;
            border-top: 3px solid #13a5a0;
            margin-top: auto;
        }
        .footer-wrap {
            width: min(1400px, 96vw);
            margin: 0 auto;
            padding: 12px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            font-size: 12px;
        }
        .footer-wrap strong { color: #fff; }
        @media (max-width: 900px) {
            .field input { min-width: 100%; width: 100%; }
            .topbar-wrap { padding: 10px 0 12px; }
            .brand { min-width: auto; }
            .brand-welcome { margin-left: 0; }
            .topbar-right { justify-content: flex-start; }
            .menu { justify-content: flex-start; }
        }
        .app-alert-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(8, 19, 32, .45);
            z-index: 9998;
            display: none;
        }
        .app-alert-modal {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: min(430px, 92vw);
            background: #fff;
            border: 1px solid #d7deea;
            border-radius: 12px;
            box-shadow: 0 22px 44px rgba(15, 23, 42, .28);
            z-index: 9999;
            display: none;
            overflow: hidden;
        }
        .app-alert-head {
            padding: 10px 14px;
            background: linear-gradient(90deg, #eaf5fb 0%, #eefaf7 100%);
            border-bottom: 1px solid #d7e4ed;
            font-weight: 600;
            color: #184860;
        }
        .app-alert-body {
            padding: 14px;
            color: #233746;
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 14px;
            line-height: 1.45;
        }
        .app-alert-foot {
            padding: 10px 14px 14px;
            display: flex;
            justify-content: flex-end;
        }
        .app-alert-open { display: block; }
    </style>
</head>
<body>
<div id="appAlertBackdrop" class="app-alert-backdrop"></div>
<div id="appAlertModal" class="app-alert-modal" role="dialog" aria-modal="true" aria-labelledby="appAlertTitle">
    <div id="appAlertTitle" class="app-alert-head">Informasi</div>
    <div id="appAlertBody" class="app-alert-body"></div>
    <div class="app-alert-foot">
        <button type="button" id="appAlertOkBtn">OK</button>
    </div>
</div>
<script>
    (function () {
        var modal = document.getElementById('appAlertModal');
        var backdrop = document.getElementById('appAlertBackdrop');
        var body = document.getElementById('appAlertBody');
        var okBtn = document.getElementById('appAlertOkBtn');
        if (!modal || !backdrop || !body || !okBtn) return;

        var queue = [];
        var open = false;

        function closeCurrent() {
            modal.classList.remove('app-alert-open');
            backdrop.classList.remove('app-alert-open');
            open = false;
            if (queue.length > 0) {
                show(queue.shift());
            }
        }

        function show(message) {
            if (open) {
                queue.push(message);
                return;
            }
            body.textContent = String(message == null ? '' : message);
            modal.classList.add('app-alert-open');
            backdrop.classList.add('app-alert-open');
            open = true;
            setTimeout(function () { okBtn.focus(); }, 10);
        }

        okBtn.addEventListener('click', closeCurrent);
        backdrop.addEventListener('click', closeCurrent);
        document.addEventListener('keydown', function (ev) {
            if (!open) return;
            if (ev.key === 'Escape' || ev.key === 'Enter') {
                ev.preventDefault();
                closeCurrent();
            }
        });

        window.alert = function (message) {
            show(message);
        };
    })();
</script>
<script>
    (function () {
        var dropdown = document.querySelector('.menu-dropdown');
        if (!dropdown) return;
        var trigger = dropdown.querySelector('.menu-trigger');
        if (!trigger) return;
        var links = dropdown.querySelectorAll('.menu-dropdown-list a');

        function setOpen(open) {
            if (open) {
                dropdown.classList.add('open');
                trigger.setAttribute('aria-expanded', 'true');
            } else {
                dropdown.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
            }
        }

        trigger.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            setOpen(!dropdown.classList.contains('open'));
        });

        document.addEventListener('click', function (ev) {
            if (!dropdown.contains(ev.target)) {
                setOpen(false);
            }
        });

        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') {
                setOpen(false);
            }
        });

        links.forEach(function (a) {
            a.addEventListener('click', function () {
                setOpen(false);
            });
        });
    })();
</script>
<?php if ($embedMode): ?>
<main class="main" style="width:100%;margin:0;padding:12px;">
    <?php require $viewFile; ?>
</main>
</body>
</html>
<?php return; endif; ?>
<div class="page">
    <header class="topbar">
        <div class="topbar-wrap">
            <div class="brand-panel">
                <a class="brand" href="?page=dashboard">
                    <img src="?page=app-logo" alt="Logo RS">
                    <div class="brand-title">
                        <strong><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></strong>
                        <small>Sistem Informasi Manajemen Rumah Sakit</small>
                    </div>
                </a>
                <?php if ($isLoggedIn): ?>
                    <div class="welcome brand-welcome">
                        Welcome <?= htmlspecialchars((string)($auth['nama'] ?? $auth['kode'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($isLoggedIn): ?>
                <div class="topbar-right">
                    <nav class="menu">
                        <a class="<?= $pageNow === 'registrasi' ? 'active' : '' ?>" href="?page=registrasi">Registrasi</a>
                        <a class="<?= $pageNow === 'rawatjalan' ? 'active' : '' ?>" href="?page=rawatjalan">Rawat Jalan</a>
                        <a class="<?= $pageNow === 'rawatinap' ? 'active' : '' ?>" href="?page=rawatinap">Rawat Inap</a>
                        <a class="<?= $pageNow === 'farmasi' ? 'active' : '' ?>" href="?page=farmasi">Farmasi</a>
                        <div class="menu-dropdown">
                            <button type="button" class="menu-trigger <?= in_array($pageNow, ['menu','menu-pasien','menu-obat','menu-stok-opname'], true) ? 'active' : '' ?>" aria-haspopup="true" aria-expanded="false">Menu</button>
                            <div class="menu-dropdown-list">
                                <a class="<?= $pageNow === 'menu-pasien' ? 'active' : '' ?>" href="?page=menu-pasien">Pasien</a>
                                <a class="<?= $pageNow === 'menu-obat' ? 'active' : '' ?>" href="?page=menu-obat">Obat</a>
                                <a class="<?= $pageNow === 'menu-stok-opname' ? 'active' : '' ?>" href="?page=menu-stok-opname">Stok Opname</a>
                            </div>
                        </div>
                        <a href="?page=logout">Logout</a>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="main">
        <?php require $viewFile; ?>
    </main>

    <footer class="footer">
        <div class="footer-wrap">
            <div>
                <strong><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if (!empty($settingRs['alamat_instansi'])): ?>
                    | <?= htmlspecialchars((string)$settingRs['alamat_instansi'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
            <div>
                <?php if (!empty($settingRs['kontak'])): ?>
                    Kontak: <?= htmlspecialchars((string)$settingRs['kontak'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
                <?php if (!empty($settingRs['email'])): ?>
                    | Email: <?= htmlspecialchars((string)$settingRs['email'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
        </div>
    </footer>
</div>
</body>
</html>





