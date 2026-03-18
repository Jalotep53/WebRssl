<?php

declare(strict_types=1);

namespace WebBaru\Services;

final class LegacyScanService
{
    public function menuAccessMap(): array
    {
        $file = WEBBARU_ROOT . '/docs_menu_access.json';
        if (!is_file($file)) {
            return [];
        }
        $json = file_get_contents($file);
        $data = json_decode((string)$json, true);
        return is_array($data) ? $data : [];
    }

    public function configKeys(): array
    {
        $file = WEBBARU_ROOT . '/docs_config_keys.json';
        if (!is_file($file)) {
            return [];
        }
        $json = file_get_contents($file);
        $data = json_decode((string)$json, true);
        return is_array($data) ? $data : [];
    }

    public function packageStats(): array
    {
        $file = WEBBARU_ROOT . '/docs_packages.json';
        if (!is_file($file)) {
            return [];
        }
        $json = file_get_contents($file);
        $data = json_decode((string)$json, true);
        return is_array($data) ? $data : [];
    }

    public function groupedMenus(array $map): array
    {
        $groups = [
            'Registrasi & RM' => [],
            'Pelayanan Medis' => [],
            'Farmasi & Gudang' => [],
            'Keuangan & Billing' => [],
            'Bridging & Integrasi' => [],
            'Laporan & Analitik' => [],
            'SDM & Umum' => [],
            'Lainnya' => [],
        ];

        foreach ($map as $item) {
            $label = strtolower((string)($item['label'] ?? ''));
            $target = 'Lainnya';
            if (preg_match('/registrasi|rawat|ranap|ralan|pasien|rujukan|rekam|rm|soap|resume/', $label)) {
                $target = 'Registrasi & RM';
            } elseif (preg_match('/lab|radiologi|operasi|igd|utd|diagnosa|tindakan|dokter|poli/', $label)) {
                $target = 'Pelayanan Medis';
            } elseif (preg_match('/obat|apotek|alkes|bhp|stok|gudang|suplier|pemesanan|pengadaan/', $label)) {
                $target = 'Farmasi & Gudang';
            } elseif (preg_match('/billing|pembayaran|piutang|hutang|jurnal|cash|rekening|keuangan|kasir/', $label)) {
                $target = 'Keuangan & Billing';
            } elseif (preg_match('/bpjs|pcare|vclaim|inhealth|satusehat|aplicare|bridging|sisrute|sirs/', $label)) {
                $target = 'Bridging & Integrasi';
            } elseif (preg_match('/laporan|rekap|grafik|sensus|rl\\s|monitoring|analisa/', $label)) {
                $target = 'Laporan & Analitik';
            } elseif (preg_match('/pegawai|presensi|parkir|inventaris|surat|pengaduan|setup|set\\s/', $label)) {
                $target = 'SDM & Umum';
            }
            $groups[$target][] = $item;
        }

        foreach ($groups as $k => $items) {
            usort($items, static fn(array $a, array $b): int => strcmp((string)$a['label'], (string)$b['label']));
            $groups[$k] = $items;
        }
        return $groups;
    }
}

