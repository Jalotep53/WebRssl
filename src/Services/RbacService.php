<?php

declare(strict_types=1);

namespace WebBaru\Services;

use WebBaru\Database;

final class RbacService
{
    private const PAGE_PERMISSIONS = [
        'dashboard' => ['dashboard.view'],
        'registrasi' => ['registrasi.access'],
        'rawatjalan' => ['rawatjalan.access'],
        'rawatinap' => ['rawatinap.access'],
        'farmasi' => ['farmasi.access'],
        'laporan' => ['laporan.access'],
        'tracker' => ['tracker.access'],
        'bridging-bpjs' => ['bridging.bpjs.access'],
        'vclaim-bpjs' => ['vclaim.bpjs.access'],
        'sep-print' => ['sep.print.access'],
        'menu' => ['menu.modul.access'],
        'menu-pasien' => ['menu.modul.access'],
        'menu-obat' => ['menu.modul.access'],
        'menu-stok-opname' => ['menu.modul.access'],
        'backend-admin' => ['backend.admin.access'],
        'billing-ralan' => ['billing.ralan.access'],
        'berkas-rm' => ['berkas.rm.access'],
        'menu-catalog' => ['menu.catalog.access'],
        'legacy-scan' => ['legacy.scan.access'],
        'legacy-module' => ['legacy.module.access'],
        'konfigurasi' => ['konfigurasi.access'],
    ];

    private static ?bool $tableReady = null;

    public static function enrichAuth(array $auth): array
    {
        $username = trim((string)($auth['kode'] ?? ''));
        if ($username === '') {
            return $auth;
        }
        if (!self::isTableReady()) {
            return $auth;
        }

        $pdo = Database::pdo();
        $rolesStmt = $pdo->prepare(
            "SELECT r.role_code
             FROM web_users u
             INNER JOIN web_user_roles ur ON ur.user_id = u.id
             INNER JOIN web_roles r ON r.id = ur.role_id
             WHERE u.username = :u AND u.is_active = 1 AND r.is_active = 1"
        );
        $rolesStmt->execute(['u' => $username]);
        $roleRows = $rolesStmt->fetchAll();

        $roles = [];
        foreach ($roleRows as $row) {
            $roleCode = strtoupper(trim((string)($row['role_code'] ?? '')));
            if ($roleCode !== '') {
                $roles[$roleCode] = true;
            }
        }

        if (empty($roles)) {
            $auth['web_rbac_enforced'] = false;
            $auth['web_roles'] = [];
            $auth['web_permissions'] = [];
            return $auth;
        }

        $permStmt = $pdo->prepare(
            "SELECT p.permission_code
             FROM web_users u
             INNER JOIN web_user_roles ur ON ur.user_id = u.id
             INNER JOIN web_role_permissions rp ON rp.role_id = ur.role_id
             INNER JOIN web_permissions p ON p.id = rp.permission_id
             WHERE u.username = :u AND u.is_active = 1 AND p.is_active = 1"
        );
        $permStmt->execute(['u' => $username]);
        $permRows = $permStmt->fetchAll();

        $permissions = [];
        foreach ($permRows as $row) {
            $code = trim((string)($row['permission_code'] ?? ''));
            if ($code !== '') {
                $permissions[$code] = true;
            }
        }

        if (isset($roles['SUPERADMIN'])) {
            $permissions['*'] = true;
        }

        $auth['web_rbac_enforced'] = true;
        $auth['web_roles'] = array_keys($roles);
        $auth['web_permissions'] = array_keys($permissions);
        return $auth;
    }

    public static function canAccessPage(string $page, array $auth): bool
    {
        $page = trim($page);
        if ($page === '' || !($auth['web_rbac_enforced'] ?? false)) {
            return true;
        }

        $roles = self::toSet($auth['web_roles'] ?? []);
        if (isset($roles['SUPERADMIN'])) {
            return true;
        }

        $permissions = self::toSet($auth['web_permissions'] ?? []);
        if (isset($permissions['*'])) {
            return true;
        }

        $required = self::PAGE_PERMISSIONS[$page] ?? [];
        if (empty($required)) {
            return true;
        }

        foreach ($required as $perm) {
            if (isset($permissions[$perm])) {
                return true;
            }
        }
        return false;
    }

    private static function toSet(array $values): array
    {
        $set = [];
        foreach ($values as $value) {
            $key = trim((string)$value);
            if ($key !== '') {
                $set[$key] = true;
            }
        }
        return $set;
    }

    private static function isTableReady(): bool
    {
        if (self::$tableReady !== null) {
            return self::$tableReady;
        }

        try {
            $stmt = Database::pdo()->query(
                "SELECT COUNT(*)
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name IN ('web_users','web_roles','web_permissions','web_role_permissions','web_user_roles')"
            );
            $count = (int)($stmt->fetchColumn() ?: 0);
            self::$tableReady = ($count === 5);
        } catch (\Throwable $e) {
            self::$tableReady = false;
        }

        return self::$tableReady;
    }
}
