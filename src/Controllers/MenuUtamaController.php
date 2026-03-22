<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use DateTimeImmutable;
use Throwable;
use WebBaru\Database;
use WebBaru\Services\SimrsQueryService;

final class MenuUtamaController
{
    public function index(): void
    {
        $page = trim((string)($_GET['page'] ?? 'menu'));
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost($page);
            return;
        }

        if ($page === 'menu-pasien') {
            $this->pasien();
            return;
        }
        if ($page === 'menu-obat') {
            $this->obat();
            return;
        }
        if ($page === 'menu-stok-opname') {
            $this->stokOpname();
            return;
        }

        view('menu_utama', [
            'title' => 'Menu Modul',
        ]);
    }

    private function handlePost(string $page): void
    {
        $action = trim((string)($_POST['action'] ?? ''));
        switch ($action) {
            case 'pasien_create':
                $this->createPasien();
                return;
            case 'pasien_update':
                $this->updatePasien();
                return;
            case 'pasien_delete':
                $this->deletePasien();
                return;
            case 'obat_create':
                $this->createObat();
                return;
            case 'obat_update':
                $this->updateObat();
                return;
            case 'obat_delete':
                $this->deleteObat();
                return;
            case 'opname_create':
                $this->createOpname();
                return;
            case 'opname_update':
                $this->updateOpname();
                return;
            case 'opname_delete':
                $this->deleteOpname();
                return;
            default:
                $fallback = in_array($page, ['menu-pasien', 'menu-obat', 'menu-stok-opname'], true) ? $page : 'menu';
                $this->redirectWithMsg($fallback, 'Aksi tidak dikenali', 'error');
                return;
        }
    }

    private function pasien(): void
    {
        $db = new SimrsQueryService();
        $q = trim((string)($_GET['q'] ?? ''));
        $editNoRm = trim((string)($_GET['edit'] ?? ''));
        $msg = trim((string)($_GET['msg'] ?? ''));
        $msgType = trim((string)($_GET['t'] ?? 'ok'));
        $params = [];
        $where = '';
        if ($q !== '') {
            $where = "WHERE p.no_rkm_medis LIKE :q1 OR p.nm_pasien LIKE :q2 OR p.no_ktp LIKE :q3 OR p.no_peserta LIKE :q4";
            $params = [
                'q1' => '%' . $q . '%',
                'q2' => '%' . $q . '%',
                'q3' => '%' . $q . '%',
                'q4' => '%' . $q . '%',
            ];
        }

        $rows = $db->run(
            "SELECT p.no_rkm_medis, p.nm_pasien, p.jk, p.tgl_lahir, p.no_ktp, p.no_peserta, p.no_tlp, p.alamat, p.kd_pj
             FROM pasien p
             {$where}
             ORDER BY p.no_rkm_medis DESC
             LIMIT 150",
            $params
        );
        $penjab = $db->run("SELECT kd_pj, png_jawab FROM penjab ORDER BY png_jawab");
        $editRow = null;
        if ($editNoRm !== '') {
            $res = $db->run(
                "SELECT no_rkm_medis, nm_pasien, jk, tgl_lahir, tmp_lahir, no_ktp, no_peserta, no_tlp, alamat, kd_pj, nm_ibu
                 FROM pasien
                 WHERE no_rkm_medis = :rm
                 LIMIT 1",
                ['rm' => $editNoRm]
            );
            if ($res['ok'] && !empty($res['data'])) {
                $editRow = $res['data'][0];
            }
        }

        view('menu_pasien', [
            'title' => 'Data Master Pasien',
            'q' => $q,
            'rows' => $rows['data'],
            'error' => $rows['ok'] ? null : $rows['error'],
            'penjab' => $penjab['ok'] ? $penjab['data'] : [],
            'editRow' => $editRow,
            'msg' => $msg,
            'msgType' => $msgType,
        ]);
    }

    private function obat(): void
    {
        $db = new SimrsQueryService();
        $q = trim((string)($_GET['q'] ?? ''));
        $editKode = trim((string)($_GET['edit'] ?? ''));
        $msg = trim((string)($_GET['msg'] ?? ''));
        $msgType = trim((string)($_GET['t'] ?? 'ok'));
        $params = [];
        $where = '';
        if ($q !== '') {
            $where = "WHERE db.kode_brng LIKE :q1 OR db.nama_brng LIKE :q2";
            $params = [
                'q1' => '%' . $q . '%',
                'q2' => '%' . $q . '%',
            ];
        }

        $rows = $db->run(
            "SELECT db.kode_brng, db.nama_brng, db.kode_sat, db.h_beli, db.ralan, db.letak_barang, db.stokminimal, db.status,
                    IFNULL(stok.total_stok, 0) AS total_stok
             FROM databarang db
             LEFT JOIN (
                SELECT kode_brng, SUM(stok) AS total_stok
                FROM gudangbarang
                GROUP BY kode_brng
             ) stok ON stok.kode_brng = db.kode_brng
             {$where}
             ORDER BY db.nama_brng ASC
             LIMIT 200",
            $params
        );
        $editRow = null;
        if ($editKode !== '') {
            $res = $db->run(
                "SELECT kode_brng, nama_brng, kode_sat, h_beli, ralan, letak_barang, stokminimal, status
                 FROM databarang
                 WHERE kode_brng = :kode
                 LIMIT 1",
                ['kode' => $editKode]
            );
            if ($res['ok'] && !empty($res['data'])) {
                $editRow = $res['data'][0];
            }
        }

        view('menu_obat', [
            'title' => 'Data Master Obat',
            'q' => $q,
            'rows' => $rows['data'],
            'error' => $rows['ok'] ? null : $rows['error'],
            'editRow' => $editRow,
            'msg' => $msg,
            'msgType' => $msgType,
        ]);
    }

    private function stokOpname(): void
    {
        $db = new SimrsQueryService();
        $q = trim((string)($_GET['q'] ?? ''));
        $msg = trim((string)($_GET['msg'] ?? ''));
        $msgType = trim((string)($_GET['t'] ?? 'ok'));
        $editKey = [
            'kode_brng' => trim((string)($_GET['ek'] ?? '')),
            'tanggal' => trim((string)($_GET['et'] ?? '')),
            'kd_bangsal' => trim((string)($_GET['eb'] ?? '')),
            'no_batch' => trim((string)($_GET['enb'] ?? '')),
            'no_faktur' => trim((string)($_GET['enf'] ?? '')),
        ];

        // Halaman ini dipakai untuk melihat & mengatur stok per bangsal (gudangbarang)
        // dengan ringkasan stok pada 3 lokasi: Logistik (GDO), Farmasi (FM), OK (OK)
        $rows = ['ok' => true, 'data' => [], 'error' => null];
        $params = [];
        $where = '';
        if ($q !== '') {
            $where = "WHERE db.kode_brng LIKE :q1 OR db.nama_brng LIKE :q2";
            $params = [
                'q1' => '%' . $q . '%',
                'q2' => '%' . $q . '%',
            ];
        }

        $rows = $db->run(
            "SELECT db.kode_brng, db.nama_brng,
                    IFNULL(gl.stok, 0) AS stok_logistik,
                    IFNULL(gf.stok, 0) AS stok_farmasi,
                    IFNULL(gok.stok, 0) AS stok_ok
             FROM databarang db
             LEFT JOIN gudangbarang gl ON gl.kode_brng = db.kode_brng AND gl.kd_bangsal = 'GDO'
             LEFT JOIN gudangbarang gf ON gf.kode_brng = db.kode_brng AND gf.kd_bangsal = 'FM'
             LEFT JOIN gudangbarang gok ON gok.kode_brng = db.kode_brng AND gok.kd_bangsal = 'OK'
             {$where}
             ORDER BY db.nama_brng ASC
             LIMIT 200",
            $params
        );

        // Form edit stok gudangbarang (1 item) berdasarkan key gudangbarang
        $editRow = null;
        if ($editKey['kode_brng'] !== '' && $editKey['kd_bangsal'] !== '') {
            $res = $db->run(
                "SELECT kode_brng, kd_bangsal, stok, no_batch, no_faktur
                 FROM gudangbarang
                 WHERE kode_brng=:kode AND kd_bangsal=:bangsal AND no_batch=:batch AND no_faktur=:faktur
                 LIMIT 1",
                [
                    'kode' => $editKey['kode_brng'],
                    'bangsal' => $editKey['kd_bangsal'],
                    'batch' => $editKey['no_batch'] === '' ? '-' : $editKey['no_batch'],
                    'faktur' => $editKey['no_faktur'] === '' ? '-' : $editKey['no_faktur'],
                ]
            );
            if ($res['ok'] && !empty($res['data'])) {
                $editRow = $res['data'][0];
            }
        }

        view('menu_stok_opname', [
            'title' => 'Stok Opname',
            'q' => $q,
            'rows' => $rows['data'],
            'error' => $rows['ok'] ? null : $rows['error'],
            'opnameTableReady' => true,
            'editRow' => $editRow,
            'msg' => $msg,
            'msgType' => $msgType,
        ]);
    }

    private function createPasien(): void
    {
        $noRm = trim((string)($_POST['no_rkm_medis'] ?? ''));
        $nmPasien = trim((string)($_POST['nm_pasien'] ?? ''));
        $jk = strtoupper(trim((string)($_POST['jk'] ?? 'L')));
        $tglLahir = trim((string)($_POST['tgl_lahir'] ?? ''));
        $tmpLahir = trim((string)($_POST['tmp_lahir'] ?? '-'));
        $alamat = trim((string)($_POST['alamat'] ?? '-'));
        $noTlp = trim((string)($_POST['no_tlp'] ?? '-'));
        $noKtp = preg_replace('/\D+/', '', trim((string)($_POST['no_ktp'] ?? ''))) ?? '';
        $noPeserta = preg_replace('/\D+/', '', trim((string)($_POST['no_peserta'] ?? ''))) ?? '';
        $nmIbu = trim((string)($_POST['nm_ibu'] ?? '-'));
        $kdPj = trim((string)($_POST['kd_pj'] ?? ''));

        if ($noRm === '' || $nmPasien === '' || $tglLahir === '' || $kdPj === '') {
            $this->redirectWithMsg('menu-pasien', 'Data pasien belum lengkap', 'error');
        }
        if (!in_array($jk, ['L', 'P'], true)) {
            $jk = 'L';
        }

        $db = new SimrsQueryService();
        $cek = $db->value("SELECT COUNT(*) FROM pasien WHERE no_rkm_medis = :rm", ['rm' => $noRm]);
        if (!$cek['ok']) {
            $this->redirectWithMsg('menu-pasien', 'Gagal cek No RM: ' . (string)$cek['error'], 'error');
        }
        if ((int)$cek['data'] > 0) {
            $this->redirectWithMsg('menu-pasien', 'No RM sudah digunakan', 'error');
        }
        $meta = $this->loadDefaultMeta($db);
        if (!empty($meta['error'])) {
            $this->redirectWithMsg('menu-pasien', (string)$meta['error'], 'error');
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            $umur = $this->umurText($tglLahir, date('Y-m-d'));
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
                'umur' => $umur,
                'kd_pj' => $kdPj,
                'no_peserta' => $noPeserta === '' ? '-' : $noPeserta,
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
            $this->redirectWithMsg('menu-pasien', 'Pasien berhasil ditambahkan');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->redirectWithMsg('menu-pasien', 'Gagal tambah pasien: ' . $e->getMessage(), 'error');
        }
    }

    private function updatePasien(): void
    {
        $noRm = trim((string)($_POST['no_rkm_medis'] ?? ''));
        $nmPasien = trim((string)($_POST['nm_pasien'] ?? ''));
        $jk = strtoupper(trim((string)($_POST['jk'] ?? 'L')));
        $tglLahir = trim((string)($_POST['tgl_lahir'] ?? ''));
        $tmpLahir = trim((string)($_POST['tmp_lahir'] ?? '-'));
        $alamat = trim((string)($_POST['alamat'] ?? '-'));
        $noTlp = trim((string)($_POST['no_tlp'] ?? '-'));
        $noKtp = preg_replace('/\D+/', '', trim((string)($_POST['no_ktp'] ?? ''))) ?? '';
        $noPeserta = preg_replace('/\D+/', '', trim((string)($_POST['no_peserta'] ?? ''))) ?? '';
        $nmIbu = trim((string)($_POST['nm_ibu'] ?? '-'));
        $kdPj = trim((string)($_POST['kd_pj'] ?? ''));
        if ($noRm === '' || $nmPasien === '' || $tglLahir === '' || $kdPj === '') {
            $this->redirectWithMsg('menu-pasien', 'Data edit pasien belum lengkap', 'error');
        }
        if (!in_array($jk, ['L', 'P'], true)) {
            $jk = 'L';
        }

        $db = new SimrsQueryService();
        $umur = $this->umurText($tglLahir, date('Y-m-d'));
        $res = $db->run(
            "UPDATE pasien
             SET nm_pasien=:nm_pasien,jk=:jk,tgl_lahir=:tgl_lahir,tmp_lahir=:tmp_lahir,no_ktp=:no_ktp,no_peserta=:no_peserta,no_tlp=:no_tlp,
                 alamat=:alamat,alamatpj=:alamatpj,kd_pj=:kd_pj,nm_ibu=:nm_ibu,umur=:umur
             WHERE no_rkm_medis=:no_rkm_medis",
            [
                'nm_pasien' => $nmPasien,
                'jk' => $jk,
                'tgl_lahir' => $tglLahir,
                'tmp_lahir' => $tmpLahir === '' ? '-' : $tmpLahir,
                'no_ktp' => $noKtp === '' ? '-' : $noKtp,
                'no_peserta' => $noPeserta === '' ? '-' : $noPeserta,
                'no_tlp' => $noTlp === '' ? '-' : $noTlp,
                'alamat' => $alamat === '' ? '-' : $alamat,
                'alamatpj' => $alamat === '' ? '-' : $alamat,
                'kd_pj' => $kdPj,
                'nm_ibu' => $nmIbu === '' ? '-' : $nmIbu,
                'umur' => $umur,
                'no_rkm_medis' => $noRm,
            ]
        );
        if (!$res['ok']) {
            $this->redirectWithMsg('menu-pasien', 'Gagal update pasien: ' . (string)$res['error'], 'error');
        }
        $this->redirectWithMsg('menu-pasien', 'Data pasien berhasil diupdate');
    }

    private function deletePasien(): void
    {
        $noRm = trim((string)($_POST['no_rkm_medis'] ?? ''));
        if ($noRm === '') {
            $this->redirectWithMsg('menu-pasien', 'No RM tidak valid', 'error');
        }
        $db = new SimrsQueryService();
        $res = $db->run("DELETE FROM pasien WHERE no_rkm_medis=:no_rkm_medis LIMIT 1", ['no_rkm_medis' => $noRm]);
        if (!$res['ok']) {
            $this->redirectWithMsg('menu-pasien', 'Gagal hapus pasien: ' . (string)$res['error'], 'error');
        }
        $this->redirectWithMsg('menu-pasien', 'Data pasien berhasil dihapus');
    }

    private function createObat(): void
    {
        $kode = trim((string)($_POST['kode_brng'] ?? ''));
        $nama = trim((string)($_POST['nama_brng'] ?? ''));
        $kodeSat = trim((string)($_POST['kode_sat'] ?? ''));
        $hBeli = (float)($_POST['h_beli'] ?? 0);
        $ralan = (float)($_POST['ralan'] ?? 0);
        $letak = trim((string)($_POST['letak_barang'] ?? ''));
        $stokMinimal = (float)($_POST['stokminimal'] ?? 0);
        $status = trim((string)($_POST['status'] ?? '1')) === '0' ? '0' : '1';

        if ($kode === '' || $nama === '' || $kodeSat === '') {
            $this->redirectWithMsg('menu-obat', 'Kode, nama, dan satuan obat wajib diisi', 'error');
        }

        $db = new SimrsQueryService();
        $cek = $db->value("SELECT COUNT(*) FROM databarang WHERE kode_brng=:kode", ['kode' => $kode]);
        if (!$cek['ok']) {
            $this->redirectWithMsg('menu-obat', 'Gagal cek kode obat: ' . (string)$cek['error'], 'error');
        }
        if ((int)$cek['data'] > 0) {
            $this->redirectWithMsg('menu-obat', 'Kode obat sudah ada', 'error');
        }

        $refJns = $this->firstCode('SELECT kdjns FROM jenis ORDER BY kdjns');
        $refInd = $this->firstCode('SELECT kode_industri FROM industrifarmasi ORDER BY kode_industri');
        $refKat = $this->firstCode('SELECT kode FROM kategori_barang ORDER BY kode');
        $refGol = $this->firstCode('SELECT kode FROM golongan_barang ORDER BY kode');
        if ($refJns === '' || $refInd === '' || $refKat === '' || $refGol === '') {
            $this->redirectWithMsg('menu-obat', 'Referensi master obat belum lengkap', 'error');
        }

        $res = $db->run(
            "INSERT INTO databarang (
                kode_brng,nama_brng,kode_satbesar,kode_sat,letak_barang,dasar,h_beli,ralan,kelas1,kelas2,kelas3,utama,vip,vvip,beliluar,jualbebas,karyawan,stokminimal,kdjns,isi,kapasitas,expire,status,kode_industri,kode_kategori,kode_golongan
            ) VALUES (
                :kode,:nama,:kode_satbesar,:kode_sat,:letak,:dasar,:h_beli,:ralan,:kelas1,:kelas2,:kelas3,:utama,:vip,:vvip,:beliluar,:jualbebas,:karyawan,:stokminimal,:kdjns,:isi,:kapasitas,NULL,:status,:kode_industri,:kode_kategori,:kode_golongan
            )",
            [
                'kode' => $kode,
                'nama' => $nama,
                'kode_satbesar' => $kodeSat,
                'kode_sat' => $kodeSat,
                'letak' => $letak,
                'dasar' => $hBeli,
                'h_beli' => $hBeli,
                'ralan' => $ralan,
                'kelas1' => $ralan,
                'kelas2' => $ralan,
                'kelas3' => $ralan,
                'utama' => $ralan,
                'vip' => $ralan,
                'vvip' => $ralan,
                'beliluar' => $ralan,
                'jualbebas' => $ralan,
                'karyawan' => $ralan,
                'stokminimal' => $stokMinimal,
                'kdjns' => $refJns,
                'isi' => 1,
                'kapasitas' => 0,
                'status' => $status,
                'kode_industri' => $refInd,
                'kode_kategori' => $refKat,
                'kode_golongan' => $refGol,
            ]
        );
        if (!$res['ok']) {
            $this->redirectWithMsg('menu-obat', 'Gagal tambah obat: ' . (string)$res['error'], 'error');
        }
        $this->redirectWithMsg('menu-obat', 'Data obat berhasil ditambahkan');
    }

    private function updateObat(): void
    {
        $kode = trim((string)($_POST['kode_brng'] ?? ''));
        $nama = trim((string)($_POST['nama_brng'] ?? ''));
        $kodeSat = trim((string)($_POST['kode_sat'] ?? ''));
        $hBeli = (float)($_POST['h_beli'] ?? 0);
        $ralan = (float)($_POST['ralan'] ?? 0);
        $letak = trim((string)($_POST['letak_barang'] ?? ''));
        $stokMinimal = (float)($_POST['stokminimal'] ?? 0);
        $status = trim((string)($_POST['status'] ?? '1')) === '0' ? '0' : '1';

        if ($kode === '' || $nama === '' || $kodeSat === '') {
            $this->redirectWithMsg('menu-obat', 'Data edit obat belum lengkap', 'error');
        }

        $db = new SimrsQueryService();
        $res = $db->run(
            "UPDATE databarang
             SET nama_brng=:nama,kode_sat=:kode_sat,kode_satbesar=:kode_satbesar,letak_barang=:letak,dasar=:dasar,h_beli=:h_beli,ralan=:ralan,stokminimal=:stokminimal,status=:status
             WHERE kode_brng=:kode",
            [
                'nama' => $nama,
                'kode_sat' => $kodeSat,
                'kode_satbesar' => $kodeSat,
                'letak' => $letak,
                'dasar' => $hBeli,
                'h_beli' => $hBeli,
                'ralan' => $ralan,
                'stokminimal' => $stokMinimal,
                'status' => $status,
                'kode' => $kode,
            ]
        );
        if (!$res['ok']) {
            $this->redirectWithMsg('menu-obat', 'Gagal update obat: ' . (string)$res['error'], 'error');
        }
        $this->redirectWithMsg('menu-obat', 'Data obat berhasil diupdate');
    }

    private function deleteObat(): void
    {
        $kode = trim((string)($_POST['kode_brng'] ?? ''));
        if ($kode === '') {
            $this->redirectWithMsg('menu-obat', 'Kode obat tidak valid', 'error');
        }
        $db = new SimrsQueryService();
        $res = $db->run("DELETE FROM databarang WHERE kode_brng=:kode LIMIT 1", ['kode' => $kode]);
        if (!$res['ok']) {
            $this->redirectWithMsg('menu-obat', 'Gagal hapus obat: ' . (string)$res['error'], 'error');
        }
        $this->redirectWithMsg('menu-obat', 'Data obat berhasil dihapus');
    }

    private function createOpname(): void
    {
        // Disimpan ke gudangbarang (posisi stok saat ini)
        $kode = trim((string)($_POST['kode_brng'] ?? ''));
        $tanggal = trim((string)($_POST['tanggal'] ?? date('Y-m-d'))); // tidak disimpan (gudangbarang tidak punya tanggal)
        $bangsal = trim((string)($_POST['kd_bangsal'] ?? ''));
        $stok = (float)($_POST['stok'] ?? 0);

        if ($kode === '' || $tanggal === '' || $bangsal === '') {
            $this->redirectWithMsg('menu-stok-opname', 'Kode barang, tanggal, dan bangsal wajib diisi', 'error');
        }

        $db = new SimrsQueryService();
        // no_batch & no_faktur wajib NOT NULL + bagian dari PK
        $res = $db->run(
            "INSERT INTO gudangbarang (kode_brng, kd_bangsal, stok, no_batch, no_faktur)
             VALUES (:kode, :bangsal, :stok, :batch, :faktur)
             ON DUPLICATE KEY UPDATE stok = :stok",
            [
                'kode' => $kode,
                'bangsal' => $bangsal,
                'stok' => $stok,
                'batch' => '-',
                'faktur' => '-',
            ]
        );
        if (!$res['ok']) {
            $this->redirectWithMsg('menu-stok-opname', 'Gagal simpan stok: ' . (string)$res['error'], 'error');
        }
        $this->redirectWithMsg('menu-stok-opname', 'Stok berhasil disimpan');
    }

    private function updateOpname(): void
    {
        // Disimpan ke gudangbarang (posisi stok saat ini)
        $oldKode = trim((string)($_POST['old_kode_brng'] ?? ''));
        $oldBangsal = trim((string)($_POST['old_kd_bangsal'] ?? ''));

        $kode = trim((string)($_POST['kode_brng'] ?? ''));
        $tanggal = trim((string)($_POST['tanggal'] ?? date('Y-m-d'))); // tidak disimpan
        $bangsal = trim((string)($_POST['kd_bangsal'] ?? ''));
        $stok = (float)($_POST['stok'] ?? 0);

        if ($oldKode === '' || $oldBangsal === '' || $kode === '' || $tanggal === '' || $bangsal === '') {
            $this->redirectWithMsg('menu-stok-opname', 'Data kunci stok tidak lengkap', 'error');
        }

        // Key gudangbarang: kode_brng + kd_bangsal + no_batch + no_faktur
        // Kita pakai default '-' untuk batch/faktur.
        $db = new SimrsQueryService();
        $res = $db->run(
            "UPDATE gudangbarang
             SET kode_brng=:kode, kd_bangsal=:bangsal, stok=:stok
             WHERE kode_brng=:old_kode AND kd_bangsal=:old_bangsal AND no_batch=:batch AND no_faktur=:faktur",
            [
                'kode' => $kode,
                'bangsal' => $bangsal,
                'stok' => $stok,
                'old_kode' => $oldKode,
                'old_bangsal' => $oldBangsal,
                'batch' => '-',
                'faktur' => '-',
            ]
        );
        if (!$res['ok']) {
            $this->redirectWithMsg('menu-stok-opname', 'Gagal update stok: ' . (string)$res['error'], 'error');
        }
        $this->redirectWithMsg('menu-stok-opname', 'Stok berhasil diupdate');
    }

    private function deleteOpname(): void
    {
        $kode = trim((string)($_POST['kode_brng'] ?? ''));
        $bangsal = trim((string)($_POST['kd_bangsal'] ?? ''));
        if ($kode === '' || $bangsal === '') {
            $this->redirectWithMsg('menu-stok-opname', 'Data hapus stok tidak valid', 'error');
        }

        $db = new SimrsQueryService();
        $res = $db->run(
            "DELETE FROM gudangbarang
             WHERE kode_brng=:kode AND kd_bangsal=:bangsal AND no_batch=:batch AND no_faktur=:faktur",
            [
                'kode' => $kode,
                'bangsal' => $bangsal,
                'batch' => '-',
                'faktur' => '-',
            ]
        );
        if (!$res['ok']) {
            $this->redirectWithMsg('menu-stok-opname', 'Gagal hapus stok: ' . (string)$res['error'], 'error');
        }
        $this->redirectWithMsg('menu-stok-opname', 'Stok berhasil dihapus');
    }

    private function redirectWithMsg(string $page, string $msg, string $type = 'ok'): void
    {
        $safeType = $type === 'error' ? 'error' : 'ok';
        header('Location: ?page=' . urlencode($page) . '&t=' . urlencode($safeType) . '&msg=' . urlencode($msg));
        exit;
    }

    private function umurText(string $tglLahir, string $tglAcuan): string
    {
        $dtLahir = new DateTimeImmutable($tglLahir);
        $dtAcuan = new DateTimeImmutable($tglAcuan);
        $diff = $dtLahir->diff($dtAcuan);
        return sprintf('%d Th %d Bl %d Hr', $diff->y, $diff->m, $diff->d);
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

    private function firstCode(string $sql): string
    {
        $db = new SimrsQueryService();
        $res = $db->run($sql . ' LIMIT 1');
        if (!$res['ok'] || empty($res['data'])) {
            return '';
        }
        return trim((string)array_values($res['data'][0])[0]);
    }

    private function hitungOpname(float $stok, float $real, float $hBeli): array
    {
        $selisih = 0.0;
        $nomiHilang = 0.0;
        $lebih = 0.0;
        $nomiLebih = 0.0;

        if ($real < $stok) {
            $selisih = $stok - $real;
            $nomiHilang = $selisih * $hBeli;
        } elseif ($real > $stok) {
            $lebih = $real - $stok;
            $nomiLebih = $lebih * $hBeli;
        }

        return [
            'selisih' => $selisih,
            'nomihilang' => $nomiHilang,
            'lebih' => $lebih,
            'nomilebih' => $nomiLebih,
        ];
    }
}
