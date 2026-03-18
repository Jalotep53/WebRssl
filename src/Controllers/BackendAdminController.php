<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Database;
use WebBaru\Services\AuthService;
use WebBaru\Services\KhanzaJavaConfigService;
use WebBaru\Services\LegacyScanService;
use WebBaru\Services\SatuSehatEncounterAdminService;
use WebBaru\Services\SatuSehatService;

final class BackendAdminController
{
    private const SATUSEHAT_FLASH_KEY = 'backend_satusehat_flash';
    private const RBAC_FLASH_KEY = 'backend_rbac_flash';
    public function index(): void
    {
        $module = trim((string)($_GET['module'] ?? 'dashboard'));
        $allowed = ['dashboard', 'modules', 'config', 'bridging-bpjs', 'bridging-satusehat', 'rbac', 'monitoring'];
        if (!in_array($module, $allowed, true)) {
            $module = 'dashboard';
        }

        if ($module === 'bridging-satusehat' && strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->handleSatuSehatPost();
            return;
        }

        if ($module === 'rbac' && strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->handleRbacPost();
            return;
        }

        $scan = new LegacyScanService();
        $javaConfig = new KhanzaJavaConfigService();
        $menuMap = $scan->menuAccessMap();
        $packageStats = $scan->packageStats();
        $configKeys = $scan->configKeys();
        $adminMenus = $this->filterAdminMenus($menuMap);
        $satuSehatMenus = $this->filterMenusByPattern($menuMap, '/satusehat|satu sehat/i');

        $backendModules = $this->backendModules();
        $data = [
            'title' => 'Backend Admin',
            'backendModules' => $backendModules,
            'currentModule' => $module,
            'authUser' => AuthService::user(),
            'summary' => [
                'total_menus' => count($menuMap),
                'admin_menus' => count($adminMenus),
                'satu_sehat_menus' => count($satuSehatMenus),
                'config_keys' => count($configKeys),
                'package_count' => count($packageStats),
            ],
        ];

        switch ($module) {
            case 'modules':
                $data['title'] = 'Backend Admin - Modul';
                $data['viewFile'] = 'backend_modules';
                $data['adminMenuGroups'] = $this->groupAdminMenus($adminMenus);
                $data['quickLinks'] = $this->legacyQuickLinks();
                break;

            case 'config':
                $data['title'] = 'Backend Admin - Konfigurasi';
                $data['viewFile'] = 'backend_config';
                $data['configGroups'] = $this->groupConfigKeys($configKeys);
                $data['bpjsConfig'] = $javaConfig->getBpjsVclaimConfig();
                $data['satuSehatConfig'] = $javaConfig->getSatuSehatConfig();
                break;

            case 'bridging-bpjs':
                $data['title'] = 'Backend Admin - Bridging BPJS';
                $data['viewFile'] = 'backend_bpjs';
                $data['bpjsConfig'] = $javaConfig->getBpjsVclaimConfig();
                $data['bpjsExtras'] = $javaConfig->getConfigSubset([
                    'URLAPIAPLICARE',
                    'URLAPIMOBILEJKN',
                    'URLAPIICARE',
                    'URLAPIPCARE',
                    'ADDANTRIANAPIMOBILEJKN',
                ], false);
                break;

            case 'bridging-satusehat':
                $encounterAdmin = new SatuSehatEncounterAdminService();
                $data['title'] = 'Backend Admin - Bridging Satu Sehat';
                $data['viewFile'] = 'backend_satusehat';
                $data['satuSehatActionGroups'] = $this->groupSatuSehatActions($satuSehatMenus);
                $data['satuSehatSummary'] = $this->summarizeSatuSehatActions($satuSehatMenus);
                $data['satusehatFlash'] = $this->pullSatuSehatFlash();
                $data['satuSehatAvailable'] = (new SatuSehatService($javaConfig))->isAvailable();
                $data['encounterFilters'] = $encounterAdmin->filtersFromQuery();
                $data['encounterRows'] = $encounterAdmin->loadQueue($data['encounterFilters']);
                $data['encounterQueueSummary'] = $encounterAdmin->summarizeQueue($data['encounterRows']);
                break;

            case 'rbac':
                $data['title'] = 'Backend Admin - RBAC User';
                $data['viewFile'] = 'backend_rbac';
                $data = array_merge($data, $this->loadRbacData());
                break;

            case 'monitoring':
                $data['title'] = 'Backend Admin - Monitoring';
                $data['viewFile'] = 'backend_monitoring';
                $data['packageStats'] = $packageStats;
                $data['adminMenuGroups'] = $this->groupAdminMenus($adminMenus);
                break;

            case 'dashboard':
            default:
                $data['title'] = 'Backend Admin - Dashboard';
                $data['viewFile'] = 'backend_dashboard';
                $data['latestModules'] = array_slice($adminMenus, 0, 12);
                $data['bpjsConfig'] = $javaConfig->getBpjsVclaimConfig();
                $data['satuSehatConfig'] = $javaConfig->getSatuSehatConfig();
                $data['packageStats'] = $packageStats;
                break;
        }

        backend_view((string)$data['viewFile'], $data);
    }

