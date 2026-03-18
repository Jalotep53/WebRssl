<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Services\LegacyScanService;
use WebBaru\Services\LegacyMenuRouteService;

final class MenuCatalogController
{
    public function index(): void
    {
        $q = strtolower(trim((string)($_GET['q'] ?? '')));
        $scan = new LegacyScanService();
        $route = new LegacyMenuRouteService();
        $menus = $scan->menuAccessMap();
        if ($q !== '') {
            $menus = array_values(array_filter($menus, static function (array $m) use ($q): bool {
                $label = strtolower((string)($m['label'] ?? ''));
                $perm = strtolower((string)($m['permission'] ?? ''));
                $btn = strtolower((string)($m['button'] ?? ''));
                return str_contains($label, $q) || str_contains($perm, $q) || str_contains($btn, $q);
            }));
        }

        foreach ($menus as &$m) {
            $perm = (string)($m['permission'] ?? '');
            $btn = (string)($m['button'] ?? '');
            $m['implemented'] = $route->isImplemented($perm);
            $m['url'] = '?page=legacy-module&perm=' . urlencode($perm) . '&btn=' . urlencode($btn);
        }
        unset($m);

        view('menu_catalog', [
            'title' => 'Katalog Menu SIMRS',
            'query' => $q,
            'menus' => $menus,
            'total' => count($menus),
            'groups' => $scan->groupedMenus($menus),
        ]);
    }
}
