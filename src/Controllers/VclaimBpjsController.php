<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Database;
use WebBaru\Services\AuthService;
use WebBaru\Services\BpjsVclaimService;
use WebBaru\Services\SimrsQueryService;

final class VclaimBpjsController
{
    public function index(): void
    {
        $svc = new BpjsVclaimService();
        $auth = AuthService::user();
        $defaultUser = (string)($auth['kode'] ?? 'web');
        if (strlen($defaultUser) > 9) {
            $defaultUser = substr($defaultUser, 0, 9);
        }

        $rujukanNo = trim((string)($_GET['rujukan_no'] ?? ''));
        $rujukanAsal = strtolower(trim((string)($_GET['rujukan_asal'] ?? 'faskes1')));
        if (!in_array($rujukanAsal, ['faskes1', 'rs'], true)) {
            $rujukanAsal = 'faskes1';
        }

        $suratNo = trim((string)($_GET['surat_no'] ?? ''));
        $ralanSepJenis = trim((string)($_GET['ralan_sep_jenis'] ?? ''));
        $createNoSep = trim((string)($_GET['no_sep'] ?? ''));
        $createNoRawat = trim((string)($_GET['no_rawat'] ?? ''));
        $viewMode = strtolower(trim((string)($_GET['view'] ?? 'sep')));
        if (!in_array($viewMode, ['sep', 'rujukan', 'surat'], true)) {
            $viewMode = 'sep';
        }
        $doRujukan = trim((string)($_GET['cek_rujukan'] ?? '')) === '1';
        $doSurat = trim((string)($_GET['cek_surat'] ?? '')) === '1';
        $ajax = trim((string)($_GET['ajax'] ?? ''));

        $visitContext = null;
        $visitError = null;
        if ($createNoRawat !== '') {
            $ctx = $this->loadVisitContext($createNoRawat);
            if ($ctx['ok']) {
                $visitContext = $ctx['data'];
                if ($createNoSep === '' && !empty($visitContext['existing_sep']['no_sep'])) {
                    $createNoSep = (string)$visitContext['existing_sep']['no_sep'];
                }
                if ($suratNo === '') {
                    if ((string)($visitContext['sep_context'] ?? '') === 'ranap' && !empty($visitContext['latest_spri']['no_surat'])) {
                        $suratNo = (string)$visitContext['latest_spri']['no_surat'];
                    } elseif (!empty($visitContext['latest_skdp']['no_antrian'])) {
                        $suratNo = (string)$visitContext['latest_skdp']['no_antrian'];
                    }
                }
            } else {
                $visitError = (string)$ctx['error'];
            }
        }

        if ($ajax === 'surat-kontrol-list') {
            $this->serveSuratKontrolList($svc, $visitContext);
            return;
        }
        if ($ajax === 'spri-list') {
            $this->serveSpriList($visitContext);
            return;
        }
        if ($ajax === 'rujukan-list') {
            $this->serveRujukanList($svc, $visitContext);
            return;
        }
        if ($ajax === 'diag-list') {
            $this->serveDiagnosaList($svc);
            return;
        }

        $rujukanResult = null;
        $suratResult = null;
        $createSepResult = null;
        $createSuratResult = null;
        $createRujukanResult = null;
        if ($doRujukan && $rujukanNo !== '') {
            $rujukanResult = $svc->checkRujukan($rujukanNo, $rujukanAsal === 'rs' ? 'rs' : 'faskes1');
        }
        if ($doSurat && $suratNo !== '') {
            $suratResult = $svc->checkSuratKontrol($suratNo);
        }

        $createSepData = $this->buildCreateSepData(
            $visitContext,
            [
                'no_sep' => $createNoSep,
                'surat_no' => $suratNo,
                'ralan_sep_jenis' => $ralanSepJenis,
            ],
            $defaultUser
        );

        $createSuratData = [
            'no_sep' => trim((string)($_POST['cs_no_sep'] ?? $createNoSep)),
            'kode_dokter' => trim((string)($_POST['cs_kode_dokter'] ?? '')),
            'poli_kontrol' => trim((string)($_POST['cs_poli_kontrol'] ?? '')),
            'tgl_rencana' => trim((string)($_POST['cs_tgl_rencana'] ?? date('Y-m-d'))),
            'user' => trim((string)($_POST['cs_user'] ?? $defaultUser)),
        ];
        $createRujukanData = [
            'no_sep' => trim((string)($_POST['cr_no_sep'] ?? $createNoSep)),
            'tgl_rujukan' => trim((string)($_POST['cr_tgl_rujukan'] ?? date('Y-m-d'))),
            'tgl_rencana' => trim((string)($_POST['cr_tgl_rencana'] ?? date('Y-m-d'))),
            'ppk_dirujuk' => trim((string)($_POST['cr_ppk_dirujuk'] ?? '')),
            'jns_pelayanan' => trim((string)($_POST['cr_jns_pelayanan'] ?? '2')),
            'catatan' => trim((string)($_POST['cr_catatan'] ?? '-')),
            'diag_rujukan' => trim((string)($_POST['cr_diag_rujukan'] ?? '')),
            'tipe_rujukan' => trim((string)($_POST['cr_tipe_rujukan'] ?? '0')),
            'poli_rujukan' => trim((string)($_POST['cr_poli_rujukan'] ?? '')),
            'user' => trim((string)($_POST['cr_user'] ?? $defaultUser)),
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = trim((string)($_POST['action'] ?? ''));
            if ($action === 'create_sep') {
                $createSepData = $this->mergeCreateSepPost($createSepData, $_POST);
                $createSepData = $this->normalizeCreateSepData($createSepData, $visitContext);
                $createSepResult = $svc->createSep($this->toSepPayload($createSepData));
                if (!empty($createSepResult['ok'])) {
                    $createdNoSep = $this->extractCreatedSep($createSepResult);
                    if ($createdNoSep !== '') {
                        $createNoSep = $createdNoSep;
                        $createSepData['no_sep'] = $createdNoSep;
                        $createSuratData['no_sep'] = $createdNoSep;
                        $createRujukanData['no_sep'] = $createdNoSep;
                    }
                    if ($visitContext !== null) {
                        try {
                            $this->persistBridgingSep($visitContext, $createSepData, $createSepResult, $defaultUser);
                            $reload = $this->loadVisitContext($createNoRawat);
                            if ($reload['ok']) {
                                $visitContext = $reload['data'];
                                $createSepData = $this->buildCreateSepData(
                                    $visitContext,
                                    [
                                        'no_sep' => $createNoSep,
                                        'surat_no' => $suratNo,
                                        'ralan_sep_jenis' => (string)($createSepData['ralan_sep_jenis'] ?? $ralanSepJenis),
                                    ],
                                    $defaultUser
                                );
                                $createSepData = $this->normalizeCreateSepData($createSepData, $visitContext);
                            }
                        } catch (
                            \Throwable $e
                        ) {
                            $createSepResult['persist_error'] = $e->getMessage();
                        }
                    }
                }
            } elseif ($action === 'create_surat') {
                $createSuratResult = $svc->createSuratKontrol(
                    $createSuratData['no_sep'],
                    $createSuratData['kode_dokter'],
                    $createSuratData['poli_kontrol'],
                    $createSuratData['tgl_rencana'],
                    $createSuratData['user']
                );
                if (!empty($createSuratResult['ok'])) {
                    $suratNo = (string)($createSuratResult['data']['noSuratKontrol'] ?? '');
                }
            } elseif ($action === 'create_rujukan') {
                $createRujukanResult = $svc->createRujukan(
                    $createRujukanData['no_sep'],
                    $createRujukanData['tgl_rujukan'],
                    $createRujukanData['tgl_rencana'],
                    $createRujukanData['ppk_dirujuk'],
                    $createRujukanData['jns_pelayanan'],
                    $createRujukanData['catatan'],
                    $createRujukanData['diag_rujukan'],
                    $createRujukanData['tipe_rujukan'],
                    $createRujukanData['poli_rujukan'],
                    $createRujukanData['user']
                );
                if (!empty($createRujukanResult['ok'])) {
                    $rujukanNo = (string)($createRujukanResult['data']['rujukan']['noRujukan'] ?? '');
                }
            }
        }

        view('vclaim_bpjs', [
            'title' => 'VClaim BPJS',
            'rujukanNo' => $rujukanNo,
            'rujukanAsal' => $rujukanAsal,
            'suratNo' => $suratNo,
            'createNoSep' => $createNoSep,
            'createNoRawat' => $createNoRawat,
            'viewMode' => $viewMode,
            'visitContext' => $visitContext,
            'visitError' => $visitError,
            'rujukanResult' => $rujukanResult,
            'suratResult' => $suratResult,
            'createSepData' => $createSepData,
            'createSepResult' => $createSepResult,
            'createSuratData' => $createSuratData,
            'createRujukanData' => $createRujukanData,
            'createSuratResult' => $createSuratResult,
            'createRujukanResult' => $createRujukanResult,
        ]);
    }