    private function backendModules(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'desc' => 'Ringkasan backend admin dan integrasi.'],
            ['key' => 'modules', 'label' => 'Modul Admin', 'desc' => 'Daftar modul admin dan tautan ke modul utama.'],
            ['key' => 'config', 'label' => 'Konfigurasi', 'desc' => 'Kunci konfigurasi legacy dan ringkasan integrasi.'],
            ['key' => 'bridging-bpjs', 'label' => 'Bridging BPJS', 'desc' => 'Konfigurasi BPJS/VClaim dari Khanza Java.'],
            ['key' => 'bridging-satusehat', 'label' => 'Bridging Satu Sehat', 'desc' => 'Pengiriman data Satu Sehat langsung dari backend.'],
            ['key' => 'rbac', 'label' => 'RBAC User', 'desc' => 'Kelola role dan hak akses user WebBaru.'],
            ['key' => 'monitoring', 'label' => 'Monitoring', 'desc' => 'Statistik package dan gambaran area sistem.'],
        ];
    }

    private function filterAdminMenus(array $menus): array
    {
        $filtered = array_values(array_filter($menus, function (array $item): bool {
            $label = strtolower((string)($item['label'] ?? ''));
            $perm = strtolower((string)($item['permission'] ?? ''));
            $btn = strtolower((string)($item['button'] ?? ''));
            $haystack = $label . ' ' . $perm . ' ' . $btn;
            return (bool)preg_match('/set user|user|konfigurasi|setup|setting|bridging|bpjs|satusehat|satu sehat|mapping|referensi|tracker|monitoring|laporan|hak akses|admin/', $haystack);
        }));

        usort($filtered, static function (array $a, array $b): int {
            return strcmp((string)($a['label'] ?? ''), (string)($b['label'] ?? ''));
        });
        return $filtered;
    }

    private function filterMenusByPattern(array $menus, string $pattern): array
    {
        $filtered = array_values(array_filter($menus, static function (array $item) use ($pattern): bool {
            $label = (string)($item['label'] ?? '');
            $perm = (string)($item['permission'] ?? '');
            $btn = (string)($item['button'] ?? '');
            return (bool)preg_match($pattern, $label . ' ' . $perm . ' ' . $btn);
        }));
        usort($filtered, static fn(array $a, array $b): int => strcmp((string)($a['label'] ?? ''), (string)($b['label'] ?? '')));
        return $filtered;
    }

    private function groupAdminMenus(array $menus): array
    {
        $groups = [
            'Setup & User' => [],
            'Bridging & Integrasi' => [],
            'Monitoring & Laporan' => [],
            'Lainnya' => [],
        ];

        foreach ($menus as $item) {
            $label = strtolower((string)($item['label'] ?? ''));
            $target = 'Lainnya';
            if (preg_match('/set user|user|setup|setting|konfigurasi|admin/', $label)) {
                $target = 'Setup & User';
            } elseif (preg_match('/bridging|bpjs|satusehat|satu sehat|mapping|referensi|vclaim|pcare|icare/', $label)) {
                $target = 'Bridging & Integrasi';
            } elseif (preg_match('/tracker|monitoring|laporan|rekap|grafik/', $label)) {
                $target = 'Monitoring & Laporan';
            }
            $groups[$target][] = $item;
        }

        return $groups;
    }

    private function groupConfigKeys(array $keys): array
    {
        sort($keys);
        $groups = [
            'Database & Host' => [],
            'BPJS & Bridging' => [],
            'Satu Sehat' => [],
            'Operasional' => [],
            'Lainnya' => [],
        ];

        foreach ($keys as $key) {
            $k = (string)$key;
            if (preg_match('/HOST|PORT|DATABASE|USER|PAS/', $k)) {
                $groups['Database & Host'][] = $k;
            } elseif (preg_match('/SATUSEHAT/', $k)) {
                $groups['Satu Sehat'][] = $k;
            } elseif (preg_match('/BPJS|PCARE|ICARE|INHEALTH|APLICARE|MOBILEJKN|SMARTCLAIM|SISRUTE|SIRS/', $k)) {
                $groups['BPJS & Bridging'][] = $k;
            } elseif (preg_match('/ANTRIAN|ALARM|BILLING|OBAT|TRACKSQL|KAMAR|JADWAL/', $k)) {
                $groups['Operasional'][] = $k;
            } else {
                $groups['Lainnya'][] = $k;
            }
        }

        return $groups;
    }

    private function groupSatuSehatActions(array $menus): array
    {
        $groups = [
            'Referensi & Mapping' => [],
            'Klinis' => [],
            'Farmasi' => [],
            'Penunjang' => [],
            'Lainnya' => [],
        ];

        foreach ($menus as $item) {
            $label = strtolower((string)($item['label'] ?? ''));
            $perm = (string)($item['permission'] ?? '');
            $btn = (string)($item['button'] ?? '');
            $nativeSupported = (bool)preg_match('/encounter/', $label);
            $entry = [
                'label' => (string)($item['label'] ?? '-'),
                'permission' => $perm,
                'button' => $btn,
                'legacy_url' => '../?page=legacy-module&perm=' . urlencode($perm) . '&btn=' . urlencode($btn),
                'catalog_url' => '../?page=menu-catalog&q=' . urlencode((string)($item['label'] ?? '')),
                'native_supported' => $nativeSupported,
                'native_url' => $nativeSupported ? '#native-encounter' : '',
            ];

            $target = 'Lainnya';
            if (preg_match('/referensi|mapping/', $label)) {
                $target = 'Referensi & Mapping';
            } elseif (preg_match('/medication|diet|care plan|clinical impression|condition|encounter|procedure|observation-ttv/', $label)) {
                $target = 'Klinis';
            } elseif (preg_match('/medication request|medication dispense|medication statement|obat|alkes|bhp/', $label)) {
                $target = 'Farmasi';
            } elseif (preg_match('/service request|specimen|diagnostic report|lab|radiologi|observation radiologi|observation lab/', $label)) {
                $target = 'Penunjang';
            }

            $groups[$target][] = $entry;
        }

        foreach ($groups as $key => $items) {
            usort($items, static fn(array $a, array $b): int => strcmp((string)$a['label'], (string)$b['label']));
            $groups[$key] = $items;
        }

        return $groups;
    }

    private function summarizeSatuSehatActions(array $menus): array
    {
        $labels = array_map(static fn(array $item): string => strtolower((string)($item['label'] ?? '')), $menus);
        return [
            'total' => count($menus),
            'referensi_mapping' => count(array_filter($labels, static fn(string $label): bool => (bool)preg_match('/referensi|mapping/', $label))),
            'klinis' => count(array_filter($labels, static fn(string $label): bool => (bool)preg_match('/encounter|condition|procedure|clinical impression|diet|care plan|observation-ttv/', $label))),
            'farmasi' => count(array_filter($labels, static fn(string $label): bool => (bool)preg_match('/medication|obat|alkes|bhp/', $label))),
            'penunjang' => count(array_filter($labels, static fn(string $label): bool => (bool)preg_match('/lab|radiologi|service request|specimen|diagnostic report/', $label))),
        ];
    }

    private function handleSatuSehatPost(): void
    {
        $action = trim((string)($_POST['satusehat_action'] ?? ''));
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $redirectParams = [
            'module' => 'bridging-satusehat',
            'date_from' => trim((string)($_POST['date_from'] ?? '')),
            'date_to' => trim((string)($_POST['date_to'] ?? '')),
            'q' => trim((string)($_POST['q'] ?? '')),
        ];

        if (!in_array($action, ['send-encounter', 'update-encounter'], true)) {
            $this->storeSatuSehatFlash('error', 'Aksi Satu Sehat tidak dikenali.');
            $this->redirectSatuSehat($redirectParams);
        }

        if ($noRawat === '') {
            $this->storeSatuSehatFlash('error', 'No. Rawat wajib dipilih untuk proses kirim Encounter.');
            $this->redirectSatuSehat($redirectParams);
        }

        $result = (new SatuSehatEncounterAdminService())->syncEncounter($noRawat);
        $this->storeSatuSehatFlash(
            $result['ok'] ? 'success' : 'error',
            (string)$result['message'],
            (string)($result['detail'] ?? '')
        );
        $this->redirectSatuSehat($redirectParams);
    }

    private function redirectSatuSehat(array $params): void
    {
        $filtered = array_filter($params, static fn(mixed $value): bool => trim((string)$value) !== '');
        header('Location: ?' . http_build_query($filtered) . '#native-encounter');
        exit;
    }

    private function storeSatuSehatFlash(string $type, string $message, string $detail = ''): void
    {
        $_SESSION[self::SATUSEHAT_FLASH_KEY] = [
            'type' => $type,
            'message' => $message,
            'detail' => $detail,
        ];
    }

    private function pullSatuSehatFlash(): array
    {
        $flash = $_SESSION[self::SATUSEHAT_FLASH_KEY] ?? [];
        unset($_SESSION[self::SATUSEHAT_FLASH_KEY]);
        return is_array($flash) ? $flash : [];
    }

    private function handleRbacPost(): void
    {
        $action = trim((string)($_POST['rbac_action'] ?? ''));
        if ($action === 'save_user') {
            $this->saveRbacUser();
            return;
        }
        if ($action === 'delete_user') {
            $this->deleteRbacUser();
            return;
        }
        if ($action === 'save_role_permissions') {
            $this->saveRolePermissions();
            return;
        }
        $this->storeRbacFlash('error', 'Aksi RBAC tidak dikenali.');
        $this->redirectRbac();
    }

    private function saveRbacUser(): void
    {
        $username = trim((string)($_POST['username'] ?? ''));
        $displayName = trim((string)($_POST['display_name'] ?? ''));
        $isActive = trim((string)($_POST['is_active'] ?? '1')) === '0' ? 0 : 1;
        $roleIdsRaw = $_POST['role_ids'] ?? [];
        $roleIds = [];
        if (is_array($roleIdsRaw)) {
            foreach ($roleIdsRaw as $roleId) {
                $id = (int)$roleId;
                if ($id > 0) {
                    $roleIds[] = $id;
                }
            }
        }
        $roleIds = array_values(array_unique($roleIds));

        if ($username === '') {
            $this->storeRbacFlash('error', 'Username wajib diisi.');
            $this->redirectRbac();
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO web_users (username, display_name, is_active)
                 VALUES (:username, :display_name, :is_active)
                 ON DUPLICATE KEY UPDATE
                    display_name = VALUES(display_name),
                    is_active = VALUES(is_active)"
            );
            $stmt->execute([
                'username' => $username,
                'display_name' => $displayName === '' ? $username : $displayName,
                'is_active' => $isActive,
            ]);

            $userIdStmt = $pdo->prepare('SELECT id FROM web_users WHERE username=:username LIMIT 1');
            $userIdStmt->execute(['username' => $username]);
            $userId = (int)($userIdStmt->fetchColumn() ?: 0);
            if ($userId <= 0) {
                throw new \RuntimeException('Gagal mendapatkan user id.');
            }

            $pdo->prepare('DELETE FROM web_user_roles WHERE user_id=:user_id')->execute(['user_id' => $userId]);

            if (!empty($roleIds)) {
                $insertRole = $pdo->prepare('INSERT IGNORE INTO web_user_roles (user_id, role_id) VALUES (:user_id,:role_id)');
                $roleCheck = $pdo->prepare('SELECT COUNT(*) FROM web_roles WHERE id=:id');
                foreach ($roleIds as $roleId) {
                    $roleCheck->execute(['id' => $roleId]);
                    if ((int)$roleCheck->fetchColumn() > 0) {
                        $insertRole->execute(['user_id' => $userId, 'role_id' => $roleId]);
                    }
                }
            }

            $pdo->commit();
            $this->storeRbacFlash('success', 'RBAC user berhasil disimpan: ' . $username);
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->storeRbacFlash('error', 'Gagal simpan RBAC user: ' . $e->getMessage());
        }

        $this->redirectRbac($username);
    }

    private function deleteRbacUser(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->storeRbacFlash('error', 'User RBAC tidak valid.');
            $this->redirectRbac();
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM web_user_roles WHERE user_id=:user_id')->execute(['user_id' => $userId]);
            $pdo->prepare('DELETE FROM web_users WHERE id=:id')->execute(['id' => $userId]);
            $pdo->commit();
            $this->storeRbacFlash('success', 'User RBAC berhasil dihapus.');
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->storeRbacFlash('error', 'Gagal hapus user RBAC: ' . $e->getMessage());
        }

        $this->redirectRbac();
    }

    private function saveRolePermissions(): void
    {
        $roleId = (int)($_POST['role_id'] ?? 0);
        $permissionIdsRaw = $_POST['permission_ids'] ?? [];
        $permissionIds = [];
        if (is_array($permissionIdsRaw)) {
            foreach ($permissionIdsRaw as $permissionId) {
                $id = (int)$permissionId;
                if ($id > 0) {
                    $permissionIds[] = $id;
                }
            }
        }
        $permissionIds = array_values(array_unique($permissionIds));

        if ($roleId <= 0) {
            $this->storeRbacFlash('error', 'Role tidak valid.');
            $this->redirectRbac('', 0);
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();

            $roleCheck = $pdo->prepare('SELECT COUNT(*) FROM web_roles WHERE id=:id');
            $roleCheck->execute(['id' => $roleId]);
            if ((int)$roleCheck->fetchColumn() <= 0) {
                throw new \RuntimeException('Role tidak ditemukan.');
            }

            $pdo->prepare('DELETE FROM web_role_permissions WHERE role_id=:role_id')->execute(['role_id' => $roleId]);

            if (!empty($permissionIds)) {
                $insert = $pdo->prepare('INSERT IGNORE INTO web_role_permissions (role_id, permission_id) VALUES (:role_id,:permission_id)');
                $permCheck = $pdo->prepare('SELECT COUNT(*) FROM web_permissions WHERE id=:id');
                foreach ($permissionIds as $permissionId) {
                    $permCheck->execute(['id' => $permissionId]);
                    if ((int)$permCheck->fetchColumn() > 0) {
                        $insert->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
                    }
                }
            }

            $pdo->commit();
            $this->storeRbacFlash('success', 'Permission role berhasil disimpan.');
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->storeRbacFlash('error', 'Gagal simpan role permission: ' . $e->getMessage());
        }

        $this->redirectRbac('', $roleId);
    }

    private function redirectRbac(string $editUsername = '', int $editRoleId = 0): void
    {
        $params = ['module' => 'rbac'];
        if ($editUsername !== '') {
            $params['edit'] = $editUsername;
        }
        header('Location: ?' . http_build_query($params));
        exit;
    }

    private function storeRbacFlash(string $type, string $message): void
    {
        $_SESSION[self::RBAC_FLASH_KEY] = ['type' => $type, 'message' => $message];
    }

    private function pullRbacFlash(): array
    {
        $flash = $_SESSION[self::RBAC_FLASH_KEY] ?? [];
        unset($_SESSION[self::RBAC_FLASH_KEY]);
        return is_array($flash) ? $flash : [];
    }

    private function loadRbacData(): array
    {
        $data = [
            'rbacTableReady' => false,
            'rbacUsers' => [],
            'rbacRoles' => [],
            'rbacPermissions' => [],
            'rbacEdit' => null,
            'rbacRoleEdit' => null,
            'rbacFlash' => $this->pullRbacFlash(),
            'rbacSummary' => ['users' => 0, 'roles' => 0, 'permissions' => 0],
            'rbacUserSearch' => trim((string)($_GET['u'] ?? '')),
            'rbacUserCandidates' => [],
        ];

        if (!$this->isRbacSchemaReady()) {
            return $data;
        }

        $data['rbacTableReady'] = true;

        try {
            $pdo = Database::pdo();
            $data['rbacSummary'] = [
                'users' => (int)($pdo->query('SELECT COUNT(*) FROM web_users')->fetchColumn() ?: 0),
                'roles' => (int)($pdo->query('SELECT COUNT(*) FROM web_roles')->fetchColumn() ?: 0),
                'permissions' => (int)($pdo->query('SELECT COUNT(*) FROM web_permissions')->fetchColumn() ?: 0),
            ];

            $data['rbacRoles'] = $pdo->query(
                "SELECT r.id, r.role_code, r.role_name, r.is_active,
                        COUNT(DISTINCT rp.permission_id) AS permission_count,
                        COALESCE(GROUP_CONCAT(DISTINCT p.permission_code ORDER BY p.permission_code SEPARATOR ', '), '-') AS permissions
                 FROM web_roles r
                 LEFT JOIN web_role_permissions rp ON rp.role_id = r.id
                 LEFT JOIN web_permissions p ON p.id = rp.permission_id
                 GROUP BY r.id, r.role_code, r.role_name, r.is_active
                 ORDER BY r.role_code"
            )->fetchAll() ?: [];

            $data['rbacPermissions'] = $pdo->query(
                "SELECT id, permission_code, permission_name, is_active
                 FROM web_permissions
                 ORDER BY permission_code"
            )->fetchAll() ?: [];

            $data['rbacUsers'] = $pdo->query(
                "SELECT u.id, u.username, u.display_name, u.is_active,
                        COALESCE(GROUP_CONCAT(r.role_code ORDER BY r.role_code SEPARATOR ', '), '-') AS roles
                 FROM web_users u
                 LEFT JOIN web_user_roles ur ON ur.user_id = u.id
                 LEFT JOIN web_roles r ON r.id = ur.role_id
                 GROUP BY u.id, u.username, u.display_name, u.is_active
                 ORDER BY u.username"
            )->fetchAll() ?: [];

            $editUsername = trim((string)($_GET['edit'] ?? ''));
            if ($editUsername !== '') {
                $editStmt = $pdo->prepare('SELECT id, username, display_name, is_active FROM web_users WHERE username=:username LIMIT 1');
                $editStmt->execute(['username' => $editUsername]);
                $editRow = $editStmt->fetch();
                if ($editRow) {
                    $roleStmt = $pdo->prepare('SELECT role_id FROM web_user_roles WHERE user_id=:user_id');
                    $roleStmt->execute(['user_id' => (int)$editRow['id']]);
                    $editRow['role_ids'] = array_map('intval', array_column($roleStmt->fetchAll() ?: [], 'role_id'));
                    $data['rbacEdit'] = $editRow;
                }
            }

            $editRoleId = (int)($_GET['edit_role'] ?? 0);
            if ($editRoleId > 0) {
                $roleEditStmt = $pdo->prepare('SELECT id, role_code, role_name, is_active FROM web_roles WHERE id=:id LIMIT 1');
                $roleEditStmt->execute(['id' => $editRoleId]);
                $roleEdit = $roleEditStmt->fetch();
                if ($roleEdit) {
                    $rpStmt = $pdo->prepare('SELECT permission_id FROM web_role_permissions WHERE role_id=:role_id');
                    $rpStmt->execute(['role_id' => $editRoleId]);
                    $roleEdit['permission_ids'] = array_map('intval', array_column($rpStmt->fetchAll() ?: [], 'permission_id'));
                    $data['rbacRoleEdit'] = $roleEdit;
                }
            }

            $search = $data['rbacUserSearch'];
            if ($search !== '') {
                $candStmt = $pdo->prepare(
                    "SELECT DISTINCT CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4) AS username, p.nama
                     FROM user u
                     LEFT JOIN pegawai p
                        ON p.nik = CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4)
                        OR p.id = CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4)
                     WHERE CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4) LIKE :q
                     ORDER BY username
                     LIMIT 30"
                );
                $candStmt->execute(['q' => '%' . $search . '%']);
                $data['rbacUserCandidates'] = $candStmt->fetchAll() ?: [];
            }
        } catch (\Throwable $e) {
            $data['rbacFlash'] = ['type' => 'error', 'message' => 'Gagal load data RBAC: ' . $e->getMessage()];
        }

        return $data;
    }

    private function isRbacSchemaReady(): bool
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->query(
                "SELECT COUNT(*)
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name IN ('web_users','web_roles','web_permissions','web_role_permissions','web_user_roles')"
            );
            return ((int)($stmt->fetchColumn() ?: 0)) === 5;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function legacyQuickLinks(): array
    {
        return [
            ['label' => 'Katalog Menu SIMRS', 'url' => '../?page=menu-catalog'],
            ['label' => 'Konfigurasi Legacy', 'url' => '../?page=konfigurasi'],
            ['label' => 'Tracker SQL', 'url' => '../?page=tracker'],
            ['label' => 'Bridging BPJS', 'url' => '../?page=bridging-bpjs'],
        ];
    }
}












