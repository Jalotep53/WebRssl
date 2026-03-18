<?php

declare(strict_types=1);

namespace WebBaru\Services;

use WebBaru\Database;
use WebBaru\SqlTracker;

final class AuthService
{
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['auth']) && is_array($_SESSION['auth']);
    }

    public static function user(): array
    {
        $user = $_SESSION['auth'] ?? [];
        if (!is_array($user)) {
            return [];
        }

        if (($user['role'] ?? '') === 'admin') {
            if (empty($user['nama'])) {
                $user['nama'] = 'Admin';
            }
            $user = RbacService::enrichAuth($user);
            $_SESSION['auth'] = $user;
            return $user;
        }

        if (($user['role'] ?? '') === 'user' && empty($user['nama']) && !empty($user['kode'])) {
            try {
                $stmt = Database::pdo()->prepare(
                    "SELECT nama FROM pegawai WHERE nik=:u OR id=:u LIMIT 1"
                );
                $stmt->execute(['u' => (string)$user['kode']]);
                $nama = trim((string)($stmt->fetchColumn() ?: ''));
                $user['nama'] = $nama !== '' ? $nama : (string)$user['kode'];
            } catch (\Throwable $e) {
                $user['nama'] = (string)$user['kode'];
            }
        }

        $user = RbacService::enrichAuth($user);
        $_SESSION['auth'] = $user;
        return $user;
    }

    public static function attemptLogin(string $username, string $password, string $mode = 'auto'): array
    {
        $username = trim($username);
        $password = trim($password);
        $mode = trim($mode) === '' ? 'auto' : trim($mode);
        if (!in_array($mode, ['auto', 'admin', 'user'], true)) {
            $mode = 'auto';
        }
        if ($username === '' || $password === '') {
            return ['ok' => false, 'message' => 'ID User dan password wajib diisi'];
        }

        $pdo = Database::pdo();

        $adminStmt = $pdo->prepare(
            "SELECT 1
             FROM admin
             WHERE usere=AES_ENCRYPT(:u,'nur')
               AND passworde=AES_ENCRYPT(:p,'windi')
             LIMIT 1"
        );
        if ($mode === 'auto' || $mode === 'admin') {
            $adminStmt->execute(['u' => $username, 'p' => $password]);
            if ($adminStmt->fetch()) {
                session_regenerate_id(true);
                $auth = [
                    'kode' => 'Admin Utama',
                    'nama' => 'Admin',
                    'role' => 'admin',
                ];
                $_SESSION['auth'] = RbacService::enrichAuth($auth);
                SqlTracker::logLogin('Admin Utama');
                return ['ok' => true];
            }
            if ($mode === 'admin') {
                return ['ok' => false, 'message' => 'Login admin gagal: ID User atau password salah'];
            }
        }

        $userStmt = $pdo->prepare(
            "SELECT
                CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4) AS id_user_plain,
                p.nama AS nama_pegawai
             FROM user u
             LEFT JOIN pegawai p
               ON p.nik = CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4)
               OR p.id = CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4)
             WHERE u.id_user=AES_ENCRYPT(:u,'nur')
               AND u.password=AES_ENCRYPT(:p,'windi')
             LIMIT 1"
        );
        if ($mode === 'auto' || $mode === 'user') {
            $userStmt->execute(['u' => $username, 'p' => $password]);
            $row = $userStmt->fetch();
            if ($row) {
                session_regenerate_id(true);
                $kode = (string)($row['id_user_plain'] ?? $username);
                $nama = trim((string)($row['nama_pegawai'] ?? ''));
                $auth = [
                    'kode' => $kode,
                    'nama' => $nama !== '' ? $nama : $kode,
                    'role' => 'user',
                ];
                $_SESSION['auth'] = RbacService::enrichAuth($auth);
                SqlTracker::logLogin($kode);
                return ['ok' => true];
            }
            if ($mode === 'user') {
                return ['ok' => false, 'message' => 'Login user gagal: ID User atau password salah'];
            }
        }

        return ['ok' => false, 'message' => 'ID User atau password salah'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                (string)($params['path'] ?? '/'),
                (string)($params['domain'] ?? ''),
                (bool)($params['secure'] ?? false),
                (bool)($params['httponly'] ?? true)
            );
        }
        session_destroy();
    }
}
