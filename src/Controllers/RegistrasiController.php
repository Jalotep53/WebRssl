<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use DateTimeImmutable;
use Throwable;
use WebBaru\Database;
use WebBaru\Services\BpjsVclaimService;
use WebBaru\Services\SimrsQueryService;

final class RegistrasiController
{
    public function index(): void
    {
        if (trim((string)($_GET['ajax'] ?? '')) === 'cek-rm') {
            $this->ajaxCheckNoRm();
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost();
            return;
        }

        $db = new SimrsQueryService();
        $todayCount = $db->value("SELECT COUNT(*) FROM reg_periksa WHERE DATE(tgl_registrasi)=CURDATE()");
        $from = trim((string)($_GET['from'] ?? date('Y-m-d')));
        $to = trim((string)($_GET['to'] ?? date('Y-m-d')));
        $q = trim((string)($_GET['q'] ?? ''));
        $kdPoli = trim((string)($_GET['kd_poli'] ?? ''));
        $kdPj = trim((string)($_GET['kd_pj'] ?? ''));
        $detailNoRawat = trim((string)($_GET['detail'] ?? ''));
        $selectedNoRm = trim((string)($_GET['no_rkm_medis'] ?? ''));
        $nikBaru = trim((string)($_GET['nik_baru'] ?? ''));
        $nikStatus = trim((string)($_GET['nik_status'] ?? ''));
        if ($nikBaru !== '') {
            $nikBaru = preg_replace('/\D+/', '', $nikBaru) ?? '';
        }
        $searchPasien = trim((string)($_GET['q_pasien'] ?? ''));
        $msg = trim((string)($_GET['msg'] ?? ''));
        $msgDetail = trim((string)($_GET['msgd'] ?? ''));
        $bpjsPesertaValue = trim((string)($_GET['bpjs_peserta'] ?? ''));
        $bpjsPesertaType = strtolower(trim((string)($_GET['bpjs_tipe'] ?? 'auto')));
        $bpjsOpen = trim((string)($_GET['bpjs_open'] ?? '')) === '1';
        if (!in_array($bpjsPesertaType, ['auto', 'nik', 'nokartu'], true)) {
            $bpjsPesertaType = 'auto';
        }

        $poliList = $db->run("SELECT kd_poli, nm_poli, registrasi, registrasilama FROM poliklinik WHERE status='1' ORDER BY nm_poli");
        $dokterList = $db->run("SELECT kd_dokter, nm_dokter FROM dokter WHERE status='1' ORDER BY nm_dokter");
        $penjabList = $db->run("SELECT kd_pj, png_jawab FROM penjab WHERE status='1' ORDER BY png_jawab");
        $defaultMeta = $this->loadDefaultMeta($db);
        $pasienTerpilih = null;
        $pasienCari = ['ok' => true, 'data' => [], 'error' => null];
        if ($searchPasien !== '') {
            $pasienCari = $db->run(
                "SELECT no_rkm_medis, nm_pasien, jk, tgl_lahir, alamat, no_tlp, kd_pj
                 FROM pasien
                 WHERE no_rkm_medis LIKE :q1 OR nm_pasien LIKE :q2 OR no_ktp LIKE :q3
                 ORDER BY no_rkm_medis DESC
                 LIMIT 30",
                ['q1' => '%' . $searchPasien . '%', 'q2' => '%' . $searchPasien . '%', 'q3' => '%' . $searchPasien . '%']
            );
        }
        if ($selectedNoRm !== '') {
            $pasienRes = $db->run(
                "SELECT no_rkm_medis, nm_pasien, jk, tmp_lahir, tgl_lahir, alamat, no_tlp, kd_pj,
                        keluarga, namakeluarga, alamatpj, pekerjaanpj
                 FROM pasien
                 WHERE no_rkm_medis = :rm
                 LIMIT 1",
                ['rm' => $selectedNoRm]
            );
            if ($pasienRes['ok'] && !empty($pasienRes['data'])) {
                $pasienTerpilih = $pasienRes['data'][0];
            }
        }

        $where = ["rp.tgl_registrasi BETWEEN :from AND :to"];
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
        if ($kdPj !== '') {
            $where[] = "rp.kd_pj = :kd_pj";
            $params['kd_pj'] = $kdPj;
        }

        $sql = "SELECT rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.no_rkm_medis,
                       p.nm_pasien, pl.kd_poli, pl.nm_poli, pj.kd_pj, pj.png_jawab,
                       rp.status_lanjut, rp.stts
                FROM reg_periksa rp
                INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
                INNER JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
                INNER JOIN penjab pj ON pj.kd_pj = rp.kd_pj
                WHERE " . implode(' AND ', $where) . "
                ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC
                LIMIT 200";
        $recent = $db->run($sql, $params);

        $detail = null;
        $detailStats = [];
        $detailError = null;
        if ($detailNoRawat !== '') {
            $detailRes = $db->run(
                "SELECT rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.no_rkm_medis, rp.stts, rp.status_lanjut,
                        p.nm_pasien, p.jk, p.tgl_lahir, p.no_tlp, p.alamat, pl.nm_poli, pj.png_jawab, d.nm_dokter
                 FROM reg_periksa rp
                 INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
                 INNER JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
                 INNER JOIN penjab pj ON pj.kd_pj = rp.kd_pj
                 INNER JOIN dokter d ON d.kd_dokter = rp.kd_dokter
                 WHERE rp.no_rawat = :no_rawat
                 LIMIT 1",
                ['no_rawat' => $detailNoRawat]
            );
            if ($detailRes['ok'] && !empty($detailRes['data'])) {
                $detail = $detailRes['data'][0];
                $detailStats = [
                    'tindakan_dokter' => $db->value("SELECT COUNT(*) FROM rawat_jl_dr WHERE no_rawat=:n", ['n' => $detailNoRawat]),
                    'tindakan_perawat' => $db->value("SELECT COUNT(*) FROM rawat_jl_pr WHERE no_rawat=:n", ['n' => $detailNoRawat]),
                    'tindakan_drpr' => $db->value("SELECT COUNT(*) FROM rawat_jl_drpr WHERE no_rawat=:n", ['n' => $detailNoRawat]),
                    'lab' => $db->value("SELECT COUNT(*) FROM periksa_lab WHERE no_rawat=:n", ['n' => $detailNoRawat]),
                    'radiologi' => $db->value("SELECT COUNT(*) FROM periksa_radiologi WHERE no_rawat=:n", ['n' => $detailNoRawat]),
                    'obat_total' => $db->value("SELECT IFNULL(SUM(total),0) FROM detail_pemberian_obat WHERE no_rawat=:n", ['n' => $detailNoRawat]),
                    'billing_total' => $db->value("SELECT IFNULL(SUM(totalbiaya),0) FROM billing WHERE no_rawat=:n", ['n' => $detailNoRawat]),
                ];
            } else {
                $detailError = $detailRes['error'] ?? 'Data detail tidak ditemukan';
            }
        }

        $bpjsPesertaResult = null;
        $bpjsHistoriResult = null;
        if ($bpjsOpen && $bpjsPesertaValue !== '') {
            $bpjsTypeRequest = $bpjsPesertaType === 'auto' ? '' : $bpjsPesertaType;
            $bpjsSvc = new BpjsVclaimService();
            $bpjsPesertaResult = $bpjsSvc->checkPeserta($bpjsPesertaValue, $bpjsTypeRequest);
            if (!empty($bpjsPesertaResult['ok'])) {
                $pesertaData = is_array($bpjsPesertaResult['data'] ?? null) ? $bpjsPesertaResult['data'] : [];
                $noKartuHistori = trim((string)($pesertaData['noKartu'] ?? $bpjsPesertaValue));
                if ($noKartuHistori !== '') {
                    $bpjsHistoriResult = $bpjsSvc->listHistoriPelayanan(
                        $noKartuHistori,
                        date('Y-m-d', strtotime('-90 days')),
                        date('Y-m-d')
                    );
                }
            }
        }

        view('registrasi', [
            'title' => 'Modul Registrasi',
            'todayCount' => $todayCount['ok'] ? (int)$todayCount['data'] : null,
            'rows' => $recent['data'],
            'error' => $recent['ok'] ? null : $recent['error'],
            'from' => $from,
            'to' => $to,
            'q' => $q,
            'kdPoli' => $kdPoli,
            'kdPj' => $kdPj,
            'poliList' => $poliList['data'],
            'dokterList' => $dokterList['data'],
            'penjabList' => $penjabList['data'],
            'detail' => $detail,
            'detailNoRawat' => $detailNoRawat,
            'detailStats' => $detailStats,
            'detailError' => $detailError,
            'selectedNoRm' => $selectedNoRm,
            'searchPasien' => $searchPasien,
            'pasienCari' => $pasienCari['data'],
            'pasienCariError' => $pasienCari['ok'] ? null : $pasienCari['error'],
            'pasienTerpilih' => $pasienTerpilih,
            'defaultMeta' => $defaultMeta,
            'msg' => $msg,
            'msgDetail' => $msgDetail,
            'bpjsPesertaValue' => $bpjsPesertaValue,
            'bpjsPesertaType' => $bpjsPesertaType,
            'bpjsOpen' => $bpjsOpen,
            'bpjsPesertaResult' => $bpjsPesertaResult,
            'bpjsHistoriResult' => $bpjsHistoriResult,
            'nikBaru' => $nikBaru,
            'nikStatus' => $nikStatus,
        ]);
    }

