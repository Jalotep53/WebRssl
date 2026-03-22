<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Services\AuthService;

final class LoginController
{
    public function index(): void
    {
        $error = '';
        $username = '';
        $settingRs = \app_settings();
        $appName = trim((string)($settingRs['nama_instansi'] ?? 'SIMRS Web'));
        if ($appName === '') {
            $appName = 'SIMRS Web';
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $login = AuthService::attemptLogin($username, $password, 'auto');
            if (!empty($login['ok'])) {
                header('Location: ?page=dashboard');
                exit;
            }
            $error = (string)($login['message'] ?? 'Login gagal');
        }

        view('login', [
            'title' => $appName,
            'appName' => $appName,
            'error' => $error,
            'username' => $username,
        ]);
    }
}
