<?php

declare(strict_types=1);

namespace WebBaru\Controllers;

final class LaporanController
{
    public function index(): void
    {
        $reportDir = LEGACY_ROOT . DIRECTORY_SEPARATOR . 'report';
        $rows = [];
        if (is_dir($reportDir)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($reportDir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $f) {
                if (!$f->isFile()) {
                    continue;
                }
                $ext = strtolower($f->getExtension());
                if (!in_array($ext, ['jrxml', 'jasper'], true)) {
                    continue;
                }
                $rows[] = [
                    'nama' => $f->getFilename(),
                    'tipe' => strtoupper($ext),
                    'ukuran' => $f->getSize(),
                    'path' => str_replace(LEGACY_ROOT . DIRECTORY_SEPARATOR, '', $f->getPathname()),
                ];
                if (count($rows) >= 200) {
                    break;
                }
            }
        }

        view('laporan', [
            'title' => 'Modul Laporan',
            'rows' => $rows,
            'reportDir' => $reportDir,
        ]);
    }
}

