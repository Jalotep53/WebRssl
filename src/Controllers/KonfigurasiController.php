<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Services\LegacyScanService;

final class KonfigurasiController
{
    public function index(): void
    {
        $scan = new LegacyScanService();
        $keys = $scan->configKeys();
        sort($keys);

        $groups = [
            'Database & Koneksi' => [],
            'Bridging/API' => [],
            'Operasional' => [],
            'Lainnya' => [],
        ];

        foreach ($keys as $key) {
            $k = (string)$key;
            if (preg_match('/HOST|PORT|DATABASE|USER|PAS/', $k)) {
                $groups['Database & Koneksi'][] = $k;
            } elseif (preg_match('/API|BPJS|PCARE|SATUSEHAT|APLICARE|INHEALTH|SIRS|SISRUTE|DUKCAPIL|ORTHANC|LIS/', $k)) {
                $groups['Bridging/API'][] = $k;
            } elseif (preg_match('/ANTRIAN|ALARM|BILLING|HARGA|OBAT|PRESENSI|KAMAR|JADWAL|TRACKSQL|CARICEPAT/', $k)) {
                $groups['Operasional'][] = $k;
            } else {
                $groups['Lainnya'][] = $k;
            }
        }

        view('konfigurasi', [
            'title' => 'Konfigurasi Legacy',
            'groups' => $groups,
            'count' => count($keys),
        ]);
    }
}

