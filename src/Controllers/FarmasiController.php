<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use Throwable;
use WebBaru\Database;
use WebBaru\Services\SimrsQueryService;

final class FarmasiController
{
    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost();
            return;
        }

        $db = new SimrsQueryService();

        $from = trim((string)($_GET['from'] ?? date('Y-m-d')));
        $to = trim((string)($_GET['to'] ?? date('Y-m-d')));
        $q = trim((string)($_GET['q'] ?? ''));
        $status = trim((string)($_GET['status'] ?? 'belum'));
        if (!in_array($status, ['semua', 'belum', 'sudah'], true)) {
            $status = 'belum';
        }
        $sttsRawat = trim((string)($_GET['stts_rawat'] ?? 'semua'));
        if (!in_array($sttsRawat, ['semua', 'ralan', 'ranap'], true)) {
            $sttsRawat = 'semua';
        }
        $detailNoResep = trim((string)($_GET['detail'] ?? ''));
        $openModal = trim((string)($_GET['open'] ?? '')) === '1';
        $msg = trim((string)($_GET['msg'] ?? ''));
        $msgDetail = trim((string)($_GET['msgd'] ?? ''));

        $where = ['ro.tgl_peresepan BETWEEN :from AND :to'];
        $params = ['from' => $from, 'to' => $to];

        if ($q !== '') {
            $where[] = "(ro.no_resep LIKE :q1 OR ro.no_rawat LIKE :q2 OR rp.no_rkm_medis LIKE :q3 OR p.nm_pasien LIKE :q4 OR d.nm_dokter LIKE :q5)";
            $params['q1'] = '%' . $q . '%';
            $params['q2'] = '%' . $q . '%';
            $params['q3'] = '%' . $q . '%';
            $params['q4'] = '%' . $q . '%';
            $params['q5'] = '%' . $q . '%';
        }
        if ($status === 'belum') {
            $where[] = "(ro.tgl_perawatan='0000-00-00' OR ro.tgl_perawatan IS NULL)";
        } elseif ($status === 'sudah') {
            $where[] = "(ro.tgl_perawatan<>'0000-00-00' AND ro.tgl_perawatan IS NOT NULL)";
        }
        if ($sttsRawat !== 'semua') {
            $where[] = "ro.status = :stts_rawat";
            $params['stts_rawat'] = $sttsRawat;
        }

        $rows = $db->run(
            "SELECT ro.no_resep, ro.tgl_peresepan, ro.jam_peresepan, ro.no_rawat, ro.status,
                    rp.no_rkm_medis, p.nm_pasien, d.nm_dokter, pl.nm_poli, pj.png_jawab,
                    IF(ro.tgl_perawatan='0000-00-00' OR ro.tgl_perawatan IS NULL, 'Belum Terlayani', 'Sudah Terlayani') AS status_layanan,
                    IF(ro.tgl_perawatan='0000-00-00' OR ro.tgl_perawatan IS NULL, NULL, ro.tgl_perawatan) AS tgl_validasi,
                    ro.jam AS jam_validasi,
                    IF(ro.tgl_penyerahan='0000-00-00' OR ro.tgl_penyerahan IS NULL, NULL, ro.tgl_penyerahan) AS tgl_penyerahan,
                    ro.jam_penyerahan
             FROM resep_obat ro
             INNER JOIN reg_periksa rp ON rp.no_rawat = ro.no_rawat
             INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
             INNER JOIN dokter d ON d.kd_dokter = ro.kd_dokter
             INNER JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
             INNER JOIN penjab pj ON pj.kd_pj = rp.kd_pj
             WHERE " . implode(' AND ', $where) . "
             ORDER BY (ro.tgl_perawatan='0000-00-00' OR ro.tgl_perawatan IS NULL) DESC,
                      ro.tgl_peresepan DESC, ro.jam_peresepan DESC
             LIMIT 400",
            $params
        );

        $detail = null;
        $detailItems = [];
        $detailError = null;
        if ($detailNoResep !== '') {
            $detailLookup = $db->run(
                "SELECT no_resep
                 FROM resep_obat
                 WHERE no_resep = :detail OR no_rawat = :detail
                 LIMIT 1",
                ['detail' => $detailNoResep]
            );
            $resolvedNoResep = $detailLookup['data'][0]['no_resep'] ?? $detailNoResep;
            $head = $db->run(
                "SELECT ro.no_resep, ro.tgl_peresepan, ro.jam_peresepan, ro.no_rawat, ro.status,
                        IF(ro.tgl_perawatan='0000-00-00' OR ro.tgl_perawatan IS NULL, 'Belum Terlayani', 'Sudah Terlayani') AS status_layanan,
                        IF(ro.tgl_perawatan='0000-00-00' OR ro.tgl_perawatan IS NULL, NULL, ro.tgl_perawatan) AS tgl_validasi,
                        ro.jam AS jam_validasi,
                        IF(ro.tgl_penyerahan='0000-00-00' OR ro.tgl_penyerahan IS NULL, NULL, ro.tgl_penyerahan) AS tgl_penyerahan,
                        ro.jam_penyerahan,
                        rp.no_rkm_medis, p.nm_pasien, d.nm_dokter, pl.nm_poli, pj.png_jawab
                 FROM resep_obat ro
                 INNER JOIN reg_periksa rp ON rp.no_rawat = ro.no_rawat
                 INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
                 INNER JOIN dokter d ON d.kd_dokter = ro.kd_dokter
                 INNER JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
                 INNER JOIN penjab pj ON pj.kd_pj = rp.kd_pj
                 WHERE ro.no_resep=:no_resep
                 LIMIT 1",
                ['no_resep' => $resolvedNoResep]
            );
            if ($head['ok'] && !empty($head['data'])) {
                $detail = $head['data'][0];
                $detailNoResep = (string)($detail['no_resep'] ?? $resolvedNoResep);
                $detailItems = $db->run(
                    "SELECT rd.kode_brng, db.nama_brng, rd.jml, db.kode_sat, rd.aturan_pakai, db.ralan,
                            IFNULL(stk.stok_total, 0) AS stok_total,
                            'Obat' AS kategori
                     FROM resep_dokter rd
                     INNER JOIN databarang db ON db.kode_brng = rd.kode_brng
                     LEFT JOIN (
                        SELECT kode_brng, SUM(stok) AS stok_total
                        FROM gudangbarang
                        GROUP BY kode_brng
                     ) stk ON stk.kode_brng = rd.kode_brng
                     WHERE rd.no_resep = :no_resep
                     ORDER BY db.nama_brng",
                    ['no_resep' => $detailNoResep]
                )['data'];
            } else {
                $detailError = $head['error'] ?? 'Detail resep tidak ditemukan';
            }
        }

        view('farmasi', [
            'title' => 'Modul Farmasi',
            'rows' => $rows['data'],
            'error' => $rows['ok'] ? null : $rows['error'],
            'from' => $from,
            'to' => $to,
            'q' => $q,
            'status' => $status,
            'sttsRawat' => $sttsRawat,
            'detailNoResep' => $detailNoResep,
            'openModal' => $openModal,
            'detail' => $detail,
            'detailItems' => $detailItems,
            'detailError' => $detailError,
            'msg' => $msg,
            'msgDetail' => $msgDetail,
        ]);
    }

    private function handlePost(): void
    {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'validasi_resep') {
            $this->validasiResep();
            return;
        }
        if ($action === 'update_resep_item') {
            $this->updateResepItem();
            return;
        }
        if ($action === 'delete_resep_item') {
            $this->deleteResepItem();
            return;
        }
        $this->goList('error', 'Aksi tidak dikenali');
    }

    private function validasiResep(): void
    {
        $noResep = trim((string)($_POST['no_resep'] ?? ''));
        if ($noResep === '') {
            $this->goList('error', 'No resep belum dipilih');
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();

            $headStmt = $pdo->prepare(
                "SELECT no_rawat, IFNULL(status,'ralan') AS status
                 FROM resep_obat
                 WHERE no_resep = :no_resep
                 LIMIT 1"
            );
            $headStmt->execute(['no_resep' => $noResep]);
            $head = $headStmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            if (!is_array($head)) {
                throw new \RuntimeException('Resep tidak ditemukan');
            }

            if ($this->isResepSudahTervalidasi($noResep)) {
                throw new \RuntimeException('Resep sudah tervalidasi');
            }

            $itemStmt = $pdo->prepare(
                "SELECT rd.kode_brng, rd.jml, db.h_beli, db.ralan
                 FROM resep_dokter rd
                 INNER JOIN databarang db ON db.kode_brng = rd.kode_brng
                 WHERE rd.no_resep = :no_resep
                 ORDER BY rd.kode_brng"
            );
            $itemStmt->execute(['no_resep' => $noResep]);
            $items = $itemStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            if (empty($items)) {
                throw new \RuntimeException('Item resep kosong, tidak bisa divalidasi');
            }

            $selectGudang = $pdo->prepare(
                "SELECT kd_bangsal, no_batch, no_faktur, stok
                 FROM gudangbarang
                 WHERE kode_brng = :kode
                 ORDER BY (kd_bangsal='FM') DESC, stok DESC, kd_bangsal ASC
                 LIMIT 50"
            );
            $insertDetail = $pdo->prepare(
                "INSERT INTO detail_pemberian_obat
                (tgl_perawatan,jam,no_rawat,kode_brng,h_beli,biaya_obat,jml,embalase,tuslah,total,status,kd_bangsal,no_batch,no_faktur)
                 VALUES
                (:tgl,:jam,:no_rawat,:kode_brng,:h_beli,:biaya_obat,:jml,0,0,:total,:status,:kd_bangsal,:no_batch,:no_faktur)"
            );
            $nowDate = date('Y-m-d');
            $nowTime = date('H:i:s');
            $statusLayanan = strtolower((string)($head['status'] ?? 'ralan')) === 'ranap' ? 'Ranap' : 'Ralan';
            $noRawat = (string)($head['no_rawat'] ?? '');

            foreach ($items as $item) {
                $kodeBrng = trim((string)($item['kode_brng'] ?? ''));
                $jumlah = (float)($item['jml'] ?? 0);
                if ($kodeBrng === '' || $jumlah <= 0) {
                    continue;
                }

                $selectGudang->execute(['kode' => $kodeBrng]);
                $gudangRows = $selectGudang->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                $chosenGudang = null;
                foreach ($gudangRows as $gudangRow) {
                    if ((float)($gudangRow['stok'] ?? 0) >= $jumlah) {
                        $chosenGudang = $gudangRow;
                        break;
                    }
                }
                if (!is_array($chosenGudang)) {
                    throw new \RuntimeException('Stok tidak cukup untuk obat ' . $kodeBrng);
                }

                $hargaJual = (float)($item['ralan'] ?? 0);
                $insertDetail->execute([
                    'tgl' => $nowDate,
                    'jam' => $nowTime,
                    'no_rawat' => $noRawat,
                    'kode_brng' => $kodeBrng,
                    'h_beli' => (float)($item['h_beli'] ?? 0),
                    'biaya_obat' => $hargaJual,
                    'jml' => $jumlah,
                    'total' => $hargaJual * $jumlah,
                    'status' => $statusLayanan,
                    'kd_bangsal' => (string)($chosenGudang['kd_bangsal'] ?? ''),
                    'no_batch' => (string)($chosenGudang['no_batch'] ?? ''),
                    'no_faktur' => (string)($chosenGudang['no_faktur'] ?? ''),
                ]);
            }

            $stmt = $pdo->prepare(
                "UPDATE resep_obat
                 SET
                    tgl_perawatan = CURDATE(),
                    jam = CURTIME(),
                    tgl_penyerahan = IF(tgl_penyerahan='0000-00-00' OR tgl_penyerahan IS NULL, CURDATE(), tgl_penyerahan),
                    jam_penyerahan = IF(jam_penyerahan='00:00:00' OR jam_penyerahan IS NULL, CURTIME(), jam_penyerahan)
                 WHERE no_resep = :no_resep
                   AND (tgl_perawatan='0000-00-00' OR tgl_perawatan IS NULL)"
            );
            $stmt->execute(['no_resep' => $noResep]);
            if ($stmt->rowCount() <= 0) {
                throw new \RuntimeException('Resep sudah tervalidasi');
            }

            $pdo->commit();
            $this->goDetail($noResep, 'ok', 'Resep berhasil divalidasi dan masuk ke detail pemberian obat');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noResep, 'error', 'Gagal validasi resep: ' . $e->getMessage());
        }
    }

    private function updateResepItem(): void
    {
        $noResep = trim((string)($_POST['no_resep'] ?? ''));
        $kodeBrng = trim((string)($_POST['kode_brng'] ?? ''));
        $jumlah = (float)($_POST['jml'] ?? 0);
        $aturanPakai = trim((string)($_POST['aturan_pakai'] ?? '-'));

        if ($noResep === '' || $kodeBrng === '') {
            $this->goList('error', 'Item resep belum dipilih');
        }
        if ($jumlah <= 0) {
            $this->goDetail($noResep, 'error', 'Jumlah obat harus lebih dari 0');
        }
        if ($this->isResepSudahTervalidasi($noResep)) {
            $this->goDetail($noResep, 'error', 'Resep sudah tervalidasi, item tidak bisa diubah');
        }

        try {
            $stmt = Database::pdo()->prepare(
                "UPDATE resep_dokter
                 SET jml = :jml, aturan_pakai = :aturan
                 WHERE no_resep = :no_resep AND kode_brng = :kode_brng"
            );
            $stmt->execute([
                'jml' => $jumlah,
                'aturan' => $aturanPakai === '' ? '-' : $aturanPakai,
                'no_resep' => $noResep,
                'kode_brng' => $kodeBrng,
            ]);
            if ($stmt->rowCount() > 0) {
                $this->goDetail($noResep, 'ok', 'Item resep berhasil diperbarui');
            }
            $this->goDetail($noResep, 'error', 'Tidak ada perubahan item resep');
        } catch (Throwable $e) {
            $this->goDetail($noResep, 'error', 'Gagal update item resep: ' . $e->getMessage());
        }
    }

    private function deleteResepItem(): void
    {
        $noResep = trim((string)($_POST['no_resep'] ?? ''));
        $kodeBrng = trim((string)($_POST['kode_brng'] ?? ''));
        if ($noResep === '' || $kodeBrng === '') {
            $this->goList('error', 'Item resep belum dipilih');
        }
        if ($this->isResepSudahTervalidasi($noResep)) {
            $this->goDetail($noResep, 'error', 'Resep sudah tervalidasi, item tidak bisa dihapus');
        }

        try {
            $stmt = Database::pdo()->prepare(
                "DELETE FROM resep_dokter
                 WHERE no_resep = :no_resep AND kode_brng = :kode_brng"
            );
            $stmt->execute([
                'no_resep' => $noResep,
                'kode_brng' => $kodeBrng,
            ]);
            if ($stmt->rowCount() > 0) {
                $this->goDetail($noResep, 'ok', 'Item resep berhasil dihapus');
            }
            $this->goDetail($noResep, 'error', 'Item resep tidak ditemukan');
        } catch (Throwable $e) {
            $this->goDetail($noResep, 'error', 'Gagal hapus item resep: ' . $e->getMessage());
        }
    }

    private function isResepSudahTervalidasi(string $noResep): bool
    {
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*)
             FROM resep_obat
             WHERE no_resep = :no_resep
               AND (tgl_perawatan<>'0000-00-00' AND tgl_perawatan IS NOT NULL)"
        );
        $stmt->execute(['no_resep' => $noResep]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function baseQueryString(): string
    {
        $from = trim((string)($_REQUEST['from'] ?? date('Y-m-d')));
        $to = trim((string)($_REQUEST['to'] ?? date('Y-m-d')));
        $q = trim((string)($_REQUEST['q'] ?? ''));
        $status = trim((string)($_REQUEST['status'] ?? 'belum'));
        $sttsRawat = trim((string)($_REQUEST['stts_rawat'] ?? 'semua'));

        return '?page=farmasi'
            . '&from=' . urlencode($from)
            . '&to=' . urlencode($to)
            . '&q=' . urlencode($q)
            . '&status=' . urlencode($status)
            . '&stts_rawat=' . urlencode($sttsRawat);
    }

    private function goList(string $msg, string $detail): void
    {
        $url = $this->baseQueryString();
        $url .= '&msg=' . urlencode($msg) . '&msgd=' . urlencode($detail);
        header('Location: ' . $url);
        exit;
    }

    private function goDetail(string $noResep, string $msg, string $detail): void
    {
        $url = $this->baseQueryString();
        $url .= '&detail=' . urlencode($noResep) . '&open=1';
        $url .= '&msg=' . urlencode($msg) . '&msgd=' . urlencode($detail);
        header('Location: ' . $url);
        exit;
    }
}
