<?php

declare(strict_types=1);

namespace WebBaru;

use PDO;
use Throwable;

final class SqlTracker
{
    private static bool $isLogging = false;

    public static function log(string $sql, ?array $params = null): void
    {
        if (self::$isLogging) {
            return;
        }

        $trimmed = ltrim($sql);
        if ($trimmed === '') {
            return;
        }
        if (stripos($trimmed, 'insert into trackersql') === 0) {
            return;
        }
        if (stripos($trimmed, 'select') === 0) {
            return;
        }

        $finalSql = self::buildSql($sql, $params ?? []);
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $usere = self::currentUser();
        $payload = $ip . ' ' . $finalSql;

        self::$isLogging = true;
        try {
            $pdo = Database::trackerPdo();
            $stmt = $pdo->prepare(
                "INSERT INTO trackersql(tanggal,sqle,usere)\n                 VALUES(:tanggal,:sqle,:usere)"
            );
            $stmt->execute([
                'tanggal' => date('Y-m-d H:i:s'),
                'sqle' => $payload,
                'usere' => $usere,
            ]);
        } catch (Throwable $e) {
            // Silent fail: tracker tidak boleh mengganggu transaksi utama.
        } finally {
            self::$isLogging = false;
        }
    }

    public static function logLogin(string $kodeUser): void
    {
        $kodeUser = trim($kodeUser);
        if ($kodeUser === '') {
            return;
        }

        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare(
                "INSERT INTO tracker(nip,tgl_login,jam_login)\n                 VALUES(:nip,CURRENT_DATE(),CURRENT_TIME())"
            );
            $stmt->execute(['nip' => $kodeUser]);
        } catch (Throwable $e) {
            // Silent fail: login tetap berjalan walau tracker gagal.
        }
    }

    private static function currentUser(): string
    {
        $auth = $_SESSION['auth'] ?? null;
        if (is_array($auth) && !empty($auth['kode'])) {
            return (string)$auth['kode'];
        }
        return 'SYSTEM';
    }

    private static function buildSql(string $sql, array $params): string
    {
        if (empty($params)) {
            return trim($sql);
        }

        $out = $sql;
        $isAssoc = array_keys($params) !== range(0, count($params) - 1);
        if ($isAssoc) {
            foreach ($params as $k => $v) {
                $key = (string)$k;
                if ($key === '') {
                    continue;
                }
                if ($key[0] !== ':') {
                    $key = ':' . $key;
                }
                $out = str_replace($key, self::quote($v), $out);
            }
            return trim($out);
        }

        foreach ($params as $v) {
            $pos = strpos($out, '?');
            if ($pos === false) {
                break;
            }
            $out = substr_replace($out, self::quote($v), $pos, 1);
        }
        return trim($out);
    }

    private static function quote(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }
        if (is_scalar($value)) {
            $v = (string)$value;
            return "'" . str_replace("'", "\\'", $v) . "'";
        }
        return "'[non-scalar]'";
    }
}
