<?php

declare(strict_types=1);

namespace WebBaru\Services;

final class KhanzaJavaConfigService
{
    private const AES_KEY = 'Bar12345Bar12345';
    private const AES_IV = 'sayangsamakhanza';

    public function getBpjsVclaimConfig(): array
    {
        $file = $this->resolveDatabaseXmlPath();
        $props = $this->loadJavaPropertiesXml($file);

        $url = trim((string)($props['URLAPIBPJS'] ?? ''));
        $consid = $this->decryptMaybe((string)($props['CONSIDAPIBPJS'] ?? ''));
        $secret = $this->decryptMaybe((string)($props['SECRETKEYAPIBPJS'] ?? ''));
        $userkey = $this->decryptMaybe((string)($props['USERKEYAPIBPJS'] ?? ''));

        return [
            'url' => rtrim($url, '/'),
            'consid' => $consid,
            'secret' => $secret,
            'userkey' => $userkey,
            'available' => ($url !== '' && $consid !== '' && $secret !== '' && $userkey !== ''),
            'source' => $file,
        ];
    }

    public function getSatuSehatConfig(): array
    {
        $file = $this->resolveDatabaseXmlPath();
        $props = $this->loadJavaPropertiesXml($file);

        $clientId = $this->decryptMaybe((string)($props['CLIENTIDSATUSEHAT'] ?? ''));
        $secretKey = $this->decryptMaybe((string)($props['SECRETKEYSATUSEHAT'] ?? ''));
        $organizationId = $this->decryptMaybe((string)($props['IDSATUSEHAT'] ?? ''));
        $authUrl = trim((string)($props['URLAUTHSATUSEHAT'] ?? ''));
        $fhirUrl = trim((string)($props['URLFHIRSATUSEHAT'] ?? ''));

        return [
            'client_id' => $clientId,
            'secret_key' => $secretKey,
            'organization_id' => $organizationId,
            'auth_url' => rtrim($authUrl, '/'),
            'fhir_url' => rtrim($fhirUrl, '/'),
            'kelurahan' => trim((string)($props['KELURAHANSATUSEHAT'] ?? '')),
            'kecamatan' => trim((string)($props['KECAMATANSATUSEHAT'] ?? '')),
            'kabupaten' => trim((string)($props['KABUPATENSATUSEHAT'] ?? '')),
            'propinsi' => trim((string)($props['PROPINSISATUSEHAT'] ?? '')),
            'kode_pos' => trim((string)($props['KODEPOSSATUSEHAT'] ?? '')),
            'available' => ($clientId !== '' && $secretKey !== '' && $organizationId !== '' && $authUrl !== '' && $fhirUrl !== ''),
            'source' => $file,
        ];
    }

    public function getConfigSubset(array $keys, bool $decrypt = true): array
    {
        $file = $this->resolveDatabaseXmlPath();
        $props = $this->loadJavaPropertiesXml($file);
        $result = [];
        foreach ($keys as $key) {
            $name = trim((string)$key);
            if ($name === '') {
                continue;
            }
            $value = (string)($props[$name] ?? '');
            if ($decrypt) {
                $value = $this->decryptMaybe($value);
            }
            $result[$name] = $value;
        }
        return [
            'source' => $file,
            'entries' => $result,
        ];
    }

    private function loadJavaPropertiesXml(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $xml = @simplexml_load_file($file);
        if ($xml === false) {
            return [];
        }

        $data = [];
        foreach ($xml->entry as $entry) {
            $key = (string)($entry['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $data[$key] = (string)$entry;
        }

        return $data;
    }

    private function resolveDatabaseXmlPath(): string
    {
        $candidates = [];

        $envRoot = trim((string)getenv('KHANZA_JAVA_ROOT'));
        if ($envRoot !== '') {
            $candidates[] = rtrim($envRoot, '\\/') . DIRECTORY_SEPARATOR . 'setting' . DIRECTORY_SEPARATOR . 'database.xml';
        }

        $candidates[] = LEGACY_ROOT . DIRECTORY_SEPARATOR . 'setting' . DIRECTORY_SEPARATOR . 'database.xml';
        $candidates[] = 'D:\\WORK\\Khanza\\Khanza_rssl\\setting\\database.xml';
        $candidates[] = 'D:\\WORK\\khanza\\Khanza_rssl\\setting\\database.xml';

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return (string)$candidates[0];
    }

    private function decryptMaybe(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return $value;
        }

        $plain = openssl_decrypt(
            $decoded,
            'AES-128-CBC',
            self::AES_KEY,
            OPENSSL_RAW_DATA,
            self::AES_IV
        );

        if ($plain === false) {
            return $value;
        }

        $plain = trim($plain);
        return $plain === '' ? $value : $plain;
    }
}