    private function handlePost(): void
    {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'create_patient') {
            $this->createPatient();
            return;
        }
        if ($action === 'register_visit') {
            $this->registerVisit();
            return;
        }
        if ($action === 'route_by_nik') {
            $this->routeByNik();
            return;
        }
        header('Location: ?page=registrasi&msg=error&msgd=' . urlencode('Aksi tidak dikenali'));
        exit;
    }


    private function routeByNik(): void
    {
        $nik = trim((string)($_POST['nik'] ?? ''));
        $nik = preg_replace('/\D+/', '', $nik) ?? '';
        if ($nik === '') {
            header('Location: ?page=registrasi&msg=error&msgd=' . urlencode('NIK wajib diisi'));
            exit;
        }

        $db = new SimrsQueryService();
        $pasien = $db->run(
            "SELECT no_rkm_medis, nm_pasien
             FROM pasien
             WHERE no_ktp = :nik
             ORDER BY no_rkm_medis DESC
             LIMIT 1",
            ['nik' => $nik]
        );

        if (!$pasien['ok']) {
            header('Location: ?page=registrasi&msg=error&msgd=' . urlencode('Gagal cek NIK: ' . (string)($pasien['error'] ?? 'Unknown error')));
            exit;
        }

        if (!empty($pasien['data'][0]['no_rkm_medis'])) {
            $noRm = trim((string)$pasien['data'][0]['no_rkm_medis']);
            $nama = trim((string)($pasien['data'][0]['nm_pasien'] ?? ''));
            $msg = 'NIK ditemukan. Pasien masuk ke pendaftaran rawat jalan';
            if ($nama !== '') {
                $msg .= ': ' . $nama;
            }
            header('Location: ?page=registrasi&no_rkm_medis=' . urlencode($noRm) . '&nik_status=found&msg=ok&msgd=' . urlencode($msg));
            exit;
        }

        header('Location: ?page=registrasi&nik_baru=' . urlencode($nik) . '&nik_status=not_found&msg=ok&msgd=' . urlencode('NIK belum terdaftar. Lanjutkan ke pendaftaran pasien baru.'));
        exit;
    }
    private function createPatient(): void
    {
        $noRm = trim((string)($_POST['no_rkm_medis'] ?? ''));
        $nmPasien = trim((string)($_POST['nm_pasien'] ?? ''));
        $jk = trim((string)($_POST['jk'] ?? 'L'));
        $tglLahir = trim((string)($_POST['tgl_lahir'] ?? ''));
        $tmpLahir = trim((string)($_POST['tmp_lahir'] ?? '-'));
        $alamat = trim((string)($_POST['alamat'] ?? '-'));
        $noTlp = trim((string)($_POST['no_tlp'] ?? '-'));
        $nikPasienBaru = preg_replace('/\\D+/', '', trim((string)($_POST['nik'] ?? ''))) ?? '';
        $noKtp = trim((string)($_POST['no_ktp'] ?? '-'));
        if ($nikPasienBaru !== '') {
            $noKtp = $nikPasienBaru;
        } elseif ($noKtp === '' || $noKtp === '-') {
            $noKtp = '-';
        }
        $kdPj = trim((string)($_POST['kd_pj_pasien'] ?? ''));
        $noPeserta = preg_replace('/\\D+/', '', trim((string)($_POST['no_peserta'] ?? ''))) ?? '';
        if ($noPeserta === '') {
            $noPeserta = '-';
        }
        $nmIbu = trim((string)($_POST['nm_ibu'] ?? '-'));

        if ($noRm === '' || $nmPasien === '' || $tglLahir === '' || $kdPj === '') {
            header('Location: ?page=registrasi&msg=error&msgd=' . urlencode('Data pasien baru belum lengkap'));
            exit;
        }
        if (!in_array($jk, ['L', 'P'], true)) {
            $jk = 'L';
        }

        $db = new SimrsQueryService();
        $cekNoRm = $db->value("SELECT COUNT(*) FROM pasien WHERE no_rkm_medis = :rm", ['rm' => $noRm]);
        if ($cekNoRm['ok'] && (int)$cekNoRm['data'] > 0) {
            header('Location: ?page=registrasi&msg=error&msgd=' . urlencode('No. Rekam Medik sudah terpakai'));
            exit;
        }

        $meta = $this->loadDefaultMeta($db);
        if (!empty($meta['error'])) {
            header('Location: ?page=registrasi&msg=error&msgd=' . urlencode((string)$meta['error']));
            exit;
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();

            [$umurText] = $this->umurTextAndUnit($tglLahir, date('Y-m-d'));

            $stmt = $pdo->prepare(
                "INSERT INTO pasien (
                    no_rkm_medis,nm_pasien,no_ktp,jk,tmp_lahir,tgl_lahir,nm_ibu,alamat,gol_darah,pekerjaan,stts_nikah,agama,
                    tgl_daftar,no_tlp,umur,pnd,keluarga,namakeluarga,kd_pj,no_peserta,kd_kel,kd_kec,kd_kab,pekerjaanpj,alamatpj,
                    kelurahanpj,kecamatanpj,kabupatenpj,perusahaan_pasien,suku_bangsa,bahasa_pasien,cacat_fisik,email,nip,kd_prop,propinsipj
                ) VALUES (
                    :no_rkm_medis,:nm_pasien,:no_ktp,:jk,:tmp_lahir,:tgl_lahir,:nm_ibu,:alamat,'-','-','BELUM MENIKAH','-',
                    CURDATE(),:no_tlp,:umur,'-','SAUDARA','-',:kd_pj,:no_peserta,:kd_kel,:kd_kec,:kd_kab,'-',:alamatpj,
                    '-','-','-',:perusahaan_pasien,:suku_bangsa,:bahasa_pasien,:cacat_fisik,'-','-',:kd_prop,'-'
                )"
            );
            $stmt->execute([
                'no_rkm_medis' => $noRm,
                'nm_pasien' => $nmPasien,
                'no_ktp' => $noKtp === '' ? '-' : $noKtp,
                'jk' => $jk,
                'tmp_lahir' => $tmpLahir === '' ? '-' : $tmpLahir,
                'tgl_lahir' => $tglLahir,
                'nm_ibu' => $nmIbu === '' ? '-' : $nmIbu,
                'alamat' => $alamat === '' ? '-' : $alamat,
                'no_tlp' => $noTlp === '' ? '-' : $noTlp,
                'umur' => $umurText,
                'kd_pj' => $kdPj,
                'no_peserta' => $noPeserta,
                'kd_kel' => (int)$meta['kd_kel'],
                'kd_kec' => (int)$meta['kd_kec'],
                'kd_kab' => (int)$meta['kd_kab'],
                'alamatpj' => $alamat === '' ? '-' : $alamat,
                'perusahaan_pasien' => (string)$meta['perusahaan_pasien'],
                'suku_bangsa' => (int)$meta['suku_bangsa'],
                'bahasa_pasien' => (int)$meta['bahasa_pasien'],
                'cacat_fisik' => (int)$meta['cacat_fisik'],
                'kd_prop' => (int)$meta['kd_prop'],
            ]);

            $pdo->commit();
            header('Location: ?page=registrasi&no_rkm_medis=' . urlencode($noRm) . '&msg=ok&msgd=' . urlencode('Pasien baru berhasil dibuat'));
            exit;
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            header('Location: ?page=registrasi&msg=error&msgd=' . urlencode('Gagal simpan pasien: ' . $e->getMessage()));
            exit;
        }
    }

    private function registerVisit(): void
    {
        $noRm = trim((string)($_POST['no_rkm_medis'] ?? ''));
        $kdPoli = trim((string)($_POST['kd_poli'] ?? ''));
        $kdDokter = trim((string)($_POST['kd_dokter'] ?? ''));
        $kdPj = trim((string)($_POST['kd_pj'] ?? ''));
        $tglReg = trim((string)($_POST['tgl_registrasi'] ?? date('Y-m-d')));
        $pJawab = trim((string)($_POST['p_jawab'] ?? '-'));
        $almtPj = trim((string)($_POST['almt_pj'] ?? '-'));
        $hubunganPj = trim((string)($_POST['hubunganpj'] ?? 'SAUDARA'));

        if ($noRm === '' || $kdPoli === '' || $kdDokter === '' || $kdPj === '') {
            header('Location: ?page=registrasi&no_rkm_medis=' . urlencode($noRm) . '&msg=error&msgd=' . urlencode('Data registrasi belum lengkap'));
            exit;
        }

        $db = new SimrsQueryService();
        $pasienRes = $db->run(
            "SELECT no_rkm_medis, tgl_lahir, alamat, keluarga, namakeluarga
             FROM pasien
             WHERE no_rkm_medis = :rm
             LIMIT 1",
            ['rm' => $noRm]
        );
        if (!$pasienRes['ok'] || empty($pasienRes['data'])) {
            header('Location: ?page=registrasi&msg=error&msgd=' . urlencode('Pasien tidak ditemukan'));
            exit;
        }
        $pasien = $pasienRes['data'][0];

        $ranapCheck = $db->value(
            "SELECT COUNT(*) 
             FROM kamar_inap ki
             INNER JOIN reg_periksa rp ON rp.no_rawat = ki.no_rawat
             WHERE rp.no_rkm_medis = :rm AND ki.stts_pulang = '-'",
            ['rm' => $noRm]
        );
        if ($ranapCheck['ok'] && (int)$ranapCheck['data'] > 0) {
            header('Location: ?page=registrasi&no_rkm_medis=' . urlencode($noRm) . '&msg=error&msgd=' . urlencode('Pasien masih dalam perawatan rawat inap'));
            exit;
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();

            $noRawat = $this->nextNoRawat($pdo, $tglReg);
            $noReg = $this->nextNoReg($pdo, $tglReg, $kdPoli);
            $sttsDaftar = $this->hasAnyRegistration($pdo, $noRm) ? 'Lama' : 'Baru';
            $statusPoli = $this->hasRegistrationInPoli($pdo, $noRm, $kdPoli) ? 'Lama' : 'Baru';
            $biayaReg = $this->biayaRegistrasi($pdo, $kdPoli, $statusPoli);
            [$umurValue, $sttsUmur] = $this->umurValueAndUnit((string)$pasien['tgl_lahir'], $tglReg);

            $stmt = $pdo->prepare(
                "INSERT INTO reg_periksa (
                    no_reg,no_rawat,tgl_registrasi,jam_reg,kd_dokter,no_rkm_medis,kd_poli,p_jawab,almt_pj,hubunganpj,
                    biaya_reg,stts,stts_daftar,status_lanjut,kd_pj,umurdaftar,sttsumur,status_bayar,status_poli
                ) VALUES (
                    :no_reg,:no_rawat,:tgl_registrasi,CURTIME(),:kd_dokter,:no_rkm_medis,:kd_poli,:p_jawab,:almt_pj,:hubunganpj,
                    :biaya_reg,'Belum',:stts_daftar,'Ralan',:kd_pj,:umurdaftar,:sttsumur,'Belum Bayar',:status_poli
                )"
            );
            $stmt->execute([
                'no_reg' => $noReg,
                'no_rawat' => $noRawat,
                'tgl_registrasi' => $tglReg,
                'kd_dokter' => $kdDokter,
                'no_rkm_medis' => $noRm,
                'kd_poli' => $kdPoli,
                'p_jawab' => $pJawab === '' ? ((string)$pasien['namakeluarga'] ?: '-') : $pJawab,
                'almt_pj' => $almtPj === '' ? ((string)$pasien['alamat'] ?: '-') : $almtPj,
                'hubunganpj' => $hubunganPj === '' ? ((string)$pasien['keluarga'] ?: 'SAUDARA') : $hubunganPj,
                'biaya_reg' => $biayaReg,
                'stts_daftar' => $sttsDaftar,
                'kd_pj' => $kdPj,
                'umurdaftar' => $umurValue,
                'sttsumur' => $sttsUmur,
                'status_poli' => $statusPoli,
            ]);

            $pdo->commit();
            header('Location: ?page=registrasi&detail=' . urlencode($noRawat) . '&no_rkm_medis=' . urlencode($noRm) . '&msg=ok&msgd=' . urlencode('Registrasi pasien berhasil disimpan'));
            exit;
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            header('Location: ?page=registrasi&no_rkm_medis=' . urlencode($noRm) . '&msg=error&msgd=' . urlencode('Gagal simpan registrasi: ' . $e->getMessage()));
            exit;
        }
    }

    private function nextNoRm(\PDO $pdo): string
    {
        $row = $pdo->query("SELECT no_rkm_medis FROM set_no_rkm_medis LIMIT 1 FOR UPDATE")->fetch();
        $current = $row['no_rkm_medis'] ?? null;
        if ($current === null || trim((string)$current) === '') {
            $current = (string)$pdo->query("SELECT LPAD(IFNULL(MAX(CAST(no_rkm_medis AS UNSIGNED)),0),6,'0') AS n FROM pasien")->fetchColumn();
        }
        $digitsOnly = preg_replace('/\D+/', '', (string)$current);
        if ($digitsOnly === null || $digitsOnly === '') {
            $digitsOnly = '0';
        }
        $next = (string)((int)$digitsOnly + 1);
        $width = max(strlen($digitsOnly), 6);
        $noRm = str_pad($next, $width, '0', STR_PAD_LEFT);

        if ($row) {
            $stmt = $pdo->prepare("UPDATE set_no_rkm_medis SET no_rkm_medis = :n");
            $stmt->execute(['n' => $noRm]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO set_no_rkm_medis(no_rkm_medis) VALUES(:n)");
            $stmt->execute(['n' => $noRm]);
        }

        return $noRm;
    }

    private function ajaxCheckNoRm(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $noRm = trim((string)($_GET['no_rkm_medis'] ?? ''));
        if ($noRm === '') {
            echo json_encode(['ok' => true, 'exists' => false, 'message' => '']);
            exit;
        }

        $db = new SimrsQueryService();
        $check = $db->value("SELECT COUNT(*) FROM pasien WHERE no_rkm_medis = :rm", ['rm' => $noRm]);
        if (!$check['ok']) {
            echo json_encode(['ok' => false, 'exists' => false, 'message' => (string)($check['error'] ?? 'Gagal cek no RM')]);
            exit;
        }

        $exists = ((int)$check['data']) > 0;
        echo json_encode([
            'ok' => true,
            'exists' => $exists,
            'message' => $exists ? 'No. Rekam Medik sudah terdaftar' : '',
        ]);
        exit;
    }

    private function nextNoRawat(\PDO $pdo, string $tglReg): string
    {
        $stmt = $pdo->prepare("SELECT IFNULL(MAX(CAST(RIGHT(no_rawat,6) AS UNSIGNED)),0) FROM reg_periksa WHERE tgl_registrasi = :tgl");
        $stmt->execute(['tgl' => $tglReg]);
        $num = ((int)$stmt->fetchColumn()) + 1;
        $datePart = str_replace('-', '/', $tglReg);
        return $datePart . '/' . str_pad((string)$num, 6, '0', STR_PAD_LEFT);
    }

    private function nextNoReg(\PDO $pdo, string $tglReg, string $kdPoli): string
    {
        $stmt = $pdo->prepare(
            "SELECT IFNULL(MAX(CAST(no_reg AS UNSIGNED)),0)
             FROM reg_periksa
             WHERE tgl_registrasi = :tgl AND kd_poli = :kd_poli"
        );
        $stmt->execute(['tgl' => $tglReg, 'kd_poli' => $kdPoli]);
        return str_pad((string)(((int)$stmt->fetchColumn()) + 1), 3, '0', STR_PAD_LEFT);
    }

    private function hasAnyRegistration(\PDO $pdo, string $noRm): bool
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE no_rkm_medis = :rm");
        $stmt->execute(['rm' => $noRm]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function hasRegistrationInPoli(\PDO $pdo, string $noRm, string $kdPoli): bool
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reg_periksa WHERE no_rkm_medis = :rm AND kd_poli = :kd_poli");
        $stmt->execute(['rm' => $noRm, 'kd_poli' => $kdPoli]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function biayaRegistrasi(\PDO $pdo, string $kdPoli, string $statusPoli): float
    {
        $stmt = $pdo->prepare("SELECT registrasi, registrasilama FROM poliklinik WHERE kd_poli = :kd_poli LIMIT 1");
        $stmt->execute(['kd_poli' => $kdPoli]);
        $row = $stmt->fetch() ?: ['registrasi' => 0, 'registrasilama' => 0];
        return $statusPoli === 'Baru' ? (float)$row['registrasi'] : (float)$row['registrasilama'];
    }

    private function umurTextAndUnit(string $tglLahir, string $tglAcuan): array
    {
        $dtLahir = new DateTimeImmutable($tglLahir);
        $dtAcuan = new DateTimeImmutable($tglAcuan);
        $diff = $dtLahir->diff($dtAcuan);
        $text = sprintf('%d Th %d Bl %d Hr', $diff->y, $diff->m, $diff->d);
        $unit = 'Hr';
        $value = $diff->d;
        if ($diff->y > 0) {
            $unit = 'Th';
            $value = $diff->y;
        } elseif ($diff->m > 0) {
            $unit = 'Bl';
            $value = $diff->m;
        }
        return [$text, $value, $unit];
    }

    private function umurValueAndUnit(string $tglLahir, string $tglAcuan): array
    {
        [, $value, $unit] = $this->umurTextAndUnit($tglLahir, $tglAcuan);
        return [$value, $unit];
    }

    private function loadDefaultMeta(SimrsQueryService $db): array
    {
        $out = [
            'kd_kel' => null,
            'kd_kec' => null,
            'kd_kab' => null,
            'kd_prop' => null,
            'perusahaan_pasien' => null,
            'suku_bangsa' => null,
            'bahasa_pasien' => null,
            'cacat_fisik' => null,
            'error' => null,
        ];

        $map = [
            'kd_kel' => "SELECT kd_kel FROM kelurahan ORDER BY kd_kel LIMIT 1",
            'kd_kec' => "SELECT kd_kec FROM kecamatan ORDER BY kd_kec LIMIT 1",
            'kd_kab' => "SELECT kd_kab FROM kabupaten ORDER BY kd_kab LIMIT 1",
            'kd_prop' => "SELECT kd_prop FROM propinsi ORDER BY kd_prop LIMIT 1",
            'perusahaan_pasien' => "SELECT kode_perusahaan FROM perusahaan_pasien ORDER BY kode_perusahaan LIMIT 1",
            'suku_bangsa' => "SELECT id FROM suku_bangsa ORDER BY id LIMIT 1",
            'bahasa_pasien' => "SELECT id FROM bahasa_pasien ORDER BY id LIMIT 1",
            'cacat_fisik' => "SELECT id FROM cacat_fisik ORDER BY id LIMIT 1",
        ];

        foreach ($map as $key => $sql) {
            $res = $db->run($sql);
            if (!$res['ok'] || empty($res['data'])) {
                $out['error'] = 'Referensi default pasien belum lengkap: ' . $key;
                return $out;
            }
            $out[$key] = array_values($res['data'][0])[0];
        }
        return $out;
    }
}



