<?php

declare(strict_types=1);

namespace WebBaru;

use PDOStatement;
use Throwable;

final class TrackedPDOStatement extends PDOStatement
{
    protected function __construct()
    {
    }

    public function execute(?array $params = null): bool
    {
        $ok = parent::execute($params ?? []);
        try {
            SqlTracker::log($this->queryString, $params);
        } catch (Throwable $e) {
            // Tracker tidak boleh menghentikan flow utama.
        }
        return $ok;
    }
}

