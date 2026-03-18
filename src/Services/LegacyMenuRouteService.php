<?php

declare(strict_types=1);

namespace WebBaru\Services;

final class LegacyMenuRouteService
{
    /**
     * Pemetaan permission legacy ke route web yang sudah fungsional.
     */
    private const MAP = [
        'registrasi' => 'registrasi',
        'tindakan_ralan' => 'rawatjalan',
        'periksa_lab' => 'rawatjalan',
        'periksa_radiologi' => 'rawatjalan',
        'beri_obat' => 'farmasi',
        'resep_obat' => 'farmasi',
        'pengadaan_obat' => 'farmasi',
        'pemesanan_obat' => 'farmasi',
        'billing_ralan' => 'billing-ralan',
        'pembayaran_ralan' => 'billing-ralan',
        'laporan_tindakan' => 'laporan',
    ];

    public function resolvePage(string $permission): ?string
    {
        return self::MAP[$permission] ?? null;
    }

    public function isImplemented(string $permission): bool
    {
        return isset(self::MAP[$permission]);
    }
}

