<?php

declare(strict_types=1);

namespace WebBaru\Services;

final class SatuSehatService
{
    private KhanzaJavaConfigService $configService;
    private ?array $config = null;
    private ?string $accessToken = null;

    public function __construct(?KhanzaJavaConfigService $configService = null)
    {
        $this->configService = $configService ?? new KhanzaJavaConfigService();
    }

    public function config(): array
    {
        if ($this->config === null) {
            $this->config = $this->configService->getSatuSehatConfig();
        }

        return $this->config;
    }

    public function isAvailable(): bool
    {
        return (bool)($this->config()['available'] ?? false);
    }

    public function organizationId(): string
    {
        return trim((string)($this->config()['organization_id'] ?? ''));
    }

    public function lookupPatientIdByNik(string $nik): string
    {
        return $this->lookupResourceIdByIdentifier('Patient', 'https://fhir.kemkes.go.id/id/nik', $nik);
    }

    public function lookupPractitionerIdByNik(string $nik): string
    {
        return $this->lookupResourceIdByIdentifier('Practitioner', 'https://fhir.kemkes.go.id/id/nik', $nik);
    }

    public function createEpisodeOfCare(array $payload): array
    {
        return $this->sendJson('POST', '/EpisodeOfCare', $payload);
    }

    public function createEncounter(array $payload): array
    {
        return $this->sendJson('POST', '/Encounter', $payload);
    }

    public function updateEncounter(string $encounterId, array $payload): array
    {
        return $this->sendJson('PUT', '/Encounter/' . rawurlencode($encounterId), $payload);
    }

    public function sendJson(string $method, string $path, array $payload): array
    {
        $body = $this->safeJsonEncode($payload);
        return $this->httpRequest($method, $path, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken(),
        ], $body);
    }

    public function fetchJson(string $path): array
    {
        return $this->httpRequest('GET', $path, [
            'Accept: application/json',
            'Authorization: Bearer ' . $this->accessToken(),
        ], null);
    }

    public function accessToken(): string
    {
        if ($this->accessToken !== null && $this->accessToken !== '') {
            return $this->accessToken;
        }

        $config = $this->config();
        $authUrl = rtrim((string)($config['auth_url'] ?? ''), '/');
        $clientId = trim((string)($config['client_id'] ?? ''));
        $secretKey = trim((string)($config['secret_key'] ?? ''));

        if ($authUrl === '' || $clientId === '' || $secretKey === '') {
            throw new \RuntimeException('Konfigurasi Satu Sehat belum lengkap.');
        }

        $tokenUrl = $authUrl . '/accesstoken?grant_type=client_credentials';
        $result = $this->httpRequestAbsolute('POST', $tokenUrl, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ], http_build_query([
            'client_id' => $clientId,
            'client_secret' => $secretKey,
        ]));

        if (!$result['ok']) {
            throw new \RuntimeException((string)($result['error'] ?? 'Gagal meminta token Satu Sehat.'));
        }

        $json = $result['json'];
        $token = trim((string)($json['access_token'] ?? ''));
        if ($token === '') {
            throw new \RuntimeException('Token Satu Sehat tidak ditemukan pada respons auth.');
        }

        $this->accessToken = $token;
        return $token;
    }

    private function lookupResourceIdByIdentifier(string $resourceType, string $system, string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $path = sprintf(
            '/%s?identifier=%s|%s',
            rawurlencode($resourceType),
            rawurlencode($system),
            rawurlencode($value)
        );
        $result = $this->fetchJson($path);
        if (!$result['ok']) {
            return '';
        }

        $entries = $result['json']['entry'] ?? null;
        if (!is_array($entries)) {
            return '';
        }

        foreach ($entries as $entry) {
            $id = trim((string)($entry['resource']['id'] ?? ''));
            if ($id !== '') {
                return $id;
            }
        }

        return '';
    }

    private function httpRequest(string $method, string $path, array $headers, ?string $body): array
    {
        $baseUrl = rtrim((string)($this->config()['fhir_url'] ?? ''), '/');
        if ($baseUrl === '') {
            return [
                'ok' => false,
                'http_code' => 0,
                'body' => null,
                'json' => null,
                'error' => 'URL FHIR Satu Sehat belum diatur.',
            ];
        }

        return $this->httpRequestAbsolute($method, $baseUrl . '/' . ltrim($path, '/'), $headers, $body);
    }

    private function httpRequestAbsolute(string $method, string $url, array $headers, ?string $body): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return $this->errorResult('Gagal inisialisasi koneksi HTTP.');
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $responseBody = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($responseBody === false) {
                return $this->errorResult($error !== '' ? $error : 'Request Satu Sehat gagal.', $httpCode);
            }

            return $this->normalizeHttpResult((string)$responseBody, $httpCode);
        }

        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($method),
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
                'timeout' => 30,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $responseBody = @file_get_contents($url, false, $context);
        if ($responseBody === false) {
            return $this->errorResult('Request Satu Sehat gagal.');
        }

        $httpCode = 0;
        foreach ((array)($http_response_header ?? []) as $headerLine) {
            if (preg_match('#HTTP/\S+\s+(\d{3})#', (string)$headerLine, $m)) {
                $httpCode = (int)$m[1];
                break;
            }
        }

        return $this->normalizeHttpResult((string)$responseBody, $httpCode);
    }

    private function normalizeHttpResult(string $responseBody, int $httpCode): array
    {
        $decoded = json_decode($responseBody, true);
        $json = is_array($decoded) ? $decoded : null;
        $isOk = $httpCode >= 200 && $httpCode < 300;
        $error = null;

        if (!$isOk) {
            $error = $this->extractOperationOutcomeMessage($json) ?: ('HTTP ' . ($httpCode > 0 ? $httpCode : 0));
        }

        return [
            'ok' => $isOk,
            'http_code' => $httpCode,
            'body' => $responseBody,
            'json' => $json,
            'error' => $error,
        ];
    }

    private function errorResult(string $message, int $httpCode = 0): array
    {
        return [
            'ok' => false,
            'http_code' => $httpCode,
            'body' => null,
            'json' => null,
            'error' => $message,
        ];
    }

    private function extractOperationOutcomeMessage(?array $json): string
    {
        if (!is_array($json)) {
            return '';
        }

        $issues = $json['issue'] ?? null;
        if (is_array($issues)) {
            $messages = [];
            foreach ($issues as $issue) {
                $details = trim((string)($issue['details']['text'] ?? ''));
                $diagnostics = trim((string)($issue['diagnostics'] ?? ''));
                $message = $details !== '' ? $details : $diagnostics;
                if ($message !== '') {
                    $messages[] = $message;
                }
            }
            if ($messages !== []) {
                return implode(' | ', array_unique($messages));
            }
        }

        return trim((string)($json['message'] ?? ''));
    }

    private function safeJsonEncode(array $payload): string
    {
        $json = json_encode($this->normalizeUtf8($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Gagal menyusun JSON Satu Sehat: ' . json_last_error_msg());
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
}