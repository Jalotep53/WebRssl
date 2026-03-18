<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Services\BpjsVclaimService;
use WebBaru\Services\SimrsQueryService;
use function app_settings;

final class SepPrintController
{
    public function index(): void
    {
        $noSep = trim((string)($_GET['no_sep'] ?? ''));
        $noRawat = trim((string)($_GET['no_rawat'] ?? ''));
        $db = new SimrsQueryService();

        $row = null;
        $error = null;
        if ($noSep === '' && $noRawat !== '') {
            $sepLookup = $db->run(
                "SELECT bs.no_sep
                 FROM bridging_sep bs
                 WHERE bs.no_rawat = :no_rawat
                 ORDER BY bs.tglsep DESC, bs.no_sep DESC
                 LIMIT 1",
                ['no_rawat' => $noRawat]
            );
            if (!$sepLookup['ok']) {
                $error = (string)$sepLookup['error'];
            } elseif (!empty($sepLookup['data'][0]['no_sep'])) {
                $noSep = trim((string)$sepLookup['data'][0]['no_sep']);
            } else {
                $error = 'Data SEP BPJS untuk no_rawat ' . $noRawat . ' tidak ditemukan';
            }
        }

        if ($error === null) {
            if ($noSep === '') {
                $error = 'No SEP tidak boleh kosong';
            } else {
                $res = $db->run(
                    "SELECT bs.*,
                            rp.no_reg, rp.kd_pj, pj.png_jawab, rp.kd_poli, pl.nm_poli, rp.kd_dokter, d.nm_dokter,
                            bp.prb,
                            pg.nama AS nama_petugas
                     FROM bridging_sep bs
                     LEFT JOIN reg_periksa rp ON rp.no_rawat = bs.no_rawat
                     LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
                     LEFT JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
                     LEFT JOIN dokter d ON d.kd_dokter = rp.kd_dokter
                     LEFT JOIN bpjs_prb bp ON bp.no_sep = bs.no_sep
                     LEFT JOIN pegawai pg ON pg.nik = bs.user OR pg.id = bs.user
                     WHERE bs.no_sep = :no_sep
                     LIMIT 1",
                    ['no_sep' => $noSep]
                );
                if (!$res['ok']) {
                    $error = (string)$res['error'];
                } elseif (!empty($res['data'])) {
                    $row = $res['data'][0];
                } else {
                    $error = 'Data SEP tidak ditemukan';
                }
            }
        }

        $bpjsCheck = null;
        $requestPayload = null;
        if ($error === null && $row !== null) {
            $svc = new BpjsVclaimService();
            $bpjsCheck = $svc->checkSep((string)($row['no_sep'] ?? $noSep));
            $requestPayload = $svc->buildSepRequestPayload($this->mapRowToSepPayloadInput($row));
        }

        $setting = app_settings();
        view('sep_print', [
            'title' => 'Cetak SEP',
            'row' => $row,
            'error' => $error,
            'noSep' => $noSep,
            'noRawat' => $noRawat,
            'bpjsCheck' => $bpjsCheck,
            'requestPayload' => $requestPayload,
            'setting' => $setting,
        ]);
    }

    private function mapRowToSepPayloadInput(array $row): array
    {
        return [
            'noKartu' => (string)($row['no_kartu'] ?? ''),
            'tglSep' => $this->normalizeDate((string)($row['tglsep'] ?? '')),
            'ppkPelayanan' => (string)($row['kdppkpelayanan'] ?? ''),
            'jnsPelayanan' => (string)($row['jnspelayanan'] ?? ''),
            'klsRawatHak' => (string)($row['klsrawat'] ?? ''),
            'klsRawatNaik' => (string)($row['klsnaik'] ?? ''),
            'pembiayaan' => (string)($row['pembiayaan'] ?? ''),
            'penanggungJawab' => (string)($row['pjnaikkelas'] ?? ''),
            'noMR' => (string)($row['nomr'] ?? ''),
            'asalRujukan' => $this->extractCode((string)($row['asal_rujukan'] ?? ''), '1'),
            'tglRujukan' => $this->normalizeDate((string)($row['tglrujukan'] ?? '')),
            'noRujukan' => (string)($row['no_rujukan'] ?? ''),
            'ppkRujukan' => (string)($row['kdppkrujukan'] ?? ''),
            'catatan' => (string)($row['catatan'] ?? ''),
            'diagAwal' => (string)($row['diagawal'] ?? ''),
            'poliTujuan' => (string)($row['kdpolitujuan'] ?? ''),
            'eksekutif' => $this->extractCode((string)($row['eksekutif'] ?? ''), '0'),
            'cob' => $this->extractCode((string)($row['cob'] ?? ''), '0'),
            'katarak' => $this->extractCode((string)($row['katarak'] ?? ''), '0'),
            'lakaLantas' => (string)($row['lakalantas'] ?? '0'),
            'noLP' => '',
            'penjamin' => '',
            'tglKejadian' => $this->normalizeDate((string)($row['tglkkl'] ?? '')),
            'keteranganKkl' => (string)($row['keterangankkl'] ?? ''),
            'suplesi' => $this->extractCode((string)($row['suplesi'] ?? ''), '0'),
            'noSepSuplesi' => (string)($row['no_sep_suplesi'] ?? ''),
            'kdPropinsi' => (string)($row['kdprop'] ?? ''),
            'kdKabupaten' => (string)($row['kdkab'] ?? ''),
            'kdKecamatan' => (string)($row['kdkec'] ?? ''),
            'tujuanKunj' => (string)($row['tujuankunjungan'] ?? '0'),
            'flagProcedure' => (string)($row['flagprosedur'] ?? ''),
            'kdPenunjang' => (string)($row['penunjang'] ?? ''),
            'assesmentPel' => (string)($row['asesmenpelayanan'] ?? ''),
            'noSurat' => (string)($row['noskdp'] ?? ''),
            'kodeDPJP' => (string)($row['kddpjp'] ?? ''),
            'dpjpLayan' => (string)($row['kddpjplayanan'] ?? ''),
            'noTelp' => (string)($row['notelep'] ?? ''),
            'user' => (string)($row['user'] ?? 'web'),
        ];
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '0000-00-00' || str_starts_with($value, '0000-00-00')) {
            return '';
        }
        return substr($value, 0, 10);
    }

    private function extractCode(string $value, string $fallback = ''): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }
        if (preg_match('/^(\d+)/', $value, $m)) {
            return (string)$m[1];
        }
        return $value;
    }
}
