<?php

declare(strict_types=1);

namespace WebBaru\Services;

use WebBaru\Database;

final class SatuSehatEncounterAdminService
{
    public function filtersFromQuery(): array
    {
        $today = date('Y-m-d');
        $dateFrom = trim((string)($_GET['date_from'] ?? ''));
        $dateTo = trim((string)($_GET['date_to'] ?? ''));
        $query = trim((string)($_GET['q'] ?? ''));
        $statusSync = trim((string)($_GET['status_sync'] ?? 'all'));

        if (!$this->isValidDate($dateFrom)) {
            $dateFrom = date('Y-m-d', strtotime('-14 days'));
        }
        if (!$this->isValidDate($dateTo)) {
            $dateTo = $today;
        }
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        if (!in_array($statusSync, ['all', 'unsent', 'sent', 'ready', 'not-ready'], true)) {
            $statusSync = 'all';
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'q' => $query,
            'status_sync' => $statusSync,
        ];
    }

    public function loadQueue(array $filters): array
    {
        $sql = 'SELECT * FROM (' . $this->encounterUnionSql() . ') queue_rows
                WHERE tgl_registrasi BETWEEN :date_from AND :date_to';
        $params = [
            'date_from' => (string)$filters['date_from'],
            'date_to' => (string)$filters['date_to'],
        ];

        $query = trim((string)($filters['q'] ?? ''));
        if ($query !== '') {
            $sql .= ' AND (
                no_rawat LIKE :q
                OR no_rkm_medis LIKE :q
                OR nm_pasien LIKE :q
                OR no_ktp_pasien LIKE :q
                OR nm_dokter LIKE :q
                OR no_ktp_dokter LIKE :q
                OR unit_nama LIKE :q
                OR kd_dokter LIKE :q
                OR kd_poli LIKE :q
                OR status_lanjut LIKE :q
            )';
            $params['q'] = '%' . $query . '%';
        }

        $statusSync = (string)($filters['status_sync'] ?? 'all');
        if ($statusSync === 'unsent') {
            $sql .= " AND IFNULL(id_encounter, '') = ''";
        } elseif ($statusSync === 'sent') {
            $sql .= " AND IFNULL(id_encounter, '') <> ''";
        }

        $sql .= ' ORDER BY tgl_registrasi DESC, jam_reg DESC, no_rawat DESC LIMIT 100';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $decorated = array_map(fn(array $row): array => $this->decorateRow($row), $rows);
        if ($statusSync === 'ready') {
            $decorated = array_values(array_filter($decorated, static fn(array $row): bool => !empty($row['ready']) && trim((string)($row['id_encounter'] ?? '')) === ''));
        } elseif ($statusSync === 'not-ready') {
            $decorated = array_values(array_filter($decorated, static fn(array $row): bool => empty($row['ready'])));
        }

