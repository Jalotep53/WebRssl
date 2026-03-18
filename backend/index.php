<?php

declare(strict_types=1);

use WebBaru\Controllers\BackendAdminController;
use WebBaru\Services\AuthService;
use WebBaru\Services\RbacService;

require __DIR__ . '/../src/bootstrap.php';

if (!AuthService::isLoggedIn()) {
    header('Location: ../?page=login');
    exit;
}

$authUser = AuthService::user();
if (!RbacService::canAccessPage('backend-admin', $authUser)) {
    http_response_code(403);
    echo '<h2>Akses Ditolak</h2><p>User tidak memiliki izin membuka backend admin.</p><p><a href="../?page=dashboard">Kembali</a></p>';
    exit;
}

(new BackendAdminController())->index();
