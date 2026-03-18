<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

use WebBaru\Database;

final class AppAssetController
{
    public function logo(): void
    {
        $this->outputSettingImage('logo');
    }

    public function icon(): void
    {
        $this->outputSettingImage('logo');
    }

    public function bpjsLogo(): void
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->query("SELECT bpjs FROM gambar LIMIT 1");
            $blob = $stmt ? $stmt->fetchColumn() : null;
            if (!is_string($blob) || $blob === '') {
                $this->outputFallbackPng();
                return;
            }

            [$blob, $mime] = $this->prepareImageBlob($blob);
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=300');
            echo $blob;
            return;
        } catch (\Throwable $e) {
            $this->outputFallbackPng();
        }
    }

    private function outputSettingImage(string $column): void
    {
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->query("SELECT {$column} FROM setting LIMIT 1");
            $blob = $stmt ? $stmt->fetchColumn() : null;
            if (!is_string($blob) || $blob === '') {
                $this->outputFallbackPng();
                return;
            }

            [$blob, $mime] = $this->prepareImageBlob($blob);
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=300');
            echo $blob;
            return;
        } catch (\Throwable $e) {
            $this->outputFallbackPng();
        }
    }

    /**
     * Some legacy BLOBs are saved through UTF-8 text paths (seen in gambar.bpjs),
     * so bytes shift and MIME detection fails. Try raw, then iconv recovery.
     *
     * @return array{0:string,1:string}
     */
    private function prepareImageBlob(string $blob): array
    {
        $raw = $this->repairPngSignature($this->normalizeImageBinary($blob));
        $rawMime = $this->detectMime($raw);
        if ($rawMime !== 'application/octet-stream') {
            return [$raw, $rawMime];
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $blob);
            if (is_string($converted) && $converted !== '') {
                $fixed = $this->repairPngSignature($this->normalizeImageBinary($converted));
                $fixedMime = $this->detectMime($fixed);
                if ($fixedMime !== 'application/octet-stream') {
                    return [$fixed, $fixedMime];
                }
            }
        }

        return [$raw, $rawMime];
    }

    private function detectMime(string $blob): string
    {
        if (strncmp($blob, "\x89PNG", 4) === 0) {
            return 'image/png';
        }
        if (strncmp($blob, "PNG\r\n\x1A\n", 7) === 0) {
            return 'image/png';
        }
        if (strncmp($blob, "\xFF\xD8\xFF", 3) === 0) {
            return 'image/jpeg';
        }
        if (strncmp($blob, "GIF8", 4) === 0) {
            return 'image/gif';
        }
        if (strncmp($blob, "RIFF", 4) === 0 && strpos($blob, "WEBP") !== false) {
            return 'image/webp';
        }
        return 'application/octet-stream';
    }

    private function normalizeImageBinary(string $blob): string
    {
        $headers = ["\x89PNG", "PNG\r\n\x1A\n", "\xFF\xD8\xFF", "GIF8", "RIFF"];
        foreach ($headers as $header) {
            $pos = strpos($blob, $header);
            if ($pos === 0) {
                return $blob;
            }
            if ($pos !== false && $pos > 0 && $pos <= 32) {
                return (string)substr($blob, $pos);
            }
        }
        return $blob;
    }

    private function repairPngSignature(string $blob): string
    {
        if (strncmp($blob, "\x89PNG", 4) === 0) {
            return $blob;
        }
        if (strncmp($blob, "PNG\r\n\x1A\n", 7) === 0) {
            return "\x89" . $blob;
        }
        return $blob;
    }

    private function outputFallbackPng(): void
    {
        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/w8AAgMBgM2w7NwAAAAASUVORK5CYII=');
    }
}
