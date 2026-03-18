<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use PDO;
use Throwable;
use WebBaru\Database;
use WebBaru\Services\SimrsQueryService;

final class BerkasRmController
{
    public function index(): void
    {
        $db = new SimrsQueryService();
        $noRawat = trim((string)($_GET['no_rawat'] ?? ''));
        $embedMode = trim((string)($_GET['embed'] ?? '')) === '1';

        $visit = null;
        $forms = [];
        $sections = [];
        $error = null;

        if ($noRawat === '') {
            $error = 'No. rawat belum dipilih';
        } else {
            $visitRes = $db->run(
                "SELECT rp.no_rawat, rp.no_rkm_medis, rp.tgl_registrasi, rp.jam_reg, rp.status_lanjut,
                        p.nm_pasien, pl.nm_poli, d.nm_dokter, pj.png_jawab
                 FROM reg_periksa rp
                 INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
                 LEFT JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
                 LEFT JOIN dokter d ON d.kd_dokter = rp.kd_dokter
                 LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
                 WHERE rp.no_rawat = :no_rawat
                 LIMIT 1",
                ['no_rawat' => $noRawat]
            );

            if (!$visitRes['ok']) {
                $error = (string)($visitRes['error'] ?? 'Gagal mengambil data kunjungan');
            } elseif (empty($visitRes['data'])) {
                $error = 'Data kunjungan tidak ditemukan';
            } else {
                $visit = $visitRes['data'][0];
                $isRanap = strtoupper((string)($visit['status_lanjut'] ?? '')) === 'RANAP';
                $sections = $isRanap ? $this->ranapForms() : $this->ralanForms();
                $forms = $this->loadFormStatus(Database::pdo(), $noRawat, $sections);
            }
        }

        view('berkas_rm', [
            'title' => 'Berkas Rekam Medik',
            'embedMode' => $embedMode,
            'noRawat' => $noRawat,
            'visit' => $visit,
            'sections' => $sections,
            'forms' => $forms,
            'error' => $error,
        ]);
    }

    private function ralanForms(): array
    {
        return [
            'Asesmen Keperawatan' => [
                ['key' => 'kep_ralan', 'label' => 'Penilaian Awal Keperawatan Rawat Jalan', 'table' => 'penilaian_awal_keperawatan_ralan'],
                ['key' => 'kep_gigi', 'label' => 'Penilaian Awal Keperawatan Gigi', 'table' => 'penilaian_awal_keperawatan_gigi'],
                ['key' => 'kep_kebidanan', 'label' => 'Penilaian Awal Keperawatan Kebidanan', 'table' => 'penilaian_awal_keperawatan_kebidanan'],
                ['key' => 'kep_mata', 'label' => 'Penilaian Awal Keperawatan Mata', 'table' => 'penilaian_awal_keperawatan_mata'],
                ['key' => 'kep_geriatri', 'label' => 'Penilaian Awal Keperawatan Ralan Geriatri', 'table' => 'penilaian_awal_keperawatan_ralan_geriatri'],
                ['key' => 'kep_psikiatri', 'label' => 'Penilaian Awal Keperawatan Ralan Psikiatri', 'table' => 'penilaian_awal_keperawatan_ralan_psikiatri'],
            ],
            'Asesmen Medis' => [
                ['key' => 'medis_ralan', 'label' => 'Penilaian Awal Medis Rawat Jalan', 'table' => 'penilaian_awal_medis_ralan'],
                ['key' => 'medis_anak', 'label' => 'Penilaian Awal Medis Ralan Anak', 'table' => 'penilaian_awal_medis_ralan_anak'],
                ['key' => 'medis_bedah', 'label' => 'Penilaian Awal Medis Ralan Bedah', 'table' => 'penilaian_awal_medis_ralan_bedah'],
                ['key' => 'medis_bedah_mulut', 'label' => 'Penilaian Awal Medis Ralan Bedah Mulut', 'table' => 'penilaian_awal_medis_ralan_bedah_mulut'],
                ['key' => 'medis_geriatri', 'label' => 'Penilaian Awal Medis Ralan Geriatri', 'table' => 'penilaian_awal_medis_ralan_geriatri'],
                ['key' => 'medis_kebidanan', 'label' => 'Penilaian Awal Medis Ralan Kebidanan', 'table' => 'penilaian_awal_medis_ralan_kebidanan'],
                ['key' => 'medis_kulit', 'label' => 'Penilaian Awal Medis Ralan Kulit dan Kelamin', 'table' => 'penilaian_awal_medis_ralan_kulit_kelamin'],
                ['key' => 'medis_mata', 'label' => 'Penilaian Awal Medis Ralan Mata', 'table' => 'penilaian_awal_medis_ralan_mata'],
                ['key' => 'medis_neuro', 'label' => 'Penilaian Awal Medis Ralan Neurologi', 'table' => 'penilaian_awal_medis_ralan_neurologi'],
                ['key' => 'medis_ortho', 'label' => 'Penilaian Awal Medis Ralan Orthopedi', 'table' => 'penilaian_awal_medis_ralan_orthopedi'],
                ['key' => 'medis_paru', 'label' => 'Penilaian Awal Medis Ralan Paru', 'table' => 'penilaian_awal_medis_ralan_paru'],
                ['key' => 'medis_penyakit_dalam', 'label' => 'Penilaian Awal Medis Ralan Penyakit Dalam', 'table' => 'penilaian_awal_medis_ralan_penyakit_dalam'],
                ['key' => 'medis_psikiatri', 'label' => 'Penilaian Awal Medis Ralan Psikiatri', 'table' => 'penilaian_awal_medis_ralan_psikiatri'],
                ['key' => 'medis_tht', 'label' => 'Penilaian Awal Medis Ralan THT', 'table' => 'penilaian_awal_medis_ralan_tht'],
            ],
            'Ringkasan & Lampiran' => [
                ['key' => 'resume_ralan', 'label' => 'Resume Pasien Rawat Jalan', 'table' => 'resume_pasien'],
                ['key' => 'berkas_digital', 'label' => 'Berkas Digital Perawatan', 'table' => 'berkas_digital_perawatan'],
            ],
        ];
    }

