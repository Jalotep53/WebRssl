<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Services\SimrsQueryService;

final class DashboardController
{
    public function index(): void
    {
        $db = new SimrsQueryService();

        $summary = [
            'kunjungan_hari_ini' => (int)$this->num($db, "SELECT COUNT(*) FROM reg_periksa WHERE DATE(tgl_registrasi)=CURDATE()"),
            'pasien_baru_hari_ini' => (int)$this->num($db, "SELECT COUNT(*) FROM reg_periksa WHERE DATE(tgl_registrasi)=CURDATE() AND status_poli='Baru'"),
            'resep_belum_validasi' => (int)$this->num(
                $db,
                "SELECT COUNT(*)
                 FROM resep_obat ro
                 INNER JOIN reg_periksa rp ON rp.no_rawat=ro.no_rawat
                 WHERE ro.status='ralan'
                   AND (ro.tgl_perawatan='0000-00-00' OR ro.tgl_perawatan IS NULL)
                   AND DATE(ro.tgl_peresepan)=CURDATE()"
            ),
            'billing_belum_lunas' => (int)$this->num(
                $db,
                "SELECT COUNT(*)
                 FROM reg_periksa
                 WHERE DATE(tgl_registrasi)=CURDATE()
                   AND status_lanjut='Ralan'
                   AND status_bayar='Belum Bayar'"
            ),
        ];

        $queue = [
            'registrasi' => [
                'menunggu' => (int)$this->num($db, "SELECT COUNT(*) FROM reg_periksa WHERE DATE(tgl_registrasi)=CURDATE() AND stts='Belum'"),
                'proses' => (int)$this->num(
                    $db,
                    "SELECT COUNT(*)
                     FROM reg_periksa
                     WHERE DATE(tgl_registrasi)=CURDATE()
                       AND stts='Sudah'
                       AND status_bayar='Belum Bayar'"
                ),
                'selesai' => (int)$this->num(
                    $db,
                    "SELECT COUNT(*)
                     FROM reg_periksa
                     WHERE DATE(tgl_registrasi)=CURDATE()
                       AND status_bayar='Sudah Bayar'"
                ),
            ],
            'rawatjalan' => [
                'menunggu' => (int)$this->num(
                    $db,
                    "SELECT COUNT(*)
                     FROM reg_periksa rp
                     LEFT JOIN pemeriksaan_ralan pr ON pr.no_rawat=rp.no_rawat
                     LEFT JOIN rawat_jl_dr rjd ON rjd.no_rawat=rp.no_rawat
                     LEFT JOIN rawat_jl_pr rjp ON rjp.no_rawat=rp.no_rawat
                     LEFT JOIN rawat_jl_drpr rjdp ON rjdp.no_rawat=rp.no_rawat
                     WHERE DATE(rp.tgl_registrasi)=CURDATE()
                       AND rp.status_lanjut='Ralan'
                     GROUP BY rp.no_rawat
                     HAVING COUNT(pr.no_rawat)=0 AND COUNT(rjd.no_rawat)=0 AND COUNT(rjp.no_rawat)=0 AND COUNT(rjdp.no_rawat)=0",
                    true
                ),
                'proses' => (int)$this->num(
                    $db,
                    "SELECT COUNT(*)
                     FROM reg_periksa
                     WHERE DATE(tgl_registrasi)=CURDATE()
                       AND status_lanjut='Ralan'
                       AND status_bayar='Belum Bayar'
                       AND stts='Sudah'"
                ),
                'selesai' => (int)$this->num(
                    $db,
                    "SELECT COUNT(*)
                     FROM reg_periksa
                     WHERE DATE(tgl_registrasi)=CURDATE()
                       AND status_lanjut='Ralan'
                       AND status_bayar='Sudah Bayar'"
                ),
            ],
            'farmasi' => [
                'menunggu' => (int)$this->num(
                    $db,
                    "SELECT COUNT(*)
                     FROM resep_obat ro
                     WHERE ro.status='ralan'
                       AND DATE(ro.tgl_peresepan)=CURDATE()
                       AND (ro.tgl_perawatan='0000-00-00' OR ro.tgl_perawatan IS NULL)"
                ),
                'proses' => (int)$this->num(
                    $db,
                    "SELECT COUNT(*)
                     FROM resep_obat ro
                     WHERE ro.status='ralan'
                       AND DATE(ro.tgl_peresepan)=CURDATE()
                       AND ro.tgl_perawatan<>'0000-00-00'
                       AND (ro.tgl_penyerahan='0000-00-00' OR ro.tgl_penyerahan IS NULL)"
                ),
                'selesai' => (int)$this->num(
                    $db,
                    "SELECT COUNT(*)
                     FROM resep_obat ro
                     WHERE ro.status='ralan'
                       AND DATE(ro.tgl_peresepan)=CURDATE()
                       AND ro.tgl_penyerahan<>'0000-00-00'"
                ),
            ],
        ];

        $alerts = [
            'resep_pending_lama' => (int)$this->num(
                $db,
                "SELECT COUNT(*)
                 FROM resep_obat ro
                 WHERE ro.status='ralan'
                   AND (ro.tgl_perawatan='0000-00-00' OR ro.tgl_perawatan IS NULL)
                   AND TIMESTAMP(ro.tgl_peresepan, ro.jam_peresepan) <= (NOW() - INTERVAL 2 HOUR)"
            ),
            'permintaan_lab_pending' => (int)$this->num(
                $db,
                "SELECT COUNT(*)
                 FROM permintaan_lab
                 WHERE DATE(tgl_permintaan)=CURDATE()"
            ),
            'permintaan_rad_pending' => (int)$this->num(
                $db,
                "SELECT COUNT(*)
                 FROM permintaan_radiologi
                 WHERE DATE(tgl_permintaan)=CURDATE()"
            ),
            'billing_belum_bayar' => (int)$this->num(
                $db,
                "SELECT COUNT(*)
                 FROM reg_periksa
                 WHERE DATE(tgl_registrasi)=CURDATE()
                   AND status_lanjut='Ralan'
                   AND status_bayar='Belum Bayar'"
            ),
        ];

        $sevenDays = $this->build7DaySeries($db);
        $topPoli7Day = $db->run(
            "SELECT pl.nm_poli, COUNT(*) AS total
             FROM reg_periksa rp
             INNER JOIN poliklinik pl ON pl.kd_poli=rp.kd_poli
             WHERE rp.tgl_registrasi BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
               AND rp.status_lanjut='Ralan'
             GROUP BY pl.kd_poli, pl.nm_poli
             ORDER BY total DESC
             LIMIT 7"
        )['data'];

        view('dashboard', [
            'title' => 'Dashboard Operasional',
            'summary' => $summary,
            'queue' => $queue,
            'alerts' => $alerts,
            'series7' => $sevenDays,
            'topPoli7Day' => $topPoli7Day,
        ]);
    }

