<?php

declare(strict_types=1);

namespace WebBaru\Services;

final class BpjsVclaimService
{
    private KhanzaJavaConfigService $configService;

    public function __construct()
    {
        $this->configService = new KhanzaJavaConfigService();
    }

    public function config(): array
    {
        return $this->configService->getBpjsVclaimConfig();
    }

    public function checkSep(string $noSep): array
    {
        $cfg = $this->config();
        if (!$cfg['available']) {
            return [
                'ok' => false,
                'message' => 'Konfigurasi BPJS belum lengkap di setting/database.xml',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => 0,
                'raw' => null,
            ];
        }

        $noSep = trim($noSep);
        if ($noSep === '') {
            return [
                'ok' => false,
                'message' => 'No SEP kosong',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => 0,
                'raw' => null,
            ];
        }

        $utc = (string)time();
        $signature = base64_encode(hash_hmac('sha256', $cfg['consid'] . '&' . $utc, $cfg['secret'], true));
        $url = $cfg['url'] . '/SEP/' . rawurlencode($noSep);
                $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Cons-ID: ' . $cfg['consid'],
            'X-Timestamp: ' . $utc,
            'X-Signature: ' . $signature,
            'user_key: ' . $cfg['userkey'],
        ];

        $response = $this->httpGet($url, $headers);
        if (!$response['ok']) {
            return [
                'ok' => false,
                'message' => $response['error'],
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => $response['http_code'],
                'raw' => null,
            ];
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json)) {
            return [
                'ok' => false,
                'message' => 'Response BPJS tidak valid JSON',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => $response['http_code'],
                'raw' => $response['body'],
            ];
        }

        $metaCode = isset($json['metaData']['code']) ? (string)$json['metaData']['code'] : null;
        $metaMessage = isset($json['metaData']['message']) ? (string)$json['metaData']['message'] : null;
        $ok = ($metaCode === '200');

        return [
            'ok' => $ok,
            'message' => $ok ? 'SEP ditemukan di server BPJS' : ('BPJS: ' . ($metaMessage ?? 'Gagal cek SEP')),
            'meta_code' => $metaCode,
            'meta_message' => $metaMessage,
            'http_code' => $response['http_code'],
            'raw' => $json,
        ];
    }

    public function checkPeserta(string $value, string $type): array
    {
        $cfg = $this->config();
        if (!$cfg['available']) {
            return [
                'ok' => false,
                'message' => 'Konfigurasi BPJS belum lengkap di setting/database.xml',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => 0,
                'data' => null,
                'raw' => null,
            ];
        }

        $value = trim($value);
        $type = strtolower(trim($type));
        if ($value === '') {
            return [
                'ok' => false,
                'message' => 'NIK/No Peserta belum diisi',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => 0,
                'data' => null,
                'raw' => null,
            ];
        }
        if (!in_array($type, ['nik', 'nokartu'], true)) {
            $type = (strlen($value) >= 16) ? 'nik' : 'nokartu';
        }

        $utc = (string)time();
        $signature = base64_encode(hash_hmac('sha256', $cfg['consid'] . '&' . $utc, $cfg['secret'], true));
        $tglSep = date('Y-m-d');
        $url = $cfg['url'] . '/Peserta/' . $type . '/' . rawurlencode($value) . '/tglSEP/' . $tglSep;
                $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Cons-ID: ' . $cfg['consid'],
            'X-Timestamp: ' . $utc,
            'X-Signature: ' . $signature,
            'user_key: ' . $cfg['userkey'],
        ];

        $response = $this->httpGet($url, $headers);
        if (!$response['ok']) {
            return [
                'ok' => false,
                'message' => $response['error'],
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => $response['http_code'],
                'data' => null,
                'raw' => null,
            ];
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json)) {
            $rawBody = trim((string)$response['body']);
            $low = strtolower($rawBody);
            $message = 'Response BPJS tidak valid JSON';
            if (str_contains($low, '<html') || str_contains($low, '<!doctype html')) {
                $message = 'Server BPJS mengembalikan HTML (bukan JSON). Periksa endpoint/parameter request.';
            } elseif (str_contains($low, '<?xml')) {
                $message = 'Server BPJS mengembalikan XML (bukan JSON). Periksa endpoint/parameter request.';
            }
            return [
                'ok' => false,
                'message' => $message,
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => $response['http_code'],
                'data' => null,
                'raw' => $response['body'],
            ];
        }

        $metaCode = isset($json['metaData']['code']) ? (string)$json['metaData']['code'] : null;
        $metaMessage = isset($json['metaData']['message']) ? (string)$json['metaData']['message'] : null;
        if ($metaCode !== '200') {
            return [
                'ok' => false,
                'message' => 'BPJS: ' . ($metaMessage ?? 'Gagal cek peserta'),
                'meta_code' => $metaCode,
                'meta_message' => $metaMessage,
                'http_code' => $response['http_code'],
                'data' => null,
                'raw' => $json,
            ];
        }

        $payload = $json['response'] ?? null;
        $decrypted = null;
        if (is_string($payload) && $payload !== '') {
            $decrypted = $this->decryptBpjsPayload($payload, (string)$cfg['consid'], (string)$cfg['secret'], $utc);
        } elseif (is_array($payload)) {
            $decrypted = $payload;
        }
        if (!is_array($decrypted)) {
            return [
                'ok' => false,
                'message' => 'Response peserta berhasil diterima, namun gagal didekripsi',
                'meta_code' => $metaCode,
                'meta_message' => $metaMessage,
                'http_code' => $response['http_code'],
                'data' => null,
                'raw' => $json,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Data peserta ditemukan',
            'meta_code' => $metaCode,
            'meta_message' => $metaMessage,
            'http_code' => $response['http_code'],
            'data' => $decrypted['peserta'] ?? $decrypted,
            'raw' => $decrypted,
        ];
    }

    public function listHistoriPelayanan(string $noKartu, string $tglMulai, string $tglAkhir): array
    {
        $noKartu = preg_replace('/\\D+/', '', trim($noKartu));
        if ($noKartu === '') {
            return [
                'ok' => false,
                'message' => 'No kartu pasien belum tersedia',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => 0,
                'data' => [],
                'raw' => null,
            ];
        }

        $tglMulai = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglMulai) ? $tglMulai : date('Y-m-d', strtotime('-90 days'));
        $tglAkhir = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir) ? $tglAkhir : date('Y-m-d');

        $path = sprintf(
            '/monitoring/HistoriPelayanan/NoKartu/%s/tglMulai/%s/tglAkhir/%s',
            rawurlencode($noKartu),
            rawurlencode($tglMulai),
            rawurlencode($tglAkhir)
        );
        $result = $this->requestDecoded($path, 'Data histori pelayanan ditemukan');
        if (empty($result['ok'])) {
            $result['data'] = [];
            return $result;
        }

        $payload = $result['data'] ?? null;
        $rows = [];
        if (is_array($payload)) {
            if (isset($payload['histori']) && is_array($payload['histori'])) {
                $rows = $payload['histori'];
            } elseif (array_is_list($payload)) {
                $rows = $payload;
            }
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $jnsPelayanan = trim((string)($row['jnsPelayanan'] ?? ''));
            $normalized[] = [
                'diagnosa' => trim((string)($row['diagnosa'] ?? '')),
                'jns_pelayanan' => $jnsPelayanan,
                'jns_pelayanan_label' => $jnsPelayanan === '1' ? 'Rawat Inap' : ($jnsPelayanan === '2' ? 'Rawat Jalan' : $jnsPelayanan),
                'kelas_rawat' => trim((string)($row['kelasRawat'] ?? '')),
                'nama_peserta' => trim((string)($row['namaPeserta'] ?? '')),
                'no_kartu' => trim((string)($row['noKartu'] ?? '')),
                'no_sep' => trim((string)($row['noSep'] ?? '')),
                'no_rujukan' => trim((string)($row['noRujukan'] ?? '')),
                'poli' => trim((string)($row['poli'] ?? '')),
                'ppk_pelayanan' => trim((string)($row['ppkPelayanan'] ?? '')),
                'tgl_pulang_sep' => trim((string)($row['tglPlgSep'] ?? '')),
                'tgl_sep' => trim((string)($row['tglSep'] ?? '')),
                'raw' => $row,
            ];
        }

        $result['data'] = $normalized;
        $result['raw'] = $payload;
        return $result;
    }
    public function checkRujukan(string $nomor, string $asal): array
    {
        $nomor = preg_replace('/[[:cntrl:]]+/', '', trim($nomor));
        $nomor = str_replace(' ', '', (string)$nomor);
        if ($nomor === '') {
            return [
                'ok' => false,
                'message' => 'Nomor rujukan belum diisi',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => 0,
                'data' => null,
                'raw' => null,
            ];
        }

        $asal = strtolower(trim($asal));
        $path = ($asal === 'rs') ? '/Rujukan/RS/' . rawurlencode($nomor) : '/Rujukan/' . rawurlencode($nomor);
        return $this->requestDecoded($path, 'Data rujukan ditemukan');
    }

    public function listRujukanByCard(string $noKartu, string $asal): array
    {
        $noKartu = preg_replace('/\\D+/', '', trim($noKartu));
        if ($noKartu === '') {
            return [
                'ok' => false,
                'message' => 'No kartu pasien belum tersedia',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => 0,
                'data' => [],
                'raw' => null,
            ];
        }

        $asal = strtolower(trim($asal));
        $path = ($asal === 'rs') ? '/Rujukan/RS/Peserta/' . rawurlencode($noKartu) : '/Rujukan/Peserta/' . rawurlencode($noKartu);
        $result = $this->requestDecoded($path, 'Data rujukan ditemukan');
        if (empty($result['ok'])) {
            $result['data'] = [];
            return $result;
        }

        $payload = $result['data'] ?? null;
        $rows = [];
        if (is_array($payload)) {
            if (isset($payload['rujukan']) && is_array($payload['rujukan'])) {
                $candidate = $payload['rujukan'];
                $rows = array_is_list($candidate) ? $candidate : [$candidate];
            } elseif (array_is_list($payload)) {
                $rows = $payload;
            } else {
                $rows = [$payload];
            }
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $diag = is_array($row['diagnosa'] ?? null) ? $row['diagnosa'] : [];
            $ppk = is_array($row['provPerujuk'] ?? null) ? $row['provPerujuk'] : [];
            $normalized[] = [
                'no_rujukan' => trim((string)($row['noKunjungan'] ?? $row['noRujukan'] ?? '')),
                'tgl_rujukan' => trim((string)($row['tglKunjungan'] ?? $row['tglRujukan'] ?? '')),
                'ppk_rujukan' => trim((string)($ppk['kode'] ?? $row['kodePpk'] ?? '')),
                'nm_ppk_rujukan' => trim((string)($ppk['nama'] ?? $row['namaPpk'] ?? '')),
                'diag_awal' => trim((string)($diag['kode'] ?? $row['diagAwal'] ?? '')),
                'nm_diag_awal' => trim((string)($diag['nama'] ?? $row['namaDiagnosa'] ?? '')),
                'poli_rujukan' => trim((string)((is_array($row['poliRujukan'] ?? null) ? (($row['poliRujukan']['nama'] ?? '')) : ''))),
                'raw' => $row,
            ];
        }

        $result['data'] = $normalized;
        $result['raw'] = $payload;
        return $result;
    }


    public function listDiagnosaReferensi(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [
                'ok' => false,
                'message' => 'Kata kunci diagnosa belum diisi',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => 0,
                'data' => [],
                'raw' => null,
            ];
        }

        $path = '/referensi/diagnosa/' . rawurlencode($keyword);
        $result = $this->requestDecoded($path, 'Referensi diagnosa ditemukan');
        if (empty($result['ok'])) {
            $result['data'] = [];
            return $result;
        }

        $payload = $result['data'] ?? null;
        $rows = [];
        if (is_array($payload)) {
            if (isset($payload['diagnosa']) && is_array($payload['diagnosa'])) {
                $candidate = $payload['diagnosa'];
                $rows = array_is_list($candidate) ? $candidate : [$candidate];
            } elseif (isset($payload['list']) && is_array($payload['list'])) {
                $candidate = $payload['list'];
                $rows = array_is_list($candidate) ? $candidate : [$candidate];
            } elseif (array_is_list($payload)) {
                $rows = $payload;
            } else {
                $rows = [$payload];
            }
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $kode = trim((string)($row['kode'] ?? $row['kdDiag'] ?? $row['kdPenyakit'] ?? ''));
            $nama = trim((string)($row['nama'] ?? $row['nmDiag'] ?? $row['nmPenyakit'] ?? ''));
            if ($kode === '' && $nama === '') {
                continue;
            }
            $normalized[] = [
                'kode' => $kode,
                'nama' => $nama,
                'raw' => $row,
            ];
        }

        $result['data'] = $normalized;
        $result['raw'] = $payload;
        return $result;
    }
    public function checkSuratKontrol(string $nomorSurat): array
    {
        $nomorSurat = trim($nomorSurat);
        if ($nomorSurat === '') {
            return [
                'ok' => false,
                'message' => 'Nomor surat kontrol belum diisi',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => 0,
                'data' => null,
                'raw' => null,
            ];
        }

        $path = '/RencanaKontrol/noSuratKontrol/' . rawurlencode($nomorSurat);
        return $this->requestDecoded($path, 'Data surat kontrol ditemukan');
    }

    public function listSuratKontrolByCard(string $noKartu, int $bulan, int $tahun, int $filter = 2): array
    {
        $noKartu = preg_replace('/\\D+/', '', trim($noKartu));
        if ($noKartu === '') {
            return [
                'ok' => false,
                'message' => 'No kartu pasien belum tersedia',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => 0,
                'data' => [],
                'raw' => null,
            ];
        }

        $bulan = max(1, min(12, $bulan));
        $tahun = max(2000, min(2100, $tahun));
        $filter = in_array($filter, [1, 2], true) ? $filter : 2;

        $path = sprintf(
            '/RencanaKontrol/ListRencanaKontrol/Bulan/%02d/Tahun/%04d/Nokartu/%s/filter/%d',
            $bulan,
            $tahun,
            rawurlencode($noKartu),
            $filter
        );
        $result = $this->requestDecoded($path, 'Daftar surat kontrol ditemukan');
        if (empty($result['ok'])) {
            $result['data'] = [];
            return $result;
        }

        $payload = is_array($result['data'] ?? null) ? $result['data'] : [];
        $list = $payload['list'] ?? $payload['suratKontrol'] ?? $payload['suratkontrol'] ?? $payload['rencanaKontrol'] ?? $payload['listRencanaKontrol'] ?? [];
        if (!is_array($list)) {
            $list = [];
        }

        $normalized = [];
        foreach ($list as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized[] = [
                'no_surat' => trim((string)($row['noSuratKontrol'] ?? $row['noSurat'] ?? $row['nomorSurat'] ?? '')),
                'no_sep' => trim((string)($row['noSep'] ?? $row['nosep'] ?? '')),
                'tgl_terbit' => trim((string)($row['tglTerbitKontrol'] ?? $row['tglSurat'] ?? '')),
                'tgl_kontrol' => trim((string)($row['tglRencanaKontrol'] ?? $row['tglKontrol'] ?? '')),
                'kode_poli' => trim((string)($row['kodePoli'] ?? $row['kodePoliTujuan'] ?? '')),
                'nama_poli' => trim((string)($row['namaPoli'] ?? $row['namaPoliTujuan'] ?? '')),
                'kode_dokter' => trim((string)($row['kodeDokter'] ?? '')),
                'nama_dokter' => trim((string)($row['namaDokter'] ?? $row['namaDPJP'] ?? '')),
                'terbit_sep' => trim((string)($row['terbitSEP'] ?? '')),
                'raw' => $row,
            ];
        }

        $result['data'] = $normalized;
        $result['raw'] = $payload;
        return $result;
    }

    public function createSuratKontrol(
        string $noSep,
        string $kodeDokter,
        string $poliKontrol,
        string $tglRencanaKontrol,
        string $user
    ): array {
        $payload = [
            'request' => [
                'noSEP' => trim($noSep),
                'kodeDokter' => trim($kodeDokter),
                'poliKontrol' => trim($poliKontrol),
                'tglRencanaKontrol' => trim($tglRencanaKontrol),
                'user' => trim($user) !== '' ? trim($user) : 'web',
            ],
        ];
        return $this->requestDecodedWithBody('/RencanaKontrol/insert', 'POST', $payload, 'Surat kontrol berhasil dibuat');
    }

    public function createRujukan(
        string $noSep,
        string $tglRujukan,
        string $tglRencanaKunjungan,
        string $ppkDirujuk,
        string $jnsPelayanan,
        string $catatan,
        string $diagRujukan,
        string $tipeRujukan,
        string $poliRujukan,
        string $user
    ): array {
        $payload = [
            'request' => [
                't_rujukan' => [
                    'noSep' => trim($noSep),
                    'tglRujukan' => trim($tglRujukan),
                    'tglRencanaKunjungan' => trim($tglRencanaKunjungan),
                    'ppkDirujuk' => trim($ppkDirujuk),
                    'jnsPelayanan' => trim($jnsPelayanan),
                    'catatan' => trim($catatan),
                    'diagRujukan' => trim($diagRujukan),
                    'tipeRujukan' => trim($tipeRujukan),
                    'poliRujukan' => trim($poliRujukan),
                    'user' => trim($user) !== '' ? trim($user) : 'web',
                ],
            ],
        ];
        return $this->requestDecodedWithBody('/Rujukan/2.0/insert', 'POST', $payload, 'Rujukan berhasil dibuat');
    }


    public function buildSepRequestPayload(array $data): array
    {
        $kelasHak = trim((string)($data['klsRawat'] ?? $data['klsRawatHak'] ?? ''));
        $kelasNaik = trim((string)($data['klsRawatNaik'] ?? ''));
        $pembiayaan = trim((string)($data['pembiayaan'] ?? ''));
        $penanggungJawab = trim((string)($data['penanggungJawab'] ?? ''));
        $ralanSepJenis = strtolower(trim((string)($data['ralanSepJenis'] ?? '')));
        $isRalan = trim((string)($data['jnsPelayanan'] ?? '')) === '2';
        $isRujukanPertama = $isRalan && $ralanSepJenis === 'rujukan_pertama';
        $tujuanKunj = $isRujukanPertama ? '' : trim((string)($data['tujuanKunj'] ?? '0'));
        $flagProcedure = $isRujukanPertama ? '' : trim((string)($data['flagProcedure'] ?? ''));
        $kdPenunjang = $isRujukanPertama ? '' : trim((string)($data['kdPenunjang'] ?? ''));
        $assesmentPel = $isRujukanPertama ? '' : trim((string)($data['assesmentPel'] ?? ''));
        $noSurat = $isRujukanPertama ? '' : trim((string)($data['noSurat'] ?? ''));
        $kodeDpjpSkdp = $isRujukanPertama ? '' : trim((string)($data['kodeDPJP'] ?? ''));
        $dpjpLayan = $isRujukanPertama ? '' : trim((string)($data['dpjpLayan'] ?? ''));

        return [
            'request' => [
                't_sep' => [
                    'noKartu' => trim((string)($data['noKartu'] ?? '')),
                    'tglSep' => trim((string)($data['tglSep'] ?? '')),
                    'ppkPelayanan' => trim((string)($data['ppkPelayanan'] ?? '')),
                    'jnsPelayanan' => trim((string)($data['jnsPelayanan'] ?? '')),
                    'klsRawat' => [
                        'klsRawatHak' => $kelasHak,
                        'klsRawatNaik' => $kelasNaik,
                        'pembiayaan' => $pembiayaan,
                        'penanggungJawab' => $penanggungJawab,
                    ],
                    'noMR' => trim((string)($data['noMR'] ?? '')),
                    'rujukan' => [
                        'asalRujukan' => trim((string)($data['asalRujukan'] ?? '')),
                        'tglRujukan' => trim((string)($data['tglRujukan'] ?? '')),
                        'noRujukan' => trim((string)($data['noRujukan'] ?? '')),
                        'ppkRujukan' => trim((string)($data['ppkRujukan'] ?? '')),
                    ],
                    'catatan' => trim((string)($data['catatan'] ?? '')),
                    'diagAwal' => trim((string)($data['diagAwal'] ?? '')),
                    'poli' => [
                        'tujuan' => trim((string)($data['poliTujuan'] ?? '')),
                        'eksekutif' => trim((string)($data['eksekutif'] ?? '0')),
                    ],
                    'cob' => [
                        'cob' => trim((string)($data['cob'] ?? '0')),
                    ],
                    'katarak' => [
                        'katarak' => trim((string)($data['katarak'] ?? '0')),
                    ],
                    'jaminan' => [
                        'lakaLantas' => trim((string)($data['lakaLantas'] ?? '0')),
                        'noLP' => trim((string)($data['noLP'] ?? '')),
                        'penjamin' => [
                            'tglKejadian' => trim((string)($data['tglKejadian'] ?? '')),
                            'keterangan' => trim((string)($data['keteranganKkl'] ?? '')),
                            'suplesi' => [
                                'suplesi' => trim((string)($data['suplesi'] ?? '0')),
                                'noSepSuplesi' => trim((string)($data['noSepSuplesi'] ?? '')),
                                'lokasiLaka' => [
                                    'kdPropinsi' => trim((string)($data['kdPropinsi'] ?? '')),
                                    'kdKabupaten' => trim((string)($data['kdKabupaten'] ?? '')),
                                    'kdKecamatan' => trim((string)($data['kdKecamatan'] ?? '')),
                                ],
                            ],
                        ],
                    ],
                    'tujuanKunj' => $tujuanKunj,
                    'flagProcedure' => $flagProcedure,
                    'kdPenunjang' => $kdPenunjang,
                    'assesmentPel' => $assesmentPel,
                    'skdp' => [
                        'noSurat' => $noSurat,
                        'kodeDPJP' => $kodeDpjpSkdp,
                    ],
                    'dpjpLayan' => $dpjpLayan,
                    'noTelp' => trim((string)($data['noTelp'] ?? '')),
                    'user' => trim((string)($data['user'] ?? 'web')),
                ],
            ],
        ];
    }

    public function createSep(array $data): array
    {
        return $this->requestDecodedWithBody('/SEP/2.0/insert', 'POST', $this->buildSepRequestPayload($data), 'SEP berhasil dibuat');
    }

    private function requestDecoded(string $path, string $successMessage): array
    {
        return $this->requestDecodedWithBody($path, 'GET', null, $successMessage);
    }

    private function requestDecodedWithBody(string $path, string $method, ?array $body, string $successMessage): array
    {
        $cfg = $this->config();
        if (!$cfg['available']) {
            return [
                'ok' => false,
                'message' => 'Konfigurasi BPJS belum lengkap di setting/database.xml',
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => 0,
                'data' => null,
                'raw' => null,
            ];
        }

        $utc = (string)time();
        $signature = base64_encode(hash_hmac('sha256', $cfg['consid'] . '&' . $utc, $cfg['secret'], true));
        $url = rtrim((string)$cfg['url'], '/') . '/' . ltrim($path, '/');
        $contentType = strtoupper($method) === 'GET' ? 'application/json' : 'application/x-www-form-urlencoded';
        $headers = [
            'Accept: application/json',
            'Content-Type: ' . $contentType,
            'X-Cons-ID: ' . $cfg['consid'],
            'X-Timestamp: ' . $utc,
            'X-Signature: ' . $signature,
            'user_key: ' . $cfg['userkey'],
        ];

        $response = strtoupper($method) === 'GET'
            ? $this->httpGet($url, $headers)
            : $this->httpRequest($url, strtoupper($method), $headers, $body === null ? null : $this->safeJsonEncode($body));
        if (!$response['ok']) {
            return [
                'ok' => false,
                'message' => $response['error'],
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => $response['http_code'],
                'data' => null,
                'raw' => null,
            ];
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json)) {
            $rawBody = trim((string)$response['body']);
            $low = strtolower($rawBody);
            $message = 'Response BPJS tidak valid JSON';
            if (str_contains($low, '<html') || str_contains($low, '<!doctype html')) {
                $message = 'Server BPJS mengembalikan HTML (bukan JSON). Periksa endpoint/parameter request.';
            } elseif (str_contains($low, '<?xml')) {
                $message = 'Server BPJS mengembalikan XML (bukan JSON). Periksa endpoint/parameter request.';
            }
            return [
                'ok' => false,
                'message' => $message,
                'meta_code' => null,
                'meta_message' => null,
                'http_code' => $response['http_code'],
                'data' => null,
                'raw' => $response['body'],
            ];
        }

        $metaCode = isset($json['metaData']['code']) ? (string)$json['metaData']['code'] : null;
        $metaMessage = isset($json['metaData']['message']) ? (string)$json['metaData']['message'] : null;
        if ($metaCode !== '200') {
            return [
                'ok' => false,
                'message' => 'BPJS: ' . ($metaMessage ?? 'Gagal request VClaim'),
                'meta_code' => $metaCode,
                'meta_message' => $metaMessage,
                'http_code' => $response['http_code'],
                'data' => null,
                'raw' => $json,
            ];
        }

        $payload = $json['response'] ?? null;
        $decrypted = null;
        if (is_string($payload) && $payload !== '') {
            $decrypted = $this->decryptBpjsPayload($payload, (string)$cfg['consid'], (string)$cfg['secret'], $utc);
        } elseif (is_array($payload)) {
            $decrypted = $payload;
        }

        if (!is_array($decrypted)) {
            return [
                'ok' => false,
                'message' => 'Response VClaim berhasil diterima, namun gagal didekripsi',
                'meta_code' => $metaCode,
                'meta_message' => $metaMessage,
                'http_code' => $response['http_code'],
                'data' => null,
                'raw' => $json,
            ];
        }

        return [
            'ok' => true,
            'message' => $successMessage,
            'meta_code' => $metaCode,
            'meta_message' => $metaMessage,
            'http_code' => $response['http_code'],
            'data' => $decrypted,
            'raw' => $decrypted,
        ];
    }

    private function safeJsonEncode(array $payload): string
    {
        $json = json_encode($this->normalizeUtf8($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Gagal menyusun JSON request BPJS: ' . json_last_error_msg());
        }
        return $json;
    }

    private function normalizeUtf8(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeUtf8($item);
            }
            return $normalized;
        }
        if (is_string($value)) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            return $converted === false ? $value : $converted;
        }
        return $value;
    }
    private function httpRequest(string $url, string $method, array $headers, ?string $body = null): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return ['ok' => false, 'body' => null, 'http_code' => 0, 'error' => 'Gagal inisialisasi cURL'];
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body ?? '');
            } elseif ($method !== 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
            }

            $resBody = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($resBody === false) {
                return ['ok' => false, 'body' => null, 'http_code' => $httpCode, 'error' => ($error !== '' ? $error : 'Request BPJS gagal')];
            }

            return ['ok' => true, 'body' => (string)$resBody, 'http_code' => $httpCode, 'error' => null];
        }

        $opts = [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'timeout' => 25,
            'ignore_errors' => true,
        ];
        if ($body !== null) {
            $opts['content'] = $body;
        }
        $context = stream_context_create([
            'http' => $opts,
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $resBody = @file_get_contents($url, false, $context);
        if ($resBody === false) {
            return ['ok' => false, 'body' => null, 'http_code' => 0, 'error' => 'Request BPJS gagal'];
        }
        return ['ok' => true, 'body' => (string)$resBody, 'http_code' => 200, 'error' => null];
    }

    private function httpGet(string $url, array $headers): array
    {
        return $this->httpRequest($url, 'GET', $headers, null);
    }

    private function decryptBpjsPayload(string $payload, string $consid, string $secret, string $utc): ?array
    {
        $keyMaterial = $consid . $secret . $utc;
        $hash = hash('sha256', $keyMaterial, true);
        $iv = substr($hash, 0, 16);
        $cipher = base64_decode($payload, true);
        if ($cipher === false) {
            return null;
        }

        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $hash, OPENSSL_RAW_DATA, $iv);
        if ($plain === false || $plain === '') {
            return null;
        }

        $json = json_decode($plain, true);
        if (is_array($json)) {
            return $json;
        }

        $decoded = $this->lzDecompressFromEncodedURIComponent($plain);
        if (!is_string($decoded) || $decoded === '') {
            return null;
        }
        $json = json_decode($decoded, true);
        return is_array($json) ? $json : null;
    }

    private function lzDecompressFromEncodedURIComponent(string $input): ?string
    {
        if ($input === '') {
            return null;
        }
        $input = str_replace(' ', '+', $input);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+-$';
        $baseMap = [];
        $len = strlen($alphabet);
        for ($i = 0; $i < $len; $i++) {
            $baseMap[$alphabet[$i]] = $i;
        }

        $getNext = static function (int $index) use ($input, $baseMap): int {
            $ch = $input[$index] ?? '';
            return $baseMap[$ch] ?? 0;
        };

        $length = strlen($input);
        $resetValue = 32;
        $dictionary = [0 => '', 1 => '', 2 => ''];
        $enlargeIn = 4;
        $dictSize = 4;
        $numBits = 3;
        $dataVal = $getNext(0);
        $dataPos = $resetValue;
        $dataIndex = 1;

        $readBits = static function (int $n) use (&$dataVal, &$dataPos, &$dataIndex, $length, $resetValue, $getNext): ?int {
            $bits = 0;
            $maxpower = 1 << $n;
            $power = 1;
            while ($power !== $maxpower) {
                $resb = $dataVal & $dataPos;
                $dataPos >>= 1;
                if ($dataPos === 0) {
                    $dataPos = $resetValue;
                    if ($dataIndex >= $length) {
                        return null;
                    }
                    $dataVal = $getNext($dataIndex++);
                }
                if ($resb > 0) {
                    $bits |= $power;
                }
                $power <<= 1;
            }
            return $bits;
        };

        $next = $readBits(2);
        if ($next === null) {
            return null;
        }
        if ($next === 0) {
            $c = $readBits(8);
            if ($c === null) {
                return null;
            }
            $entry = $this->chrUtf($c);
        } elseif ($next === 1) {
            $c = $readBits(16);
            if ($c === null) {
                return null;
            }
            $entry = $this->chrUtf($c);
        } else {
            return '';
        }
        $dictionary[3] = $entry;
        $w = $entry;
        $result = [$entry];

        while (true) {
            if ($dataIndex > $length) {
                return null;
            }
            $cc = $readBits($numBits);
            if ($cc === null) {
                return null;
            }
            if ($cc === 0) {
                $bits = $readBits(8);
                if ($bits === null) {
                    return null;
                }
                $dictionary[$dictSize++] = $this->chrUtf($bits);
                $cc = $dictSize - 1;
                $enlargeIn--;
            } elseif ($cc === 1) {
                $bits = $readBits(16);
                if ($bits === null) {
                    return null;
                }
                $dictionary[$dictSize++] = $this->chrUtf($bits);
                $cc = $dictSize - 1;
                $enlargeIn--;
            } elseif ($cc === 2) {
                return implode('', $result);
            }

            if ($enlargeIn === 0) {
                $enlargeIn = 1 << $numBits;
                $numBits++;
            }

            if (array_key_exists($cc, $dictionary)) {
                $entry = (string)$dictionary[$cc];
            } elseif ($cc === $dictSize) {
                $entry = $w . mb_substr($w, 0, 1, 'UTF-8');
            } else {
                return null;
            }

            $result[] = $entry;
            $dictionary[$dictSize++] = $w . mb_substr($entry, 0, 1, 'UTF-8');
            $enlargeIn--;
            $w = $entry;

            if ($enlargeIn === 0) {
                $enlargeIn = 1 << $numBits;
                $numBits++;
            }
        }
    }

    private function chrUtf(int $code): string
    {
        if (function_exists('mb_chr')) {
            $c = mb_chr($code, 'UTF-8');
            if ($c !== false) {
                return $c;
            }
        }
        return html_entity_decode('&#' . $code . ';', ENT_QUOTES, 'UTF-8');
    }
}