    private function loadVisitContext(string $noRawat): array
    {
        $db = new SimrsQueryService();
        $head = $db->run(
            "SELECT rp.no_rawat, rp.no_rkm_medis, rp.tgl_registrasi, rp.kd_poli, pl.nm_poli, rp.kd_dokter, d.nm_dokter,
                    rp.status_lanjut, rp.kd_pj, pj.png_jawab,
                    p.nm_pasien, p.no_peserta, p.no_tlp, p.tgl_lahir, p.jk,
                    s.nama_instansi, s.kode_ppk
             FROM reg_periksa rp
             INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
             LEFT JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
             LEFT JOIN dokter d ON d.kd_dokter = rp.kd_dokter
             LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
             LEFT JOIN setting s ON 1=1
             WHERE rp.no_rawat = :no_rawat
             LIMIT 1",
            ['no_rawat' => $noRawat]
        );
        if (!$head['ok']) {
            return ['ok' => false, 'data' => null, 'error' => (string)$head['error']];
        }
        if (empty($head['data'])) {
            return ['ok' => false, 'data' => null, 'error' => 'Data kunjungan tidak ditemukan'];
        }

        $row = $head['data'][0];
        $diag = $db->run(
            "SELECT dp.kd_penyakit, py.nm_penyakit, dp.prioritas, dp.status
             FROM diagnosa_pasien dp
             INNER JOIN penyakit py ON py.kd_penyakit = dp.kd_penyakit
             WHERE dp.no_rawat = :no_rawat
             ORDER BY CASE WHEN dp.status = :status THEN 0 ELSE 1 END, dp.prioritas ASC
             LIMIT 1",
            [
                'no_rawat' => $noRawat,
                'status' => ((string)($row['status_lanjut'] ?? '') === 'Ranap') ? 'Ranap' : 'Ralan',
            ]
        );
        $latestSkdp = $db->run(
            "SELECT tahun, no_antrian, tanggal_datang, tanggal_rujukan, status
             FROM skdp_bpjs
             WHERE no_rkm_medis = :no_rkm_medis
             ORDER BY tanggal_datang DESC
             LIMIT 1",
            ['no_rkm_medis' => (string)($row['no_rkm_medis'] ?? '')]
        );
        $latestSpri = $db->run(
            "SELECT no_surat, tgl_surat, tgl_rencana, kd_dokter_bpjs, nm_dokter_bpjs, kd_poli_bpjs, nm_poli_bpjs, diagnosa, no_sep
             FROM bridging_surat_pri_bpjs
             WHERE no_rawat = :no_rawat
             ORDER BY tgl_surat DESC, no_surat DESC
             LIMIT 1",
            ['no_rawat' => $noRawat]
        );
        $poliMap = $db->run(
            "SELECT kd_poli_bpjs, nm_poli_bpjs
             FROM maping_poli_bpjs
             WHERE kd_poli_rs = :kd_poli
             LIMIT 1",
            ['kd_poli' => (string)($row['kd_poli'] ?? '')]
        );
        $dokterMap = $db->run(
            "SELECT kd_dokter_bpjs, nm_dokter_bpjs
             FROM maping_dokter_dpjpvclaim
             WHERE kd_dokter = :kd_dokter
             LIMIT 1",
            ['kd_dokter' => (string)($row['kd_dokter'] ?? '')]
        );
        $existingSep = $db->run(
            "SELECT *
             FROM bridging_sep
             WHERE no_rawat = :no_rawat
             ORDER BY tglsep DESC, no_sep DESC
             LIMIT 1",
            ['no_rawat' => $noRawat]
        );
        $latestRanapSep = $db->run(
            "SELECT no_sep, tglsep, tglpulang, no_rujukan, kdppkrujukan, nmppkrujukan, kdppkpelayanan, nmppkpelayanan,
                    diagawal, nmdiagnosaawal, kdpolitujuan, nmpolitujuan
             FROM bridging_sep
             WHERE nomr = :no_rkm_medis AND jnspelayanan = '1'
             ORDER BY COALESCE(NULLIF(tglpulang,'0000-00-00 00:00:00'), CONCAT(tglsep,' 00:00:00')) DESC, no_sep DESC
             LIMIT 1",
            ['no_rkm_medis' => (string)($row['no_rkm_medis'] ?? '')]
        );
        $ranapSepOptions = $db->run(
            "SELECT no_sep, tglsep, tglpulang, no_rujukan, kdppkrujukan, nmppkrujukan, kdppkpelayanan, nmppkpelayanan,
                    diagawal, nmdiagnosaawal, kdpolitujuan, nmpolitujuan
             FROM bridging_sep
             WHERE nomr = :no_rkm_medis AND jnspelayanan = '1'
             ORDER BY COALESCE(NULLIF(tglpulang,'0000-00-00 00:00:00'), CONCAT(tglsep,' 00:00:00')) DESC, no_sep DESC
             LIMIT 20",
            ['no_rkm_medis' => (string)($row['no_rkm_medis'] ?? '')]
        );
        $activeRoom = $db->run(
            "SELECT ki.kd_kamar, km.kd_bangsal, km.kelas, IFNULL(b.nm_bangsal, km.kd_kamar) AS nm_ruang
             FROM kamar_inap ki
             LEFT JOIN kamar km ON km.kd_kamar = ki.kd_kamar
             LEFT JOIN bangsal b ON b.kd_bangsal = km.kd_bangsal
             WHERE ki.no_rawat = :no_rawat AND ki.stts_pulang = '-'
             ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC
             LIMIT 1",
            ['no_rawat' => $noRawat]
        );

        $existingSepRow = $existingSep['data'][0] ?? null;

        return [
            'ok' => true,
            'data' => [
                'visit' => $row,
                'diagnosa' => $diag['data'][0] ?? null,
                'latest_skdp' => $latestSkdp['data'][0] ?? null,
                'latest_spri' => $latestSpri['data'][0] ?? null,
                'poli_map' => $poliMap['data'][0] ?? null,
                'dokter_map' => $dokterMap['data'][0] ?? null,
                'existing_sep' => $existingSepRow,
                'latest_ranap_sep' => $latestRanapSep['data'][0] ?? null,
                'ranap_sep_options' => is_array($ranapSepOptions['data'] ?? null) ? $ranapSepOptions['data'] : [],
                'active_room' => $activeRoom['data'][0] ?? null,
                'sep_context' => $this->detectSepContext($row, is_array($existingSepRow) ? $existingSepRow : null),
            ],
            'error' => null,
        ];
    }
    private function serveSuratKontrolList(BpjsVclaimService $svc, ?array $visitContext): void
    {
        $visit = is_array($visitContext['visit'] ?? null) ? $visitContext['visit'] : [];
        $noKartu = trim((string)($_GET['no_kartu'] ?? $visit['no_peserta'] ?? ''));
        $bulan = (int)($_GET['bulan'] ?? date('m'));
        $tahun = (int)($_GET['tahun'] ?? date('Y'));
        $filter = (int)($_GET['filter'] ?? 2);

        $result = $svc->listSuratKontrolByCard($noKartu, $bulan, $tahun, $filter);
        $this->jsonResponse([
            'ok' => !empty($result['ok']),
            'message' => (string)($result['message'] ?? ''),
            'data' => is_array($result['data'] ?? null) ? $result['data'] : [],
            'meta_code' => $result['meta_code'] ?? null,
            'meta_message' => $result['meta_message'] ?? null,
        ]);
    }

