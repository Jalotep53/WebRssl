<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Services\LegacyMenuRouteService;
use WebBaru\Services\LegacyScanService;

final class LegacyModuleController
{
    public function index(): void
    {
        $permission = trim((string)($_GET['perm'] ?? ''));
        $button = trim((string)($_GET['btn'] ?? ''));

        $scan = new LegacyScanService();
        $route = new LegacyMenuRouteService();
        $menus = $scan->menuAccessMap();

        $selected = null;
        foreach ($menus as $m) {
            if (((string)($m['permission'] ?? '') === $permission) && ((string)($m['button'] ?? '') === $button)) {
                $selected = $m;
                break;
            }
        }

        if ($selected === null) {
            view('legacy_module', [
                'title' => 'Modul Legacy',
                'found' => false,
                'selected' => null,
                'implemented' => false,
                'targetPage' => null,
            ]);
            return;
        }

        $target = $route->resolvePage((string)$selected['permission']);
        view('legacy_module', [
            'title' => 'Modul Legacy: ' . (string)($selected['label'] ?? ''),
            'found' => true,
            'selected' => $selected,
            'implemented' => $target !== null,
            'targetPage' => $target,
        ]);
    }
}

