<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use Throwable;
use WebBaru\Database;
use WebBaru\Services\SimrsQueryService;

final class RawatJalanController
{
    public function index(): void
    {
        if ((string)($_GET['ajax'] ?? '') === 'search_obat') {
            $this->ajaxSearchObat();
            return;
        }
        if ((string)($_GET['ajax'] ?? '') === 'search_tindakan') {
            $this->ajaxSearchTindakan();
            return;
        }
        if ((string)($_GET['ajax'] ?? '') === 'search_lab') {
            $this->ajaxSearchLab();
            return;
        }
        if ((string)($_GET['ajax'] ?? '') === 'search_rad') {
            $this->ajaxSearchRad();
            return;
        }
        if ((string)($_GET['ajax'] ?? '') === 'search_operasi') {
            $this->ajaxSearchOperasi();
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost();
            return;
        }

        $db = new SimrsQueryService();
        $from = trim((string)($_GET['from'] ?? date('Y-m-d')));
        $to = trim((string)($_GET['to'] ?? date('Y-m-d')));
        $q = trim((string)($_GET['q'] ?? ''));
        $kdPoli = trim((string)($_GET['kd_poli'] ?? ''));
        $detailNoRawat = trim((string)($_GET['detail'] ?? ''));
        $openModal = trim((string)($_GET['open'] ?? '')) === '1';
        $msg = trim((string)($_GET['msg'] ?? ''));
        $msgDetail = trim((string)($_GET['msgd'] ?? ''));

        $poliList = $db->run("SELECT kd_poli, nm_poli FROM poliklinik WHERE status='1' ORDER BY nm_poli");

        $where = [
            "rp.tgl_registrasi BETWEEN :from AND :to",
            "rp.status_lanjut='Ralan'",
            "NOT EXISTS (SELECT 1 FROM kamar_inap ki WHERE ki.no_rawat=rp.no_rawat AND ki.stts_pulang='-')"
        ];
        $params = ['from' => $from, 'to' => $to];
        if ($q !== '') {
            $where[] = "(rp.no_rawat LIKE :q1 OR rp.no_rkm_medis LIKE :q2 OR p.nm_pasien LIKE :q3)";
            $params['q1'] = '%' . $q . '%';
            $params['q2'] = '%' . $q . '%';
            $params['q3'] = '%' . $q . '%';
        }
        if ($kdPoli !== '') {
            $where[] = "rp.kd_poli = :kd_poli";
            $params['kd_poli'] = $kdPoli;
        }

        $visits = $db->run(
            "SELECT rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.no_rkm_medis, p.nm_pasien, pl.nm_poli, d.nm_dokter,
                    pj.png_jawab, rp.status_bayar
             FROM reg_periksa rp
             INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis
             INNER JOIN poliklinik pl ON pl.kd_poli=rp.kd_poli
             INNER JOIN dokter d ON d.kd_dokter=rp.kd_dokter
             LEFT JOIN penjab pj ON pj.kd_pj=rp.kd_pj
             WHERE " . implode(' AND ', $where) . "
             ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC
             LIMIT 200",
            $params
        );

        $topPoli = $db->run(
            "SELECT pl.nm_poli, COUNT(*) AS total
             FROM reg_periksa rp
             INNER JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
             WHERE rp.tgl_registrasi BETWEEN :from AND :to
             GROUP BY pl.kd_poli, pl.nm_poli
             ORDER BY total DESC
             LIMIT 15",
            ['from' => $from, 'to' => $to]
        );

        $detail = null;
        $detailDr = [];
        $detailPr = [];
        $detailDrpr = [];
        $detailPemeriksaan = [];
        $detailResep = [];
        $detailLab = [];
        $detailRad = [];
        $detailOperasi = [];
        $detailKamarInap = [];
        $kamarInapOptions = [];
        $isAlreadyRanap = false;
        $tindakanOptions = [];
        $obatOptions = [];
        $labOptions = [];
        $radOptions = [];
        $operasiOptions = [];
        $aturanPakaiOptions = [];
        $metodeRacikOptions = [];
        $detailError = null;
        if ($detailNoRawat !== '') {
            $head = $db->run(
                "SELECT rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.no_rkm_medis, p.nm_pasien, pl.nm_poli, d.nm_dokter,
                        rp.kd_dokter, rp.kd_poli, rp.kd_pj, pj.png_jawab
                 FROM reg_periksa rp
                 INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis
                 INNER JOIN poliklinik pl ON pl.kd_poli=rp.kd_poli
                 INNER JOIN dokter d ON d.kd_dokter=rp.kd_dokter
                 LEFT JOIN penjab pj ON pj.kd_pj=rp.kd_pj
                 WHERE rp.no_rawat=:n LIMIT 1",
                ['n' => $detailNoRawat]
            );
            if ($head['ok'] && !empty($head['data'])) {
                $detail = $head['data'][0];
                $detailDr = $db->run(
                    "SELECT r.kd_jenis_prw, j.nm_perawatan, r.biaya_rawat, r.jam_rawat, r.tgl_perawatan
                     FROM rawat_jl_dr r
                     INNER JOIN jns_perawatan j ON j.kd_jenis_prw=r.kd_jenis_prw
                     WHERE r.no_rawat=:n
                     ORDER BY r.tgl_perawatan DESC, r.jam_rawat DESC
                     LIMIT 200",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailPr = $db->run(
                    "SELECT j.nm_perawatan, r.biaya_rawat, r.jam_rawat
                     FROM rawat_jl_pr r
                     INNER JOIN jns_perawatan j ON j.kd_jenis_prw=r.kd_jenis_prw
                     WHERE r.no_rawat=:n
                     ORDER BY r.jam_rawat DESC
                     LIMIT 200",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailDrpr = $db->run(
                    "SELECT j.nm_perawatan, r.biaya_rawat, r.jam_rawat
                     FROM rawat_jl_drpr r
                     INNER JOIN jns_perawatan j ON j.kd_jenis_prw=r.kd_jenis_prw
                     WHERE r.no_rawat=:n
                     ORDER BY r.jam_rawat DESC
                     LIMIT 200",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailPemeriksaan = $db->run(
                    "SELECT tgl_perawatan, jam_rawat, suhu_tubuh, tensi, nadi, respirasi, tinggi, berat, spo2, gcs, kesadaran, keluhan, pemeriksaan, alergi, lingkar_perut, penilaian, rtl, instruksi, evaluasi
                     FROM pemeriksaan_ralan
                     WHERE no_rawat=:n
                     ORDER BY tgl_perawatan DESC, jam_rawat DESC
                     LIMIT 100",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailResep = $this->loadDetailResep($db, $detailNoRawat);
                $detailLab = $db->run(
                    "SELECT pl.noorder, pl.tgl_permintaan, pl.jam_permintaan,
                            GROUP_CONCAT(jl.nm_perawatan SEPARATOR '; ') AS item_lab
                     FROM permintaan_lab pl
                     LEFT JOIN permintaan_pemeriksaan_lab ppl ON ppl.noorder = pl.noorder
                     LEFT JOIN jns_perawatan_lab jl ON jl.kd_jenis_prw = ppl.kd_jenis_prw
                     WHERE pl.no_rawat=:n
                     GROUP BY pl.noorder, pl.tgl_permintaan, pl.jam_permintaan
                     ORDER BY pl.tgl_permintaan DESC, pl.jam_permintaan DESC
                     LIMIT 100",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailRad = $db->run(
                    "SELECT pr.noorder, pr.tgl_permintaan, pr.jam_permintaan,
                            GROUP_CONCAT(jr.nm_perawatan SEPARATOR '; ') AS item_radiologi
                     FROM permintaan_radiologi pr
                     LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON ppr.noorder = pr.noorder
                     LEFT JOIN jns_perawatan_radiologi jr ON jr.kd_jenis_prw = ppr.kd_jenis_prw
                     WHERE pr.no_rawat=:n
                     GROUP BY pr.noorder, pr.tgl_permintaan, pr.jam_permintaan
                     ORDER BY pr.tgl_permintaan DESC, pr.jam_permintaan DESC
                     LIMIT 100",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailOperasi = $db->run(
                    "SELECT o.no_rawat, o.tgl_operasi, o.kode_paket, po.nm_perawatan,
                            (o.biayaoperator1+o.biayaoperator2+o.biayaoperator3+o.biayaasisten_operator1+o.biayaasisten_operator2+
                             IFNULL(o.biayaasisten_operator3,0)+IFNULL(o.biayainstrumen,0)+o.biayadokter_anak+o.biayaperawaat_resusitas+
                             o.biayadokter_anestesi+o.biayaasisten_anestesi+IFNULL(o.biayaasisten_anestesi2,0)+o.biayabidan+IFNULL(o.biayabidan2,0)+
                             IFNULL(o.biayabidan3,0)+o.biayaperawat_luar+o.biayaalat+o.biayasewaok+IFNULL(o.akomodasi,0)+o.bagian_rs+
                             IFNULL(o.biaya_omloop,0)+IFNULL(o.biaya_omloop2,0)+IFNULL(o.biaya_omloop3,0)+IFNULL(o.biaya_omloop4,0)+
                             IFNULL(o.biaya_omloop5,0)+IFNULL(o.biayasarpras,0)+IFNULL(o.biaya_dokter_pjanak,0)+IFNULL(o.biaya_dokter_umum,0)
                            ) AS total_biaya
                     FROM operasi o
                     INNER JOIN paket_operasi po ON po.kode_paket=o.kode_paket
                     WHERE o.no_rawat=:n AND o.status='Ralan'
                     ORDER BY o.tgl_operasi DESC
                     LIMIT 100",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailKamarInap = $db->run(
                    "SELECT ki.kd_kamar, b.nm_bangsal, k.kelas, ki.trf_kamar, ki.diagnosa_awal, ki.tgl_masuk, ki.jam_masuk, ki.stts_pulang
                     FROM kamar_inap ki
                     LEFT JOIN kamar k ON k.kd_kamar=ki.kd_kamar
                     LEFT JOIN bangsal b ON b.kd_bangsal=k.kd_bangsal
                     WHERE ki.no_rawat=:n
                     ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC
                     LIMIT 30",
                    ['n' => $detailNoRawat]
                )['data'];
                $activeRanap = $db->run(
                    "SELECT COUNT(*) AS jml
                     FROM kamar_inap
                     WHERE no_rawat=:n AND stts_pulang='-'",
                    ['n' => $detailNoRawat]
                );
                $isAlreadyRanap = $activeRanap['ok'] && !empty($activeRanap['data']) && ((int)($activeRanap['data'][0]['jml'] ?? 0) > 0);
                $kamarInapOptions = $db->run(
                    "SELECT k.kd_kamar, b.nm_bangsal, k.kelas, k.trf_kamar, k.status
                     FROM kamar k
                     INNER JOIN bangsal b ON b.kd_bangsal=k.kd_bangsal
                     WHERE k.status='KOSONG'
                       AND (k.statusdata='1' OR k.statusdata IS NULL OR k.statusdata='')
                     ORDER BY b.nm_bangsal, k.kd_kamar",
                    []
                )['data'];

                $tindakanOptions = $db->run(
                    "SELECT kd_jenis_prw, nm_perawatan, material, bhp, tarif_tindakandr, kso, menejemen, total_byrdr
                     FROM jns_perawatan
                     WHERE status='1'
                       AND kd_pj=:kd_pj
                     ORDER BY nm_perawatan",
                    ['kd_pj' => $detail['kd_pj']]
                )['data'];

                $obatOptions = [];
                $labOptions = $db->run(
                    "SELECT kd_jenis_prw, nm_perawatan, total_byr
                     FROM jns_perawatan_lab
                     WHERE status='1'
                       AND kategori='PK'
                       AND kelas IN ('-','Rawat Jalan')
                       AND kd_pj=:kd_pj
                     ORDER BY nm_perawatan",
                    ['kd_pj' => $detail['kd_pj']]
                )['data'];
                $radOptions = $db->run(
                    "SELECT kd_jenis_prw, nm_perawatan, total_byr
                     FROM jns_perawatan_radiologi
                     WHERE status='1'
                       AND kelas IN ('-','Rawat Jalan')
                       AND kd_pj=:kd_pj
                     ORDER BY nm_perawatan",
                    ['kd_pj' => $detail['kd_pj']]
                )['data'];
                $operasiOptions = $db->run(
                    "SELECT kode_paket, nm_perawatan, operator1, operator2, operator3, asisten_operator1, asisten_operator2, asisten_operator3,
                            instrumen, dokter_anak, perawaat_resusitas, dokter_anestesi, asisten_anestesi, asisten_anestesi2,
                            bidan, bidan2, bidan3, perawat_luar, omloop, omloop2, omloop3, omloop4, omloop5, dokter_pjanak, dokter_umum,
                            sewa_ok, alat, akomodasi, bagian_rs, sarpras, status, kelas, kd_pj
                     FROM paket_operasi
                     WHERE status='1'
                       AND kd_pj=:kd_pj
                       AND (kelas='-' OR kelas='Rawat Jalan' OR kelas IS NULL)
                     ORDER BY nm_perawatan",
                    ['kd_pj' => $detail['kd_pj']]
                )['data'];

                $aturanPakaiOptions = $this->fetchAturanPakaiOptions($db);
                $metodeRacikOptions = $this->fetchMetodeRacikOptions($db);
            } else {
                $detailError = $head['error'] ?? 'Detail kunjungan tidak ditemukan';
            }
        }

        view('rawatjalan', [
            'title' => 'Modul Rawat Jalan',
            'topPoli' => $topPoli['data'],
            'rows' => $visits['data'],
            'error' => (!$topPoli['ok']) ? $topPoli['error'] : (!$visits['ok'] ? $visits['error'] : null),
            'from' => $from,
            'to' => $to,
            'q' => $q,
            'kdPoli' => $kdPoli,
            'poliList' => $poliList['data'],
            'detailNoRawat' => $detailNoRawat,
            'openModal' => $openModal,
            'detail' => $detail,
            'detailDr' => $detailDr,
            'detailPr' => $detailPr,
            'detailDrpr' => $detailDrpr,
            'detailPemeriksaan' => $detailPemeriksaan,
            'detailResep' => $detailResep,
            'detailLab' => $detailLab,
            'detailRad' => $detailRad,
            'detailOperasi' => $detailOperasi,
            'detailKamarInap' => $detailKamarInap,
            'kamarInapOptions' => $kamarInapOptions,
            'isAlreadyRanap' => $isAlreadyRanap,
            'tindakanOptions' => $tindakanOptions,
            'obatOptions' => $obatOptions,
            'labOptions' => $labOptions,
            'radOptions' => $radOptions,
            'operasiOptions' => $operasiOptions,
            'aturanPakaiOptions' => $aturanPakaiOptions,
            'metodeRacikOptions' => $metodeRacikOptions,
            'detailError' => $detailError,
            'msg' => $msg,
            'msgDetail' => $msgDetail,
        ]);
    }

    private function handlePost(): void
    {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'input_tindakan') {
            $this->inputTindakan();
            return;
        }
        if ($action === 'input_pemeriksaan') {
            $this->inputPemeriksaan();
            return;
        }
        if ($action === 'update_pemeriksaan') {
            $this->updatePemeriksaan();
            return;
        }
        if ($action === 'input_resep') {
            $this->inputResep();
            return;
        }
        if ($action === 'input_lab') {
            $this->inputLab();
            return;
        }
        if ($action === 'input_radiologi') {
            $this->inputRadiologi();
            return;
        }
        if ($action === 'input_operasi') {
            $this->inputOperasi();
            return;
        }
        if ($action === 'input_kamarinap') {
            $this->inputKamarInap();
            return;
        }
        if ($action === 'hapus_tindakan') {
            $this->hapusTindakan();
            return;
        }
        if ($action === 'hapus_pemeriksaan') {
            $this->hapusPemeriksaan();
            return;
        }
        if ($action === 'hapus_lab') {
            $this->hapusLab();
            return;
        }
        if ($action === 'hapus_radiologi') {
            $this->hapusRadiologi();
            return;
        }
        if ($action === 'hapus_operasi') {
            $this->hapusOperasi();
            return;
        }

        header('Location: ?page=rawatjalan&msg=error&msgd=' . urlencode('Aksi tidak dikenali'));
        exit;
    }