    private function serveSpriList(?array $visitContext): void
    {
        $visit = is_array($visitContext['visit'] ?? null) ? $visitContext['visit'] : [];
        $noRawat = trim((string)($_GET['no_rawat'] ?? $visit['no_rawat'] ?? ''));
        $noKartu = trim((string)($_GET['no_kartu'] ?? $visit['no_peserta'] ?? ''));

        $db = new SimrsQueryService();
        if ($noRawat !== '') {
            $result = $db->run(
                "SELECT no_surat, tgl_surat, tgl_rencana, kd_dokter_bpjs, nm_dokter_bpjs, kd_poli_bpjs, nm_poli_bpjs, diagnosa, no_sep
                 FROM bridging_surat_pri_bpjs
                 WHERE no_rawat = :no_rawat
                 ORDER BY tgl_surat DESC, no_surat DESC",
                ['no_rawat' => $noRawat]
            );
        } elseif ($noKartu !== '') {
            $result = $db->run(
                "SELECT no_surat, tgl_surat, tgl_rencana, kd_dokter_bpjs, nm_dokter_bpjs, kd_poli_bpjs, nm_poli_bpjs, diagnosa, no_sep
                 FROM bridging_surat_pri_bpjs
                 WHERE no_kartu = :no_kartu
                 ORDER BY tgl_surat DESC, no_surat DESC",
                ['no_kartu' => $noKartu]
            );
        } else {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Data pasien untuk pencarian SPRI belum tersedia',
                'data' => [],
            ]);
            return;
        }

        if (!$result['ok']) {
            $this->jsonResponse([
                'ok' => false,
                'message' => (string)$result['error'],
                'data' => [],
            ]);
            return;
        }

        $rows = [];
        foreach ((array)($result['data'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rows[] = [
                'no_surat' => trim((string)($row['no_surat'] ?? '')),
                'tgl_terbit' => trim((string)($row['tgl_surat'] ?? '')),
                'tgl_kontrol' => trim((string)($row['tgl_rencana'] ?? '')),
                'kode_poli' => trim((string)($row['kd_poli_bpjs'] ?? '')),
                'nama_poli' => trim((string)($row['nm_poli_bpjs'] ?? '')),
                'kode_dokter' => trim((string)($row['kd_dokter_bpjs'] ?? '')),
                'nama_dokter' => trim((string)($row['nm_dokter_bpjs'] ?? '')),
                'no_sep' => trim((string)($row['no_sep'] ?? '')),
                'diagnosa' => trim((string)($row['diagnosa'] ?? '')),
            ];
        }

        $this->jsonResponse([
            'ok' => true,
            'message' => empty($rows) ? 'Data SPRI tidak ditemukan' : 'Data SPRI ditemukan',
            'data' => $rows,
        ]);
    }

    private function serveDiagnosaList(BpjsVclaimService $svc): void
    {
        $keyword = trim((string)($_GET['q'] ?? $_GET['keyword'] ?? ''));

        $result = $svc->listDiagnosaReferensi($keyword);
        $this->jsonResponse([
            'ok' => !empty($result['ok']),
            'message' => (string)($result['message'] ?? ''),
            'data' => is_array($result['data'] ?? null) ? $result['data'] : [],
            'meta_code' => $result['meta_code'] ?? null,
            'meta_message' => $result['meta_message'] ?? null,
        ]);
    }
    private function serveRujukanList(BpjsVclaimService $svc, ?array $visitContext): void
    {
        $visit = is_array($visitContext['visit'] ?? null) ? $visitContext['visit'] : [];
        $noKartu = trim((string)($_GET['no_kartu'] ?? $visit['no_peserta'] ?? ''));
        $asal = trim((string)($_GET['asal'] ?? 'faskes1'));

        $result = $svc->listRujukanByCard($noKartu, $asal);
        $this->jsonResponse([
            'ok' => !empty($result['ok']),
            'message' => (string)($result['message'] ?? ''),
            'data' => is_array($result['data'] ?? null) ? $result['data'] : [],
            'meta_code' => $result['meta_code'] ?? null,
            'meta_message' => $result['meta_message'] ?? null,
        ]);
    }

    private function jsonResponse(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function buildCreateSepData(?array $context, array $seed, string $defaultUser): array
    {
        $visit = is_array($context['visit'] ?? null) ? $context['visit'] : [];
        $diag = is_array($context['diagnosa'] ?? null) ? $context['diagnosa'] : [];
        $poliMap = is_array($context['poli_map'] ?? null) ? $context['poli_map'] : [];
        $dokterMap = is_array($context['dokter_map'] ?? null) ? $context['dokter_map'] : [];
        $existingSep = is_array($context['existing_sep'] ?? null) ? $context['existing_sep'] : [];
        $latestSkdp = is_array($context['latest_skdp'] ?? null) ? $context['latest_skdp'] : [];
        $latestSpri = is_array($context['latest_spri'] ?? null) ? $context['latest_spri'] : [];
        $latestRanapSep = is_array($context['latest_ranap_sep'] ?? null) ? $context['latest_ranap_sep'] : [];
        $activeRoom = is_array($context['active_room'] ?? null) ? $context['active_room'] : [];

        $sepContext = $this->sanitizeSepContext((string)($context['sep_context'] ?? ''));
        $isRanap = $sepContext === 'ranap';
        $isIgd = $sepContext === 'igd';
        $ralanSepJenis = $sepContext === 'ralan'
            ? $this->sanitizeRalanSepType((string)($seed['ralan_sep_jenis'] ?? $this->inferRalanSepType($existingSep, $latestSkdp, $latestRanapSep)))
            : '';
        $jnsPelayanan = $isRanap ? '1' : '2';
        $diagnosaKode = trim((string)($diag['kd_penyakit'] ?? $existingSep['diagawal'] ?? ''));
        $diagnosaNama = trim((string)($diag['nm_penyakit'] ?? $existingSep['nmdiagnosaawal'] ?? ''));
        $kodeDpjpBpjs = trim((string)($dokterMap['kd_dokter_bpjs'] ?? $existingSep['kddpjp'] ?? ''));
        $namaDpjpBpjs = trim((string)($dokterMap['nm_dokter_bpjs'] ?? $existingSep['nmdpdjp'] ?? ''));
        $asalRujukanValue = trim((string)($existingSep['asal_rujukan'] ?? ''));
        $asalRujukan = $asalRujukanValue === '2. Faskes 2(RS)' ? '2' : '1';
        if ($sepContext === 'ralan') {
            $asalRujukan = $ralanSepJenis === 'post_opname' ? '2' : '1';
        }

        $tglRujukan = !empty($existingSep['tglrujukan']) ? (string)$existingSep['tglrujukan'] : date('Y-m-d');
        $noRujukan = trim((string)($existingSep['no_rujukan'] ?? ''));
        $ppkRujukan = trim((string)($existingSep['kdppkrujukan'] ?? ''));
        $nmPpkRujukan = trim((string)($existingSep['nmppkrujukan'] ?? ''));
        if ($sepContext === 'ralan' && $ralanSepJenis === 'post_opname') {
            if ($noRujukan === '') {
                $noRujukan = trim((string)($latestRanapSep['no_sep'] ?? ''));
            }
            if ($ppkRujukan === '') {
                $ppkRujukan = trim((string)($latestRanapSep['kdppkpelayanan'] ?? $visit['kode_ppk'] ?? ''));
            }
            if ($nmPpkRujukan === '') {
                $nmPpkRujukan = trim((string)($latestRanapSep['nmppkpelayanan'] ?? $visit['nama_instansi'] ?? ''));
            }
            if (empty($existingSep['tglrujukan']) && trim((string)($latestRanapSep['tglsep'] ?? '')) !== '') {
                $tglRujukan = trim((string)$latestRanapSep['tglsep']);
            }
        }

        $noSurat = trim((string)($isRanap ? ($seed['surat_no'] ?? $existingSep['noskdp'] ?? $latestSpri['no_surat'] ?? '') : ($isIgd ? '' : ($seed['surat_no'] ?? $existingSep['noskdp'] ?? $latestSkdp['no_antrian'] ?? ''))));
        if ($sepContext === 'ralan' && $ralanSepJenis === 'rujukan_pertama') {
            $noSurat = '';
        }

        if ($isRanap) {
            $poliTujuan = trim((string)($existingSep['kdpolitujuan'] ?? $activeRoom['kd_kamar'] ?? ''));
            $nmPoliTujuan = trim((string)($existingSep['nmpolitujuan'] ?? $activeRoom['nm_ruang'] ?? ''));
        } else {
            $poliTujuan = trim((string)($existingSep['kdpolitujuan'] ?? $poliMap['kd_poli_bpjs'] ?? $visit['kd_poli'] ?? ''));
            $nmPoliTujuan = trim((string)($existingSep['nmpolitujuan'] ?? $poliMap['nm_poli_bpjs'] ?? $visit['nm_poli'] ?? ''));
        }

        return [
            'no_rawat' => (string)($visit['no_rawat'] ?? ''),
            'no_sep' => trim((string)($seed['no_sep'] ?? '')),
            'sep_context' => $sepContext,
            'sep_context_label' => $this->sepContextLabel($sepContext),
            'poli_field_label' => $isRanap ? 'Bangsal/Kamar' : ($isIgd ? 'Unit Gawat Darurat' : 'Poli Tujuan BPJS'),
            'tgl_sep' => date('Y-m-d'),
            'no_kartu' => trim((string)($visit['no_peserta'] ?? '')),
            'ppk_pelayanan' => trim((string)($visit['kode_ppk'] ?? '')),
            'nm_ppk_pelayanan' => trim((string)($visit['nama_instansi'] ?? '')),
            'jns_pelayanan' => $jnsPelayanan,
            'ralan_sep_jenis' => $ralanSepJenis,
            'ralan_sep_jenis_label' => $this->ralanSepTypeLabel($ralanSepJenis),
            'kls_rawat_hak' => trim((string)($existingSep['klsrawat'] ?? '3')),
            'kls_rawat_naik' => trim((string)($existingSep['klsnaik'] ?? '')),
            'pembiayaan' => trim((string)($existingSep['pembiayaan'] ?? '')),
            'penanggung_jawab' => trim((string)($existingSep['pjnaikkelas'] ?? '')),
            'no_mr' => trim((string)($visit['no_rkm_medis'] ?? '')),
            'asal_rujukan' => $asalRujukan,
            'tgl_rujukan' => $tglRujukan,
            'no_rujukan' => $noRujukan,
            'ppk_rujukan' => $ppkRujukan,
            'nm_ppk_rujukan' => $nmPpkRujukan,
            'catatan' => trim((string)($existingSep['catatan'] ?? '-')),
            'diag_awal' => $diagnosaKode,
            'nm_diag_awal' => $diagnosaNama,
            'poli_tujuan' => $poliTujuan,
            'nm_poli_tujuan' => $nmPoliTujuan,
            'eksekutif' => trim((string)(str_starts_with((string)($existingSep['eksekutif'] ?? ''), '1') ? '1' : '0')),
            'cob' => trim((string)(str_starts_with((string)($existingSep['cob'] ?? ''), '1') ? '1' : '0')),
            'katarak' => trim((string)(str_starts_with((string)($existingSep['katarak'] ?? ''), '1') ? '1' : '0')),
            'laka_lantas' => trim((string)($existingSep['lakalantas'] ?? '0')),
            'no_lp' => trim((string)($existingSep['no_sep_suplesi'] ?? '')),
            'tgl_kkl' => !empty($existingSep['tglkkl']) && (string)$existingSep['tglkkl'] !== '0000-00-00' ? (string)$existingSep['tglkkl'] : '',
            'keterangan_kkl' => trim((string)($existingSep['keterangankkl'] ?? '')),
            'suplesi' => trim((string)(str_starts_with((string)($existingSep['suplesi'] ?? ''), '1') ? '1' : '0')),
            'no_sep_suplesi' => trim((string)($existingSep['no_sep_suplesi'] ?? '')),
            'kd_propinsi' => trim((string)($existingSep['kdprop'] ?? '')),
            'nm_propinsi' => trim((string)($existingSep['nmprop'] ?? '')),
            'kd_kabupaten' => trim((string)($existingSep['kdkab'] ?? '')),
            'nm_kabupaten' => trim((string)($existingSep['nmkab'] ?? '')),
            'kd_kecamatan' => trim((string)($existingSep['kdkec'] ?? '')),
            'nm_kecamatan' => trim((string)($existingSep['nmkec'] ?? '')),
            'tujuan_kunj' => trim((string)($existingSep['tujuankunjungan'] ?? ($isRanap || $isIgd ? '' : '0'))),
            'flag_procedure' => trim((string)($existingSep['flagprosedur'] ?? '')),
            'kd_penunjang' => trim((string)($existingSep['penunjang'] ?? '')),
            'assesment_pel' => trim((string)($existingSep['asesmenpelayanan'] ?? '')),
            'no_surat' => $noSurat,
            'kode_dpjp' => $kodeDpjpBpjs,
            'nm_dpjp' => $namaDpjpBpjs,
            'dpjp_layan' => trim((string)($isRanap ? '' : ($existingSep['kddpjplayanan'] ?? $kodeDpjpBpjs))),
            'nm_dpjp_layan' => trim((string)($isRanap ? '' : ($existingSep['nmdpjplayanan'] ?? $namaDpjpBpjs))),
            'no_telp' => trim((string)($visit['no_tlp'] ?? $existingSep['notelep'] ?? '-')),
            'user' => trim((string)($defaultUser !== '' ? $defaultUser : 'web')),
            'nama_pasien' => trim((string)($visit['nm_pasien'] ?? '')),
            'tgl_lahir' => trim((string)($visit['tgl_lahir'] ?? '')),
            'jkel' => trim((string)($visit['jk'] ?? '')),
            'peserta' => trim((string)($visit['png_jawab'] ?? 'BPJS')),
            'nm_poli_rs' => trim((string)($visit['nm_poli'] ?? '')),
            'nm_dokter_rs' => trim((string)($visit['nm_dokter'] ?? '')),
            'kd_kamar_aktif' => trim((string)($activeRoom['kd_kamar'] ?? '')),
            'nm_kamar_aktif' => trim((string)($activeRoom['nm_ruang'] ?? '')),
            'latest_skdp_no' => trim((string)($latestSkdp['no_antrian'] ?? '')),
            'latest_ranap_sep_no' => trim((string)($latestRanapSep['no_sep'] ?? '')),
            'latest_ranap_sep_tgl' => trim((string)($latestRanapSep['tglsep'] ?? '')),
        ];
    }
    private function detectSepContext(array $visit, ?array $existingSep): string
    {
        if (strtolower(trim((string)($visit['status_lanjut'] ?? ''))) === 'ranap') {
            return 'ranap';
        }

        $targetName = strtolower(trim((string)($existingSep['nmpolitujuan'] ?? $visit['nm_poli'] ?? '')));
        $targetCode = strtolower(trim((string)($existingSep['kdpolitujuan'] ?? $visit['kd_poli'] ?? '')));
        if (
            str_contains($targetName, 'darurat') ||
            str_contains($targetName, 'igd') ||
            str_contains($targetName, 'emergency') ||
            str_contains($targetCode, 'igd')
        ) {
            return 'igd';
        }

        return 'ralan';
    }

    private function sanitizeSepContext(string $context): string
    {
        $context = strtolower(trim($context));
        return in_array($context, ['ralan', 'igd', 'ranap'], true) ? $context : 'ralan';
    }

    private function sepContextLabel(string $context): string
    {
        return match ($this->sanitizeSepContext($context)) {
            'ranap' => 'Rawat Inap',
            'igd' => 'Gawat Darurat',
            default => 'Rawat Jalan',
        };
    }

    private function sanitizeRalanSepType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, ['post_opname', 'kontrol_berulang', 'rujukan_pertama'], true) ? $type : 'kontrol_berulang';
    }

    private function ralanSepTypeLabel(string $type): string
    {
        return match ($this->sanitizeRalanSepType($type)) {
            'post_opname' => 'Kontrol Post Opname',
            'rujukan_pertama' => 'Rujukan Pertama',
            default => 'Kontrol Berulang',
        };
    }

    private function inferRalanSepType(array $existingSep, array $latestSkdp, array $latestRanapSep): string
    {
        $asalRujukan = trim((string)($existingSep['asal_rujukan'] ?? ''));
        $noRujukan = trim((string)($existingSep['no_rujukan'] ?? ''));
        $noSkdp = trim((string)($existingSep['noskdp'] ?? ''));
        $ranapSepNo = trim((string)($latestRanapSep['no_sep'] ?? ''));

        if ($asalRujukan === '2. Faskes 2(RS)' || $asalRujukan === '2') {
            return 'post_opname';
        }
        if ($ranapSepNo !== '' && $noRujukan !== '' && $noRujukan === $ranapSepNo) {
            return 'post_opname';
        }
        if ($noSkdp === '' && trim((string)($latestSkdp['no_antrian'] ?? '')) === '') {
            return 'rujukan_pertama';
        }
        return 'kontrol_berulang';
    }
    private function normalizeCreateSepData(array $data, ?array $context): array
    {
        $contextKey = $this->sanitizeSepContext((string)($data['sep_context'] ?? $context['sep_context'] ?? ''));
        $isRanap = $contextKey === 'ranap';
        $isIgd = $contextKey === 'igd';
        $latestSkdp = is_array($context['latest_skdp'] ?? null) ? $context['latest_skdp'] : [];
        $latestRanapSep = is_array($context['latest_ranap_sep'] ?? null) ? $context['latest_ranap_sep'] : [];
        $activeRoom = is_array($context['active_room'] ?? null) ? $context['active_room'] : [];

        $data['sep_context'] = $contextKey;
        $data['sep_context_label'] = $this->sepContextLabel($contextKey);
        $data['poli_field_label'] = $isRanap ? 'Bangsal/Kamar' : ($isIgd ? 'Unit Gawat Darurat' : 'Poli Tujuan BPJS');
        $data['jns_pelayanan'] = $isRanap ? '1' : '2';
        $data['ralan_sep_jenis'] = $contextKey === 'ralan' ? $this->sanitizeRalanSepType((string)($data['ralan_sep_jenis'] ?? '')) : '';
        $data['ralan_sep_jenis_label'] = $contextKey === 'ralan' ? $this->ralanSepTypeLabel((string)($data['ralan_sep_jenis'] ?? '')) : '';
        $data['catatan'] = trim((string)($data['catatan'] ?? '')) !== '' ? trim((string)$data['catatan']) : '-';
        $data['no_telp'] = trim((string)($data['no_telp'] ?? '')) !== '' ? trim((string)$data['no_telp']) : '-';
        $data['asal_rujukan'] = trim((string)($data['asal_rujukan'] ?? '')) !== '' ? trim((string)$data['asal_rujukan']) : '1';
        $data['tgl_rujukan'] = trim((string)($data['tgl_rujukan'] ?? '')) !== '' ? trim((string)$data['tgl_rujukan']) : ((string)($data['tgl_sep'] ?? date('Y-m-d')));

        if ($isRanap) {
            if (trim((string)($data['poli_tujuan'] ?? '')) === '') {
                $data['poli_tujuan'] = trim((string)($activeRoom['kd_kamar'] ?? ''));
            }
            if (trim((string)($data['nm_poli_tujuan'] ?? '')) === '') {
                $data['nm_poli_tujuan'] = trim((string)($activeRoom['nm_ruang'] ?? ''));
            }
            $data['tujuan_kunj'] = '';
            $data['flag_procedure'] = '';
            $data['kd_penunjang'] = '';
            $data['assesment_pel'] = '';
            $data['no_surat'] = '';
            $data['dpjp_layan'] = '';
            $data['nm_dpjp_layan'] = '';
        } elseif ($isIgd) {
            $data['tujuan_kunj'] = '';
            $data['flag_procedure'] = '';
            $data['kd_penunjang'] = '';
            $data['assesment_pel'] = '';
            $data['no_surat'] = '';
            if (trim((string)($data['dpjp_layan'] ?? '')) === '') {
                $data['dpjp_layan'] = trim((string)($data['kode_dpjp'] ?? ''));
            }
            if (trim((string)($data['nm_dpjp_layan'] ?? '')) === '') {
                $data['nm_dpjp_layan'] = trim((string)($data['nm_dpjp'] ?? ''));
            }
        } else {
            $userCatatan = trim((string)($data['user'] ?? ''));
            if ($userCatatan === '') {
                $userCatatan = 'web';
            }
            $kategoriRalan = $this->ralanSepTypeLabel((string)($data['ralan_sep_jenis'] ?? ''));
            $data['catatan'] = $kategoriRalan . ' (' . $userCatatan . ')';

            $data['tujuan_kunj'] = trim((string)($data['tujuan_kunj'] ?? '')) !== '' ? trim((string)$data['tujuan_kunj']) : '0';
            if (trim((string)($data['dpjp_layan'] ?? '')) === '') {
                $data['dpjp_layan'] = trim((string)($data['kode_dpjp'] ?? ''));
            }
            if (trim((string)($data['nm_dpjp_layan'] ?? '')) === '') {
                $data['nm_dpjp_layan'] = trim((string)($data['nm_dpjp'] ?? ''));
            }
            if ((string)($data['ralan_sep_jenis'] ?? '') === 'post_opname') {
                $data['asal_rujukan'] = '2';
                if (trim((string)($data['no_rujukan'] ?? '')) === '') {
                    $data['no_rujukan'] = trim((string)($latestRanapSep['no_sep'] ?? ''));
                }
                if (trim((string)($data['ppk_rujukan'] ?? '')) === '') {
                    $data['ppk_rujukan'] = trim((string)($latestRanapSep['kdppkpelayanan'] ?? $data['ppk_pelayanan'] ?? ''));
                }
                if (trim((string)($data['nm_ppk_rujukan'] ?? '')) === '') {
                    $data['nm_ppk_rujukan'] = trim((string)($latestRanapSep['nmppkpelayanan'] ?? $data['nm_ppk_pelayanan'] ?? ''));
                }
                if (trim((string)($data['tgl_rujukan'] ?? '')) === '') {
                    $data['tgl_rujukan'] = trim((string)($latestRanapSep['tglsep'] ?? $data['tgl_sep'] ?? date('Y-m-d')));
                }
                if (trim((string)($data['no_surat'] ?? '')) === '') {
                    $data['no_surat'] = trim((string)($latestSkdp['no_antrian'] ?? ''));
                }
            } elseif ((string)($data['ralan_sep_jenis'] ?? '') === 'rujukan_pertama') {
                $data['asal_rujukan'] = '1';
                $data['tujuan_kunj'] = '';
                $data['flag_procedure'] = '';
                $data['kd_penunjang'] = '';
                $data['assesment_pel'] = '';
                $data['no_surat'] = '';
                $data['dpjp_layan'] = '';
            } else {
                $data['asal_rujukan'] = '1';
                if (trim((string)($data['no_surat'] ?? '')) === '') {
                    $data['no_surat'] = trim((string)($latestSkdp['no_antrian'] ?? ''));
                }
            }
        }

        return $data;
    }
    private function mergeCreateSepPost(array $data, array $post): array
    {
        $fields = [
            'no_rawat','no_sep','sep_context','tgl_sep','no_kartu','ppk_pelayanan','nm_ppk_pelayanan','jns_pelayanan','kls_rawat_hak','kls_rawat_naik',
            'pembiayaan','penanggung_jawab','no_mr','asal_rujukan','tgl_rujukan','no_rujukan','ppk_rujukan','nm_ppk_rujukan',
            'catatan','diag_awal','nm_diag_awal','poli_tujuan','nm_poli_tujuan','eksekutif','cob','katarak','laka_lantas','no_lp','penjamin',
            'tgl_kkl','keterangan_kkl','suplesi','no_sep_suplesi','kd_propinsi','nm_propinsi','kd_kabupaten','nm_kabupaten',
            'kd_kecamatan','nm_kecamatan','tujuan_kunj','flag_procedure','kd_penunjang','assesment_pel','no_surat','ralan_sep_jenis','kode_dpjp',
            'nm_dpjp','dpjp_layan','nm_dpjp_layan','no_telp','user','nama_pasien','tgl_lahir','jkel','peserta','nm_poli_rs','nm_dokter_rs'
        ];
        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                $data[$field] = trim((string)$post[$field]);
            }
        }
        return $data;
    }
    private function toSepPayload(array $data): array
    {
        return [
            'noKartu' => (string)($data['no_kartu'] ?? ''),
            'tglSep' => (string)($data['tgl_sep'] ?? ''),
            'ppkPelayanan' => (string)($data['ppk_pelayanan'] ?? ''),
            'jnsPelayanan' => (string)($data['jns_pelayanan'] ?? ''),
            'klsRawatHak' => (string)($data['kls_rawat_hak'] ?? ''),
            'klsRawatNaik' => (string)($data['kls_rawat_naik'] ?? ''),
            'pembiayaan' => (string)($data['pembiayaan'] ?? ''),
            'penanggungJawab' => (string)($data['penanggung_jawab'] ?? ''),
            'noMR' => (string)($data['no_mr'] ?? ''),
            'asalRujukan' => (string)($data['asal_rujukan'] ?? ''),
            'tglRujukan' => (string)($data['tgl_rujukan'] ?? ''),
            'noRujukan' => (string)($data['no_rujukan'] ?? ''),
            'ppkRujukan' => (string)($data['ppk_rujukan'] ?? ''),
            'catatan' => (string)($data['catatan'] ?? ''),
            'diagAwal' => (string)($data['diag_awal'] ?? ''),
            'poliTujuan' => (string)($data['poli_tujuan'] ?? ''),
            'eksekutif' => (string)($data['eksekutif'] ?? '0'),
            'cob' => (string)($data['cob'] ?? '0'),
            'katarak' => (string)($data['katarak'] ?? '0'),
            'lakaLantas' => (string)($data['laka_lantas'] ?? '0'),
            'noLP' => (string)($data['no_lp'] ?? ''),
            'penjamin' => (string)($data['penjamin'] ?? ''),
            'tglKejadian' => (string)($data['tgl_kkl'] ?? ''),
            'keteranganKkl' => (string)($data['keterangan_kkl'] ?? ''),
            'suplesi' => (string)($data['suplesi'] ?? '0'),
            'noSepSuplesi' => (string)($data['no_sep_suplesi'] ?? ''),
            'kdPropinsi' => (string)($data['kd_propinsi'] ?? ''),
            'kdKabupaten' => (string)($data['kd_kabupaten'] ?? ''),
            'kdKecamatan' => (string)($data['kd_kecamatan'] ?? ''),
            'tujuanKunj' => (string)($data['tujuan_kunj'] ?? '0'),
            'flagProcedure' => (string)($data['flag_procedure'] ?? ''),
            'kdPenunjang' => (string)($data['kd_penunjang'] ?? ''),
            'assesmentPel' => (string)($data['assesment_pel'] ?? ''),
            'noSurat' => (string)($data['no_surat'] ?? ''),
            'kodeDPJP' => (string)($data['kode_dpjp'] ?? ''),
            'dpjpLayan' => (string)($data['dpjp_layan'] ?? ''),
            'ralanSepJenis' => (string)($data['ralan_sep_jenis'] ?? ''),
            'noTelp' => (string)($data['no_telp'] ?? ''),
            'user' => (string)($data['user'] ?? 'web'),
        ];
    }

    private function extractCreatedSep(array $result): string
    {
        $data = $result['data'] ?? null;
        if (!is_array($data)) {
            return '';
        }
        $sep = $data['sep'] ?? null;
        if (is_array($sep) && !empty($sep['noSep'])) {
            return trim((string)$sep['noSep']);
        }
        if (!empty($data['noSep'])) {
            return trim((string)$data['noSep']);
        }
        return '';
    }

    private function persistBridgingSep(array $context, array $form, array $result, string $defaultUser): void
    {
        $visit = is_array($context['visit'] ?? null) ? $context['visit'] : [];
        $noSep = $this->extractCreatedSep($result);
        if ($noSep === '') {
            throw new \RuntimeException('No SEP hasil create tidak ditemukan');
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "INSERT INTO bridging_sep (
                no_sep,no_rawat,tglsep,tglrujukan,no_rujukan,kdppkrujukan,nmppkrujukan,kdppkpelayanan,nmppkpelayanan,jnspelayanan,
                catatan,diagawal,nmdiagnosaawal,kdpolitujuan,nmpolitujuan,klsrawat,klsnaik,pembiayaan,pjnaikkelas,lakalantas,
                user,nomr,nama_pasien,tanggal_lahir,peserta,jkel,no_kartu,tglpulang,asal_rujukan,eksekutif,
                cob,notelep,katarak,tglkkl,keterangankkl,suplesi,no_sep_suplesi,kdprop,nmprop,kdkab,
                nmkab,kdkec,nmkec,noskdp,kddpjp,nmdpdjp,tujuankunjungan,flagprosedur,penunjang,asesmenpelayanan,
                kddpjplayanan,nmdpjplayanan
            ) VALUES (
                :no_sep,:no_rawat,:tglsep,:tglrujukan,:no_rujukan,:kdppkrujukan,:nmppkrujukan,:kdppkpelayanan,:nmppkpelayanan,:jnspelayanan,
                :catatan,:diagawal,:nmdiagnosaawal,:kdpolitujuan,:nmpolitujuan,:klsrawat,:klsnaik,:pembiayaan,:pjnaikkelas,:lakalantas,
                :user,:nomr,:nama_pasien,:tanggal_lahir,:peserta,:jkel,:no_kartu,:tglpulang,:asal_rujukan,:eksekutif,
                :cob,:notelep,:katarak,:tglkkl,:keterangankkl,:suplesi,:no_sep_suplesi,:kdprop,:nmprop,:kdkab,
                :nmkab,:kdkec,:nmkec,:noskdp,:kddpjp,:nmdpdjp,:tujuankunjungan,:flagprosedur,:penunjang,:asesmenpelayanan,
                :kddpjplayanan,:nmdpjplayanan
            )
            ON DUPLICATE KEY UPDATE
                no_rawat=VALUES(no_rawat), tglsep=VALUES(tglsep), tglrujukan=VALUES(tglrujukan), no_rujukan=VALUES(no_rujukan),
                kdppkrujukan=VALUES(kdppkrujukan), nmppkrujukan=VALUES(nmppkrujukan), kdppkpelayanan=VALUES(kdppkpelayanan), nmppkpelayanan=VALUES(nmppkpelayanan),
                jnspelayanan=VALUES(jnspelayanan), catatan=VALUES(catatan), diagawal=VALUES(diagawal), nmdiagnosaawal=VALUES(nmdiagnosaawal),
                kdpolitujuan=VALUES(kdpolitujuan), nmpolitujuan=VALUES(nmpolitujuan), klsrawat=VALUES(klsrawat), klsnaik=VALUES(klsnaik), pembiayaan=VALUES(pembiayaan),
                pjnaikkelas=VALUES(pjnaikkelas), lakalantas=VALUES(lakalantas), user=VALUES(user), nomr=VALUES(nomr), nama_pasien=VALUES(nama_pasien),
                tanggal_lahir=VALUES(tanggal_lahir), peserta=VALUES(peserta), jkel=VALUES(jkel), no_kartu=VALUES(no_kartu), tglpulang=VALUES(tglpulang),
                asal_rujukan=VALUES(asal_rujukan), eksekutif=VALUES(eksekutif), cob=VALUES(cob), notelep=VALUES(notelep), katarak=VALUES(katarak),
                tglkkl=VALUES(tglkkl), keterangankkl=VALUES(keterangankkl), suplesi=VALUES(suplesi), no_sep_suplesi=VALUES(no_sep_suplesi), kdprop=VALUES(kdprop),
                nmprop=VALUES(nmprop), kdkab=VALUES(kdkab), nmkab=VALUES(nmkab), kdkec=VALUES(kdkec), nmkec=VALUES(nmkec), noskdp=VALUES(noskdp),
                kddpjp=VALUES(kddpjp), nmdpdjp=VALUES(nmdpdjp), tujuankunjungan=VALUES(tujuankunjungan), flagprosedur=VALUES(flagprosedur), penunjang=VALUES(penunjang),
                asesmenpelayanan=VALUES(asesmenpelayanan), kddpjplayanan=VALUES(kddpjplayanan), nmdpjplayanan=VALUES(nmdpjplayanan)"
        );

        $tglPulang = null;
        if ((string)($form['jns_pelayanan'] ?? '') === '2') {
            $tglPulang = trim((string)($form['tgl_sep'] ?? '')) !== '' ? ((string)$form['tgl_sep'] . ' ' . date('H:i:s')) : date('Y-m-d H:i:s');
        }

        $stmt->execute([
            'no_sep' => $noSep,
            'no_rawat' => (string)($visit['no_rawat'] ?? $form['no_rawat'] ?? ''),
            'tglsep' => (string)($form['tgl_sep'] ?? null),
            'tglrujukan' => (string)($form['tgl_rujukan'] ?? null),
            'no_rujukan' => (string)($form['no_rujukan'] ?? ''),
            'kdppkrujukan' => (string)($form['ppk_rujukan'] ?? ''),
            'nmppkrujukan' => (string)($form['nm_ppk_rujukan'] ?? ''),
            'kdppkpelayanan' => (string)($form['ppk_pelayanan'] ?? ''),
            'nmppkpelayanan' => (string)($form['nm_ppk_pelayanan'] ?? ''),
            'jnspelayanan' => (string)($form['jns_pelayanan'] ?? ''),
            'catatan' => (string)($form['catatan'] ?? ''),
            'diagawal' => (string)($form['diag_awal'] ?? ''),
            'nmdiagnosaawal' => (string)($form['nm_diag_awal'] ?? ''),
            'kdpolitujuan' => (string)($form['poli_tujuan'] ?? ''),
            'nmpolitujuan' => (string)($form['nm_poli_tujuan'] ?? ''),
            'klsrawat' => (string)($form['kls_rawat_hak'] ?? ''),
            'klsnaik' => (string)($form['kls_rawat_naik'] ?? ''),
            'pembiayaan' => (string)($form['pembiayaan'] ?? ''),
            'pjnaikkelas' => (string)($form['penanggung_jawab'] ?? ''),
            'lakalantas' => (string)($form['laka_lantas'] ?? '0'),
            'user' => (string)($form['user'] ?? $defaultUser),
            'nomr' => (string)($form['no_mr'] ?? ''),
            'nama_pasien' => (string)($form['nama_pasien'] ?? $visit['nm_pasien'] ?? ''),
            'tanggal_lahir' => (string)($form['tgl_lahir'] ?? $visit['tgl_lahir'] ?? null),
            'peserta' => (string)($form['peserta'] ?? $visit['png_jawab'] ?? 'BPJS'),
            'jkel' => (string)($form['jkel'] ?? $visit['jk'] ?? ''),
            'no_kartu' => (string)($form['no_kartu'] ?? ''),
            'tglpulang' => $tglPulang,
            'asal_rujukan' => ((string)($form['asal_rujukan'] ?? '1') === '2') ? '2. Faskes 2(RS)' : '1. Faskes 1',
            'eksekutif' => ((string)($form['eksekutif'] ?? '0') === '1') ? '1.Ya' : '0. Tidak',
            'cob' => ((string)($form['cob'] ?? '0') === '1') ? '1.Ya' : '0. Tidak',
            'notelep' => (string)($form['no_telp'] ?? '-'),
            'katarak' => ((string)($form['katarak'] ?? '0') === '1') ? '1.Ya' : '0. Tidak',
            'tglkkl' => trim((string)($form['tgl_kkl'] ?? '')) !== '' ? (string)$form['tgl_kkl'] : '0000-00-00',
            'keterangankkl' => (string)($form['keterangan_kkl'] ?? ''),
            'suplesi' => ((string)($form['suplesi'] ?? '0') === '1') ? '1.Ya' : '0. Tidak',
            'no_sep_suplesi' => (string)($form['no_sep_suplesi'] ?? ''),
            'kdprop' => (string)($form['kd_propinsi'] ?? ''),
            'nmprop' => (string)($form['nm_propinsi'] ?? ''),
            'kdkab' => (string)($form['kd_kabupaten'] ?? ''),
            'nmkab' => (string)($form['nm_kabupaten'] ?? ''),
            'kdkec' => (string)($form['kd_kecamatan'] ?? ''),
            'nmkec' => (string)($form['nm_kecamatan'] ?? ''),
            'noskdp' => (string)($form['no_surat'] ?? ''),
            'kddpjp' => (string)($form['kode_dpjp'] ?? ''),
            'nmdpdjp' => (string)($form['nm_dpjp'] ?? ''),
            'tujuankunjungan' => (string)($form['tujuan_kunj'] ?? '0'),
            'flagprosedur' => (string)($form['flag_procedure'] ?? ''),
            'penunjang' => (string)($form['kd_penunjang'] ?? ''),
            'asesmenpelayanan' => (string)($form['assesment_pel'] ?? ''),
            'kddpjplayanan' => (string)($form['dpjp_layan'] ?? ''),
            'nmdpjplayanan' => (string)($form['nm_dpjp_layan'] ?? ''),
        ]);
    }
}



