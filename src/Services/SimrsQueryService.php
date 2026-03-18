<?php

declare(strict_types=1);

namespace WebBaru\Services;

use PDOException;
use WebBaru\Database;

final class SimrsQueryService
{
    public function run(string $sql, array $params = []): array
    {
        try {
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            return ['ok' => true, 'data' => $stmt->fetchAll(), 'error' => null];
        } catch (PDOException $e) {
            return ['ok' => false, 'data' => [], 'error' => $e->getMessage()];
        }
    }

    public function value(string $sql, array $params = []): array
    {
        $result = $this->run($sql, $params);
        if (!$result['ok']) {
            return $result;
        }
        $val = 0;
        if (!empty($result['data'][0])) {
            $row = $result['data'][0];
            $val = (float)array_values($row)[0];
        }
        return ['ok' => true, 'data' => $val, 'error' => null];
    }
}

