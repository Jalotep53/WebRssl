<?php

declare(strict_types=1);

use WebBaru\Controllers\BillingRalanController;
use WebBaru\Controllers\BridgingBpjsController;
use WebBaru\Controllers\DashboardController;
use WebBaru\Controllers\AppAssetController;
use WebBaru\Controllers\BerkasRmController;
use WebBaru\Controllers\FarmasiController;
use WebBaru\Controllers\KonfigurasiController;
use WebBaru\Controllers\LaporanController;
use WebBaru\Controllers\LegacyScanController;
use WebBaru\Controllers\LegacyModuleController;
use WebBaru\Controllers\LoginController;
use WebBaru\Controllers\MenuCatalogController;
use WebBaru\Controllers\MenuUtamaController;
use WebBaru\Controllers\RawatJalanController;
use WebBaru\Controllers\RawatInapController;
use WebBaru\Controllers\RegistrasiController;
use WebBaru\Controllers\SepPrintController;
use WebBaru\Controllers\TrackerController;
use WebBaru\Controllers\VclaimBpjsController;
use WebBaru\Services\AuthService;
use WebBaru\Services\RbacService;

require __DIR__ . '/../src/bootstrap.php';

$page = (string)($_GET['page'] ?? 'dashboard');
$publicPages = ['login', 'app-logo', 'app-icon', 'app-bpjs-logo'];

if ($page === 'logout') {
    AuthService::logout();
    header('Location: ?page=login');
    exit;
}

if (!in_array($page, $publicPages, true) && !AuthService::isLoggedIn()) {
    header('Location: ?page=login');
    exit;
}

if ($page === 'login' && AuthService::isLoggedIn()) {
    header('Location: ?page=dashboard');
    exit;
}

if (!in_array($page, $publicPages, true) && AuthService::isLoggedIn()) {
    $authUser = AuthService::user();
    if (!RbacService::canAccessPage($page, $authUser)) {
        http_response_code(403);
        view('forbidden', [
            'title' => 'Akses Ditolak',
            'message' => 'Akun Anda tidak memiliki hak akses ke modul ini.',
        ]);
        exit;
    }
}

switch ($page) {
    case 'login':
        (new LoginController())->index();
        break;

    case 'app-logo':
        (new AppAssetController())->logo();
        break;

    case 'app-icon':
        (new AppAssetController())->icon();
        break;

    case 'app-bpjs-logo':
        (new AppAssetController())->bpjsLogo();
        break;

    case 'billing-ralan':
        (new BillingRalanController())->index();
        break;

    case 'bridging-bpjs':
        (new BridgingBpjsController())->index();
        break;

    case 'berkas-rm':
        (new BerkasRmController())->index();
        break;

    case 'vclaim-bpjs':
        (new VclaimBpjsController())->index();
        break;

    case 'sep-print':
        (new SepPrintController())->index();
        break;

    case 'legacy-scan':
        (new LegacyScanController())->index();
        break;

    case 'menu-catalog':
        (new MenuCatalogController())->index();
        break;

    case 'legacy-module':
        (new LegacyModuleController())->index();
        break;

    case 'konfigurasi':
        (new KonfigurasiController())->index();
        break;

    case 'registrasi':
        (new RegistrasiController())->index();
        break;

    case 'rawatjalan':
        (new RawatJalanController())->index();
        break;

    case 'rawatinap':
        (new RawatInapController())->index();
        break;

    case 'farmasi':
        (new FarmasiController())->index();
        break;

    case 'laporan':
        (new LaporanController())->index();
        break;

    case 'tracker':
        (new TrackerController())->index();
        break;

    case 'menu':
    case 'menu-pasien':
    case 'menu-obat':
    case 'menu-stok-opname':
        (new MenuUtamaController())->index();
        break;

    case 'dashboard':
    default:
        (new DashboardController())->index();
        break;
}
