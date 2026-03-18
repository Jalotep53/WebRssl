<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Services\BpjsVclaimService;
use WebBaru\Services\SimrsQueryService;

final class BridgingBpjsController
{
    public function index(): void
    {
        $db = new SimrsQueryService();

        $from = trim((string)($_GET['from'] ?? date('Y-m-d')));
        $to = trim((string)($_GET['to'] ?? date('Y-m-d')));
        $noRawat = trim((string)($_GET['no_rawat'] ?? ''));
        $noSep = trim((string)($_GET['no_sep'] ?? ''));
        $jenisRawat = trim((string)($_GET['jenis_rawat'] ?? ''));
        $autoFromAction = trim((string)($_GET['auto'] ?? '')) === '1';
        $action = trim((string)($_GET['action'] ?? ''));
        $targetSep = trim((string)($_GET['target_sep'] ?? ''));

        $where = [];
        $params = [];
        if (!($autoFromAction && $noRawat !== '')) {
            $where[] = 'bs.tglsep BETWEEN :from AND :to';
            $params['from'] = $from;
            $params['to'] = $to;
        }
        if ($noRawat !== '') {
            $where[] = 'bs.no_rawat = :no_rawat';
            $params['no_rawat'] = $noRawat;
        }
        if ($noSep !== '') {
            $where[] = 'bs.no_sep LIKE :no_sep';
            $params['no_sep'] = '%' . $noSep . '%';
        }
        if (in_array($jenisRawat, ['1', '2'], true)) {
            $where[] = 'bs.jnspelayanan = :jenis_rawat';
            $params['jenis_rawat'] = $jenisRawat;
        }
        if (empty($where)) {
            $where[] = '1=1';
        }

        $rows = $db->run(
            "SELECT bs.no_sep, bs.no_rawat, bs.tglsep, bs.jnspelayanan, bs.no_kartu, bs.nomr, bs.nama_pasien,
                    bs.nmdiagnosaawal, bs.nmpolitujuan, bs.klsrawat, bs.no_rujukan,
                    rp.status_lanjut, rp.status_bayar, rp.kd_pj, pj.png_jawab
             FROM bridging_sep bs
             LEFT JOIN reg_periksa rp ON rp.no_rawat = bs.no_rawat
             LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
             WHERE " . implode(' AND ', $where) . "
             ORDER BY bs.tglsep DESC, bs.no_sep DESC
             LIMIT 1000",
            $params
        );

        $bpjsService = new BpjsVclaimService();
        $bpjsConfig = $bpjsService->config();
        $bpjsCheck = null;
        if ($action === 'cek-sep' && $targetSep !== '') {
            $bpjsCheck = $bpjsService->checkSep($targetSep);
            $bpjsCheck['target_sep'] = $targetSep;
        }

        view('bridging_bpjs', [
            'title' => 'Bridging BPJS',
            'from' => $from,
            'to' => $to,
            'noRawat' => $noRawat,
            'noSep' => $noSep,
            'jenisRawat' => $jenisRawat,
            'autoFromAction' => $autoFromAction,
            'rows' => $rows['data'],
            'error' => $rows['ok'] ? null : $rows['error'],
            'bpjsConfig' => $this->maskBpjsConfig($bpjsConfig),
            'bpjsCheck' => $bpjsCheck,
        ]);
    }

    private function maskBpjsConfig(array $cfg): array
    {
        return [
            'available' => (bool)($cfg['available'] ?? false),
            'url' => (string)($cfg['url'] ?? ''),
            'consid' => $this->mask((string)($cfg['consid'] ?? '')),
            'secret' => $this->mask((string)($cfg['secret'] ?? '')),
            'userkey' => $this->mask((string)($cfg['userkey'] ?? '')),
            'source' => (string)($cfg['source'] ?? ''),
        ];
    }

    private function mask(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '-';
        }

        if (strlen($value) <= 6) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 3) . str_repeat('*', max(0, strlen($value) - 6)) . substr($value, -3);
    }
}
