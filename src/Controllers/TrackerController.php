<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Services\SimrsQueryService;

final class TrackerController
{
    public function index(): void
    {
        $db = new SimrsQueryService();

        $from = trim((string)($_GET['from'] ?? date('Y-m-d')));
        $to = trim((string)($_GET['to'] ?? date('Y-m-d')));
        $user = trim((string)($_GET['user'] ?? ''));
        $q = trim((string)($_GET['q'] ?? ''));
        $limit = (int)($_GET['limit'] ?? 200);
        if ($limit < 20) {
            $limit = 20;
        }
        if ($limit > 1000) {
            $limit = 1000;
        }

        $whereSql = ["DATE(ts.tanggal) BETWEEN :from AND :to"];
        $params = ['from' => $from, 'to' => $to];
        if ($user !== '') {
            $whereSql[] = "ts.usere LIKE :user";
            $params['user'] = '%' . $user . '%';
        }
        if ($q !== '') {
            $whereSql[] = "ts.sqle LIKE :q";
            $params['q'] = '%' . $q . '%';
        }

        $rowsSql = $db->run(
            "SELECT ts.tanggal, ts.usere, ts.sqle
             FROM trackersql ts
             WHERE " . implode(' AND ', $whereSql) . "
             ORDER BY ts.tanggal DESC
             LIMIT {$limit}",
            $params
        );

        $whereLogin = ["t.tgl_login BETWEEN :from AND :to"];
        $paramsLogin = ['from' => $from, 'to' => $to];
        if ($user !== '') {
            $whereLogin[] = "t.nip LIKE :user";
            $paramsLogin['user'] = '%' . $user . '%';
        }
        $rowsLogin = $db->run(
            "SELECT t.nip, t.tgl_login, t.jam_login
             FROM tracker t
             WHERE " . implode(' AND ', $whereLogin) . "
             ORDER BY t.tgl_login DESC, t.jam_login DESC
             LIMIT 500",
            $paramsLogin
        );

        $countSql = $db->value(
            "SELECT COUNT(*) FROM trackersql ts WHERE " . implode(' AND ', $whereSql),
            $params
        );

        view('tracker', [
            'title' => 'Tracker SQL',
            'from' => $from,
            'to' => $to,
            'user' => $user,
            'q' => $q,
            'limit' => $limit,
            'rowsSql' => $rowsSql['data'],
            'rowsLogin' => $rowsLogin['data'],
            'errorSql' => $rowsSql['ok'] ? null : $rowsSql['error'],
            'errorLogin' => $rowsLogin['ok'] ? null : $rowsLogin['error'],
            'totalSql' => (int)($countSql['data'] ?? 0),
        ]);
    }
}