    private function ajaxSearchObat(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $qRaw = trim((string)($_GET['q'] ?? ''));
        if (strlen($qRaw) < 3) {
            echo json_encode(['ok' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        $db = new SimrsQueryService();
        $like = '%' . $qRaw . '%';
        $prefix = $qRaw . '%';
        $res = $db->run(
            "SELECT db.kode_brng, db.nama_brng, db.ralan, db.letak_barang AS kandungan_obat
             FROM databarang db
             WHERE db.status='1'
               AND (
                    db.kode_brng LIKE :q
                    OR db.nama_brng LIKE :q
                    OR db.letak_barang LIKE :q
               )
             ORDER BY
                CASE
                    WHEN db.kode_brng LIKE :pfx THEN 0
                    WHEN db.nama_brng LIKE :pfx THEN 1
                    WHEN db.letak_barang LIKE :pfx THEN 2
                    ELSE 3
                END,
                db.nama_brng ASC
             LIMIT 100",
            ['q' => $like, 'pfx' => $prefix]
        );

        if (!$res['ok']) {
            echo json_encode(['ok' => false, 'error' => (string)($res['error'] ?? 'gagal cari obat')], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['ok' => true, 'data' => $res['data']], JSON_UNESCAPED_UNICODE);
    }

    private function ajaxSearchTindakan(): void
    {
        $this->ajaxSearchByVisitContext('tindakan');
    }

    private function ajaxSearchLab(): void
    {
        $this->ajaxSearchByVisitContext('lab');
    }

    private function ajaxSearchRad(): void
    {
        $this->ajaxSearchByVisitContext('rad');
    }

    private function ajaxSearchOperasi(): void
    {
        $this->ajaxSearchByVisitContext('operasi');
    }

    private function ajaxSearchByVisitContext(string $type): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $qRaw = trim((string)($_GET['q'] ?? ''));
        $noRawat = trim((string)($_GET['no_rawat'] ?? ''));
        if ($noRawat === '' || strlen($qRaw) < 3) {
            echo json_encode(['ok' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $pdo = Database::pdo();
            $visit = $this->visitMeta($pdo, $noRawat);
            if (!$visit) {
                echo json_encode(['ok' => false, 'error' => 'Kunjungan tidak ditemukan'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $db = new SimrsQueryService();
            $like = '%' . $qRaw . '%';
            $prefix = $qRaw . '%';
            if ($type === 'tindakan') {
                $res = $db->run(
                    "SELECT kd_jenis_prw, nm_perawatan, material, bhp, tarif_tindakandr, kso, menejemen, total_byrdr
                     FROM jns_perawatan
                     WHERE status='1'
                       AND kd_pj=:kd_pj
                       AND (kd_jenis_prw LIKE :q OR nm_perawatan LIKE :q)
                     ORDER BY
                        CASE
                            WHEN kd_jenis_prw LIKE :pfx THEN 0
                            WHEN nm_perawatan LIKE :pfx THEN 1
                            ELSE 2
                        END,
                        nm_perawatan ASC
                     LIMIT 100",
                    ['kd_pj' => (string)$visit['kd_pj'], 'q' => $like, 'pfx' => $prefix]
                );
            } elseif ($type === 'lab') {
                $res = $db->run(
                    "SELECT kd_jenis_prw, nm_perawatan, total_byr
                     FROM jns_perawatan_lab
                     WHERE status='1'
                       AND kategori='PK'
                       AND kelas IN ('-','Rawat Jalan')
                       AND kd_pj=:kd_pj
                       AND (kd_jenis_prw LIKE :q OR nm_perawatan LIKE :q)
                     ORDER BY
                        CASE
                            WHEN kd_jenis_prw LIKE :pfx THEN 0
                            WHEN nm_perawatan LIKE :pfx THEN 1
                            ELSE 2
                        END,
                        nm_perawatan ASC
                     LIMIT 100",
                    ['kd_pj' => (string)$visit['kd_pj'], 'q' => $like, 'pfx' => $prefix]
                );
            } elseif ($type === 'rad') {
                $res = $db->run(
                    "SELECT kd_jenis_prw, nm_perawatan, total_byr
                     FROM jns_perawatan_radiologi
                     WHERE status='1'
                       AND kelas IN ('-','Rawat Jalan')
                       AND kd_pj=:kd_pj
                       AND (kd_jenis_prw LIKE :q OR nm_perawatan LIKE :q)
                     ORDER BY
                        CASE
                            WHEN kd_jenis_prw LIKE :pfx THEN 0
                            WHEN nm_perawatan LIKE :pfx THEN 1
                            ELSE 2
                        END,
                        nm_perawatan ASC
                     LIMIT 100",
                    ['kd_pj' => (string)$visit['kd_pj'], 'q' => $like, 'pfx' => $prefix]
                );
            } else {
                $res = $db->run(
                    "SELECT kode_paket AS kd_jenis_prw, nm_perawatan, operator1, operator2, operator3, asisten_operator1, asisten_operator2, asisten_operator3,
                            instrumen, dokter_anak, perawaat_resusitas, dokter_anestesi, asisten_anestesi, asisten_anestesi2,
                            bidan, bidan2, bidan3, perawat_luar, omloop, omloop2, omloop3, omloop4, omloop5, dokter_pjanak, dokter_umum,
                            sewa_ok, alat, akomodasi, bagian_rs, sarpras
                     FROM paket_operasi
                     WHERE status='1'
                       AND kd_pj=:kd_pj
                       AND (kelas='-' OR kelas='Rawat Jalan' OR kelas IS NULL)
                       AND (kode_paket LIKE :q OR nm_perawatan LIKE :q)
                     ORDER BY
                        CASE
                            WHEN kode_paket LIKE :pfx THEN 0
                            WHEN nm_perawatan LIKE :pfx THEN 1
                            ELSE 2
                        END,
                        nm_perawatan ASC
                     LIMIT 100",
                    ['kd_pj' => (string)$visit['kd_pj'], 'q' => $like, 'pfx' => $prefix]
                );
            }

            if (!$res['ok']) {
                echo json_encode(['ok' => false, 'error' => (string)($res['error'] ?? 'gagal cari data')], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo json_encode(['ok' => true, 'data' => $res['data']], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    private function inputTindakan(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $kdJenis = trim((string)($_POST['kd_jenis_prw'] ?? ''));
        if ($noRawat === '' || $kdJenis === '') {
            $this->goDetail($noRawat, 'error', 'Data tindakan belum lengkap');
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();

            $visit = $this->visitMeta($pdo, $noRawat);
            if (!$visit) {
                throw new \RuntimeException('Kunjungan tidak ditemukan');
            }
            $jns = $pdo->prepare("SELECT material, bhp, tarif_tindakandr, kso, menejemen, total_byrdr FROM jns_perawatan WHERE kd_jenis_prw=:k LIMIT 1");
            $jns->execute(['k' => $kdJenis]);
            $tindakan = $jns->fetch();
            if (!$tindakan) {
                throw new \RuntimeException('Jenis tindakan tidak ditemukan');
            }

            $tgl = date('Y-m-d');
            $jam = $this->nextUniqueTimeForTindakan($pdo, $noRawat, $kdJenis, (string)$visit['kd_dokter'], $tgl);
            $material = (float)($tindakan['material'] ?? 0);
            $bhp = (float)($tindakan['bhp'] ?? 0);
            $tarif = (float)($tindakan['tarif_tindakandr'] ?? 0);
            $kso = (float)($tindakan['kso'] ?? 0);
            $menejemen = (float)($tindakan['menejemen'] ?? 0);
            $biaya = (float)($tindakan['total_byrdr'] ?? 0);
            if ($biaya <= 0) {
                $biaya = $material + $bhp + $tarif + $kso + $menejemen;
            }

            $ins = $pdo->prepare(
                "INSERT INTO rawat_jl_dr
                (no_rawat,kd_jenis_prw,kd_dokter,tgl_perawatan,jam_rawat,material,bhp,tarif_tindakandr,kso,menejemen,biaya_rawat,stts_bayar)
                VALUES
                (:n,:k,:d,:tgl,:jam,:material,:bhp,:tarif,:kso,:menejemen,:biaya,'Belum')"
            );
            $ins->execute([
                'n' => $noRawat,
                'k' => $kdJenis,
                'd' => $visit['kd_dokter'],
                'tgl' => $tgl,
                'jam' => $jam,
                'material' => $material,
                'bhp' => $bhp,
                'tarif' => $tarif,
                'kso' => $kso,
                'menejemen' => $menejemen,
                'biaya' => $biaya,
            ]);

            $pdo->commit();
            $this->goDetail($noRawat, 'ok', 'Tindakan berhasil disimpan');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noRawat, 'error', 'Gagal simpan tindakan: ' . $e->getMessage());
        }
    }

    private function inputPemeriksaan(): void
    {
        $data = $this->collectPemeriksaanRalanInput();
        $noRawat = $data['no_rawat'];
        if ($noRawat === '') {
            $this->goDetail($noRawat, 'error', 'No rawat belum dipilih');
        }
        if ($data['keluhan'] === '' || $data['pemeriksaan'] === '') {
            $this->goDetail($noRawat, 'error', 'Data pemeriksaan belum lengkap');
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $visit = $this->visitMeta($pdo, $noRawat);
            if (!$visit) {
                throw new \RuntimeException('Kunjungan tidak ditemukan');
            }
            $tgl = date('Y-m-d');
            $jam = $this->nextUniqueTimeForSimple($pdo, 'pemeriksaan_ralan', $noRawat, $tgl);

            $ins = $pdo->prepare(
                "INSERT INTO pemeriksaan_ralan
                (no_rawat,tgl_perawatan,jam_rawat,suhu_tubuh,tensi,nadi,respirasi,tinggi,berat,spo2,gcs,kesadaran,keluhan,pemeriksaan,alergi,lingkar_perut,rtl,penilaian,instruksi,evaluasi,nip)
                VALUES
                (:no_rawat,:tgl_perawatan,:jam_rawat,:suhu_tubuh,:tensi,:nadi,:respirasi,:tinggi,:berat,:spo2,:gcs,:kesadaran,:keluhan,:pemeriksaan,:alergi,:lingkar_perut,:rtl,:penilaian,:instruksi,:evaluasi,:nip)"
            );
            $ins->execute([
                'no_rawat' => $noRawat,
                'tgl_perawatan' => $tgl,
                'jam_rawat' => $jam,
                'suhu_tubuh' => $data['suhu_tubuh'],
                'tensi' => $data['tensi'],
                'nadi' => $data['nadi'],
                'respirasi' => $data['respirasi'],
                'tinggi' => $data['tinggi'],
                'berat' => $data['berat'],
                'spo2' => $data['spo2'],
                'gcs' => $data['gcs'],
                'kesadaran' => $data['kesadaran'],
                'keluhan' => $data['keluhan'],
                'pemeriksaan' => $data['pemeriksaan'],
                'alergi' => $data['alergi'],
                'lingkar_perut' => $data['lingkar_perut'],
                'rtl' => $data['rtl'],
                'penilaian' => $data['penilaian'],
                'instruksi' => $data['instruksi'],
                'evaluasi' => $data['evaluasi'],
                'nip' => $visit['kd_dokter'],
            ]);

            $pdo->commit();
            $this->goDetail($noRawat, 'ok', 'Hasil pemeriksaan berhasil disimpan');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noRawat, 'error', 'Gagal simpan pemeriksaan: ' . $e->getMessage());
        }
    }

    private function updatePemeriksaan(): void
    {
        $data = $this->collectPemeriksaanRalanInput();
        $noRawat = $data['no_rawat'];
        $oldTgl = trim((string)($_POST['old_tgl_perawatan'] ?? ''));
        $oldJam = trim((string)($_POST['old_jam_rawat'] ?? ''));
        if ($noRawat === '' || $oldTgl === '' || $oldJam === '') {
            $this->goDetail($noRawat, 'error', 'Data pemeriksaan yang akan diupdate tidak lengkap');
        }
        if ($data['keluhan'] === '' || $data['pemeriksaan'] === '') {
            $this->goDetail($noRawat, 'error', 'Data pemeriksaan belum lengkap');
        }

        try {
            $visit = $this->visitMeta(Database::pdo(), $noRawat);
            if (!$visit) {
                throw new \RuntimeException('Kunjungan tidak ditemukan');
            }
            $stmt = Database::pdo()->prepare(
                "UPDATE pemeriksaan_ralan
                 SET suhu_tubuh=:suhu_tubuh, tensi=:tensi, nadi=:nadi, respirasi=:respirasi, tinggi=:tinggi, berat=:berat,
                     spo2=:spo2, gcs=:gcs, kesadaran=:kesadaran, keluhan=:keluhan, pemeriksaan=:pemeriksaan,
                     alergi=:alergi, lingkar_perut=:lingkar_perut, rtl=:rtl, penilaian=:penilaian,
                     instruksi=:instruksi, evaluasi=:evaluasi, nip=:nip
                 WHERE no_rawat=:no_rawat AND tgl_perawatan=:old_tgl AND jam_rawat=:old_jam"
            );
            $stmt->execute([
                'suhu_tubuh' => $data['suhu_tubuh'],
                'tensi' => $data['tensi'],
                'nadi' => $data['nadi'],
                'respirasi' => $data['respirasi'],
                'tinggi' => $data['tinggi'],
                'berat' => $data['berat'],
                'spo2' => $data['spo2'],
                'gcs' => $data['gcs'],
                'kesadaran' => $data['kesadaran'],
                'keluhan' => $data['keluhan'],
                'pemeriksaan' => $data['pemeriksaan'],
                'alergi' => $data['alergi'],
                'lingkar_perut' => $data['lingkar_perut'],
                'rtl' => $data['rtl'],
                'penilaian' => $data['penilaian'],
                'instruksi' => $data['instruksi'],
                'evaluasi' => $data['evaluasi'],
                'nip' => $visit['kd_dokter'],
                'no_rawat' => $noRawat,
                'old_tgl' => $oldTgl,
                'old_jam' => $oldJam,
            ]);
            if ($stmt->rowCount() > 0) {
                $this->goDetail($noRawat, 'ok', 'Pemeriksaan berhasil diupdate');
            }
            $this->goDetail($noRawat, 'error', 'Pemeriksaan tidak ditemukan atau tidak berubah');
        } catch (Throwable $e) {
            $this->goDetail($noRawat, 'error', 'Gagal update pemeriksaan: ' . $e->getMessage());
        }
    }

    private function hapusPemeriksaan(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $tgl = trim((string)($_POST['tgl_perawatan'] ?? ''));
        $jam = trim((string)($_POST['jam_rawat'] ?? ''));
        if ($noRawat === '' || $tgl === '' || $jam === '') {
            $this->goDetail($noRawat, 'error', 'Data pemeriksaan tidak lengkap');
        }
        try {
            $stmt = Database::pdo()->prepare(
                "DELETE FROM pemeriksaan_ralan
                 WHERE no_rawat=:n AND tgl_perawatan=:tgl AND jam_rawat=:jam
                 LIMIT 1"
            );
            $stmt->execute(['n' => $noRawat, 'tgl' => $tgl, 'jam' => $jam]);
            if ($stmt->rowCount() > 0) {
                $this->goDetail($noRawat, 'ok', 'Pemeriksaan berhasil dihapus');
            }
            $this->goDetail($noRawat, 'error', 'Pemeriksaan tidak ditemukan');
        } catch (Throwable $e) {
            $this->goDetail($noRawat, 'error', 'Gagal hapus pemeriksaan: ' . $e->getMessage());
        }
    }

    private function collectPemeriksaanRalanInput(): array
    {
        $kesadaran = trim((string)($_POST['kesadaran'] ?? 'Compos Mentis'));
        $allowedKesadaran = ['Compos Mentis','Somnolence','Sopor','Coma','Alert','Confusion','Voice','Pain','Unresponsive','Apatis','Delirium'];
        if (!in_array($kesadaran, $allowedKesadaran, true)) {
            $kesadaran = 'Compos Mentis';
        }
        return [
            'no_rawat' => trim((string)($_POST['no_rawat'] ?? '')),
            'suhu_tubuh' => trim((string)($_POST['suhu_tubuh'] ?? '')),
            'tensi' => trim((string)($_POST['tensi'] ?? '-')) ?: '-',
            'nadi' => trim((string)($_POST['nadi'] ?? '')),
            'respirasi' => trim((string)($_POST['respirasi'] ?? '')),
            'tinggi' => trim((string)($_POST['tinggi'] ?? '')),
            'berat' => trim((string)($_POST['berat'] ?? '')),
            'spo2' => trim((string)($_POST['spo2'] ?? '')),
            'gcs' => trim((string)($_POST['gcs'] ?? '')),
            'kesadaran' => $kesadaran,
            'keluhan' => trim((string)($_POST['keluhan'] ?? '')),
            'pemeriksaan' => trim((string)($_POST['pemeriksaan'] ?? '')),
            'alergi' => trim((string)($_POST['alergi'] ?? '')),
            'lingkar_perut' => trim((string)($_POST['lingkar_perut'] ?? '')),
            'rtl' => trim((string)($_POST['rtl'] ?? '')),
            'penilaian' => trim((string)($_POST['penilaian'] ?? '')),
            'instruksi' => trim((string)($_POST['instruksi'] ?? '')),
            'evaluasi' => trim((string)($_POST['evaluasi'] ?? '')),
        ];
    }

    private function inputResep(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));

        try {
            $nonRacikanItems = $this->collectNonRacikanItems();
            $racikanGroups = $this->collectRacikanGroups();
            if ($noRawat === '' || (empty($nonRacikanItems) && empty($racikanGroups))) {
                $this->goDetail($noRawat, 'error', 'Data resep belum lengkap');
            }

            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $visit = $this->visitMeta($pdo, $noRawat);
            if (!$visit) {
                throw new \RuntimeException('Kunjungan tidak ditemukan');
            }

            $nowDate = date('Y-m-d');
            $nowTime = date('H:i:s');
            $noResep = $this->nextNoResep($pdo, $nowDate);

            $insHeader = $pdo->prepare(
                "INSERT INTO resep_obat
                (no_resep,tgl_perawatan,jam,no_rawat,kd_dokter,tgl_peresepan,jam_peresepan,status,tgl_penyerahan,jam_penyerahan)
                VALUES
                (:r,'0000-00-00','00:00:00',:n,:d,:tgl,:jam,'ralan','0000-00-00','00:00:00')"
            );
            $insHeader->execute([
                'r' => $noResep,
                'tgl' => $nowDate,
                'jam' => $nowTime,
                'n' => $noRawat,
                'd' => $visit['kd_dokter'],
            ]);

            if (!empty($nonRacikanItems)) {
                $insDetail = $pdo->prepare(
                    "INSERT INTO resep_dokter(no_resep,kode_brng,jml,aturan_pakai)
                     VALUES(:r,:b,:j,:a)"
                );
                foreach ($nonRacikanItems as $item) {
                    $insDetail->execute([
                        'r' => $noResep,
                        'b' => $item['kode_brng'],
                        'j' => $item['jml'],
                        'a' => $item['aturan_pakai'],
                    ]);
                }
            }

            if (!empty($racikanGroups)) {
                $insRacik = $pdo->prepare(
                    "INSERT INTO resep_dokter_racikan(no_resep,no_racik,nama_racik,kd_racik,jml_dr,aturan_pakai,keterangan)
                     VALUES(:no_resep,:no_racik,:nama_racik,:kd_racik,:jml_dr,:aturan_pakai,:keterangan)"
                );
                $insRacikDetail = $pdo->prepare(
                    "INSERT INTO resep_dokter_racikan_detail(no_resep,no_racik,kode_brng,p1,p2,kandungan,jml)
                     VALUES(:no_resep,:no_racik,:kode_brng,:p1,:p2,:kandungan,:jml)"
                );
                foreach ($racikanGroups as $idx => $group) {
                    $noRacik = (string)($idx + 1);
                    $insRacik->execute([
                        'no_resep' => $noResep,
                        'no_racik' => $noRacik,
                        'nama_racik' => $group['nama_racik'],
                        'kd_racik' => $group['kd_racik'],
                        'jml_dr' => $group['jml_dr'],
                        'aturan_pakai' => $group['aturan_pakai'],
                        'keterangan' => $group['keterangan'],
                    ]);
                    foreach ($group['items'] as $item) {
                        $insRacikDetail->execute([
                            'no_resep' => $noResep,
                            'no_racik' => $noRacik,
                            'kode_brng' => $item['kode_brng'],
                            'p1' => $item['p1'],
                            'p2' => $item['p2'],
                            'kandungan' => $item['kandungan'],
                            'jml' => $item['jml'],
                        ]);
                    }
                }
            }

            $pdo->commit();
            $ringkasan = [];
            if (!empty($nonRacikanItems)) {
                $ringkasan[] = count($nonRacikanItems) . ' item non racikan';
            }
            if (!empty($racikanGroups)) {
                $ringkasan[] = count($racikanGroups) . ' racikan';
            }
            $this->goDetail($noRawat, 'ok', 'Resep berhasil disimpan (' . implode(', ', $ringkasan) . ') dengan no resep ' . $noResep);
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noRawat, 'error', 'Gagal simpan resep: ' . $e->getMessage());
        }
    }

    private function inputLab(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $items = $_POST['lab_items'] ?? [];
        $diagnosa = trim((string)($_POST['diagnosa_klinis_lab'] ?? '-'));
        $info = trim((string)($_POST['informasi_tambahan_lab'] ?? '-'));
        if ($noRawat === '' || !is_array($items) || empty($items)) {
            $this->goDetail($noRawat, 'error', 'Pilih minimal 1 pemeriksaan lab');
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $visit = $this->visitMeta($pdo, $noRawat);
            if (!$visit) {
                throw new \RuntimeException('Kunjungan tidak ditemukan');
            }

            $today = date('Y-m-d');
            $now = date('H:i:s');
            $noOrder = $this->nextNoOrder($pdo, 'permintaan_lab', 'PK', $today);

            $insHead = $pdo->prepare(
                "INSERT INTO permintaan_lab
                (noorder,no_rawat,tgl_permintaan,jam_permintaan,tgl_sampel,jam_sampel,tgl_hasil,jam_hasil,dokter_perujuk,status,informasi_tambahan,diagnosa_klinis)
                VALUES
                (:o,:n,:tgl,:jam,:tgl,:jam,:tgl,:jam,:d,'ralan',:i,:diag)"
            );
            $insHead->execute([
                'o' => $noOrder,
                'n' => $noRawat,
                'tgl' => $today,
                'jam' => $now,
                'd' => $visit['kd_dokter'],
                'i' => $info === '' ? '-' : $info,
                'diag' => $diagnosa === '' ? '-' : $diagnosa,
            ]);

            $insDetail = $pdo->prepare(
                "INSERT INTO permintaan_pemeriksaan_lab(noorder,kd_jenis_prw,stts_bayar)
                 VALUES(:o,:k,'Belum')"
            );
            foreach ($items as $item) {
                $kd = trim((string)$item);
                if ($kd !== '') {
                    $insDetail->execute(['o' => $noOrder, 'k' => $kd]);
                }
            }

            $pdo->commit();
            $this->goDetail($noRawat, 'ok', 'Permintaan lab berhasil disimpan: ' . $noOrder);
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noRawat, 'error', 'Gagal simpan permintaan lab: ' . $e->getMessage());
        }
    }

    private function inputRadiologi(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $items = $_POST['radiologi_items'] ?? [];
        $diagnosa = trim((string)($_POST['diagnosa_klinis_rad'] ?? '-'));
        $info = trim((string)($_POST['informasi_tambahan_rad'] ?? '-'));
        if ($noRawat === '' || !is_array($items) || empty($items)) {
            $this->goDetail($noRawat, 'error', 'Pilih minimal 1 pemeriksaan radiologi');
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $visit = $this->visitMeta($pdo, $noRawat);
            if (!$visit) {
                throw new \RuntimeException('Kunjungan tidak ditemukan');
            }

            $today = date('Y-m-d');
            $now = date('H:i:s');
            $noOrder = $this->nextNoOrder($pdo, 'permintaan_radiologi', 'PR', $today);

            $insHead = $pdo->prepare(
                "INSERT INTO permintaan_radiologi
                (noorder,no_rawat,tgl_permintaan,jam_permintaan,tgl_sampel,jam_sampel,tgl_hasil,jam_hasil,dokter_perujuk,status,informasi_tambahan,diagnosa_klinis)
                VALUES
                (:o,:n,:tgl,:jam,:tgl,:jam,:tgl,:jam,:d,'ralan',:i,:diag)"
            );
            $insHead->execute([
                'o' => $noOrder,
                'n' => $noRawat,
                'tgl' => $today,
                'jam' => $now,
                'd' => $visit['kd_dokter'],
                'i' => $info === '' ? '-' : $info,
                'diag' => $diagnosa === '' ? '-' : $diagnosa,
            ]);

            $insDetail = $pdo->prepare(
                "INSERT INTO permintaan_pemeriksaan_radiologi(noorder,kd_jenis_prw,stts_bayar)
                 VALUES(:o,:k,'Belum')"
            );
            foreach ($items as $item) {
                $kd = trim((string)$item);
                if ($kd !== '') {
                    $insDetail->execute(['o' => $noOrder, 'k' => $kd]);
                }
            }

            $pdo->commit();
            $this->goDetail($noRawat, 'ok', 'Permintaan radiologi berhasil disimpan: ' . $noOrder);
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noRawat, 'error', 'Gagal simpan permintaan radiologi: ' . $e->getMessage());
        }
    }

    private function inputKamarInap(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $kdKamar = trim((string)($_POST['kd_kamar'] ?? ''));
        $tglMasuk = trim((string)($_POST['tgl_masuk'] ?? date('Y-m-d')));
        $jamMasuk = trim((string)($_POST['jam_masuk'] ?? date('H:i:s')));
        $diagnosaAwal = trim((string)($_POST['diagnosa_awal'] ?? ''));

        if ($noRawat === '' || $kdKamar === '' || $diagnosaAwal === '') {
            $this->goDetail($noRawat, 'error', 'Data kamar inap belum lengkap');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglMasuk)) {
            $this->goDetail($noRawat, 'error', 'Format tanggal masuk tidak valid');
        }

        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $jamMasuk)) {
            $this->goDetail($noRawat, 'error', 'Format jam masuk tidak valid');
        }
        if (strlen($jamMasuk) === 5) {
            $jamMasuk .= ':00';
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();

            $visit = $this->visitMeta($pdo, $noRawat);
            if (!$visit) {
                throw new \RuntimeException('Kunjungan tidak ditemukan');
            }

            $cekAktif = $pdo->prepare("SELECT COUNT(*) FROM kamar_inap WHERE no_rawat=:n AND stts_pulang='-'");
            $cekAktif->execute(['n' => $noRawat]);
            if ((int)$cekAktif->fetchColumn() > 0) {
                throw new \RuntimeException('Pasien sudah terdaftar sebagai rawat inap aktif');
            }

            $qKamar = $pdo->prepare("SELECT kd_kamar, trf_kamar, status FROM kamar WHERE kd_kamar=:k LIMIT 1");
            $qKamar->execute(['k' => $kdKamar]);
            $kamar = $qKamar->fetch();
            if (!$kamar) {
                throw new \RuntimeException('Kamar tidak ditemukan');
            }
            if (strtoupper((string)($kamar['status'] ?? '')) !== 'KOSONG') {
                throw new \RuntimeException('Status kamar sudah terisi, silakan pilih kamar kosong');
            }

            $ins = $pdo->prepare(
                "INSERT INTO kamar_inap
                 (no_rawat,kd_kamar,trf_kamar,diagnosa_awal,diagnosa_akhir,tgl_masuk,jam_masuk,tgl_keluar,jam_keluar,lama,ttl_biaya,stts_pulang)
                 VALUES
                 (:n,:k,:trf,:da,'',:tgl,:jam,'0000-00-00','00:00:00','0','0','-')"
            );
            $ins->execute([
                'n' => $noRawat,
                'k' => $kdKamar,
                'trf' => (string)($kamar['trf_kamar'] ?? '0'),
                'da' => $diagnosaAwal,
                'tgl' => $tglMasuk,
                'jam' => $jamMasuk,
            ]);

            $pdo->prepare("UPDATE reg_periksa SET status_lanjut='Ranap' WHERE no_rawat=:n")->execute(['n' => $noRawat]);
            $pdo->prepare("UPDATE kamar SET status='ISI' WHERE kd_kamar=:k")->execute(['k' => $kdKamar]);

            $pdo->commit();
            $this->goDetail($noRawat, 'ok', 'Pasien berhasil dipindahkan ke rawat inap');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noRawat, 'error', 'Gagal input kamar inap: ' . $e->getMessage());
        }
    }

    private function inputOperasi(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $kodePaket = trim((string)($_POST['kode_paket'] ?? ''));
        $jenisAnasthesi = trim((string)($_POST['jenis_anasthesi'] ?? '-'));
        $kategori = trim((string)($_POST['kategori_operasi'] ?? '-'));
        if ($noRawat === '' || $kodePaket === '') {
            $this->goDetail($noRawat, 'error', 'Data tindakan operasi belum lengkap');
        }

        $allowedKategori = ['-', 'Khusus', 'Besar', 'Sedang', 'Kecil', 'Elektive', 'Emergency'];
        if (!in_array($kategori, $allowedKategori, true)) {
            $kategori = '-';
        }
        if ($jenisAnasthesi === '') {
            $jenisAnasthesi = '-';
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $visit = $this->visitMeta($pdo, $noRawat);
            if (!$visit) {
                throw new \RuntimeException('Kunjungan tidak ditemukan');
            }

            $paket = $pdo->prepare(
                "SELECT * FROM paket_operasi
                 WHERE kode_paket=:k AND status='1' AND kd_pj=:kd_pj
                   AND (kelas='-' OR kelas='Rawat Jalan' OR kelas IS NULL)
                 LIMIT 1"
            );
            $paket->execute(['k' => $kodePaket, 'kd_pj' => $visit['kd_pj']]);
            $p = $paket->fetch();
            if (!$p) {
                throw new \RuntimeException('Paket operasi tidak ditemukan/sesuai penjamin');
            }

            $tglOperasi = $this->nextUniqueDateTimeForOperasi($pdo, $noRawat, $kodePaket);
            $ins = $pdo->prepare(
                "INSERT INTO operasi
                (no_rawat,tgl_operasi,jenis_anasthesi,kategori,operator1,operator2,operator3,asisten_operator1,asisten_operator2,asisten_operator3,
                 instrumen,dokter_anak,perawaat_resusitas,dokter_anestesi,asisten_anestesi,asisten_anestesi2,bidan,bidan2,bidan3,perawat_luar,
                 omloop,omloop2,omloop3,omloop4,omloop5,dokter_pjanak,dokter_umum,kode_paket,biayaoperator1,biayaoperator2,biayaoperator3,
                 biayaasisten_operator1,biayaasisten_operator2,biayaasisten_operator3,biayainstrumen,biayadokter_anak,biayaperawaat_resusitas,
                 biayadokter_anestesi,biayaasisten_anestesi,biayaasisten_anestesi2,biayabidan,biayabidan2,biayabidan3,biayaperawat_luar,biayaalat,
                 biayasewaok,akomodasi,bagian_rs,biaya_omloop,biaya_omloop2,biaya_omloop3,biaya_omloop4,biaya_omloop5,biayasarpras,biaya_dokter_pjanak,
                 biaya_dokter_umum,status)
                VALUES
                (:no_rawat,:tgl_operasi,:jenis_anasthesi,:kategori,:operator1,:operator2,:operator3,:asisten_operator1,:asisten_operator2,:asisten_operator3,
                 :instrumen,:dokter_anak,:perawaat_resusitas,:dokter_anestesi,:asisten_anestesi,:asisten_anestesi2,:bidan,:bidan2,:bidan3,:perawat_luar,
                 :omloop,:omloop2,:omloop3,:omloop4,:omloop5,:dokter_pjanak,:dokter_umum,:kode_paket,:biayaoperator1,:biayaoperator2,:biayaoperator3,
                 :biayaasisten_operator1,:biayaasisten_operator2,:biayaasisten_operator3,:biayainstrumen,:biayadokter_anak,:biayaperawaat_resusitas,
                 :biayadokter_anestesi,:biayaasisten_anestesi,:biayaasisten_anestesi2,:biayabidan,:biayabidan2,:biayabidan3,:biayaperawat_luar,:biayaalat,
                 :biayasewaok,:akomodasi,:bagian_rs,:biaya_omloop,:biaya_omloop2,:biaya_omloop3,:biaya_omloop4,:biaya_omloop5,:biayasarpras,:biaya_dokter_pjanak,
                 :biaya_dokter_umum,'Ralan')"
            );
            $ins->execute([
                'no_rawat' => $noRawat,
                'tgl_operasi' => $tglOperasi,
                'jenis_anasthesi' => $jenisAnasthesi,
                'kategori' => $kategori,
                'operator1' => (string)($visit['kd_dokter'] ?? '-'),
                'operator2' => '-',
                'operator3' => '-',
                'asisten_operator1' => '-',
                'asisten_operator2' => '-',
                'asisten_operator3' => '-',
                'instrumen' => '-',
                'dokter_anak' => '-',
                'perawaat_resusitas' => '-',
                'dokter_anestesi' => '-',
                'asisten_anestesi' => '-',
                'asisten_anestesi2' => '-',
                'bidan' => '-',
                'bidan2' => '-',
                'bidan3' => '-',
                'perawat_luar' => '-',
                'omloop' => '-',
                'omloop2' => '-',
                'omloop3' => '-',
                'omloop4' => '-',
                'omloop5' => '-',
                'dokter_pjanak' => '-',
                'dokter_umum' => '-',
                'kode_paket' => $kodePaket,
                'biayaoperator1' => (float)($p['operator1'] ?? 0),
                'biayaoperator2' => (float)($p['operator2'] ?? 0),
                'biayaoperator3' => (float)($p['operator3'] ?? 0),
                'biayaasisten_operator1' => (float)($p['asisten_operator1'] ?? 0),
                'biayaasisten_operator2' => (float)($p['asisten_operator2'] ?? 0),
                'biayaasisten_operator3' => (float)($p['asisten_operator3'] ?? 0),
                'biayainstrumen' => (float)($p['instrumen'] ?? 0),
                'biayadokter_anak' => (float)($p['dokter_anak'] ?? 0),
                'biayaperawaat_resusitas' => (float)($p['perawaat_resusitas'] ?? 0),
                'biayadokter_anestesi' => (float)($p['dokter_anestesi'] ?? 0),
                'biayaasisten_anestesi' => (float)($p['asisten_anestesi'] ?? 0),
                'biayaasisten_anestesi2' => (float)($p['asisten_anestesi2'] ?? 0),
                'biayabidan' => (float)($p['bidan'] ?? 0),
                'biayabidan2' => (float)($p['bidan2'] ?? 0),
                'biayabidan3' => (float)($p['bidan3'] ?? 0),
                'biayaperawat_luar' => (float)($p['perawat_luar'] ?? 0),
                'biayaalat' => (float)($p['alat'] ?? 0),
                'biayasewaok' => (float)($p['sewa_ok'] ?? 0),
                'akomodasi' => (float)($p['akomodasi'] ?? 0),
                'bagian_rs' => (float)($p['bagian_rs'] ?? 0),
                'biaya_omloop' => (float)($p['omloop'] ?? 0),
                'biaya_omloop2' => (float)($p['omloop2'] ?? 0),
                'biaya_omloop3' => (float)($p['omloop3'] ?? 0),
                'biaya_omloop4' => (float)($p['omloop4'] ?? 0),
                'biaya_omloop5' => (float)($p['omloop5'] ?? 0),
                'biayasarpras' => (float)($p['sarpras'] ?? 0),
                'biaya_dokter_pjanak' => (float)($p['dokter_pjanak'] ?? 0),
                'biaya_dokter_umum' => (float)($p['dokter_umum'] ?? 0),
            ]);

            $pdo->commit();
            $this->goDetail($noRawat, 'ok', 'Tindakan operasi berhasil disimpan');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noRawat, 'error', 'Gagal simpan tindakan operasi: ' . $e->getMessage());
        }
    }

    private function hapusTindakan(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $kdJenis = trim((string)($_POST['kd_jenis_prw'] ?? ''));
        $tgl = trim((string)($_POST['tgl_perawatan'] ?? ''));
        $jam = trim((string)($_POST['jam_rawat'] ?? ''));
        if ($noRawat === '' || $kdJenis === '' || $tgl === '' || $jam === '') {
            $this->goDetail($noRawat, 'error', 'Data tindakan yang akan dihapus tidak lengkap');
        }

        try {
            $stmt = Database::pdo()->prepare(
                "DELETE FROM rawat_jl_dr
                 WHERE no_rawat=:n AND kd_jenis_prw=:k AND tgl_perawatan=:tgl AND jam_rawat=:jam
                 LIMIT 1"
            );
            $stmt->execute(['n' => $noRawat, 'k' => $kdJenis, 'tgl' => $tgl, 'jam' => $jam]);
            if ($stmt->rowCount() > 0) {
                $this->goDetail($noRawat, 'ok', 'Tindakan berhasil dihapus');
            }
            $this->goDetail($noRawat, 'error', 'Tindakan tidak ditemukan atau sudah terhapus');
        } catch (Throwable $e) {
            $this->goDetail($noRawat, 'error', 'Gagal hapus tindakan: ' . $e->getMessage());
        }
    }

    private function hapusLab(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $noOrder = trim((string)($_POST['noorder'] ?? ''));
        if ($noRawat === '' || $noOrder === '') {
            $this->goDetail($noRawat, 'error', 'No order lab tidak valid');
        }
        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM permintaan_pemeriksaan_lab WHERE noorder=:o")->execute(['o' => $noOrder]);
            $del = $pdo->prepare("DELETE FROM permintaan_lab WHERE noorder=:o AND no_rawat=:n LIMIT 1");
            $del->execute(['o' => $noOrder, 'n' => $noRawat]);
            $pdo->commit();
            if ($del->rowCount() > 0) {
                $this->goDetail($noRawat, 'ok', 'Permintaan lab berhasil dihapus');
            }
            $this->goDetail($noRawat, 'error', 'Permintaan lab tidak ditemukan');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noRawat, 'error', 'Gagal hapus permintaan lab: ' . $e->getMessage());
        }
    }

    private function hapusRadiologi(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $noOrder = trim((string)($_POST['noorder'] ?? ''));
        if ($noRawat === '' || $noOrder === '') {
            $this->goDetail($noRawat, 'error', 'No order radiologi tidak valid');
        }
        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM permintaan_pemeriksaan_radiologi WHERE noorder=:o")->execute(['o' => $noOrder]);
            $del = $pdo->prepare("DELETE FROM permintaan_radiologi WHERE noorder=:o AND no_rawat=:n LIMIT 1");
            $del->execute(['o' => $noOrder, 'n' => $noRawat]);
            $pdo->commit();
            if ($del->rowCount() > 0) {
                $this->goDetail($noRawat, 'ok', 'Permintaan radiologi berhasil dihapus');
            }
            $this->goDetail($noRawat, 'error', 'Permintaan radiologi tidak ditemukan');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noRawat, 'error', 'Gagal hapus permintaan radiologi: ' . $e->getMessage());
        }
    }

    private function hapusOperasi(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $kodePaket = trim((string)($_POST['kode_paket'] ?? ''));
        $tglOperasi = trim((string)($_POST['tgl_operasi'] ?? ''));
        if ($noRawat === '' || $kodePaket === '' || $tglOperasi === '') {
            $this->goDetail($noRawat, 'error', 'Data operasi yang akan dihapus tidak lengkap');
        }
        try {
            $stmt = Database::pdo()->prepare(
                "DELETE FROM operasi
                 WHERE no_rawat=:n AND kode_paket=:k AND tgl_operasi=:tgl AND status='Ralan'
                 LIMIT 1"
            );
            $stmt->execute(['n' => $noRawat, 'k' => $kodePaket, 'tgl' => $tglOperasi]);
            if ($stmt->rowCount() > 0) {
                $this->goDetail($noRawat, 'ok', 'Tindakan operasi berhasil dihapus');
            }
            $this->goDetail($noRawat, 'error', 'Tindakan operasi tidak ditemukan');
        } catch (Throwable $e) {
            $this->goDetail($noRawat, 'error', 'Gagal hapus tindakan operasi: ' . $e->getMessage());
        }
    }

    private function loadDetailResep(SimrsQueryService $db, string $noRawat): array
    {
        $res = $db->run(
            "SELECT ro.no_resep, ro.tgl_peresepan, ro.jam_peresepan,
                    COALESCE(nr.non_racikan_summary, '') AS non_racikan_summary,
                    COALESCE(rc.racikan_summary, '') AS racikan_summary,
                    CASE
                        WHEN COALESCE(nr.non_racikan_summary, '') <> '' AND COALESCE(rc.racikan_summary, '') <> '' THEN 'Campuran'
                        WHEN COALESCE(rc.racikan_summary, '') <> '' THEN 'Racikan'
                        ELSE 'Non Racikan'
                    END AS jenis_resep
             FROM resep_obat ro
             LEFT JOIN (
                SELECT rd.no_resep,
                       GROUP_CONCAT(
                            CONCAT(
                                db.nama_brng,
                                ' x',
                                rd.jml,
                                CASE
                                    WHEN IFNULL(rd.aturan_pakai, '') <> '' AND rd.aturan_pakai <> '-' THEN CONCAT(' (', rd.aturan_pakai, ')')
                                    ELSE ''
                                END
                            )
                            ORDER BY db.nama_brng SEPARATOR '; '
                       ) AS non_racikan_summary
                FROM resep_dokter rd
                INNER JOIN databarang db ON db.kode_brng = rd.kode_brng
                GROUP BY rd.no_resep
             ) nr ON nr.no_resep = ro.no_resep
             LEFT JOIN (
                SELECT rr.no_resep,
                       GROUP_CONCAT(
                            CONCAT(
                                rr.no_racik,
                                '. ',
                                rr.nama_racik,
                                ' [',
                                IFNULL(mr.nm_racik, rr.kd_racik),
                                '] ',
                                rr.jml_dr,
                                ' x',
                                CASE
                                    WHEN IFNULL(rr.aturan_pakai, '') <> '' AND rr.aturan_pakai <> '-' THEN CONCAT(' (', rr.aturan_pakai, ')')
                                    ELSE ''
                                END,
                                CASE
                                    WHEN IFNULL(rr.keterangan, '') <> '' AND rr.keterangan <> '-' THEN CONCAT(' - ', rr.keterangan)
                                    ELSE ''
                                END,
                                CASE
                                    WHEN IFNULL(det.item_detail, '') <> '' THEN CONCAT(' {', det.item_detail, '}')
                                    ELSE ''
                                END
                            )
                            ORDER BY rr.no_racik SEPARATOR '; '
                       ) AS racikan_summary
                FROM resep_dokter_racikan rr
                LEFT JOIN metode_racik mr ON mr.kd_racik = rr.kd_racik
                LEFT JOIN (
                    SELECT d.no_resep, d.no_racik,
                           GROUP_CONCAT(
                                CONCAT(
                                    db.nama_brng,
                                    ' x',
                                    d.jml,
                                    CASE
                                        WHEN d.p1 IS NOT NULL AND d.p2 IS NOT NULL THEN CONCAT(' [', d.p1, '/', d.p2, ']')
                                        ELSE ''
                                    END,
                                    CASE
                                        WHEN IFNULL(d.kandungan, '') <> '' THEN CONCAT(' ', d.kandungan)
                                        ELSE ''
                                    END
                                )
                                ORDER BY db.nama_brng SEPARATOR ', '
                           ) AS item_detail
                    FROM resep_dokter_racikan_detail d
                    INNER JOIN databarang db ON db.kode_brng = d.kode_brng
                    GROUP BY d.no_resep, d.no_racik
                ) det ON det.no_resep = rr.no_resep AND det.no_racik = rr.no_racik
                GROUP BY rr.no_resep
             ) rc ON rc.no_resep = ro.no_resep
             WHERE ro.no_rawat = :n
             ORDER BY ro.tgl_peresepan DESC, ro.jam_peresepan DESC
             LIMIT 200",
            ['n' => $noRawat]
        );
        return $res['ok'] ? $res['data'] : [];
    }

    private function fetchAturanPakaiOptions(SimrsQueryService $db): array
    {
        $res = $db->run("SELECT aturan FROM master_aturan_pakai ORDER BY aturan");
        return $res['ok'] ? $res['data'] : [];
    }

    private function fetchMetodeRacikOptions(SimrsQueryService $db): array
    {
        $res = $db->run("SELECT kd_racik, nm_racik FROM metode_racik ORDER BY kd_racik");
        return $res['ok'] ? $res['data'] : [];
    }

    private function collectNonRacikanItems(): array
    {
        $kodeBrngList = $_POST['nr_kode_brng'] ?? [];
        $jmlList = $_POST['nr_jml'] ?? [];
        $aturanList = $_POST['nr_aturan_pakai'] ?? [];
        if (!is_array($kodeBrngList)) {
            $kodeBrngList = [$kodeBrngList];
        }
        if (!is_array($jmlList)) {
            $jmlList = [$jmlList];
        }
        if (!is_array($aturanList)) {
            $aturanList = [$aturanList];
        }
        $items = [];
        $max = max(count($kodeBrngList), count($jmlList), count($aturanList));
        for ($i = 0; $i < $max; $i++) {
            $kode = trim((string)($kodeBrngList[$i] ?? ''));
            $jml = (float)($jmlList[$i] ?? 0);
            $aturan = trim((string)($aturanList[$i] ?? ''));
            if ($kode === '') {
                continue;
            }
            if ($jml <= 0) {
                throw new \RuntimeException('Setiap item non racikan wajib memiliki jumlah > 0');
            }
            $items[] = [
                'kode_brng' => $kode,
                'jml' => $jml,
                'aturan_pakai' => $aturan === '' ? '-' : $aturan,
            ];
        }
        return $items;
    }

    private function collectRacikanGroups(): array
    {
        $payload = trim((string)($_POST['racikan_payload'] ?? ''));
        if ($payload === '' || $payload === '[]') {
            return [];
        }
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Format racikan tidak valid');
        }
        $groups = [];
        foreach ($decoded as $idx => $group) {
            if (!is_array($group)) {
                continue;
            }
            $items = [];
            foreach ((array)($group['items'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $kode = trim((string)($item['kode_brng'] ?? ''));
                $jml = (float)($item['jml'] ?? 0);
                if ($kode === '') {
                    continue;
                }
                if ($jml <= 0) {
                    throw new \RuntimeException('Setiap item racikan wajib memiliki jumlah > 0');
                }
                $items[] = [
                    'kode_brng' => $kode,
                    'p1' => (float)($item['p1'] ?? 1),
                    'p2' => (float)($item['p2'] ?? 1),
                    'kandungan' => trim((string)($item['kandungan'] ?? '')),
                    'jml' => $jml,
                ];
            }
            $namaRacik = trim((string)($group['nama_racik'] ?? ''));
            $kdRacik = trim((string)($group['kd_racik'] ?? ''));
            $jmlDr = (int)($group['jml_dr'] ?? 0);
            $aturanPakai = trim((string)($group['aturan_pakai'] ?? ''));
            $keterangan = trim((string)($group['keterangan'] ?? ''));
            if (empty($items) && $namaRacik === '' && $kdRacik === '' && $jmlDr <= 0 && $aturanPakai === '' && $keterangan === '') {
                continue;
            }
            if (empty($items)) {
                throw new \RuntimeException('Racikan ' . ($idx + 1) . ' belum memiliki item obat');
            }
            if ($kdRacik === '') {
                throw new \RuntimeException('Metode racik pada racikan ' . ($idx + 1) . ' belum dipilih');
            }
            if ($jmlDr <= 0) {
                throw new \RuntimeException('Jumlah racik pada racikan ' . ($idx + 1) . ' harus lebih dari 0');
            }
            $groups[] = [
                'nama_racik' => $namaRacik === '' ? 'R' . ($idx + 1) : $namaRacik,
                'kd_racik' => $kdRacik,
                'jml_dr' => $jmlDr,
                'aturan_pakai' => $aturanPakai === '' ? '-' : $aturanPakai,
                'keterangan' => $keterangan === '' ? '-' : $keterangan,
                'items' => $items,
            ];
        }
        return $groups;
    }

    private function visitMeta(\PDO $pdo, string $noRawat): ?array
    {
        $stmt = $pdo->prepare("SELECT no_rawat, kd_dokter, kd_poli, kd_pj FROM reg_periksa WHERE no_rawat=:n LIMIT 1");
        $stmt->execute(['n' => $noRawat]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function nextUniqueTimeForTindakan(\PDO $pdo, string $noRawat, string $kdJenis, string $kdDokter, string $tgl): string
    {
        $base = time();
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM rawat_jl_dr
             WHERE no_rawat=:n AND kd_jenis_prw=:k AND kd_dokter=:d AND tgl_perawatan=:tgl AND jam_rawat=:jam"
        );
        for ($i = 0; $i < 120; $i++) {
            $jam = date('H:i:s', $base + $i);
            $check->execute(['n' => $noRawat, 'k' => $kdJenis, 'd' => $kdDokter, 'tgl' => $tgl, 'jam' => $jam]);
            if ((int)$check->fetchColumn() === 0) {
                return $jam;
            }
        }
        return date('H:i:s', $base + 121);
    }

    private function nextUniqueTimeForSimple(\PDO $pdo, string $table, string $noRawat, string $tgl): string
    {
        $base = time();
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE no_rawat=:n AND tgl_perawatan=:tgl AND jam_rawat=:jam"
        );
        for ($i = 0; $i < 120; $i++) {
            $jam = date('H:i:s', $base + $i);
            $check->execute(['n' => $noRawat, 'tgl' => $tgl, 'jam' => $jam]);
            if ((int)$check->fetchColumn() === 0) {
                return $jam;
            }
        }
        return date('H:i:s', $base + 121);
    }

    private function nextNoResep(\PDO $pdo, string $todayYmd): string
    {
        $prefix = str_replace('-', '', $todayYmd);
        $stmt = $pdo->prepare(
            "SELECT IFNULL(MAX(CAST(RIGHT(no_resep,4) AS UNSIGNED)),0)
             FROM resep_obat
             WHERE no_resep LIKE :p"
        );
        $stmt->execute(['p' => $prefix . '%']);
        $num = ((int)$stmt->fetchColumn()) + 1;
        return $prefix . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
    }

    private function nextNoOrder(\PDO $pdo, string $table, string $prefixCode, string $todayYmd): string
    {
        $datePart = str_replace('-', '', $todayYmd);
        $prefix = $prefixCode . $datePart;
        $stmt = $pdo->prepare(
            "SELECT IFNULL(MAX(CAST(RIGHT(noorder,4) AS UNSIGNED)),0)
             FROM {$table}
             WHERE noorder LIKE :p"
        );
        $stmt->execute(['p' => $prefix . '%']);
        $num = ((int)$stmt->fetchColumn()) + 1;
        return $prefix . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
    }

    private function nextUniqueDateTimeForOperasi(\PDO $pdo, string $noRawat, string $kodePaket): string
    {
        $base = time();
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM operasi
             WHERE no_rawat=:n AND kode_paket=:k AND tgl_operasi=:tgl"
        );
        for ($i = 0; $i < 120; $i++) {
            $dt = date('Y-m-d H:i:s', $base + $i);
            $check->execute(['n' => $noRawat, 'k' => $kodePaket, 'tgl' => $dt]);
            if ((int)$check->fetchColumn() === 0) {
                return $dt;
            }
        }
        return date('Y-m-d H:i:s', $base + 121);
    }

    private function goDetail(string $noRawat, string $msg, string $detail): void
    {
        $from = trim((string)($_REQUEST['from'] ?? date('Y-m-d')));
        $to = trim((string)($_REQUEST['to'] ?? date('Y-m-d')));
        $q = trim((string)($_REQUEST['q'] ?? ''));
        $kdPoli = trim((string)($_REQUEST['kd_poli'] ?? ''));
        $embed = trim((string)($_REQUEST['embed'] ?? ''));
        $serviceOnly = trim((string)($_REQUEST['service_only'] ?? ''));
        $focus = trim((string)($_REQUEST['focus'] ?? ''));

        $url = '?page=rawatjalan'
            . '&from=' . urlencode($from)
            . '&to=' . urlencode($to)
            . '&q=' . urlencode($q)
            . '&kd_poli=' . urlencode($kdPoli);
        if ($noRawat !== '') { $url .= '&detail=' . urlencode($noRawat) . '&open=1'; }
        if ($embed === '1') { $url .= '&embed=1'; }
        if ($serviceOnly === '1') { $url .= '&service_only=1'; }
        if ($focus !== '') { $url .= '&focus=' . urlencode($focus); }
        $url .= '&msg=' . urlencode($msg) . '&msgd=' . urlencode($detail);
        header('Location: ' . $url);
        exit;
    }
}

