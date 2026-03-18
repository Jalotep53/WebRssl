<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use Throwable;
use WebBaru\Database;
use WebBaru\Services\SimrsQueryService;

final class BillingRalanController
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
        $statusBayar = trim((string)($_GET['status_bayar'] ?? 'semua'));
        $mode = trim((string)($_GET['mode'] ?? 'ralan'));
        if (!in_array($mode, ['ralan', 'ranap'], true)) {
            $mode = 'ralan';
        }
        if (!in_array($statusBayar, ['semua', 'sudah', 'belum'], true)) {
            $statusBayar = 'semua';
        }
        $detailNoRawat = trim((string)($_GET['detail'] ?? ''));
        $openModal = trim((string)($_GET['open'] ?? '')) === '1';
        $isPrint = trim((string)($_GET['print'] ?? '')) === '1';
        $msg = trim((string)($_GET['msg'] ?? ''));
        $msgDetail = trim((string)($_GET['msgd'] ?? ''));

        $where = ['rp.tgl_registrasi BETWEEN :from AND :to'];
        $where[] = $mode === 'ranap' ? "rp.status_lanjut='Ranap'" : "rp.status_lanjut='Ralan'";
        $params = ['from' => $from, 'to' => $to];
        if ($q !== '') {
            $where[] = "(rp.no_rawat LIKE :q1 OR rp.no_rkm_medis LIKE :q2 OR p.nm_pasien LIKE :q3 OR IFNULL(nj.no_nota,'') LIKE :q4)";
            $params['q1'] = '%' . $q . '%';
            $params['q2'] = '%' . $q . '%';
            $params['q3'] = '%' . $q . '%';
            $params['q4'] = '%' . $q . '%';
        }
        if ($statusBayar === 'sudah') {
            $where[] = "rp.status_bayar='Sudah Bayar'";
        } elseif ($statusBayar === 'belum') {
            $where[] = "rp.status_bayar='Belum Bayar'";
        }

        $rows = $db->run(
            "SELECT rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.no_rkm_medis, rp.status_bayar,
                    p.nm_pasien, pl.nm_poli, d.nm_dokter, pj.png_jawab,
                    nj.no_nota, nj.tanggal AS tgl_nota, nj.jam AS jam_nota
             FROM reg_periksa rp
             INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
             INNER JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
             INNER JOIN dokter d ON d.kd_dokter = rp.kd_dokter
             INNER JOIN penjab pj ON pj.kd_pj = rp.kd_pj
             LEFT JOIN nota_jalan nj ON nj.no_rawat = rp.no_rawat
             WHERE " . implode(' AND ', $where) . "
             ORDER BY rp.tgl_registrasi DESC, rp.jam_reg DESC
             LIMIT 300",
            $params
        );

        $detail = null;
        $detailError = null;
        $komponen = [];
        $obatDetail = [];
        $billingRows = [];
        $settingRs = [];
        $akunBayarList = $db->run("SELECT nama_bayar, kd_rek, ppn FROM akun_bayar ORDER BY nama_bayar")['data'];
        $akunPiutangList = $db->run("SELECT nama_bayar, kd_rek FROM akun_piutang ORDER BY nama_bayar")['data'];
        $detailPembayaran = [];
        $detailPiutang = [];
        $templateMode = $mode === 'ranap' ? 'ranap' : 'rajal';
        $tindakanRows = [];
        $labRows = [];
        $radRows = [];
        $resepRows = [];
        $dokterRows = [];
        $kamarRows = [];
        $gabungInfo = null;
        $bayiNoRawat = '';
        $bayiBillingRows = [];
        $bayiTotal = 0.0;
        $labDetailItems = [];
        $radDetailItems = [];
        $resepDetailItems = [];
        $tambahanRows = [];
        $penguranganRows = [];
        if ($detailNoRawat !== '') {
            $settingRs = $db->run(
                "SELECT *
                 FROM setting
                 LIMIT 1"
            )['data'][0] ?? [];
            $head = $db->run(
                "SELECT rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.no_rkm_medis, rp.status_bayar, rp.biaya_reg, rp.status_lanjut, rp.kd_pj,
                        p.nm_pasien, p.alamat, p.no_peserta, pl.nm_poli, d.nm_dokter, pj.png_jawab,
                        nj.no_nota, nj.tanggal AS tgl_nota, nj.jam AS jam_nota
                 FROM reg_periksa rp
                 INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
                 INNER JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
                 INNER JOIN dokter d ON d.kd_dokter = rp.kd_dokter
                 INNER JOIN penjab pj ON pj.kd_pj = rp.kd_pj
                 LEFT JOIN nota_jalan nj ON nj.no_rawat = rp.no_rawat
                 WHERE rp.no_rawat=:n
                 LIMIT 1",
                ['n' => $detailNoRawat]
            );
            if ($head['ok'] && !empty($head['data'])) {
                $detail = $head['data'][0];
                $isRanap = strtolower((string)($detail['status_lanjut'] ?? '')) === 'ranap';
                $templateMode = $isRanap ? 'ranap' : 'rajal';

                if ($isRanap) {
                    $notaInap = $db->run(
                        "SELECT no_nota, tanggal AS tgl_nota, jam AS jam_nota
                         FROM nota_inap
                         WHERE no_rawat=:n
                         LIMIT 1",
                        ['n' => $detailNoRawat]
                    )['data'][0] ?? [];
                    if (!empty($notaInap)) {
                        $detail['no_nota'] = $notaInap['no_nota'] ?? ($detail['no_nota'] ?? '');
                        $detail['tgl_nota'] = $notaInap['tgl_nota'] ?? ($detail['tgl_nota'] ?? '');
                        $detail['jam_nota'] = $notaInap['jam_nota'] ?? ($detail['jam_nota'] ?? '');
                    }

                    $pair = $db->run(
                        "SELECT rg.no_rawat, rg.no_rawat2,
                                ibu.no_rkm_medis AS rm_ibu, pibu.nm_pasien AS nm_ibu,
                                bayi.no_rkm_medis AS rm_bayi, pbayi.nm_pasien AS nm_bayi
                         FROM ranap_gabung rg
                         LEFT JOIN reg_periksa ibu ON ibu.no_rawat = rg.no_rawat
                         LEFT JOIN pasien pibu ON pibu.no_rkm_medis = ibu.no_rkm_medis
                         LEFT JOIN reg_periksa bayi ON bayi.no_rawat = rg.no_rawat2
                         LEFT JOIN pasien pbayi ON pbayi.no_rkm_medis = bayi.no_rkm_medis
                         WHERE rg.no_rawat=:n OR rg.no_rawat2=:n
                         LIMIT 1",
                        ['n' => $detailNoRawat]
                    )['data'][0] ?? null;
                    if (is_array($pair) && !empty($pair)) {
                        $templateMode = 'ranap_gabung';
                        $gabungInfo = $pair;
                        $bayiNoRawat = (string)($pair['no_rawat2'] ?? '');
                        if ($bayiNoRawat !== '') {
                            $bayiBillingRows = $db->run(
                                "SELECT tgl_byr, no, nm_perawatan, status, biaya, jumlah, tambahan, totalbiaya
                                 FROM billing
                                 WHERE no_rawat=:n
                                 ORDER BY noindex",
                                ['n' => $bayiNoRawat]
                            )['data'];
                            foreach ($bayiBillingRows as $bbr) {
                                $bayiTotal += (float)($bbr['totalbiaya'] ?? 0);
                            }
                        }
                    }
                }

                $komponen = [
                    'registrasi' => (float)($detail['biaya_reg'] ?? 0),
                    'tindakan_dokter' => 0.0,
                    'tindakan_perawat' => 0.0,
                    'tindakan_drpr' => 0.0,
                    'ruang' => 0.0,
                    'lab' => (float)($db->value("SELECT IFNULL(SUM(biaya),0) FROM periksa_lab WHERE no_rawat=:n", ['n' => $detailNoRawat])['data'] ?? 0),
                    'radiologi' => (float)($db->value("SELECT IFNULL(SUM(biaya),0) FROM periksa_radiologi WHERE no_rawat=:n", ['n' => $detailNoRawat])['data'] ?? 0),
                    'tambahan' => (float)($db->value("SELECT IFNULL(SUM(besar_biaya),0) FROM tambahan_biaya WHERE no_rawat=:n", ['n' => $detailNoRawat])['data'] ?? 0),
                    'pengurangan' => (float)($db->value("SELECT IFNULL(SUM(besar_pengurangan),0) FROM pengurangan_biaya WHERE no_rawat=:n", ['n' => $detailNoRawat])['data'] ?? 0),
                    'obat' => 0.0,
                    'bmhp' => 0.0,
                    'gas_medis' => 0.0,
                    'grand_total' => 0.0,
                ];

                if ($isRanap) {
                    $komponen['tindakan_dokter'] = (float)($db->value("SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_inap_dr WHERE no_rawat=:n", ['n' => $detailNoRawat])['data'] ?? 0);
                    $komponen['tindakan_perawat'] = (float)($db->value("SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_inap_pr WHERE no_rawat=:n", ['n' => $detailNoRawat])['data'] ?? 0);
                    $komponen['tindakan_drpr'] = (float)($db->value("SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_inap_drpr WHERE no_rawat=:n", ['n' => $detailNoRawat])['data'] ?? 0);
                    $komponen['ruang'] = (float)($db->value("SELECT IFNULL(SUM(ttl_biaya),0) FROM kamar_inap WHERE no_rawat=:n", ['n' => $detailNoRawat])['data'] ?? 0);

                    $dokterRows = $db->run(
                        "SELECT DISTINCT d.nm_dokter
                         FROM rawat_inap_dr rid
                         INNER JOIN dokter d ON d.kd_dokter=rid.kd_dokter
                         WHERE rid.no_rawat=:n
                         ORDER BY d.nm_dokter",
                        ['n' => $detailNoRawat]
                    )['data'];
                    $kamarRows = $db->run(
                        "SELECT ki.kd_kamar, b.nm_bangsal, k.kelas, ki.trf_kamar, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar, ki.lama, ki.ttl_biaya
                         FROM kamar_inap ki
                         LEFT JOIN kamar k ON k.kd_kamar=ki.kd_kamar
                         LEFT JOIN bangsal b ON b.kd_bangsal=k.kd_bangsal
                         WHERE ki.no_rawat=:n
                         ORDER BY ki.tgl_masuk, ki.jam_masuk",
                        ['n' => $detailNoRawat]
                    )['data'];
                    $tindakanRows = $db->run(
                        "SELECT j.nm_perawatan, r.tgl_perawatan, r.jam_rawat, r.biaya_rawat, 'Dokter' AS jenis
                         FROM rawat_inap_dr r
                         INNER JOIN jns_perawatan_inap j ON j.kd_jenis_prw=r.kd_jenis_prw
                         WHERE r.no_rawat=:n
                         UNION ALL
                         SELECT j.nm_perawatan, r.tgl_perawatan, r.jam_rawat, r.biaya_rawat, 'Perawat' AS jenis
                         FROM rawat_inap_pr r
                         INNER JOIN jns_perawatan_inap j ON j.kd_jenis_prw=r.kd_jenis_prw
                         WHERE r.no_rawat=:n
                         UNION ALL
                         SELECT j.nm_perawatan, r.tgl_perawatan, r.jam_rawat, r.biaya_rawat, 'Dokter+Perawat' AS jenis
                         FROM rawat_inap_drpr r
                         INNER JOIN jns_perawatan_inap j ON j.kd_jenis_prw=r.kd_jenis_prw
                         WHERE r.no_rawat=:n
                         ORDER BY tgl_perawatan, jam_rawat",
                        ['n' => $detailNoRawat]
                    )['data'];
                    $detailPembayaran = $db->run(
                        "SELECT dni.nama_bayar, dni.besarppn, dni.besar_bayar, ab.kd_rek, ab.ppn
                         FROM detail_nota_inap dni
                         LEFT JOIN akun_bayar ab ON ab.nama_bayar = dni.nama_bayar
                         WHERE dni.no_rawat=:n
                         ORDER BY dni.nama_bayar",
                        ['n' => $detailNoRawat]
                    )['data'];
                } else {
                    $komponen['tindakan_dokter'] = (float)($db->value("SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_dr WHERE no_rawat=:n", ['n' => $detailNoRawat])['data'] ?? 0);
                    $komponen['tindakan_perawat'] = (float)($db->value("SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_pr WHERE no_rawat=:n", ['n' => $detailNoRawat])['data'] ?? 0);
                    $komponen['tindakan_drpr'] = (float)($db->value("SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_drpr WHERE no_rawat=:n", ['n' => $detailNoRawat])['data'] ?? 0);

                    $tindakanRows = $db->run(
                        "SELECT j.nm_perawatan, r.tgl_perawatan, r.jam_rawat, r.biaya_rawat, 'Dokter' AS jenis
                         FROM rawat_jl_dr r
                         INNER JOIN jns_perawatan j ON j.kd_jenis_prw=r.kd_jenis_prw
                         WHERE r.no_rawat=:n
                         UNION ALL
                         SELECT j.nm_perawatan, r.tgl_perawatan, r.jam_rawat, r.biaya_rawat, 'Perawat' AS jenis
                         FROM rawat_jl_pr r
                         INNER JOIN jns_perawatan j ON j.kd_jenis_prw=r.kd_jenis_prw
                         WHERE r.no_rawat=:n
                         UNION ALL
                         SELECT j.nm_perawatan, r.tgl_perawatan, r.jam_rawat, r.biaya_rawat, 'Dokter+Perawat' AS jenis
                         FROM rawat_jl_drpr r
                         INNER JOIN jns_perawatan j ON j.kd_jenis_prw=r.kd_jenis_prw
                         WHERE r.no_rawat=:n
                         ORDER BY tgl_perawatan, jam_rawat",
                        ['n' => $detailNoRawat]
                    )['data'];
                    $detailPembayaran = $db->run(
                        "SELECT dnj.nama_bayar, dnj.besarppn, dnj.besar_bayar, ab.kd_rek, ab.ppn
                         FROM detail_nota_jalan dnj
                         LEFT JOIN akun_bayar ab ON ab.nama_bayar = dnj.nama_bayar
                         WHERE dnj.no_rawat=:n
                         ORDER BY dnj.nama_bayar",
                        ['n' => $detailNoRawat]
                    )['data'];
                }
                $detailPiutang = $db->run(
                    "SELECT dpp.nama_bayar, dpp.kd_pj, dpp.totalpiutang, dpp.sisapiutang, dpp.tgltempo, ap.kd_rek
                     FROM detail_piutang_pasien dpp
                     LEFT JOIN akun_piutang ap ON ap.nama_bayar = dpp.nama_bayar
                     WHERE dpp.no_rawat=:n
                     ORDER BY dpp.nama_bayar",
                    ['n' => $detailNoRawat]
                )['data'];

                $labRows = $db->run(
                    "SELECT pl.noorder, pl.tgl_permintaan, pl.jam_permintaan,
                            GROUP_CONCAT(jl.nm_perawatan SEPARATOR '; ') AS item_lab
                     FROM permintaan_lab pl
                     LEFT JOIN permintaan_pemeriksaan_lab ppl ON ppl.noorder = pl.noorder
                     LEFT JOIN jns_perawatan_lab jl ON jl.kd_jenis_prw = ppl.kd_jenis_prw
                     WHERE pl.no_rawat=:n
                     GROUP BY pl.noorder, pl.tgl_permintaan, pl.jam_permintaan
                     ORDER BY pl.tgl_permintaan, pl.jam_permintaan",
                    ['n' => $detailNoRawat]
                )['data'];
                $labDetailItems = $db->run(
                    "SELECT ppl.noorder, ppl.kd_jenis_prw, jl.nm_perawatan, IFNULL(jl.total_byr,0) AS harga
                     FROM permintaan_pemeriksaan_lab ppl
                     INNER JOIN permintaan_lab pl ON pl.noorder = ppl.noorder
                     INNER JOIN jns_perawatan_lab jl ON jl.kd_jenis_prw = ppl.kd_jenis_prw
                     WHERE pl.no_rawat=:n
                     ORDER BY pl.tgl_permintaan, pl.jam_permintaan, jl.nm_perawatan",
                    ['n' => $detailNoRawat]
                )['data'];
                $radRows = $db->run(
                    "SELECT pr.noorder, pr.tgl_permintaan, pr.jam_permintaan,
                            GROUP_CONCAT(jr.nm_perawatan SEPARATOR '; ') AS item_radiologi
                     FROM permintaan_radiologi pr
                     LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON ppr.noorder = pr.noorder
                     LEFT JOIN jns_perawatan_radiologi jr ON jr.kd_jenis_prw = ppr.kd_jenis_prw
                     WHERE pr.no_rawat=:n
                     GROUP BY pr.noorder, pr.tgl_permintaan, pr.jam_permintaan
                     ORDER BY pr.tgl_permintaan, pr.jam_permintaan",
                    ['n' => $detailNoRawat]
                )['data'];
                $radDetailItems = $db->run(
                    "SELECT ppr.noorder, ppr.kd_jenis_prw, jr.nm_perawatan, IFNULL(jr.total_byr,0) AS harga
                     FROM permintaan_pemeriksaan_radiologi ppr
                     INNER JOIN permintaan_radiologi pr ON pr.noorder = ppr.noorder
                     INNER JOIN jns_perawatan_radiologi jr ON jr.kd_jenis_prw = ppr.kd_jenis_prw
                     WHERE pr.no_rawat=:n
                     ORDER BY pr.tgl_permintaan, pr.jam_permintaan, jr.nm_perawatan",
                    ['n' => $detailNoRawat]
                )['data'];
                $resepRows = $db->run(
                    "SELECT ro.no_resep, ro.tgl_peresepan, ro.jam_peresepan,
                            GROUP_CONCAT(CONCAT(db.nama_brng,' x',rd.jml,' (',rd.aturan_pakai,')') SEPARATOR '; ') AS item_resep
                     FROM resep_obat ro
                     INNER JOIN resep_dokter rd ON rd.no_resep = ro.no_resep
                     INNER JOIN databarang db ON db.kode_brng = rd.kode_brng
                     WHERE ro.no_rawat=:n
                     GROUP BY ro.no_resep, ro.tgl_peresepan, ro.jam_peresepan
                     ORDER BY ro.tgl_peresepan, ro.jam_peresepan",
                    ['n' => $detailNoRawat]
                )['data'];
                $resepDetailItems = $db->run(
                    "SELECT rd.no_resep, rd.kode_brng, db.nama_brng, rd.jml, rd.aturan_pakai,
                            IFNULL(db.ralan,0) AS harga_satuan,
                            (IFNULL(db.ralan,0) * IFNULL(rd.jml,0)) AS total_harga
                     FROM resep_dokter rd
                     INNER JOIN resep_obat ro ON ro.no_resep = rd.no_resep
                     INNER JOIN databarang db ON db.kode_brng = rd.kode_brng
                     WHERE ro.no_rawat=:n
                    ORDER BY ro.tgl_peresepan, ro.jam_peresepan, db.nama_brng",
                    ['n' => $detailNoRawat]
                )['data'];
                $tambahanRows = $db->run(
                    "SELECT nama_biaya, besar_biaya
                     FROM tambahan_biaya
                     WHERE no_rawat=:n
                     ORDER BY nama_biaya",
                    ['n' => $detailNoRawat]
                )['data'];
                $penguranganRows = $db->run(
                    "SELECT nama_pengurangan, besar_pengurangan
                     FROM pengurangan_biaya
                     WHERE no_rawat=:n
                     ORDER BY nama_pengurangan",
                    ['n' => $detailNoRawat]
                )['data'];

                $obatDetail = $db->run(
                    "SELECT dpo.kode_brng, db.nama_brng,
                            SUM(dpo.jml) AS jml,
                            SUM(dpo.embalase + dpo.tuslah) AS tambahan,
                            (SUM(dpo.total) - SUM(dpo.embalase + dpo.tuslah)) AS subtotal,
                            IFNULL(obo.kode_kat, 1) AS kode_kat
                     FROM detail_pemberian_obat dpo
                     INNER JOIN databarang db ON db.kode_brng = dpo.kode_brng
                     LEFT JOIN obat_bmhp_oksigen obo ON obo.kode_brng = dpo.kode_brng
                     WHERE dpo.no_rawat = :n
                     GROUP BY dpo.kode_brng, IFNULL(obo.kode_kat, 1)
                     ORDER BY IFNULL(obo.kode_kat, 1), db.nama_brng",
                    ['n' => $detailNoRawat]
                )['data'];
                foreach ($obatDetail as &$od) {
                    $totalItem = (float)$od['subtotal'] + (float)$od['tambahan'];
                    $od['total_item'] = $totalItem;
                    $od['kategori'] = $this->mapKategori((int)$od['kode_kat']);
                    if ((int)$od['kode_kat'] === 2) {
                        $komponen['bmhp'] += $totalItem;
                    } elseif ((int)$od['kode_kat'] === 3) {
                        $komponen['gas_medis'] += $totalItem;
                    } else {
                        $komponen['obat'] += $totalItem;
                    }
                }
                unset($od);

                $komponen['grand_total'] =
                    $komponen['registrasi'] +
                    $komponen['ruang'] +
                    $komponen['tindakan_dokter'] +
                    $komponen['tindakan_perawat'] +
                    $komponen['tindakan_drpr'] +
                    $komponen['lab'] +
                    $komponen['radiologi'] +
                    $komponen['obat'] +
                    $komponen['bmhp'] +
                    $komponen['gas_medis'] +
                    $komponen['tambahan'] -
                    $komponen['pengurangan'];

                $billingRows = $db->run(
                    "SELECT tgl_byr, no, nm_perawatan, status, biaya, jumlah, tambahan, totalbiaya
                     FROM billing
                     WHERE no_rawat=:n
                     ORDER BY noindex",
                    ['n' => $detailNoRawat]
                )['data'];
            } else {
                $detailError = $head['error'] ?? 'Detail billing tidak ditemukan';
            }
        }

        if ($isPrint && $detailNoRawat !== '') {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            $data = [
                'title' => $templateMode === 'rajal' ? 'Cetak Rincian Billing Rawat Jalan' : 'Cetak Rincian Billing Rawat Inap',
                'detailNoRawat' => $detailNoRawat,
                'detail' => $detail,
                'detailError' => $detailError,
                'komponen' => $komponen,
                'obatDetail' => $obatDetail,
                'billingRows' => $billingRows,
                'detailPembayaran' => $detailPembayaran,
                'settingRs' => $settingRs,
                'templateMode' => $templateMode,
                'tindakanRows' => $tindakanRows,
                'labRows' => $labRows,
                'radRows' => $radRows,
                'resepRows' => $resepRows,
                'dokterRows' => $dokterRows,
                'kamarRows' => $kamarRows,
                'gabungInfo' => $gabungInfo,
                'bayiNoRawat' => $bayiNoRawat,
                'bayiBillingRows' => $bayiBillingRows,
                'bayiTotal' => $bayiTotal,
                'labDetailItems' => $labDetailItems,
                'radDetailItems' => $radDetailItems,
                'resepDetailItems' => $resepDetailItems,
                'tambahanRows' => $tambahanRows,
                'penguranganRows' => $penguranganRows,
            ];
            extract($data, EXTR_SKIP);
            require __DIR__ . '/../Views/billing_ralan_print.php';
            return;
        }

        view('billing_ralan', [
            'title' => $mode === 'ranap' ? 'Billing Rawat Inap' : 'Billing Rawat Jalan',
            'rows' => $rows['data'],
            'error' => $rows['ok'] ? null : $rows['error'],
            'from' => $from,
            'to' => $to,
            'q' => $q,
            'statusBayar' => $statusBayar,
            'mode' => $mode,
            'detailNoRawat' => $detailNoRawat,
            'openModal' => $openModal,
            'detail' => $detail,
            'detailError' => $detailError,
            'komponen' => $komponen,
            'obatDetail' => $obatDetail,
            'billingRows' => $billingRows,
            'templateMode' => $templateMode,
            'tindakanRows' => $tindakanRows,
            'labRows' => $labRows,
            'radRows' => $radRows,
            'resepRows' => $resepRows,
            'dokterRows' => $dokterRows,
            'kamarRows' => $kamarRows,
            'gabungInfo' => $gabungInfo,
            'bayiNoRawat' => $bayiNoRawat,
            'bayiBillingRows' => $bayiBillingRows,
            'bayiTotal' => $bayiTotal,
            'labDetailItems' => $labDetailItems,
            'radDetailItems' => $radDetailItems,
            'resepDetailItems' => $resepDetailItems,
            'tambahanRows' => $tambahanRows,
            'penguranganRows' => $penguranganRows,
            'akunBayarList' => $akunBayarList,
            'akunPiutangList' => $akunPiutangList,
            'detailPembayaran' => $detailPembayaran,
            'detailPiutang' => $detailPiutang,
            'msg' => $msg,
            'msgDetail' => $msgDetail,
        ]);
    }

    private function handlePost(): void
    {
        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === 'set_sudah_bayar') {
            $this->setSudahBayar();
            return;
        }
        if ($action === 'set_belum_bayar') {
            $this->setBelumBayar();
            return;
        }
        $this->goList('error', 'Aksi tidak dikenali');
    }

    private function setSudahBayar(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        $namaBayarInput = $_POST['nama_bayar'] ?? [];
        $besarBayarInput = $_POST['besar_bayar'] ?? [];
        $namaPiutangInput = $_POST['nama_piutang'] ?? [];
        $totalPiutangInput = $_POST['total_piutang'] ?? [];
        $kdPjPiutangInput = $_POST['kd_pj_piutang'] ?? [];
        $tglTempoInput = $_POST['tgl_tempo'] ?? [];
        if ($noRawat === '') {
            $this->goList('error', 'No rawat belum dipilih');
        }

        if (!is_array($namaBayarInput)) {
            $single = trim((string)($_POST['nama_bayar'] ?? ''));
            $namaBayarInput = $single === '' ? [] : [$single];
        }
        if (!is_array($besarBayarInput)) {
            $single = (float)($_POST['besar_bayar'] ?? 0);
            $besarBayarInput = $single > 0 ? [$single] : [];
        }

        $itemsBayar = [];
        $max = max(count($namaBayarInput), count($besarBayarInput));
        for ($i = 0; $i < $max; $i++) {
            $nama = trim((string)($namaBayarInput[$i] ?? ''));
            $besar = (float)($besarBayarInput[$i] ?? 0);
            if ($nama === '' && $besar <= 0) {
                continue;
            }
            if ($nama === '' || $besar <= 0) {
                $this->goDetail($noRawat, 'error', 'Akun bayar dan nominal wajib diisi');
            }
            $itemsBayar[] = ['nama_bayar' => $nama, 'besar_bayar' => $besar];
        }
        if (empty($itemsBayar)) {
            $this->goDetail($noRawat, 'error', 'Minimal 1 akun bayar harus diisi');
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();

            $regStmt = $pdo->prepare("SELECT no_rkm_medis, IFNULL(kd_pj,'') AS kd_pj, IFNULL(status_lanjut,'Ralan') AS status_lanjut FROM reg_periksa WHERE no_rawat=:n LIMIT 1");
            $regStmt->execute(['n' => $noRawat]);
            $regInfo = $regStmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            if (!is_array($regInfo)) {
                throw new \RuntimeException('Data registrasi tidak ditemukan');
            }
            $isRanap = strtolower((string)($regInfo['status_lanjut'] ?? 'ralan')) === 'ranap';
            $noRkmMedis = (string)($regInfo['no_rkm_medis'] ?? '');
            $kdPjReg = (string)($regInfo['kd_pj'] ?? '');

            $grandTotal = $this->calculateGrandTotal($pdo, $noRawat);
            foreach ($itemsBayar as &$it) {
                if (stripos((string)$it['nama_bayar'], 'piutang') !== false && (float)$it['besar_bayar'] <= 0) {
                    $it['besar_bayar'] = $grandTotal;
                }
            }
            unset($it);
            $totalBayar = 0.0;
            foreach ($itemsBayar as $it) {
                $totalBayar += (float)$it['besar_bayar'];
            }
            $sisaPiutang = max(0.0, $grandTotal - $totalBayar);

            $today = date('Y-m-d');
            $jam = date('H:i:s');
            $noNota = $this->nextNoNota($pdo, $today, $isRanap);

            if ($isRanap) {
                $pdo->prepare("DELETE FROM nota_inap WHERE no_rawat=:n")->execute(['n' => $noRawat]);
                $pdo->prepare("DELETE FROM detail_nota_inap WHERE no_rawat=:n")->execute(['n' => $noRawat]);
                $insNota = $pdo->prepare("INSERT INTO nota_inap(no_rawat,no_nota,tanggal,jam,Uang_Muka) VALUES(:n,:nota,:tgl,:jam,:um)");
                $insNota->execute([
                    'n' => $noRawat,
                    'nota' => $noNota,
                    'tgl' => $today,
                    'jam' => $jam,
                    'um' => 0,
                ]);
            } else {
                $pdo->prepare("DELETE FROM nota_jalan WHERE no_rawat=:n")->execute(['n' => $noRawat]);
                $pdo->prepare("DELETE FROM detail_nota_jalan WHERE no_rawat=:n")->execute(['n' => $noRawat]);
                $insNota = $pdo->prepare("INSERT INTO nota_jalan(no_rawat,no_nota,tanggal,jam) VALUES(:n,:nota,:tgl,:jam)");
                $insNota->execute([
                    'n' => $noRawat,
                    'nota' => $noNota,
                    'tgl' => $today,
                    'jam' => $jam,
                ]);
            }

            $pdo->prepare("DELETE FROM billing WHERE no_rawat=:n")->execute(['n' => $noRawat]);
            $billingRows = $this->buildBillingRows($pdo, $noRawat, $noNota, $today, $isRanap);
            $insBilling = $pdo->prepare(
                "INSERT INTO billing(noindex,no_rawat,tgl_byr,no,nm_perawatan,pemisah,biaya,jumlah,tambahan,totalbiaya,status)
                 VALUES(:noindex,:n,:tgl,:no,:nm,:pemisah,:biaya,:jumlah,:tambahan,:total,:status)"
            );
            foreach ($billingRows as $idx => $row) {
                $insBilling->execute([
                    'noindex' => $idx,
                    'n' => $noRawat,
                    'tgl' => $today,
                    'no' => (string)$row['no'],
                    'nm' => (string)$row['nm_perawatan'],
                    'pemisah' => (string)$row['pemisah'],
                    'biaya' => (float)$row['biaya'],
                    'jumlah' => (float)$row['jumlah'],
                    'tambahan' => (float)$row['tambahan'],
                    'total' => (float)$row['totalbiaya'],
                    'status' => (string)$row['status'],
                ]);
            }

            $selAkun = $pdo->prepare("SELECT ppn FROM akun_bayar WHERE nama_bayar=:nama LIMIT 1");
            $insDet = $pdo->prepare($isRanap
                ? "INSERT INTO detail_nota_inap(no_rawat,nama_bayar,besarppn,besar_bayar) VALUES(:n,:nama,:ppn,:besar)"
                : "INSERT INTO detail_nota_jalan(no_rawat,nama_bayar,besarppn,besar_bayar) VALUES(:n,:nama,:ppn,:besar)"
            );
            foreach ($itemsBayar as $it) {
                $selAkun->execute(['nama' => $it['nama_bayar']]);
                $ppnPersen = (float)($selAkun->fetchColumn() ?: 0);
                $besarPpn = ((float)$it['besar_bayar'] * $ppnPersen) / 100;
                $insDet->execute([
                    'n' => $noRawat,
                    'nama' => $it['nama_bayar'],
                    'ppn' => $besarPpn,
                    'besar' => $it['besar_bayar'],
                ]);
            }

            $pdo->prepare("DELETE FROM detail_piutang_pasien WHERE no_rawat=:n")->execute(['n' => $noRawat]);
            $pdo->prepare("DELETE FROM piutang_pasien WHERE no_rawat=:n")->execute(['n' => $noRawat]);

            if ($sisaPiutang > 0) {
                if (!is_array($namaPiutangInput)) {
                    $namaPiutangInput = [trim((string)$namaPiutangInput)];
                }
                if (!is_array($totalPiutangInput)) {
                    $totalPiutangInput = [(float)$totalPiutangInput];
                }
                if (!is_array($kdPjPiutangInput)) {
                    $kdPjPiutangInput = [trim((string)$kdPjPiutangInput)];
                }
                if (!is_array($tglTempoInput)) {
                    $tglTempoInput = [trim((string)$tglTempoInput)];
                }

                $piutangRows = [];
                $m = max(count($namaPiutangInput), count($totalPiutangInput), count($kdPjPiutangInput), count($tglTempoInput));
                for ($i = 0; $i < $m; $i++) {
                    $nama = trim((string)($namaPiutangInput[$i] ?? ''));
                    $total = (float)($totalPiutangInput[$i] ?? 0);
                    $kdPj = trim((string)($kdPjPiutangInput[$i] ?? $kdPjReg));
                    $tempo = trim((string)($tglTempoInput[$i] ?? $today));
                    if ($nama === '' && $total <= 0) {
                        continue;
                    }
                    if ($nama === '' || $total <= 0) {
                        throw new \RuntimeException('Akun piutang dan nominal piutang wajib diisi');
                    }
                    if (!$this->isDateYmd($tempo)) {
                        $tempo = $today;
                    }
                    if ($kdPj === '') {
                        $kdPj = $kdPjReg;
                    }
                    $piutangRows[] = [
                        'nama_bayar' => $nama,
                        'totalpiutang' => $total,
                        'kd_pj' => $kdPj,
                        'tgltempo' => $tempo,
                    ];
                }
                if (empty($piutangRows)) {
                    $defaultPiutang = $this->valString($pdo, "SELECT IFNULL(nama_bayar,'') FROM akun_piutang ORDER BY nama_bayar LIMIT 1", []);
                    if ($defaultPiutang === '') {
                        throw new \RuntimeException('Akun piutang tidak tersedia');
                    }
                    $piutangRows[] = [
                        'nama_bayar' => $defaultPiutang,
                        'totalpiutang' => $sisaPiutang,
                        'kd_pj' => $kdPjReg,
                        'tgltempo' => $today,
                    ];
                }

                $sumPiutang = 0.0;
                foreach ($piutangRows as $rowPiutang) {
                    $sumPiutang += (float)$rowPiutang['totalpiutang'];
                }
                if (abs($sumPiutang - $sisaPiutang) > 1.0) {
                    throw new \RuntimeException('Total akun piutang harus sama dengan sisa tagihan');
                }

                $insPiutangDetail = $pdo->prepare(
                    "INSERT INTO detail_piutang_pasien(no_rawat,nama_bayar,kd_pj,totalpiutang,sisapiutang,tgltempo)
                     VALUES(:n,:nama,:kd_pj,:total,:sisa,:tempo)"
                );
                $maxTempo = $today;
                foreach ($piutangRows as $rowPiutang) {
                    $insPiutangDetail->execute([
                        'n' => $noRawat,
                        'nama' => $rowPiutang['nama_bayar'],
                        'kd_pj' => $rowPiutang['kd_pj'],
                        'total' => (float)$rowPiutang['totalpiutang'],
                        'sisa' => (float)$rowPiutang['totalpiutang'],
                        'tempo' => $rowPiutang['tgltempo'],
                    ]);
                    if ((string)$rowPiutang['tgltempo'] > $maxTempo) {
                        $maxTempo = (string)$rowPiutang['tgltempo'];
                    }
                }

                $insPiutang = $pdo->prepare(
                    "INSERT INTO piutang_pasien(no_rawat,tgl_piutang,no_rkm_medis,status,totalpiutang,uangmuka,sisapiutang,tgltempo)
                     VALUES(:n,:tgl,:rm,'Belum Lunas',:total,:uangmuka,:sisa,:tempo)"
                );
                $insPiutang->execute([
                    'n' => $noRawat,
                    'tgl' => $today,
                    'rm' => $noRkmMedis,
                    'total' => $grandTotal,
                    'uangmuka' => $totalBayar,
                    'sisa' => $sisaPiutang,
                    'tempo' => $maxTempo,
                ]);
            }

            $pdo->prepare("UPDATE reg_periksa SET status_bayar='Sudah Bayar' WHERE no_rawat=:n")->execute(['n' => $noRawat]);
            if ($isRanap) {
                $bayiNoRawat = $this->valString($pdo, "SELECT IFNULL(no_rawat2,'') FROM ranap_gabung WHERE no_rawat=:n LIMIT 1", ['n' => $noRawat]);
                if ($bayiNoRawat !== '') {
                    $pdo->prepare("UPDATE reg_periksa SET status_bayar='Sudah Bayar' WHERE no_rawat=:n")->execute(['n' => $bayiNoRawat]);
                }
            }

            $pdo->commit();
            $this->goDetail($noRawat, 'ok', 'Pembayaran berhasil disimpan. No Nota: ' . $noNota);
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noRawat, 'error', 'Gagal simpan pembayaran: ' . $e->getMessage());
        }
    }

    private function setBelumBayar(): void
    {
        $noRawat = trim((string)($_POST['no_rawat'] ?? ''));
        if ($noRawat === '') {
            $this->goList('error', 'No rawat belum dipilih');
        }

        try {
            $pdo = Database::pdo();
            $pdo->beginTransaction();

            $statusLanjut = strtolower((string)$this->valString($pdo, "SELECT IFNULL(status_lanjut,'Ralan') FROM reg_periksa WHERE no_rawat=:n", ['n' => $noRawat]));
            $isRanap = $statusLanjut === 'ranap';
            $pdo->prepare("UPDATE reg_periksa SET status_bayar='Belum Bayar' WHERE no_rawat=:n")
                ->execute(['n' => $noRawat]);
            if ($isRanap) {
                $bayiNoRawat = $this->valString($pdo, "SELECT IFNULL(no_rawat2,'') FROM ranap_gabung WHERE no_rawat=:n LIMIT 1", ['n' => $noRawat]);
                if ($bayiNoRawat !== '') {
                    $pdo->prepare("UPDATE reg_periksa SET status_bayar='Belum Bayar' WHERE no_rawat=:n")
                        ->execute(['n' => $bayiNoRawat]);
                }
            }

            $pdo->prepare("DELETE FROM detail_piutang_pasien WHERE no_rawat=:n")->execute(['n' => $noRawat]);
            $pdo->prepare("DELETE FROM piutang_pasien WHERE no_rawat=:n")->execute(['n' => $noRawat]);
            if ($isRanap) {
                $pdo->prepare("DELETE FROM detail_nota_inap WHERE no_rawat=:n")->execute(['n' => $noRawat]);
                $pdo->prepare("DELETE FROM nota_inap WHERE no_rawat=:n")->execute(['n' => $noRawat]);
            } else {
                $pdo->prepare("DELETE FROM detail_nota_jalan WHERE no_rawat=:n")->execute(['n' => $noRawat]);
                $pdo->prepare("DELETE FROM nota_jalan WHERE no_rawat=:n")->execute(['n' => $noRawat]);
            }
            $pdo->prepare("DELETE FROM billing WHERE no_rawat=:n")->execute(['n' => $noRawat]);

            $pdo->commit();
            $this->goDetail($noRawat, 'ok', 'Status pembayaran diubah menjadi Belum Bayar');
        } catch (Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->goDetail($noRawat, 'error', 'Gagal ubah status bayar: ' . $e->getMessage());
        }
    }

    private function nextNoNota(\PDO $pdo, string $tanggalYmd, bool $isRanap): string
    {
        $table = $isRanap ? 'nota_inap' : 'nota_jalan';
        $prefix = $isRanap ? 'RI' : 'RJ';
        $stmt = $pdo->prepare(
            "SELECT IFNULL(MAX(CAST(RIGHT(no_nota,4) AS UNSIGNED)),0) AS max_no
             FROM {$table}
             WHERE tanggal=:tgl"
        );
        $stmt->execute(['tgl' => $tanggalYmd]);
        $max = (int)($stmt->fetchColumn() ?: 0);
        $next = $max + 1;
        return date('Y/m/d', strtotime($tanggalYmd)) . '/' . $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    private function calculateGrandTotal(\PDO $pdo, string $noRawat): float
    {
        $reg = (float)$this->val($pdo, "SELECT IFNULL(biaya_reg,0) FROM reg_periksa WHERE no_rawat=:n", ['n' => $noRawat]);
        $statusLanjut = strtolower((string)$this->valString($pdo, "SELECT IFNULL(status_lanjut,'Ralan') FROM reg_periksa WHERE no_rawat=:n", ['n' => $noRawat]));
        $isRanap = $statusLanjut === 'ranap';
        if ($isRanap) {
            $dr = (float)$this->val($pdo, "SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_inap_dr WHERE no_rawat=:n", ['n' => $noRawat]);
            $pr = (float)$this->val($pdo, "SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_inap_pr WHERE no_rawat=:n", ['n' => $noRawat]);
            $drpr = (float)$this->val($pdo, "SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_inap_drpr WHERE no_rawat=:n", ['n' => $noRawat]);
            $ruang = (float)$this->val($pdo, "SELECT IFNULL(SUM(ttl_biaya),0) FROM kamar_inap WHERE no_rawat=:n", ['n' => $noRawat]);
        } else {
            $dr = (float)$this->val($pdo, "SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_dr WHERE no_rawat=:n", ['n' => $noRawat]);
            $pr = (float)$this->val($pdo, "SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_pr WHERE no_rawat=:n", ['n' => $noRawat]);
            $drpr = (float)$this->val($pdo, "SELECT IFNULL(SUM(biaya_rawat),0) FROM rawat_jl_drpr WHERE no_rawat=:n", ['n' => $noRawat]);
            $ruang = 0.0;
        }
        $lab = (float)$this->val($pdo, "SELECT IFNULL(SUM(biaya),0) FROM periksa_lab WHERE no_rawat=:n", ['n' => $noRawat]);
        $rad = (float)$this->val($pdo, "SELECT IFNULL(SUM(biaya),0) FROM periksa_radiologi WHERE no_rawat=:n", ['n' => $noRawat]);
        $obat = (float)$this->val($pdo, "SELECT IFNULL(SUM(total),0) FROM detail_pemberian_obat WHERE no_rawat=:n", ['n' => $noRawat]);
        $tambahan = (float)$this->val($pdo, "SELECT IFNULL(SUM(besar_biaya),0) FROM tambahan_biaya WHERE no_rawat=:n", ['n' => $noRawat]);
        $pengurangan = (float)$this->val($pdo, "SELECT IFNULL(SUM(besar_pengurangan),0) FROM pengurangan_biaya WHERE no_rawat=:n", ['n' => $noRawat]);
        return $reg + $ruang + $dr + $pr + $drpr + $lab + $rad + $obat + $tambahan - $pengurangan;
    }

    private function val(\PDO $pdo, string $sql, array $params): float
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float)($stmt->fetchColumn() ?: 0);
    }

    private function valString(\PDO $pdo, string $sql, array $params): string
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (string)($stmt->fetchColumn() ?: '');
    }

    private function mapKategori(int $kodeKat): string
    {
        return match ($kodeKat) {
            2 => 'BMHP',
            3 => 'Gas Medis',
            default => 'Obat',
        };
    }

    private function isDateYmd(string $value): bool
    {
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }

    private function buildBillingRows(\PDO $pdo, string $noRawat, string $noNota, string $tanggalYmd, bool $isRanap): array
    {
        $headStmt = $pdo->prepare(
            "SELECT rp.no_rawat, rp.tgl_registrasi, rp.jam_reg, rp.no_rkm_medis, rp.biaya_reg, p.nm_pasien, p.alamat, pl.nm_poli
             FROM reg_periksa rp
             INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis
             INNER JOIN poliklinik pl ON pl.kd_poli=rp.kd_poli
             WHERE rp.no_rawat=:n
             LIMIT 1"
        );
        $headStmt->execute(['n' => $noRawat]);
        $head = $headStmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        if (!is_array($head)) {
            return [];
        }

        $rows = [];
        $rows[] = ['no' => 'No.Nota', 'nm_perawatan' => ': ' . $noNota, 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => '-'];
        if ($isRanap) {
            $kamarInfo = (string)$this->valString(
                $pdo,
                "SELECT IFNULL(CONCAT(ki.kd_kamar, ', ', b.nm_bangsal, ' ', k.kelas),'-')
                 FROM kamar_inap ki
                 LEFT JOIN kamar k ON k.kd_kamar=ki.kd_kamar
                 LEFT JOIN bangsal b ON b.kd_bangsal=k.kd_bangsal
                 WHERE ki.no_rawat=:n
                 ORDER BY ki.tgl_masuk, ki.jam_masuk
                 LIMIT 1",
                ['n' => $noRawat]
            );
            $rows[] = ['no' => 'Bangsal/Kamar', 'nm_perawatan' => ': ' . $kamarInfo, 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => '-'];
        } else {
            $rows[] = ['no' => 'Unit/Instansi', 'nm_perawatan' => ': ' . (string)($head['nm_poli'] ?? '-'), 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => '-'];
        }
        $rows[] = ['no' => 'Tanggal & Jam', 'nm_perawatan' => ': ' . (string)($head['tgl_registrasi'] ?? $tanggalYmd) . ' ' . (string)($head['jam_reg'] ?? '00:00:00'), 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => '-'];
        $rows[] = ['no' => 'No.RM', 'nm_perawatan' => ': ' . (string)($head['no_rkm_medis'] ?? '-'), 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => '-'];
        $rows[] = ['no' => 'Nama Pasien', 'nm_perawatan' => ': ' . (string)($head['nm_pasien'] ?? '-'), 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => '-'];
        $rows[] = ['no' => 'Alamat Pasien', 'nm_perawatan' => ': ' . (string)($head['alamat'] ?? '-'), 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => '-'];
        $rows[] = ['no' => 'Dokter ', 'nm_perawatan' => ':', 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => '-'];

        $dokterSql = $isRanap
            ? "SELECT DISTINCT d.nm_dokter FROM rawat_inap_dr x INNER JOIN dokter d ON d.kd_dokter=x.kd_dokter WHERE x.no_rawat=:n ORDER BY d.nm_dokter"
            : "SELECT DISTINCT d.nm_dokter FROM rawat_jl_dr x INNER JOIN dokter d ON d.kd_dokter=x.kd_dokter WHERE x.no_rawat=:n ORDER BY d.nm_dokter";
        $dokterStmt = $pdo->prepare($dokterSql);
        $dokterStmt->execute(['n' => $noRawat]);
        foreach (($dokterStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $dokter) {
            $rows[] = ['no' => '', 'nm_perawatan' => (string)($dokter['nm_dokter'] ?? ''), 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => 'Dokter'];
        }

        $rows[] = ['no' => 'Registrasi', 'nm_perawatan' => ':', 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => (float)($head['biaya_reg'] ?? 0), 'status' => 'Registrasi'];

        if ($isRanap) {
            $rows[] = ['no' => 'Ruang', 'nm_perawatan' => ':', 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => 'Kamar'];
            $kamarStmt = $pdo->prepare(
                "SELECT ki.kd_kamar, IFNULL(b.nm_bangsal,'') AS nm_bangsal, IFNULL(k.kelas,'') AS kelas, IFNULL(ki.trf_kamar,0) AS trf_kamar,
                        IFNULL(ki.lama,0) AS lama, IFNULL(ki.ttl_biaya,0) AS ttl_biaya
                 FROM kamar_inap ki
                 LEFT JOIN kamar k ON k.kd_kamar=ki.kd_kamar
                 LEFT JOIN bangsal b ON b.kd_bangsal=k.kd_bangsal
                 WHERE ki.no_rawat=:n
                 ORDER BY ki.tgl_masuk, ki.jam_masuk"
            );
            $kamarStmt->execute(['n' => $noRawat]);
            foreach (($kamarStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $kamar) {
                $rows[] = [
                    'no' => '                           ',
                    'nm_perawatan' => trim((string)$kamar['kd_kamar'] . ', ' . (string)$kamar['nm_bangsal'] . ' ' . (string)$kamar['kelas']),
                    'pemisah' => ':',
                    'biaya' => (float)$kamar['trf_kamar'],
                    'jumlah' => (float)$kamar['lama'],
                    'tambahan' => 0,
                    'totalbiaya' => (float)$kamar['ttl_biaya'],
                    'status' => 'Kamar',
                ];
            }
        }

        $rows[] = ['no' => 'Tindakan', 'nm_perawatan' => ':', 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => $isRanap ? 'Ranap Dokter' : 'Ralan Dokter'];
        if ($isRanap) {
            $this->appendTindakanRows($rows, $pdo, $noRawat, 'rawat_inap_dr', 'jns_perawatan_inap', 'Ranap Dokter');
            $this->appendTindakanRows($rows, $pdo, $noRawat, 'rawat_inap_pr', 'jns_perawatan_inap', 'Ranap Paramedis');
            $this->appendTindakanRows($rows, $pdo, $noRawat, 'rawat_inap_drpr', 'jns_perawatan_inap', 'Ranap Dokter Paramedis');
        } else {
            $this->appendTindakanRows($rows, $pdo, $noRawat, 'rawat_jl_dr', 'jns_perawatan', 'Ralan Dokter');
            $this->appendTindakanRows($rows, $pdo, $noRawat, 'rawat_jl_pr', 'jns_perawatan', 'Ralan Paramedis');
            $this->appendTindakanRows($rows, $pdo, $noRawat, 'rawat_jl_drpr', 'jns_perawatan', 'Ralan Dokter Paramedis');
        }

        $this->appendPemeriksaanRows($rows, $pdo, $noRawat, true);
        $this->appendPemeriksaanRows($rows, $pdo, $noRawat, false);
        $this->appendObatRows($rows, $pdo, $noRawat);
        $this->appendTambahanPotonganRows($rows, $pdo, $noRawat);
        return $rows;
    }

    private function appendTindakanRows(array &$rows, \PDO $pdo, string $noRawat, string $table, string $jnsTable, string $status): void
    {
        $stmt = $pdo->prepare(
            "SELECT j.nm_perawatan, COUNT(*) AS jml, SUM(IFNULL(r.biaya_rawat,0)) AS total
             FROM {$table} r
             INNER JOIN {$jnsTable} j ON j.kd_jenis_prw=r.kd_jenis_prw
             WHERE r.no_rawat=:n
             GROUP BY r.kd_jenis_prw, j.nm_perawatan
             ORDER BY j.nm_perawatan"
        );
        $stmt->execute(['n' => $noRawat]);
        foreach (($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $row) {
            $jml = (float)($row['jml'] ?? 0);
            $total = (float)($row['total'] ?? 0);
            $biaya = $jml > 0 ? $total / $jml : $total;
            $rows[] = ['no' => '', 'nm_perawatan' => (string)($row['nm_perawatan'] ?? ''), 'pemisah' => ':', 'biaya' => $biaya, 'jumlah' => $jml, 'tambahan' => 0, 'totalbiaya' => $total, 'status' => $status];
        }
    }

    private function appendPemeriksaanRows(array &$rows, \PDO $pdo, string $noRawat, bool $lab): void
    {
        if ($lab) {
            $rows[] = ['no' => 'Pemeriksaan Lab', 'nm_perawatan' => ':', 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => 'Laborat'];
            $sql = "SELECT jl.nm_perawatan, COUNT(*) AS jml, SUM(IFNULL(jl.total_byr,0)) AS total
                    FROM permintaan_lab pl
                    INNER JOIN permintaan_pemeriksaan_lab ppl ON ppl.noorder=pl.noorder
                    INNER JOIN jns_perawatan_lab jl ON jl.kd_jenis_prw=ppl.kd_jenis_prw
                    WHERE pl.no_rawat=:n
                    GROUP BY ppl.kd_jenis_prw, jl.nm_perawatan
                    ORDER BY jl.nm_perawatan";
            $status = 'Laborat';
        } else {
            $rows[] = ['no' => 'Pemeriksaan Radiologi', 'nm_perawatan' => ':', 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => 'Radiologi'];
            $sql = "SELECT jr.nm_perawatan, COUNT(*) AS jml, SUM(IFNULL(jr.total_byr,0)) AS total
                    FROM permintaan_radiologi pr
                    INNER JOIN permintaan_pemeriksaan_radiologi ppr ON ppr.noorder=pr.noorder
                    INNER JOIN jns_perawatan_radiologi jr ON jr.kd_jenis_prw=ppr.kd_jenis_prw
                    WHERE pr.no_rawat=:n
                    GROUP BY ppr.kd_jenis_prw, jr.nm_perawatan
                    ORDER BY jr.nm_perawatan";
            $status = 'Radiologi';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['n' => $noRawat]);
        foreach (($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $row) {
            $jml = (float)($row['jml'] ?? 0);
            $total = (float)($row['total'] ?? 0);
            $biaya = $jml > 0 ? $total / $jml : $total;
            $rows[] = ['no' => '                           ', 'nm_perawatan' => (string)($row['nm_perawatan'] ?? ''), 'pemisah' => ':', 'biaya' => $biaya, 'jumlah' => $jml, 'tambahan' => 0, 'totalbiaya' => $total, 'status' => $status];
        }
    }

    private function appendObatRows(array &$rows, \PDO $pdo, string $noRawat): void
    {
        $rows[] = ['no' => 'Obat & BHP', 'nm_perawatan' => ':', 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => 'Obat'];
        $stmt = $pdo->prepare(
            "SELECT db.nama_brng, SUM(IFNULL(dpo.jml,0)) AS jml, SUM(IFNULL(dpo.total,0)) AS total, SUM(IFNULL(dpo.embalase,0)+IFNULL(dpo.tuslah,0)) AS tambahan
             FROM detail_pemberian_obat dpo
             INNER JOIN databarang db ON db.kode_brng=dpo.kode_brng
             WHERE dpo.no_rawat=:n
             GROUP BY dpo.kode_brng, db.nama_brng
             ORDER BY db.nama_brng"
        );
        $stmt->execute(['n' => $noRawat]);
        $ttlObat = 0.0;
        foreach (($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $row) {
            $jml = (float)($row['jml'] ?? 0);
            $total = (float)($row['total'] ?? 0);
            $biaya = $jml > 0 ? $total / $jml : $total;
            $ttlObat += $total;
            $rows[] = ['no' => '', 'nm_perawatan' => (string)($row['nama_brng'] ?? ''), 'pemisah' => ':', 'biaya' => $biaya, 'jumlah' => $jml, 'tambahan' => (float)($row['tambahan'] ?? 0), 'totalbiaya' => $total, 'status' => 'Obat'];
        }
        $rows[] = ['no' => '', 'nm_perawatan' => number_format($ttlObat, 0, ',', '.'), 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => 'TtlObat'];
    }

    private function appendTambahanPotonganRows(array &$rows, \PDO $pdo, string $noRawat): void
    {
        $rows[] = ['no' => 'Tambahan Biaya', 'nm_perawatan' => ':', 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => 'Tambahan'];
        $tbStmt = $pdo->prepare("SELECT nama_biaya, besar_biaya FROM tambahan_biaya WHERE no_rawat=:n ORDER BY nama_biaya");
        $tbStmt->execute(['n' => $noRawat]);
        foreach (($tbStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $tb) {
            $total = (float)($tb['besar_biaya'] ?? 0);
            $rows[] = ['no' => '', 'nm_perawatan' => (string)($tb['nama_biaya'] ?? ''), 'pemisah' => ':', 'biaya' => $total, 'jumlah' => 1, 'tambahan' => 0, 'totalbiaya' => $total, 'status' => 'Tambahan'];
        }

        $rows[] = ['no' => 'Potongan Biaya', 'nm_perawatan' => ':', 'pemisah' => '', 'biaya' => 0, 'jumlah' => 0, 'tambahan' => 0, 'totalbiaya' => 0, 'status' => 'Potongan'];
        $pgStmt = $pdo->prepare("SELECT nama_pengurangan, besar_pengurangan FROM pengurangan_biaya WHERE no_rawat=:n ORDER BY nama_pengurangan");
        $pgStmt->execute(['n' => $noRawat]);
        foreach (($pgStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $pg) {
            $total = (float)($pg['besar_pengurangan'] ?? 0);
            $rows[] = ['no' => '', 'nm_perawatan' => (string)($pg['nama_pengurangan'] ?? ''), 'pemisah' => ':', 'biaya' => -1 * $total, 'jumlah' => 1, 'tambahan' => 0, 'totalbiaya' => -1 * $total, 'status' => 'Potongan'];
        }
    }

    private function baseQueryString(): string
    {
        $from = trim((string)($_REQUEST['from'] ?? date('Y-m-d')));
        $to = trim((string)($_REQUEST['to'] ?? date('Y-m-d')));
        $q = trim((string)($_REQUEST['q'] ?? ''));
        $statusBayar = trim((string)($_REQUEST['status_bayar'] ?? 'semua'));
        $mode = trim((string)($_REQUEST['mode'] ?? 'ralan'));
        $embed = trim((string)($_REQUEST['embed'] ?? ''));

        $url = '?page=billing-ralan'
            . '&mode=' . urlencode($mode)
            . '&from=' . urlencode($from)
            . '&to=' . urlencode($to)
            . '&q=' . urlencode($q)
            . '&status_bayar=' . urlencode($statusBayar);
        if ($embed === '1') {
            $url .= '&embed=1';
        }
        return $url;
    }

    private function goList(string $msg, string $detail): void
    {
        $url = $this->baseQueryString();
        $url .= '&msg=' . urlencode($msg) . '&msgd=' . urlencode($detail);
        header('Location: ' . $url);
        exit;
    }

    private function goDetail(string $noRawat, string $msg, string $detail): void
    {
        $url = $this->baseQueryString();
        $url .= '&detail=' . urlencode($noRawat) . '&open=1';
        $url .= '&msg=' . urlencode($msg) . '&msgd=' . urlencode($detail);
        header('Location: ' . $url);
        exit;
    }
}
