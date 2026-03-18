<?php

declare(strict_types=1);

namespace WebBaru;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

final class TrackedPDO extends PDO
{
    public function query(string $query, ?int $fetchMode = null, ...$fetchModeArgs): PDOStatement|false
    {
        if ($fetchMode === null) {
            $stmt = parent::query($query);
        } else {
            $stmt = parent::query($query, $fetchMode, ...$fetchModeArgs);
        }
        if ($stmt !== false) {
            try {
                SqlTracker::log($query);
            } catch (Throwable $e) {
                // noop
            }
        }
        return $stmt;
    }

    public function exec(string $statement): int|false
    {
        $result = parent::exec($statement);
        if ($result !== false) {
            try {
                SqlTracker::log($statement);
            } catch (Throwable $e) {
                // noop
            }
        }
        return $result;
    }
}

