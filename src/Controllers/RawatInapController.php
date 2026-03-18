<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use Throwable;
use WebBaru\Database;
use WebBaru\Services\SimrsQueryService;

final class RawatInapController
{
    public function index(): void
    {
        if ((string)($_GET['ajax'] ?? '') === 'search_obat') {
            $this->ajaxSearchObat();
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost();
            return;
        }

        $db = new SimrsQueryService();
        $from = trim((string)($_GET['from'] ?? date('Y-m-d', strtotime('-30 day'))));
        $to = trim((string)($_GET['to'] ?? date('Y-m-d')));
        $status = trim((string)($_GET['status'] ?? 'aktif'));
        if (!in_array($status, ['aktif', 'semua'], true)) {
            $status = 'aktif';
        }
        $q = trim((string)($_GET['q'] ?? ''));
        $msg = trim((string)($_GET['msg'] ?? ''));
        $msgDetail = trim((string)($_GET['msgd'] ?? ''));
        $detailNoRawat = trim((string)($_GET['detail'] ?? ''));
        $openModal = trim((string)($_GET['open'] ?? '')) === '1';

        $where = [];
        $params = [];
        if ($status === 'aktif') {
            $where[] = "(ki.tgl_keluar='0000-00-00' OR ki.tgl_keluar IS NULL)";
        } else {
            $where[] = 'ki.tgl_masuk BETWEEN :from AND :to';
            $params['from'] = $from;
            $params['to'] = $to;
        }
        if ($q !== '') {
            $where[] = "(rp.no_rawat LIKE :q OR rp.no_rkm_medis LIKE :q OR p.nm_pasien LIKE :q OR b.nm_bangsal LIKE :q OR k.kd_kamar LIKE :q)";
            $params['q'] = '%' . $q . '%';
        }

        $rows = $db->run(
            "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien, rp.status_bayar, pj.png_jawab, rp.kd_pj,
                    pl.nm_poli, d.nm_dokter,
                    ki.kd_kamar, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.stts_pulang,
                    k.kelas, b.nm_bangsal
             FROM reg_periksa rp
             INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis
             LEFT JOIN penjab pj ON pj.kd_pj=rp.kd_pj
             LEFT JOIN poliklinik pl ON pl.kd_poli=rp.kd_poli
             LEFT JOIN dokter d ON d.kd_dokter=rp.kd_dokter
             INNER JOIN (
                 SELECT ki1.*
                 FROM kamar_inap ki1
                 INNER JOIN (
                     SELECT no_rawat, MAX(CONCAT(tgl_masuk,' ',jam_masuk)) AS mx
                     FROM kamar_inap
                     GROUP BY no_rawat
                 ) x ON x.no_rawat=ki1.no_rawat AND CONCAT(ki1.tgl_masuk,' ',ki1.jam_masuk)=x.mx
             ) ki ON ki.no_rawat=rp.no_rawat
             LEFT JOIN kamar k ON k.kd_kamar=ki.kd_kamar
             LEFT JOIN bangsal b ON b.kd_bangsal=k.kd_bangsal
             WHERE rp.status_lanjut='Ranap' AND " . implode(' AND ', $where) . "
             ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC
            LIMIT 200",
            $params
        );
        $listRows = $rows['ok'] ? $this->mergeGabungRows($db, $rows['data']) : [];

        $detail = null;
        $riwayatKamar = [];
        $detailDr = [];
        $detailPr = [];
        $detailDrpr = [];
        $detailPemeriksaan = [];
        $detailResep = [];
        $detailLab = [];
        $detailRad = [];
        $detailOperasi = [];
        $detailBilling = [];
        $tindakanOptions = [];
        $labOptions = [];
        $radOptions = [];
        $operasiOptions = [];
        $aturanPakaiOptions = [];
        $metodeRacikOptions = [];
        $detailError = null;
        if ($detailNoRawat !== '') {
            $head = $db->run(
                "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien, rp.status_bayar, rp.kd_pj, pj.png_jawab,
                        pl.nm_poli, d.nm_dokter
                 FROM reg_periksa rp
                 INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis
                 LEFT JOIN poliklinik pl ON pl.kd_poli=rp.kd_poli
                 LEFT JOIN dokter d ON d.kd_dokter=rp.kd_dokter
                 LEFT JOIN penjab pj ON pj.kd_pj=rp.kd_pj
                 WHERE rp.no_rawat=:n LIMIT 1",
                ['n' => $detailNoRawat]
            );
            if ($head['ok'] && !empty($head['data'])) {
                $detail = $head['data'][0];
                $riwayatKamar = $db->run(
                    "SELECT ki.kd_kamar, k.kelas, b.nm_bangsal, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.stts_pulang
                     FROM kamar_inap ki
                     LEFT JOIN kamar k ON k.kd_kamar=ki.kd_kamar
                     LEFT JOIN bangsal b ON b.kd_bangsal=k.kd_bangsal
                     WHERE ki.no_rawat=:n
                     ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailDr = $db->run(
                    "SELECT r.kd_jenis_prw, j.nm_perawatan, r.tgl_perawatan, r.jam_rawat, r.biaya_rawat
                     FROM rawat_inap_dr r
                     INNER JOIN jns_perawatan_inap j ON j.kd_jenis_prw=r.kd_jenis_prw
                     WHERE r.no_rawat=:n
                     ORDER BY r.tgl_perawatan DESC, r.jam_rawat DESC
                     LIMIT 200",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailPr = $db->run(
                    "SELECT j.nm_perawatan, r.tgl_perawatan, r.jam_rawat, r.biaya_rawat
                     FROM rawat_inap_pr r
                     INNER JOIN jns_perawatan_inap j ON j.kd_jenis_prw=r.kd_jenis_prw
                     WHERE r.no_rawat=:n
                     ORDER BY r.tgl_perawatan DESC, r.jam_rawat DESC
                     LIMIT 200",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailDrpr = $db->run(
                    "SELECT j.nm_perawatan, r.tgl_perawatan, r.jam_rawat, r.biaya_rawat
                     FROM rawat_inap_drpr r
                     INNER JOIN jns_perawatan_inap j ON j.kd_jenis_prw=r.kd_jenis_prw
                     WHERE r.no_rawat=:n
                     ORDER BY r.tgl_perawatan DESC, r.jam_rawat DESC
                     LIMIT 200",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailPemeriksaan = $db->run(
                    "SELECT tgl_perawatan, jam_rawat, suhu_tubuh, tensi, nadi, respirasi, tinggi, berat, spo2, gcs, kesadaran, keluhan, pemeriksaan, alergi, penilaian, rtl, instruksi, evaluasi
                     FROM pemeriksaan_ranap
                     WHERE no_rawat=:n
                     ORDER BY tgl_perawatan DESC, jam_rawat DESC
                     LIMIT 200",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailResep = $this->loadDetailResep($db, $detailNoRawat);
                $detailLab = $db->run(
                    "SELECT pl.noorder, pl.tgl_permintaan, pl.jam_permintaan,
                            GROUP_CONCAT(jl.nm_perawatan SEPARATOR '; ') AS item_lab
                     FROM permintaan_lab pl
                     LEFT JOIN permintaan_pemeriksaan_lab ppl ON ppl.noorder=pl.noorder
                     LEFT JOIN jns_perawatan_lab jl ON jl.kd_jenis_prw=ppl.kd_jenis_prw
                     WHERE pl.no_rawat=:n
                     GROUP BY pl.noorder, pl.tgl_permintaan, pl.jam_permintaan
                     ORDER BY pl.tgl_permintaan DESC, pl.jam_permintaan DESC
                     LIMIT 200",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailRad = $db->run(
                    "SELECT pr.noorder, pr.tgl_permintaan, pr.jam_permintaan,
                            GROUP_CONCAT(jr.nm_perawatan SEPARATOR '; ') AS item_radiologi
                     FROM permintaan_radiologi pr
                     LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON ppr.noorder=pr.noorder
                     LEFT JOIN jns_perawatan_radiologi jr ON jr.kd_jenis_prw=ppr.kd_jenis_prw
                     WHERE pr.no_rawat=:n
                     GROUP BY pr.noorder, pr.tgl_permintaan, pr.jam_permintaan
                     ORDER BY pr.tgl_permintaan DESC, pr.jam_permintaan DESC
                     LIMIT 200",
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
                     WHERE o.no_rawat=:n AND o.status='Ranap'
                     ORDER BY o.tgl_operasi DESC
                     LIMIT 100",
                    ['n' => $detailNoRawat]
                )['data'];
                $detailBilling = $db->run(
                    "SELECT tgl_byr, no, nm_perawatan, status, biaya, jumlah, tambahan, totalbiaya
                     FROM billing
                     WHERE no_rawat=:n
                     ORDER BY tgl_byr DESC, no DESC
                     LIMIT 300",
                    ['n' => $detailNoRawat]
                )['data'];
                $tindakanOptions = $db->run(
                    "SELECT kd_jenis_prw, nm_perawatan, material, bhp, tarif_tindakandr, kso, menejemen, total_byrdr
                     FROM jns_perawatan_inap
                     WHERE status='1'
                       AND kd_pj=:kd_pj
                     ORDER BY nm_perawatan",
                    ['kd_pj' => $detail['kd_pj']]
                )['data'];
                $labOptions = $db->run(
                    "SELECT kd_jenis_prw, nm_perawatan, total_byr
                     FROM jns_perawatan_lab
                     WHERE status='1'
                       AND kategori='PK'
                       AND kelas IN ('-','Rawat Inap')
                       AND kd_pj=:kd_pj
                     ORDER BY nm_perawatan",
                    ['kd_pj' => $detail['kd_pj']]
                )['data'];
                $radOptions = $db->run(
                    "SELECT kd_jenis_prw, nm_perawatan, total_byr
                     FROM jns_perawatan_radiologi
                     WHERE status='1'
                       AND kelas IN ('-','Rawat Inap')
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
                       AND (kelas IS NULL OR kelas<>'Rawat Jalan')
                     ORDER BY nm_perawatan",
                    ['kd_pj' => $detail['kd_pj']]
                )['data'];

                $aturanPakaiOptions = $this->fetchAturanPakaiOptions($db);
                $metodeRacikOptions = $this->fetchMetodeRacikOptions($db);
            } else {
                $detailError = $head['error'] ?? 'Detail rawat inap tidak ditemukan';
            }
        }

        view('rawatinap', [
            'title' => 'Modul Rawat Inap',
            'rows' => $listRows,
            'error' => $rows['ok'] ? null : $rows['error'],
            'from' => $from,
            'to' => $to,
            'q' => $q,
            'status' => $status,
            'detailNoRawat' => $detailNoRawat,
            'openModal' => $openModal,
            'detail' => $detail,
            'riwayatKamar' => $riwayatKamar,
            'detailDr' => $detailDr,
            'detailPr' => $detailPr,
            'detailDrpr' => $detailDrpr,
            'detailPemeriksaan' => $detailPemeriksaan,
            'detailResep' => $detailResep,
            'detailLab' => $detailLab,
            'detailRad' => $detailRad,
            'detailOperasi' => $detailOperasi,
            'detailBilling' => $detailBilling,
            'tindakanOptions' => $tindakanOptions,
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
        if ($action === 'input_tindakan') { $this->inputTindakan(); return; }
        if ($action === 'input_pemeriksaan') { $this->inputPemeriksaan(); return; }
        if ($action === 'update_pemeriksaan') { $this->updatePemeriksaan(); return; }
        if ($action === 'input_resep') { $this->inputResep(); return; }
        if ($action === 'input_lab') { $this->inputLab(); return; }
        if ($action === 'input_radiologi') { $this->inputRadiologi(); return; }
        if ($action === 'input_operasi') { $this->inputOperasi(); return; }
        if ($action === 'hapus_tindakan') { $this->hapusTindakan(); return; }
        if ($action === 'hapus_pemeriksaan') { $this->hapusPemeriksaan(); return; }
        if ($action === 'hapus_lab') { $this->hapusLab(); return; }
        if ($action === 'hapus_radiologi') { $this->hapusRadiologi(); return; }
        if ($action === 'hapus_operasi') { $this->hapusOperasi(); return; }
        header('Location: ?page=rawatinap&msg=error&msgd=' . urlencode('Aksi tidak dikenali'));
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

    private function mergeGabungRows(SimrsQueryService $db, array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $byNoRawat = [];
        $params = [];
        $holders = [];
        $idx = 0;
        foreach ($rows as $r) {
            $no = (string)($r['no_rawat'] ?? '');
            if ($no === '') {
                continue;
            }
            $byNoRawat[$no] = $r;
            $key = 'n' . $idx++;
            $holders[] = ':' . $key;
            $params[$key] = $no;
        }
        if (empty($holders)) {
            return $rows;
        }

        $pairRes = $db->run(
            "SELECT no_rawat, no_rawat2
             FROM ranap_gabung
             WHERE no_rawat IN (" . implode(',', $holders) . ")
                OR no_rawat2 IN (" . implode(',', $holders) . ")",
            $params
        );
        if (!$pairRes['ok']) {
            return $rows;
        }

        $pairByRawat = [];
        foreach ($pairRes['data'] as $p) {
            $a = (string)($p['no_rawat'] ?? '');
            $b = (string)($p['no_rawat2'] ?? '');
            if ($a === '' || $b === '') {
                continue;
            }
            $pairByRawat[$a] = ['a' => $a, 'b' => $b];
            $pairByRawat[$b] = ['a' => $a, 'b' => $b];
        }

        $merged = [];
        $done = [];
        foreach ($rows as $r) {
            $no = (string)($r['no_rawat'] ?? '');
            if ($no === '' || isset($done[$no])) {
                continue;
            }
            $pair = $pairByRawat[$no] ?? null;
            if (!$pair) {
                $r['is_gabung'] = 0;
                $r['no_rawat_display'] = $no;
                $r['nm_pasien_display'] = (string)($r['nm_pasien'] ?? '-');
                $r['no_rawat_ibu'] = $no;
                $r['nm_ibu'] = (string)($r['nm_pasien'] ?? '-');
                $r['no_rawat_bayi'] = '';
                $r['nm_bayi'] = '';
                $merged[] = $r;
                $done[$no] = true;
                continue;
            }

            $aNo = (string)$pair['a'];
            $bNo = (string)$pair['b'];
            $aRow = $byNoRawat[$aNo] ?? $this->fetchRanapRowSimple($db, $aNo);
            $bRow = $byNoRawat[$bNo] ?? $this->fetchRanapRowSimple($db, $bNo);
            if (!$aRow && !$bRow) {
                $r['is_gabung'] = 0;
                $r['no_rawat_display'] = $no;
                $r['nm_pasien_display'] = (string)($r['nm_pasien'] ?? '-');
                $r['no_rawat_ibu'] = $no;
                $r['nm_ibu'] = (string)($r['nm_pasien'] ?? '-');
                $r['no_rawat_bayi'] = '';
                $r['nm_bayi'] = '';
                $merged[] = $r;
                $done[$no] = true;
                continue;
            }

            $main = $aRow ?: $bRow;
            $side = $main === $aRow ? $bRow : $aRow;
            $main['is_gabung'] = 1;
            $main['no_rawat_display'] = $aNo . ' + ' . $bNo;
            $main['no_rawat_ibu'] = $aNo;
            $main['nm_ibu'] = (string)($aRow['nm_pasien'] ?? '-');
            $main['no_rawat_bayi'] = $bNo;
            $main['nm_bayi'] = (string)($bRow['nm_pasien'] ?? '-');
            $main['nm_pasien_display'] = $main['nm_ibu'] . ' / ' . $main['nm_bayi'];
            $merged[] = $main;

            $done[$aNo] = true;
            $done[$bNo] = true;
        }

        return $merged;
    }

    private function fetchRanapRowSimple(SimrsQueryService $db, string $noRawat): ?array
    {
        $res = $db->run(
            "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien
             FROM reg_periksa rp
             INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis
             WHERE rp.no_rawat=:n
               AND rp.status_lanjut='Ranap'
             LIMIT 1",
            ['n' => $noRawat]
        );
        if (!$res['ok'] || empty($res['data'])) {
            return null;
        }
        return $res['data'][0];
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
            if (!$visit) { throw new \RuntimeException('Kunjungan tidak ditemukan'); }
            $jns = $pdo->prepare("SELECT material, bhp, tarif_tindakandr, kso, menejemen, total_byrdr FROM jns_perawatan_inap WHERE kd_jenis_prw=:k LIMIT 1");
            $jns->execute(['k' => $kdJenis]);
            $t = $jns->fetch();
            if (!$t) { throw new \RuntimeException('Jenis tindakan tidak ditemukan'); }
            $tgl = date('Y-m-d');
            $jam = $this->nextUniqueTime($pdo, 'rawat_inap_dr', $noRawat, $tgl);
            $material = (float)($t['material'] ?? 0);
            $bhp = (float)($t['bhp'] ?? 0);
            $tarif = (float)($t['tarif_tindakandr'] ?? 0);
            $kso = (float)($t['kso'] ?? 0);
            $menejemen = (float)($t['menejemen'] ?? 0);
            $biaya = (float)($t['total_byrdr'] ?? 0);
            if ($biaya <= 0) { $biaya = $material + $bhp + $tarif + $kso + $menejemen; }
            $ins = $pdo->prepare(
                "INSERT INTO rawat_inap_dr
                 (no_rawat,kd_jenis_prw,kd_dokter,tgl_perawatan,jam_rawat,material,bhp,tarif_tindakandr,kso,menejemen,biaya_rawat)
                 VALUES(:n,:k,:d,:tgl,:jam,:material,:bhp,:tarif,:kso,:menejemen,:biaya)"
            );
            $ins->execute([
                'n' => $noRawat, 'k' => $kdJenis, 'd' => $visit['kd_dokter'], 'tgl' => $tgl, 'jam' => $jam,
                'material' => $material, 'bhp' => $bhp, 'tarif' => $tarif, 'kso' => $kso, 'menejemen' => $menejemen, 'biaya' => $biaya,
            ]);
            $pdo->commit();
            $this->goDetail($noRawat, 'ok', 'Tindakan rawat inap berhasil disimpan');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) { Database::pdo()->rollBack(); }
            $this->goDetail($noRawat, 'error', 'Gagal simpan tindakan: ' . $e->getMessage());
        }
    }

    private function inputPemeriksaan(): void
    {
        $data = $this->collectPemeriksaanRanapInput();
        $noRawat = $data['no_rawat'];
        if ($noRawat === '' || $data['keluhan'] === '' || $data['pemeriksaan'] === '') {
            $this->goDetail($noRawat, 'error', 'Data pemeriksaan belum lengkap');
        }
        try {
            $pdo = Database::pdo();
            $visit = $this->visitMeta($pdo, $noRawat);
            if (!$visit) { throw new \RuntimeException('Kunjungan tidak ditemukan'); }
            $tgl = date('Y-m-d');
            $jam = $this->nextUniqueTime($pdo, 'pemeriksaan_ranap', $noRawat, $tgl);
            $ins = $pdo->prepare(
                "INSERT INTO pemeriksaan_ranap
                 (no_rawat,tgl_perawatan,jam_rawat,suhu_tubuh,tensi,nadi,respirasi,tinggi,berat,spo2,gcs,kesadaran,keluhan,pemeriksaan,alergi,penilaian,rtl,instruksi,evaluasi,nip)
                 VALUES(:no_rawat,:tgl_perawatan,:jam_rawat,:suhu_tubuh,:tensi,:nadi,:respirasi,:tinggi,:berat,:spo2,:gcs,:kesadaran,:keluhan,:pemeriksaan,:alergi,:penilaian,:rtl,:instruksi,:evaluasi,:nip)"
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
                'penilaian' => $data['penilaian'],
                'rtl' => $data['rtl'],
                'instruksi' => $data['instruksi'],
                'evaluasi' => $data['evaluasi'],
                'nip' => $visit['kd_dokter'],
            ]);
            $this->goDetail($noRawat, 'ok', 'Pemeriksaan rawat inap berhasil disimpan');
        } catch (Throwable $e) {
            $this->goDetail($noRawat, 'error', 'Gagal simpan pemeriksaan: ' . $e->getMessage());
        }
    }

    private function updatePemeriksaan(): void
    {
        $data = $this->collectPemeriksaanRanapInput();
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
            if (!$visit) { throw new \RuntimeException('Kunjungan tidak ditemukan'); }
            $stmt = Database::pdo()->prepare(
                "UPDATE pemeriksaan_ranap
                 SET suhu_tubuh=:suhu_tubuh, tensi=:tensi, nadi=:nadi, respirasi=:respirasi, tinggi=:tinggi, berat=:berat,
                     spo2=:spo2, gcs=:gcs, kesadaran=:kesadaran, keluhan=:keluhan, pemeriksaan=:pemeriksaan,
                     alergi=:alergi, penilaian=:penilaian, rtl=:rtl, instruksi=:instruksi, evaluasi=:evaluasi, nip=:nip
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
                'penilaian' => $data['penilaian'],
                'rtl' => $data['rtl'],
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

    private function collectPemeriksaanRanapInput(): array
    {
        $kesadaran = trim((string)($_POST['kesadaran'] ?? 'Compos Mentis'));
        $allowedKesadaran = ['Compos Mentis','Somnolence','Sopor','Coma','Alert','Confusion','Voice','Pain','Unresponsive','Apatis','Delirium'];
        if (!in_array($kesadaran, $allowedKesadaran, true)) {
            $kesadaran = 'Compos Mentis';
        }
        return [
            'no_rawat' => trim((string)($_POST['no_rawat'] ?? '')),
            'keluhan' => trim((string)($_POST['keluhan'] ?? '')),
            'pemeriksaan' => trim((string)($_POST['pemeriksaan'] ?? '')),
            'tensi' => trim((string)($_POST['tensi'] ?? '-')) ?: '-',
            'suhu_tubuh' => trim((string)($_POST['suhu_tubuh'] ?? '')),
            'nadi' => trim((string)($_POST['nadi'] ?? '')),
            'respirasi' => trim((string)($_POST['respirasi'] ?? '')),
            'tinggi' => trim((string)($_POST['tinggi'] ?? '')),
            'berat' => trim((string)($_POST['berat'] ?? '')),
            'spo2' => trim((string)($_POST['spo2'] ?? '')),
            'gcs' => trim((string)($_POST['gcs'] ?? '')),
            'kesadaran' => $kesadaran,
            'alergi' => trim((string)($_POST['alergi'] ?? '')),
            'penilaian' => trim((string)($_POST['penilaian'] ?? '')),
            'rtl' => trim((string)($_POST['rtl'] ?? '')),
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
                (:r,'0000-00-00','00:00:00',:n,:d,:tgl,:jam,'ranap','0000-00-00','00:00:00')"
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
        if ($noRawat === '' || !is_array($items) || empty($items)) {
            $this->goDetail($noRawat, 'error', 'Pilih minimal 1 pemeriksaan lab');
        }
        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $visit = $this->visitMeta($pdo, $noRawat);
            if (!$visit) { throw new \RuntimeException('Kunjungan tidak ditemukan'); }
            $today = date('Y-m-d');
            $now = date('H:i:s');
            $noOrder = $this->nextNoOrder($pdo, 'permintaan_lab', 'PK', $today);
            $pdo->prepare(
                "INSERT INTO permintaan_lab
                 (noorder,no_rawat,tgl_permintaan,jam_permintaan,tgl_sampel,jam_sampel,tgl_hasil,jam_hasil,dokter_perujuk,status,informasi_tambahan,diagnosa_klinis)
                 VALUES(:o,:n,:tgl,:jam,:tgl,:jam,:tgl,:jam,:d,'ranap','-','-')"
            )->execute(['o' => $noOrder, 'n' => $noRawat, 'tgl' => $today, 'jam' => $now, 'd' => $visit['kd_dokter']]);
            $insDetail = $pdo->prepare("INSERT INTO permintaan_pemeriksaan_lab(noorder,kd_jenis_prw,stts_bayar) VALUES(:o,:k,'Belum')");
            foreach ($items as $item) {
                $kd = trim((string)$item);
                if ($kd !== '') { $insDetail->execute(['o' => $noOrder, 'k' => $kd]); }
            }
            $pdo->commit();
            $this->goDetail($noRawat, 'ok', 'Permintaan lab berhasil disimpan: ' . $noOrder);
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) { Database::pdo()->rollBack(); }
            $this->goDetail($noRawat, 'error', 'Gagal simpan permintaan lab: ' . $e->getMessage());
        }
    }

    private function inputRadiologi(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $items = $_POST['radiologi_items'] ?? [];
        if ($noRawat === '' || !is_array($items) || empty($items)) {
            $this->goDetail($noRawat, 'error', 'Pilih minimal 1 pemeriksaan radiologi');
        }
        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $visit = $this->visitMeta($pdo, $noRawat);
            if (!$visit) { throw new \RuntimeException('Kunjungan tidak ditemukan'); }
            $today = date('Y-m-d');
            $now = date('H:i:s');
            $noOrder = $this->nextNoOrder($pdo, 'permintaan_radiologi', 'PR', $today);
            $pdo->prepare(
                "INSERT INTO permintaan_radiologi
                 (noorder,no_rawat,tgl_permintaan,jam_permintaan,tgl_sampel,jam_sampel,tgl_hasil,jam_hasil,dokter_perujuk,status,informasi_tambahan,diagnosa_klinis)
                 VALUES(:o,:n,:tgl,:jam,:tgl,:jam,:tgl,:jam,:d,'ranap','-','-')"
            )->execute(['o' => $noOrder, 'n' => $noRawat, 'tgl' => $today, 'jam' => $now, 'd' => $visit['kd_dokter']]);
            $insDetail = $pdo->prepare("INSERT INTO permintaan_pemeriksaan_radiologi(noorder,kd_jenis_prw,stts_bayar) VALUES(:o,:k,'Belum')");
            foreach ($items as $item) {
                $kd = trim((string)$item);
                if ($kd !== '') { $insDetail->execute(['o' => $noOrder, 'k' => $kd]); }
            }
            $pdo->commit();
            $this->goDetail($noRawat, 'ok', 'Permintaan radiologi berhasil disimpan: ' . $noOrder);
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) { Database::pdo()->rollBack(); }
            $this->goDetail($noRawat, 'error', 'Gagal simpan permintaan radiologi: ' . $e->getMessage());
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
                   AND (kelas IS NULL OR kelas<>'Rawat Jalan')
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
                 :biaya_dokter_umum,'Ranap')"
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
            $this->goDetail($noRawat, 'error', 'Data tindakan tidak lengkap');
        }
        try {
            $stmt = Database::pdo()->prepare("DELETE FROM rawat_inap_dr WHERE no_rawat=:n AND kd_jenis_prw=:k AND tgl_perawatan=:tgl AND jam_rawat=:jam LIMIT 1");
            $stmt->execute(['n' => $noRawat, 'k' => $kdJenis, 'tgl' => $tgl, 'jam' => $jam]);
            $this->goDetail($noRawat, $stmt->rowCount() > 0 ? 'ok' : 'error', $stmt->rowCount() > 0 ? 'Tindakan berhasil dihapus' : 'Tindakan tidak ditemukan');
        } catch (Throwable $e) {
            $this->goDetail($noRawat, 'error', 'Gagal hapus tindakan: ' . $e->getMessage());
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
            $stmt = Database::pdo()->prepare("DELETE FROM pemeriksaan_ranap WHERE no_rawat=:n AND tgl_perawatan=:tgl AND jam_rawat=:jam LIMIT 1");
            $stmt->execute(['n' => $noRawat, 'tgl' => $tgl, 'jam' => $jam]);
            $this->goDetail($noRawat, $stmt->rowCount() > 0 ? 'ok' : 'error', $stmt->rowCount() > 0 ? 'Pemeriksaan berhasil dihapus' : 'Pemeriksaan tidak ditemukan');
        } catch (Throwable $e) {
            $this->goDetail($noRawat, 'error', 'Gagal hapus pemeriksaan: ' . $e->getMessage());
        }
    }

