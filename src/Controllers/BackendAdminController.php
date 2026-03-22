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
    private const USER_FLASH_KEY = 'backend_user_flash';
    public function index(): void
    {
        $module = trim((string)($_GET['module'] ?? 'dashboard'));
        $allowed = ['dashboard', 'modules', 'config', 'bridging-bpjs', 'bridging-satusehat', 'user', 'rbac', 'monitoring'];
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

        if ($module === 'user' && strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->handleUserPost();
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

            case 'user':
                $data['title'] = 'Backend Admin - User';
                $data['viewFile'] = 'backend_user';
                $data = array_merge($data, $this->loadUserModuleData());
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
            ['key' => 'user', 'label' => 'User', 'desc' => 'Daftar akun legacy dan ringkasan akses penting.'],
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

    private function loadUserModuleData(): array
    {
        $importantColumns = $this->userImportantColumns();
        $data = [
            'userModuleReady' => false,
            'userModuleError' => '',
            'userSearch' => trim((string)($_GET['q'] ?? '')),
            'userList' => [],
            'userSummary' => [
                'total_users' => 0,
                'linked_pegawai' => 0,
                'avg_access' => 0,
                'important_access_cols' => count($importantColumns),
            ],
            'userEdit' => null,
            'userFlash' => $this->pullUserFlash(),
            'userImportantColumns' => $importantColumns,
        ];

        try {
            $pdo = Database::pdo();
            $metadata = $this->getUserTableMetadata($pdo);
            $enumColumns = $metadata['enum_columns'];
            $accessCountExpr = $metadata['access_count_expr'];
            $importantSelect = $metadata['important_select'];

            $search = $data['userSearch'];
            $where = '';
            $params = [];
            if ($search !== '') {
                $where = "WHERE (
                    CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4) LIKE :q
                    OR COALESCE(p.nama, '') LIKE :q
                    OR COALESCE(p.nik, '') LIKE :q
                    OR COALESCE(CAST(p.id AS CHAR), '') LIKE :q
                )";
                $params['q'] = '%' . $search . '%';
            }

            $summarySql = "
                SELECT
                    COUNT(*) AS total_users,
                    SUM(CASE WHEN p.nama IS NOT NULL AND p.nama <> '' THEN 1 ELSE 0 END) AS linked_pegawai,
                    AVG($accessCountExpr) AS avg_access
                FROM user u
                LEFT JOIN pegawai p
                  ON p.nik = CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4)
                  OR p.id = CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4)
                $where
            ";
            $summaryStmt = $pdo->prepare($summarySql);
            $summaryStmt->execute($params);
            $summaryRow = $summaryStmt->fetch() ?: [];

            $listSql = "
                SELECT
                    CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4) AS username,
                    COALESCE(NULLIF(p.nama, ''), '-') AS nama_pegawai,
                    COALESCE(NULLIF(p.jbtn, ''), '-') AS jabatan,
                    ($accessCountExpr) AS akses_aktif,
                    $importantSelect
                FROM user u
                LEFT JOIN pegawai p
                  ON p.nik = CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4)
                  OR p.id = CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4)
                $where
                ORDER BY username
                LIMIT 100
            ";
            $listStmt = $pdo->prepare($listSql);
            $listStmt->execute($params);
            $rows = $listStmt->fetchAll() ?: [];

            foreach ($rows as &$row) {
                $badges = [];
                foreach ($importantColumns as $column) {
                    if (($row[$column] ?? 'false') === 'true') {
                        $badges[] = $column;
                    }
                    unset($row[$column]);
                }
                $row['akses_penting'] = $badges;
            }
            unset($row);

            $editUsername = trim((string)($_GET['edit'] ?? ''));
            if ($editUsername !== '') {
                $editSelectParts = ["CONVERT(AES_DECRYPT(u.id_user,'nur') USING utf8mb4) AS username"];
                foreach ($importantColumns as $column) {
                    $editSelectParts[] = sprintf("u.`%s` AS `%s`", $column, $column);
                }
                $editStmt = $pdo->prepare(
                    "SELECT " . implode(", ", $editSelectParts) . "
                     FROM user u
                     WHERE u.id_user=AES_ENCRYPT(:username,'nur')
                     LIMIT 1"
                );
                $editStmt->execute(['username' => $editUsername]);
                $editRow = $editStmt->fetch();
                if ($editRow) {
                    $editRow['important_permissions'] = [];
                    foreach ($importantColumns as $column) {
                        $editRow['important_permissions'][$column] = (($editRow[$column] ?? 'false') === 'true');
                        unset($editRow[$column]);
                    }
                    $data['userEdit'] = $editRow;
                }
            }

            $data['userModuleReady'] = true;
            $data['userList'] = $rows;
            $data['userSummary'] = [
                'total_users' => (int)($summaryRow['total_users'] ?? 0),
                'linked_pegawai' => (int)($summaryRow['linked_pegawai'] ?? 0),
                'avg_access' => (int)round((float)($summaryRow['avg_access'] ?? 0)),
                'important_access_cols' => count($importantColumns),
            ];
        } catch (\Throwable $e) {
            $data['userModuleError'] = $e->getMessage();
        }

        return $data;
    }

    private function handleUserPost(): void
    {
        $action = trim((string)($_POST['user_action'] ?? ''));
        if ($action === 'save_user') {
            $this->saveLegacyUser();
            return;
        }
        if ($action === 'delete_user') {
            $this->deleteLegacyUser();
            return;
        }
        $this->storeUserFlash('error', 'Aksi user tidak dikenali.');
        $this->redirectUserModule();
    }

    private function saveLegacyUser(): void
    {
        $originalUsername = trim((string)($_POST['original_username'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $selectedPermissionsRaw = $_POST['important_permissions'] ?? [];
        $selectedPermissions = [];
        if (is_array($selectedPermissionsRaw)) {
            foreach ($selectedPermissionsRaw as $permission) {
                $permission = trim((string)$permission);
                if ($permission !== '') {
                    $selectedPermissions[] = $permission;
                }
            }
        }
        $selectedPermissions = array_values(array_unique($selectedPermissions));

        if ($username === '') {
            $this->storeUserFlash('error', 'Username wajib diisi.');
            $this->redirectUserModule();
        }

        try {
            $pdo = Database::pdo();
            $metadata = $this->getUserTableMetadata($pdo);
            $enumColumns = $metadata['enum_columns'];
            $importantColumns = $this->userImportantColumns();
            $validPermissionMap = array_fill_keys($importantColumns, true);
            $selectedPermissions = array_values(array_filter(
                $selectedPermissions,
                static fn(string $permission): bool => isset($validPermissionMap[$permission])
            ));

            if ($originalUsername === '') {
                if ($password === '') {
                    throw new \RuntimeException('Password wajib diisi untuk user baru.');
                }

                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE id_user=AES_ENCRYPT(:username,'nur')");
                $checkStmt->execute(['username' => $username]);
                if ((int)$checkStmt->fetchColumn() > 0) {
                    throw new \RuntimeException('Username sudah ada.');
                }

                $insertColumns = ['id_user', 'password'];
                $insertValues = ["AES_ENCRYPT(:username,'nur')", "AES_ENCRYPT(:password,'windi')"];
                $params = [
                    'username' => $username,
                    'password' => $password,
                ];
                foreach ($enumColumns as $column) {
                    $insertColumns[] = sprintf('`%s`', $column);
                    $insertValues[] = ':' . $column;
                    $params[$column] = in_array($column, $selectedPermissions, true) ? 'true' : 'false';
                }

                $sql = sprintf(
                    'INSERT INTO user (%s) VALUES (%s)',
                    implode(', ', $insertColumns),
                    implode(', ', $insertValues)
                );
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $this->storeUserFlash('success', 'User legacy berhasil ditambahkan: ' . $username);
                $this->redirectUserModule('', $username);
            }

            $checkOriginalStmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE id_user=AES_ENCRYPT(:username,'nur')");
            $checkOriginalStmt->execute(['username' => $originalUsername]);
            if ((int)$checkOriginalStmt->fetchColumn() <= 0) {
                throw new \RuntimeException('User yang akan diedit tidak ditemukan.');
            }

            if ($username !== $originalUsername) {
                $checkTargetStmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE id_user=AES_ENCRYPT(:username,'nur')");
                $checkTargetStmt->execute(['username' => $username]);
                if ((int)$checkTargetStmt->fetchColumn() > 0) {
                    throw new \RuntimeException('Username tujuan sudah dipakai user lain.');
                }
            }

            $setParts = ["id_user=AES_ENCRYPT(:new_username,'nur')"];
            $params = [
                'new_username' => $username,
                'original_username' => $originalUsername,
            ];
            if ($password !== '') {
                $setParts[] = "password=AES_ENCRYPT(:password,'windi')";
                $params['password'] = $password;
            }
            foreach ($importantColumns as $column) {
                $setParts[] = sprintf("`%s`=:%s", $column, $column);
                $params[$column] = in_array($column, $selectedPermissions, true) ? 'true' : 'false';
            }

            $sql = sprintf(
                "UPDATE user SET %s WHERE id_user=AES_ENCRYPT(:original_username,'nur')",
                implode(', ', $setParts)
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $this->storeUserFlash('success', 'User legacy berhasil diperbarui: ' . $username);
            $this->redirectUserModule('', $username);
        } catch (\Throwable $e) {
            $this->storeUserFlash('error', 'Gagal simpan user legacy: ' . $e->getMessage());
            $editTarget = $originalUsername !== '' ? $originalUsername : $username;
            $this->redirectUserModule('', $editTarget);
        }
    }

    private function deleteLegacyUser(): void
    {
        $username = trim((string)($_POST['username'] ?? ''));
        if ($username === '') {
            $this->storeUserFlash('error', 'User tidak valid.');
            $this->redirectUserModule();
        }

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("DELETE FROM user WHERE id_user=AES_ENCRYPT(:username,'nur')");
            $stmt->execute(['username' => $username]);
            if ($stmt->rowCount() <= 0) {
                throw new \RuntimeException('User tidak ditemukan atau sudah terhapus.');
            }
            $this->storeUserFlash('success', 'User legacy berhasil dihapus: ' . $username);
        } catch (\Throwable $e) {
            $this->storeUserFlash('error', 'Gagal hapus user legacy: ' . $e->getMessage());
        }

        $this->redirectUserModule();
    }

    private function redirectUserModule(string $search = '', string $edit = ''): void
    {
        $params = ['module' => 'user'];
        if ($search !== '') {
            $params['q'] = $search;
        }
        if ($edit !== '') {
            $params['edit'] = $edit;
        }
        header('Location: ?' . http_build_query($params));
        exit;
    }

    private function storeUserFlash(string $type, string $message): void
    {
        $_SESSION[self::USER_FLASH_KEY] = ['type' => $type, 'message' => $message];
    }

    private function pullUserFlash(): array
    {
        $flash = $_SESSION[self::USER_FLASH_KEY] ?? [];
        unset($_SESSION[self::USER_FLASH_KEY]);
        return is_array($flash) ? $flash : [];
    }

    private function getUserTableMetadata(\PDO $pdo): array
    {
        $columnsStmt = $pdo->query('SHOW COLUMNS FROM user');
        $columns = $columnsStmt ? ($columnsStmt->fetchAll() ?: []) : [];
        if (empty($columns)) {
            throw new \RuntimeException('Schema tabel user tidak terbaca.');
        }

        $enumColumns = [];
        foreach ($columns as $column) {
            $field = (string)($column['Field'] ?? '');
            $type = strtolower((string)($column['Type'] ?? ''));
            if ($field !== '' && str_starts_with($type, 'enum(')) {
                $enumColumns[] = $field;
            }
        }

        if (empty($enumColumns)) {
            throw new \RuntimeException('Kolom akses user tidak ditemukan.');
        }

        $accessCountExpr = implode(' + ', array_map(
            static fn(string $field): string => sprintf("(u.`%s`='true')", str_replace('`', '', $field)),
            $enumColumns
        ));
        $importantSelect = implode(",\n                        ", array_map(
            static fn(string $field): string => sprintf("u.`%s` AS `%s`", str_replace('`', '', $field), str_replace('`', '', $field)),
            $this->userImportantColumns()
        ));

        return [
            'enum_columns' => $enumColumns,
            'access_count_expr' => $accessCountExpr,
            'important_select' => $importantSelect,
        ];
    }

    private function userImportantColumns(): array
    {
        return [
            'registrasi',
            'billing_ralan',
            'billing_ranap',
            'periksa_lab',
            'periksa_radiologi',
            'bpjs_sep',
            'satu_sehat_kirim_encounter',
            'stok_opname_obat',
        ];
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