    private function num(SimrsQueryService $db, string $sql, bool $groupCount = false): float
    {
        if ($groupCount) {
            $res = $db->run($sql);
            return $res['ok'] ? (float)count($res['data']) : 0.0;
        }
        $res = $db->value($sql);
        return $res['ok'] ? (float)($res['data'] ?? 0) : 0.0;
    }

    private function build7DaySeries(SimrsQueryService $db): array
    {
        $rawVisit = $db->run(
            "SELECT DATE(tgl_registrasi) AS tgl, COUNT(*) AS total
             FROM reg_periksa
             WHERE tgl_registrasi BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
               AND status_lanjut='Ralan'
             GROUP BY DATE(tgl_registrasi)"
        );
        $rawRevenue = $db->run(
            "SELECT DATE(b.tgl_byr) AS tgl, IFNULL(SUM(b.totalbiaya),0) AS total
             FROM billing b
             INNER JOIN reg_periksa rp ON rp.no_rawat=b.no_rawat
             WHERE b.tgl_byr BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
               AND rp.status_lanjut='Ralan'
             GROUP BY DATE(b.tgl_byr)"
        );

        $visitMap = [];
        foreach (($rawVisit['data'] ?? []) as $row) {
            $visitMap[(string)$row['tgl']] = (int)$row['total'];
        }
        $revMap = [];
        foreach (($rawRevenue['data'] ?? []) as $row) {
            $revMap[(string)$row['tgl']] = (float)$row['total'];
        }

        $rows = [];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = date('Y-m-d', strtotime("-{$i} day"));
            $rows[] = [
                'tgl' => $tgl,
                'label' => date('d/m', strtotime($tgl)),
                'kunjungan' => $visitMap[$tgl] ?? 0,
                'pendapatan' => $revMap[$tgl] ?? 0.0,
            ];
        }
        return $rows;
    }
}