        return $decorated;
    }

    public function summarizeQueue(array $rows): array
    {
        $summary = [
            'total' => count($rows),
            'ready' => 0,
            'sent' => 0,
            'pending' => 0,
        ];

        foreach ($rows as $row) {
            if (trim((string)($row['id_encounter'] ?? '')) !== '') {
                $summary['sent']++;
            } elseif (!empty($row['ready'])) {
                $summary['ready']++;
            } else {
                $summary['pending']++;
            }
        }

        return $summary;
    }

    public function syncFilteredEncounters(array $filters): array
    {
        $rows = $this->loadQueue($filters);
        $targets = array_values(array_filter($rows, static fn(array $row): bool => trim((string)($row['id_encounter'] ?? '')) === ''));

        if ($targets === []) {
            return [
                'ok' => false,
                'message' => 'Tidak ada data belum terkirim pada hasil filter saat ini.',
                'detail' => '',
            ];
        }

        $success = [];
        $failed = [];
        foreach ($targets as $row) {
            $result = $this->syncEncounter((string)$row['no_rawat']);
            if (!empty($result['ok'])) {
                $success[] = (string)$row['no_rawat'];
            } else {
                $failed[] = [
                    'no_rawat' => (string)$row['no_rawat'],
                    'message' => (string)($result['message'] ?? 'Gagal'),
                    'detail' => trim((string)($result['detail'] ?? '')),
                ];
            }
        }

        $detailLines = [
            'Target filter: ' . count($targets),
            'Berhasil: ' . count($success),
            'Gagal: ' . count($failed),
        ];
        if ($success !== []) {
            $detailLines[] = '';
            $detailLines[] = 'No. Rawat berhasil:';
            foreach ($success as $noRawat) {
                $detailLines[] = '- ' . $noRawat;
            }
        }
        if ($failed !== []) {
            $detailLines[] = '';
            $detailLines[] = 'No. Rawat gagal:';
            foreach ($failed as $item) {
                $detailLines[] = '- ' . $item['no_rawat'] . ' | ' . $item['message'];
                if ($item['detail'] !== '') {
                    $detailLines[] = '  ' . preg_replace('/\r?\n/', ' | ', $item['detail']);
                }
            }
        }

        return [
            'ok' => $success !== [],
            'message' => count($success) . ' data berhasil dikirim dari hasil filter, ' . count($failed) . ' data gagal.',
            'detail' => implode("\n", $detailLines),
        ];
    }

    public function syncEncounter(string $noRawat): array
    {
        $visit = $this->findVisit($noRawat);
        if ($visit === null) {
            return [
                'ok' => false,
                'message' => 'Data kunjungan tidak ditemukan untuk No. Rawat ' . $noRawat . '.',
                'detail' => '',
            ];
        }

        $issues = $this->issues($visit);
        if ($issues !== []) {
            return [
                'ok' => false,
                'message' => 'Kunjungan belum siap dikirim ke Satu Sehat.',
                'detail' => implode("\n", $issues),
            ];
        }

        $service = new SatuSehatService();
        if (!$service->isAvailable()) {
            return [
                'ok' => false,
                'message' => 'Konfigurasi Satu Sehat belum lengkap.',
                'detail' => 'Periksa CLIENTIDSATUSEHAT, SECRETKEYSATUSEHAT, URLAUTHSATUSEHAT, URLFHIRSATUSEHAT, dan IDSATUSEHAT pada konfigurasi Khanza Java.',
            ];
        }

        try {
            $patientId = $service->lookupPatientIdByNik((string)$visit['no_ktp_pasien']);
            if ($patientId === '') {
                return [
                    'ok' => false,
                    'message' => 'Pasien belum ditemukan di server Satu Sehat.',
                    'detail' => 'NIK pasien: ' . (string)$visit['no_ktp_pasien'],
                ];
            }

            $practitionerId = $service->lookupPractitionerIdByNik((string)$visit['no_ktp_dokter']);
            if ($practitionerId === '') {
                return [
                    'ok' => false,
                    'message' => 'Practitioner dokter belum ditemukan di server Satu Sehat.',
                    'detail' => 'NIK dokter: ' . (string)$visit['no_ktp_dokter'],
                ];
            }

            $episodeId = '';
            if ($this->isAncVisit($visit)) {
                $episodeId = $this->ensureEpisodeOfCare($service, $visit, $patientId);
            }

            $payload = $this->buildEncounterPayload($service, $visit, $patientId, $practitionerId, $episodeId);
            $isUpdate = trim((string)($visit['id_encounter'] ?? '')) !== '';
            $result = $isUpdate
                ? $service->updateEncounter((string)$visit['id_encounter'], $payload)
                : $service->createEncounter($payload);

            if (!$result['ok']) {
                return [
                    'ok' => false,
                    'message' => ($isUpdate ? 'Update' : 'Kirim') . ' Encounter gagal untuk ' . $noRawat . '.',
                    'detail' => $this->buildTechnicalDetail($payload, $result),
                ];
            }

            $encounterId = trim((string)($result['json']['id'] ?? $visit['id_encounter'] ?? ''));
            if ($encounterId !== '') {
                $this->saveEncounterId($noRawat, $encounterId);
            }

            return [
                'ok' => true,
                'message' => ($isUpdate ? 'Update' : 'Kirim') . ' Encounter berhasil untuk ' . $noRawat . '.',
                'detail' => $this->buildTechnicalDetail($payload, $result),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Proses Encounter gagal untuk ' . $noRawat . '.',
                'detail' => $e->getMessage(),
            ];
        }
    }

    private function findVisit(string $noRawat): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM (' . $this->encounterUnionSql() . ') queue_rows WHERE no_rawat = :no_rawat LIMIT 1'
        );
        $stmt->execute(['no_rawat' => $noRawat]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }

        return $this->decorateRow($row);
    }

    private function ensureEpisodeOfCare(SatuSehatService $service, array $visit, string $patientId): string
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id_encounter FROM satu_sehat_episodeofcare WHERE no_rawat = :no_rawat LIMIT 1');
        $stmt->execute(['no_rawat' => (string)$visit['no_rawat']]);
        $existingId = trim((string)($stmt->fetchColumn() ?: ''));
        if ($existingId !== '') {
            return $existingId;
        }

        $organizationId = $service->organizationId();
        $payload = [
            'resourceType' => 'EpisodeOfCare',
            'identifier' => [[
                'system' => 'http://sys-ids.kemkes.go.id/episode-of-care/' . $organizationId,
                'value' => (string)$visit['no_rawat'],
            ]],
            'status' => 'active',
            'statusHistory' => [[
                'status' => 'active',
                'period' => [
                    'start' => (string)$visit['mulai'],
                ],
            ]],
            'type' => [[
                'coding' => [[
                    'system' => 'http://terminology.kemkes.go.id/CodeSystem/episodeofcare-type',
                    'code' => 'ANC',
                    'display' => 'Antenatal Care',
                ]],
            ]],
            'patient' => [
                'reference' => 'Patient/' . $patientId,
                'display' => (string)$visit['nm_pasien'],
            ],
            'period' => [
                'start' => (string)$visit['mulai'],
            ],
            'managingOrganization' => [
                'reference' => 'Organization/' . $organizationId,
            ],
        ];

        $result = $service->createEpisodeOfCare($payload);
        if (!$result['ok']) {
            throw new \RuntimeException('EpisodeOfCare ANC gagal: ' . $this->buildTechnicalDetail($payload, $result));
        }

        $episodeId = trim((string)($result['json']['id'] ?? ''));
        if ($episodeId === '') {
            throw new \RuntimeException('EpisodeOfCare ANC tidak mengembalikan id resource.');
        }

        $save = $pdo->prepare(
            'INSERT INTO satu_sehat_episodeofcare (no_rawat, id_encounter)
             VALUES (:no_rawat, :id_encounter)
             ON DUPLICATE KEY UPDATE id_encounter = VALUES(id_encounter)'
        );
        $save->execute([
            'no_rawat' => (string)$visit['no_rawat'],
            'id_encounter' => $episodeId,
        ]);

        return $episodeId;
    }

    private function buildEncounterPayload(
        SatuSehatService $service,
        array $visit,
        string $patientId,
        string $practitionerId,
        string $episodeId
    ): array {
        $organizationId = $service->organizationId();
        $isUpdate = trim((string)($visit['id_encounter'] ?? '')) !== '';
        $isAnc = $this->isAncVisit($visit);
        $payload = [
            'resourceType' => 'Encounter',
            'identifier' => [[
                'system' => 'http://sys-ids.kemkes.go.id/encounter/' . $organizationId,
                'value' => (string)$visit['no_rawat'],
            ]],
            'status' => 'arrived',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => (string)$visit['status_lanjut'] === 'Ranap' ? 'IMP' : 'AMB',
                'display' => (string)$visit['status_lanjut'] === 'Ranap' ? 'inpatient encounter' : 'ambulatory',
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientId,
                'display' => (string)$visit['nm_pasien'],
            ],
            'participant' => [[
                'type' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                        'code' => 'ATND',
                        'display' => 'attender',
                    ]],
                ]],
                'individual' => [
                    'reference' => 'Practitioner/' . $practitionerId,
                    'display' => (string)$visit['nm_dokter'],
                ],
            ]],
            'period' => [
                'start' => (string)$visit['mulai'],
            ],
            'location' => [[
                'location' => [
                    'reference' => 'Location/' . (string)$visit['id_lokasi_satusehat'],
                    'display' => (string)$visit['unit_nama'],
                ],
            ]],
            'statusHistory' => [[
                'status' => 'arrived',
                'period' => [
                    'start' => (string)$visit['mulai'],
                    'end' => (string)$visit['selesai'],
                ],
            ]],
            'serviceProvider' => [
                'reference' => 'Organization/' . $organizationId,
            ],
        ];

        if ($isUpdate) {
            $payload['id'] = (string)$visit['id_encounter'];
        }

        if ($isAnc) {
            $payload['identifier'][] = [
                'system' => 'http://terminology.kemkes.go.id/CodeSystem/episodeofcare/ANC',
                'value' => 'K1A',
            ];
        }

        if ($episodeId !== '') {
            $payload['episodeOfCare'] = [[
                'reference' => 'EpisodeOfCare/' . $episodeId,
            ]];
        }

        return $payload;
    }

    private function saveEncounterId(string $noRawat, string $encounterId): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO satu_sehat_encounter (no_rawat, id_encounter)
             VALUES (:no_rawat, :id_encounter)
             ON DUPLICATE KEY UPDATE id_encounter = VALUES(id_encounter)'
        );
        $stmt->execute([
            'no_rawat' => $noRawat,
            'id_encounter' => $encounterId,
        ]);
    }

    private function buildTechnicalDetail(array $payload, array $result): string
    {
        $blocks = [];
        $blocks[] = 'HTTP Code: ' . (string)($result['http_code'] ?? 0);
        if (!empty($result['error'])) {
            $blocks[] = 'Error: ' . (string)$result['error'];
        }
        $blocks[] = 'Payload:';
        $blocks[] = $this->prettyJson($payload);
        $blocks[] = 'Response:';
        $blocks[] = $this->prettyJson($result['json'] ?? ($result['body'] ?? ''));
        return implode("\n\n", $blocks);
    }

    private function prettyJson(mixed $value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                return $value;
            }
        }

        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $json === false ? (string)$value : $json;
    }

    private function encounterUnionSql(): string
    {
        return <<<'SQL'
SELECT
    reg_periksa.tgl_registrasi,
    reg_periksa.jam_reg,
    reg_periksa.no_rawat,
    reg_periksa.no_rkm_medis,
    pasien.nm_pasien,
    IFNULL(pasien.no_ktp, '') AS no_ktp_pasien,
    reg_periksa.kd_dokter,
    pegawai.nama AS nm_dokter,
    IFNULL(pegawai.no_ktp, '') AS no_ktp_dokter,
    reg_periksa.kd_poli,
    poliklinik.nm_poli AS unit_nama,
    IFNULL(map_ralan.id_lokasi_satusehat, '') AS id_lokasi_satusehat,
    reg_periksa.stts,
    reg_periksa.status_lanjut,
    CONCAT(reg_periksa.tgl_registrasi, 'T', reg_periksa.jam_reg, '+07:00') AS mulai,
    CONCAT(reg_periksa.tgl_registrasi, 'T', reg_periksa.jam_reg, '+07:00') AS selesai,
    IFNULL(satu_sehat_encounter.id_encounter, '') AS id_encounter,
    'Rawat Jalan' AS jenis_kunjungan
FROM reg_periksa
INNER JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
LEFT JOIN pegawai ON pegawai.nik = reg_periksa.kd_dokter OR pegawai.id = reg_periksa.kd_dokter
LEFT JOIN poliklinik ON poliklinik.kd_poli = reg_periksa.kd_poli
LEFT JOIN satu_sehat_mapping_lokasi_ralan AS map_ralan ON map_ralan.kd_poli = reg_periksa.kd_poli
LEFT JOIN satu_sehat_encounter ON satu_sehat_encounter.no_rawat = reg_periksa.no_rawat
WHERE reg_periksa.status_lanjut = 'Ralan'
UNION ALL
SELECT
    reg_periksa.tgl_registrasi,
    reg_periksa.jam_reg,
    reg_periksa.no_rawat,
    reg_periksa.no_rkm_medis,
    pasien.nm_pasien,
    IFNULL(pasien.no_ktp, '') AS no_ktp_pasien,
    reg_periksa.kd_dokter,
    pegawai.nama AS nm_dokter,
    IFNULL(pegawai.no_ktp, '') AS no_ktp_dokter,
    reg_periksa.kd_poli,
    TRIM(CONCAT(IFNULL(bangsal.nm_bangsal, 'Rawat Inap'), ' / ', IFNULL(kamar.kd_kamar, '-'))) AS unit_nama,
    IFNULL(map_ranap.id_lokasi_satusehat, '') AS id_lokasi_satusehat,
    reg_periksa.stts,
    reg_periksa.status_lanjut,
    CONCAT(
        IFNULL(NULLIF(kamar_inap_terakhir.tgl_masuk, '0000-00-00'), reg_periksa.tgl_registrasi),
        'T',
        IFNULL(NULLIF(kamar_inap_terakhir.jam_masuk, ''), reg_periksa.jam_reg),
        '+07:00'
    ) AS mulai,
    CONCAT(
        IFNULL(
            NULLIF(kamar_inap_terakhir.tgl_keluar, '0000-00-00'),
            IFNULL(NULLIF(kamar_inap_terakhir.tgl_masuk, '0000-00-00'), reg_periksa.tgl_registrasi)
        ),
        'T',
        IFNULL(
            NULLIF(kamar_inap_terakhir.jam_keluar, ''),
            IFNULL(NULLIF(kamar_inap_terakhir.jam_masuk, ''), reg_periksa.jam_reg)
        ),
        '+07:00'
    ) AS selesai,
    IFNULL(satu_sehat_encounter.id_encounter, '') AS id_encounter,
    'Rawat Inap' AS jenis_kunjungan
FROM reg_periksa
INNER JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
LEFT JOIN pegawai ON pegawai.nik = reg_periksa.kd_dokter OR pegawai.id = reg_periksa.kd_dokter
LEFT JOIN (
    SELECT ki1.no_rawat, ki1.kd_kamar, ki1.tgl_masuk, ki1.jam_masuk, ki1.tgl_keluar, ki1.jam_keluar
    FROM kamar_inap AS ki1
    INNER JOIN (
        SELECT no_rawat, MAX(CONCAT(tgl_masuk, ' ', jam_masuk)) AS waktu_masuk_terakhir
        FROM kamar_inap
        GROUP BY no_rawat
    ) AS last_ki
        ON last_ki.no_rawat = ki1.no_rawat
       AND CONCAT(ki1.tgl_masuk, ' ', ki1.jam_masuk) = last_ki.waktu_masuk_terakhir
) AS kamar_inap_terakhir ON kamar_inap_terakhir.no_rawat = reg_periksa.no_rawat
LEFT JOIN kamar ON kamar.kd_kamar = kamar_inap_terakhir.kd_kamar
LEFT JOIN bangsal ON bangsal.kd_bangsal = kamar.kd_bangsal
LEFT JOIN satu_sehat_mapping_lokasi_ranap AS map_ranap ON map_ranap.kd_kamar = kamar_inap_terakhir.kd_kamar
LEFT JOIN satu_sehat_encounter ON satu_sehat_encounter.no_rawat = reg_periksa.no_rawat
WHERE reg_periksa.status_lanjut = 'Ranap'
SQL;
    }

    private function decorateRow(array $row): array
    {
        $issues = $this->issues($row);
        $row['issues'] = $issues;
        $row['ready'] = $issues === [];
        $row['sync_state'] = trim((string)($row['id_encounter'] ?? '')) !== ''
            ? 'Terkirim'
            : ($row['ready'] ? 'Siap Dikirim' : 'Belum Siap');
        return $row;
    }

    private function issues(array $row): array
    {
        $issues = [];
        if (trim((string)($row['no_ktp_pasien'] ?? '')) === '') {
            $issues[] = 'NIK pasien belum terisi.';
        }
        if (trim((string)($row['no_ktp_dokter'] ?? '')) === '') {
            $issues[] = 'NIK dokter belum terisi.';
        }
        if (trim((string)($row['id_lokasi_satusehat'] ?? '')) === '') {
            $issues[] = 'Mapping lokasi Satu Sehat belum ada.';
        }
        return $issues;
    }

    private function isAncVisit(array $visit): bool
    {
        return stripos((string)($visit['unit_nama'] ?? ''), 'anc') !== false;
    }

    private function isValidDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $dt instanceof \DateTimeImmutable && $dt->format('Y-m-d') === $value;
    }
}