    private function hapusLab(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $noOrder = trim((string)($_POST['noorder'] ?? ''));
        if ($noRawat === '' || $noOrder === '') { $this->goDetail($noRawat, 'error', 'No order lab tidak valid'); }
        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM permintaan_pemeriksaan_lab WHERE noorder=:o")->execute(['o' => $noOrder]);
            $del = $pdo->prepare("DELETE FROM permintaan_lab WHERE noorder=:o AND no_rawat=:n LIMIT 1");
            $del->execute(['o' => $noOrder, 'n' => $noRawat]);
            $pdo->commit();
            $this->goDetail($noRawat, $del->rowCount() > 0 ? 'ok' : 'error', $del->rowCount() > 0 ? 'Permintaan lab berhasil dihapus' : 'Permintaan lab tidak ditemukan');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) { Database::pdo()->rollBack(); }
            $this->goDetail($noRawat, 'error', 'Gagal hapus lab: ' . $e->getMessage());
        }
    }

    private function hapusRadiologi(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $noOrder = trim((string)($_POST['noorder'] ?? ''));
        if ($noRawat === '' || $noOrder === '') { $this->goDetail($noRawat, 'error', 'No order radiologi tidak valid'); }
        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM permintaan_pemeriksaan_radiologi WHERE noorder=:o")->execute(['o' => $noOrder]);
            $del = $pdo->prepare("DELETE FROM permintaan_radiologi WHERE noorder=:o AND no_rawat=:n LIMIT 1");
            $del->execute(['o' => $noOrder, 'n' => $noRawat]);
            $pdo->commit();
            $this->goDetail($noRawat, $del->rowCount() > 0 ? 'ok' : 'error', $del->rowCount() > 0 ? 'Permintaan radiologi berhasil dihapus' : 'Permintaan radiologi tidak ditemukan');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) { Database::pdo()->rollBack(); }
            $this->goDetail($noRawat, 'error', 'Gagal hapus radiologi: ' . $e->getMessage());
        }
    }

    private function hapusOperasi(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $kodePaket = trim((string)($_POST['kode_paket'] ?? ''));
        $tglOperasi = trim((string)($_POST['tgl_operasi'] ?? ''));
        if ($noRawat === '' || $kodePaket === '' || $tglOperasi === '') {
            $this->goDetail($noRawat, 'error', 'Data operasi tidak lengkap');
        }
        try {
            $stmt = Database::pdo()->prepare(
                "DELETE FROM operasi
                 WHERE no_rawat=:n AND kode_paket=:k AND tgl_operasi=:tgl AND status='Ranap'
                 LIMIT 1"
            );
            $stmt->execute(['n' => $noRawat, 'k' => $kodePaket, 'tgl' => $tglOperasi]);
            $this->goDetail(
                $noRawat,
                $stmt->rowCount() > 0 ? 'ok' : 'error',
                $stmt->rowCount() > 0 ? 'Tindakan operasi berhasil dihapus' : 'Tindakan operasi tidak ditemukan'
            );
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
        $stmt = $pdo->prepare("SELECT no_rawat, kd_dokter, kd_pj FROM reg_periksa WHERE no_rawat=:n LIMIT 1");
        $stmt->execute(['n' => $noRawat]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function nextUniqueTime(\PDO $pdo, string $table, string $noRawat, string $tgl): string
    {
        $base = time();
        $check = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE no_rawat=:n AND tgl_perawatan=:tgl AND jam_rawat=:jam");
        for ($i = 0; $i < 120; $i++) {
            $jam = date('H:i:s', $base + $i);
            $check->execute(['n' => $noRawat, 'tgl' => $tgl, 'jam' => $jam]);
            if ((int)$check->fetchColumn() === 0) { return $jam; }
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
        $stmt = $pdo->prepare("SELECT IFNULL(MAX(CAST(RIGHT(noorder,4) AS UNSIGNED)),0) FROM {$table} WHERE noorder LIKE :p");
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
            if ((int)$check->fetchColumn() === 0) { return $dt; }
        }
        return date('Y-m-d H:i:s', $base + 121);
    }

    private function goDetail(string $noRawat, string $msg, string $detail): void
    {
        $status = trim((string)($_REQUEST['status'] ?? 'aktif'));
        $from = trim((string)($_REQUEST['from'] ?? date('Y-m-d', strtotime('-30 day'))));
        $to = trim((string)($_REQUEST['to'] ?? date('Y-m-d')));
        $q = trim((string)($_REQUEST['q'] ?? ''));
        $embed = trim((string)($_REQUEST['embed'] ?? ''));
        $serviceOnly = trim((string)($_REQUEST['service_only'] ?? ''));
        $focus = trim((string)($_REQUEST['focus'] ?? ''));
        $url = '?page=rawatinap&status=' . urlencode($status) . '&from=' . urlencode($from) . '&to=' . urlencode($to) . '&q=' . urlencode($q);
        if ($noRawat !== '') { $url .= '&detail=' . urlencode($noRawat) . '&open=1'; }
        if ($embed === '1') { $url .= '&embed=1'; }
        if ($serviceOnly === '1') { $url .= '&service_only=1'; }
        if ($focus !== '') { $url .= '&focus=' . urlencode($focus); }
        $url .= '&msg=' . urlencode($msg) . '&msgd=' . urlencode($detail);
        header('Location: ' . $url);
        exit;
    }
}
