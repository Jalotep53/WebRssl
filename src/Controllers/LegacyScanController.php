<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Services\LegacyScanService;

final class LegacyScanController
{
    public function index(): void
    {
        $scan = new LegacyScanService();
        $menuMap = $scan->menuAccessMap();
        $configKeys = $scan->configKeys();
        $packages = $scan->packageStats();

        view('legacy_scan', [
            'title' => 'Scan SIMRS Legacy',
            'menuCount' => count($menuMap),
            'permissionCount' => count(array_unique(array_map(static fn($m) => (string)($m['permission'] ?? ''), $menuMap))),
            'configCount' => count($configKeys),
            'packages' => array_slice($packages, 0, 12),
            'legacyRoot' => LEGACY_ROOT,
        ]);
    }
}