    private function ranapForms(): array
    {
        return [
            'Asesmen Keperawatan' => [
                ['key' => 'kep_ranap', 'label' => 'Penilaian Awal Keperawatan Rawat Inap', 'table' => 'penilaian_awal_keperawatan_ranap'],
                ['key' => 'kep_ranap_kebidanan', 'label' => 'Penilaian Awal Keperawatan Kebidanan Ranap', 'table' => 'penilaian_awal_keperawatan_kebidanan_ranap'],
                ['key' => 'kep_ranap_bayi', 'label' => 'Penilaian Awal Keperawatan Ranap Bayi/Anak', 'table' => 'penilaian_awal_keperawatan_ranap_bayi'],
                ['key' => 'kep_ranap_neonatus', 'label' => 'Penilaian Awal Keperawatan Ranap Neonatus', 'table' => 'penilaian_awal_keperawatan_ranap_neonatus'],
            ],
            'Asesmen Medis' => [
                ['key' => 'medis_ranap', 'label' => 'Penilaian Awal Medis Rawat Inap', 'table' => 'penilaian_awal_medis_ranap'],
                ['key' => 'medis_ranap_kebidanan', 'label' => 'Penilaian Awal Medis Ranap Kebidanan', 'table' => 'penilaian_awal_medis_ranap_kebidanan'],
                ['key' => 'medis_ranap_neonatus', 'label' => 'Penilaian Awal Medis Ranap Neonatus', 'table' => 'penilaian_awal_medis_ranap_neonatus'],
            ],
            'Ringkasan & Lampiran' => [
                ['key' => 'resume_ranap', 'label' => 'Resume Pasien Rawat Inap', 'table' => 'resume_pasien_ranap'],
                ['key' => 'berkas_digital', 'label' => 'Berkas Digital Perawatan', 'table' => 'berkas_digital_perawatan'],
            ],
        ];
    }

    private function loadFormStatus(PDO $pdo, string $noRawat, array $sections): array
    {
        $forms = [];
        foreach ($sections as $section => $items) {
            foreach ($items as $item) {
                $count = $this->countByNoRawat($pdo, (string)$item['table'], $noRawat);
                $item['section'] = $section;
                $item['count'] = $count;
                $item['exists'] = $count > 0;
                $forms[] = $item;
            }
        }
        return $forms;
    }

    private function countByNoRawat(PDO $pdo, string $table, string $noRawat): int
    {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE no_rawat = :no_rawat");
            $stmt->execute(['no_rawat' => $noRawat]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}
