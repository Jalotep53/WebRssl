<?php
$flash = is_array($satusehatFlash ?? null) ? $satusehatFlash : [];
$filters = is_array($encounterFilters ?? null) ? $encounterFilters : ['date_from' => '', 'date_to' => '', 'q' => '', 'status_sync' => 'all'];
$queueSummary = is_array($encounterQueueSummary ?? null) ? $encounterQueueSummary : ['total' => 0, 'ready' => 0, 'sent' => 0, 'pending' => 0];
$encounterRows = is_array($encounterRows ?? null) ? $encounterRows : [];
$satuSehatAvailable = (bool)($satuSehatAvailable ?? false);
$flatActions = [];
foreach ((array)($satuSehatActionGroups ?? []) as $groupName => $items) {
    foreach ((array)$items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $item['group_name'] = (string)$groupName;
        $flatActions[] = $item;
    }
}
$tabDefs = [
    'encounter' => 'Encounter',
    'condition' => 'Condition',
    'clinical-impression' => 'ClinicalImpression',
    'procedure' => 'Procedure',
    'careplan' => 'CarePlan',
    'diet' => 'Diet',
    'medication' => 'Medication',
    'medication-request' => 'MedicationRequest',
    'medication-dispense' => 'MedicationDispense',
    'medication-statement' => 'MedicationStatement',
    'service-request' => 'ServiceRequest',
    'specimen' => 'Specimen',
    'observation' => 'Observation',
    'diagnostic-report' => 'DiagnosticReport',
    'referensi-mapping' => 'Referensi & Mapping',
    'lainnya' => 'Lainnya',
];
$classifyTab = static function (array $item): string {
    $label = strtolower(trim((string)($item['label'] ?? '')));
    $perm = strtolower(trim((string)($item['permission'] ?? '')));
    $btn = strtolower(trim((string)($item['button'] ?? '')));
    $text = $label . ' ' . $perm . ' ' . $btn;

    if (preg_match('/referensi|mapping/', $text)) {
        return 'referensi-mapping';
    }
    if (preg_match('/medication request/', $text)) {
        return 'medication-request';
    }
    if (preg_match('/medication dispense/', $text)) {
        return 'medication-dispense';
    }
    if (preg_match('/medication statement/', $text)) {
        return 'medication-statement';
    }
    if (preg_match('/diagnostic report/', $text)) {
        return 'diagnostic-report';
    }
    if (preg_match('/service request/', $text)) {
        return 'service-request';
    }
    if (preg_match('/clinical impression/', $text)) {
        return 'clinical-impression';
    }
    if (preg_match('/care plan|careplan/', $text)) {
        return 'careplan';
    }
    if (preg_match('/encounter/', $text)) {
        return 'encounter';
    }
    if (preg_match('/condition/', $text)) {
        return 'condition';
    }
    if (preg_match('/procedure/', $text)) {
        return 'procedure';
    }
    if (preg_match('/\bdiet\b/', $text)) {
        return 'diet';
    }
    if (preg_match('/\bspecimen\b/', $text)) {
        return 'specimen';
    }
    if (preg_match('/observation/', $text)) {
        return 'observation';
    }
    if (preg_match('/\bmedication\b|\bobat\b|\balkes\b|\bbhp\b/', $text)) {
        return 'medication';
    }
    return 'lainnya';
};
$resourceTabs = [];
foreach ($tabDefs as $key => $label) {
    $resourceTabs[$key] = ['label' => $label, 'items' => []];
}
foreach ($flatActions as $item) {
    $resourceTabs[$classifyTab($item)]['items'][] = $item;
}
$activeTab = trim((string)($_GET['tab'] ?? 'encounter'));
if (!array_key_exists($activeTab, $resourceTabs)) {
    $activeTab = 'encounter';
}
$buildQuery = static function (array $params): string {
    $query = http_build_query(array_filter($params, static fn($value): bool => trim((string)$value) !== ''));
    return $query === '' ? '?module=bridging-satusehat' : ('?module=bridging-satusehat&' . $query);
};
$currentQuery = [
    'tab' => $activeTab,
    'date_from' => (string)($filters['date_from'] ?? ''),
    'date_to' => (string)($filters['date_to'] ?? ''),
    'q' => (string)($filters['q'] ?? ''),
    'status_sync' => (string)($filters['status_sync'] ?? 'all'),
];
$selectedTabItems = $resourceTabs[$activeTab]['items'] ?? [];
$unsentFilteredCount = count(array_filter($encounterRows, static fn(array $row): bool => trim((string)($row['id_encounter'] ?? '')) === ''));
?>
<style>
    .satusehat-tools {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    }
    .satusehat-form {
        display: grid;
        gap: 10px;
    }
    .satusehat-form .row {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }
    .satusehat-form input,
    .satusehat-form select {
        width: 100%;
        border: 1px solid #cfdbe4;
        border-radius: 10px;
        padding: 10px 12px;
        font: inherit;
        background: #fff;
        color: inherit;
    }
    .satusehat-actions,
    .satusehat-inline-actions,
    .satusehat-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .satusehat-btn,
    .satusehat-inline-actions button,
    .satusehat-inline-actions a,
    .satusehat-tabs a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        border: 0;
        border-radius: 10px;
        padding: 9px 12px;
        font: inherit;
        font-size: 13px;
        cursor: pointer;
        color: #fff;
        background: #124f6b;
    }
    .satusehat-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 4px;
    }
    .satusehat-tabs a {
        white-space: nowrap;
        background: #dce8ef;
        color: #23495f;
        border: 1px solid #c6d6e1;
    }
    .satusehat-tabs a.active {
        background: #124f6b;
        border-color: #124f6b;
        color: #fff;
    }
    .satusehat-btn.secondary,
    .satusehat-inline-actions a.secondary,
    .satusehat-inline-actions button.secondary {
        background: #64748b;
    }
    .satusehat-btn.success,
    .satusehat-inline-actions button.success,
    .satusehat-inline-actions a.success {
        background: #0f766e;
    }
    .satusehat-btn.warn,
    .satusehat-inline-actions button.warn {
        background: #b45309;
    }
    .satusehat-btn:disabled,
    .satusehat-inline-actions button:disabled {
        cursor: not-allowed;
        opacity: .55;
    }
    .satusehat-alert {
        border-radius: 14px;
        padding: 14px 16px;
        margin-top: 14px;
        border: 1px solid #d7e7ef;
        background: #f8fcfe;
    }
    .satusehat-alert.success {
        background: #edfdf8;
        border-color: #bde7d7;
    }
    .satusehat-alert.error {
        background: #fff5f5;
        border-color: #f0c7c7;
    }
    .satusehat-alert pre {
        margin: 10px 0 0;
        padding: 12px;
        border-radius: 10px;
        background: rgba(15, 23, 42, .06);
        overflow: auto;
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 12px;
    }
    .satusehat-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #d4dde5;
        background: #f8fbfd;
        color: #27485b;
    }
    .satusehat-badge.ok {
        border-color: #9ad3b1;
        background: #ecfdf3;
        color: #116149;
    }
    .satusehat-badge.warn {
        border-color: #e4c893;
        background: #fff8eb;
        color: #9a5b00;
    }
    .satusehat-badge.err {
        border-color: #efc0c0;
        background: #fff1f1;
        color: #9f1d1d;
    }
    .satusehat-list {
        margin: 8px 0 0;
        padding-left: 18px;
    }
    .satusehat-list li + li {
        margin-top: 4px;
    }
    .satusehat-table-note {
        color: #607381;
        font-size: 12px;
        margin-top: 4px;
    }
</style>

<div class="grid cols-3">
    <div class="card"><div class="muted">Total Aksi Satu Sehat</div><div class="stat"><?= (int)($satuSehatSummary['total'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Encounter di Hasil Filter</div><div class="stat"><?= (int)($queueSummary['total'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Siap Dikirim</div><div class="stat"><?= (int)($queueSummary['ready'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Sudah Terkirim</div><div class="stat"><?= (int)($queueSummary['sent'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Belum Siap</div><div class="stat"><?= (int)($queueSummary['pending'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Belum Terkirim Terfilter</div><div class="stat"><?= (int)$unsentFilteredCount ?></div></div>
</div>

<?php if ($flash !== []): ?>
    <div class="satusehat-alert <?= htmlspecialchars((string)($flash['type'] ?? 'success'), ENT_QUOTES, 'UTF-8') ?>">
        <strong><?= htmlspecialchars((string)($flash['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
        <?php if (trim((string)($flash['detail'] ?? '')) !== ''): ?>
            <details style="margin-top:10px;">
                <summary>Detail teknis</summary>
                <pre><?= htmlspecialchars((string)$flash['detail'], ENT_QUOTES, 'UTF-8') ?></pre>
            </details>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card" id="satusehat-tab-menu" style="margin-top:14px;">
    <h3 style="margin-top:0;">Resource Satu Sehat</h3>
    <p class="muted">Setiap resource yang diminta Satu Sehat dipisah ke tab sendiri. Untuk sekarang yang sudah native web adalah <code>Encounter</code>; resource lain masih diarahkan ke modul legacy atau katalog.</p>
    <div class="satusehat-tabs" style="margin-top:12px;">
        <?php foreach ($resourceTabs as $tabKey => $tab): ?>
            <?php $tabUrl = $buildQuery(['tab' => $tabKey, 'date_from' => (string)($filters['date_from'] ?? ''), 'date_to' => (string)($filters['date_to'] ?? ''), 'q' => (string)($filters['q'] ?? ''), 'status_sync' => (string)($filters['status_sync'] ?? 'all')]) . '#satusehat-tab-menu'; ?>
            <a href="<?= htmlspecialchars($tabUrl, ENT_QUOTES, 'UTF-8') ?>" class="<?= $activeTab === $tabKey ? 'active' : '' ?>">
                <?= htmlspecialchars((string)$tab['label'], ENT_QUOTES, 'UTF-8') ?>
                <span style="opacity:.75;">(<?= count((array)$tab['items']) ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($activeTab === 'encounter'): ?>
    <div class="card" id="native-encounter" style="margin-top:14px;">
        <h3 style="margin-top:0;">Pengiriman Encounter Dari Halaman Ini</h3>
        <p class="muted">Bagian ini dipakai untuk kirim atau update <code>Encounter</code> ke Satu Sehat langsung dari backend. Lookup <code>Patient</code> dan <code>Practitioner</code> mengikuti pola Khanza Java, yaitu menggunakan NIK pasien dan NIK dokter ke server Satu Sehat.</p>
        <?php if (!$satuSehatAvailable): ?>
            <div class="satusehat-alert error" style="margin-top:10px;">
                <strong>Konfigurasi Satu Sehat belum lengkap.</strong>
                <div class="satusehat-table-note">Pengiriman native tidak akan jalan sampai konfigurasi di Khanza Java lengkap.</div>
            </div>
        <?php endif; ?>
        <div class="satusehat-tools" style="margin-top:14px;">
            <div class="card" style="padding:14px;">
                <h4 style="margin:0 0 10px;">Filter Kunjungan</h4>
                <form method="get" class="satusehat-form">
                    <input type="hidden" name="module" value="bridging-satusehat">
                    <input type="hidden" name="tab" value="encounter">
                    <div class="row">
                        <label>
                            <div class="muted" style="margin-bottom:4px;">Tanggal Dari</div>
                            <input type="date" name="date_from" value="<?= htmlspecialchars((string)($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label>
                            <div class="muted" style="margin-bottom:4px;">Tanggal Sampai</div>
                            <input type="date" name="date_to" value="<?= htmlspecialchars((string)($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                    </div>
                    <label>
                        <div class="muted" style="margin-bottom:4px;">Cari No. Rawat / No. RM / Pasien / Dokter / Unit</div>
                        <input type="text" name="q" value="<?= htmlspecialchars((string)($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: 2026/03/01/000001 atau nama pasien">
                    </label>
                    <label>
                        <div class="muted" style="margin-bottom:4px;">Status Pengiriman</div>
                        <select name="status_sync">
                            <option value="all" <?= ((string)($filters['status_sync'] ?? 'all') === 'all') ? 'selected' : '' ?>>Semua</option>
                            <option value="unsent" <?= ((string)($filters['status_sync'] ?? 'all') === 'unsent') ? 'selected' : '' ?>>Belum Terkirim</option>
                            <option value="sent" <?= ((string)($filters['status_sync'] ?? 'all') === 'sent') ? 'selected' : '' ?>>Sudah Terkirim</option>
                            <option value="ready" <?= ((string)($filters['status_sync'] ?? 'all') === 'ready') ? 'selected' : '' ?>>Siap Dikirim</option>
                            <option value="not-ready" <?= ((string)($filters['status_sync'] ?? 'all') === 'not-ready') ? 'selected' : '' ?>>Belum Siap</option>
                        </select>
                    </label>
                    <div class="satusehat-actions">
                        <button type="submit" class="satusehat-btn">Tampilkan</button>
                        <a href="?module=bridging-satusehat&tab=encounter#satusehat-tab-menu" class="satusehat-btn secondary">Reset</a>
                    </div>
                </form>
            </div>
            <div class="card" style="padding:14px;">
                <h4 style="margin:0 0 10px;">Kirim Cepat Per No. Rawat</h4>
                <form method="post" action="<?= htmlspecialchars($buildQuery($currentQuery) . '#native-encounter', ENT_QUOTES, 'UTF-8') ?>" class="satusehat-form">
                    <input type="hidden" name="tab" value="encounter">
                    <input type="hidden" name="date_from" value="<?= htmlspecialchars((string)($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="date_to" value="<?= htmlspecialchars((string)($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="q" value="<?= htmlspecialchars((string)($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="status_sync" value="<?= htmlspecialchars((string)($filters['status_sync'] ?? 'all'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="satusehat_action" value="send-encounter">
                    <label>
                        <div class="muted" style="margin-bottom:4px;">No. Rawat</div>
                        <input type="text" name="no_rawat" placeholder="Masukkan no_rawat yang ingin dikirim">
                    </label>
                    <div class="satusehat-actions">
                        <button type="submit" class="satusehat-btn success" <?= $satuSehatAvailable ? '' : 'disabled' ?>>Kirim Encounter</button>
                    </div>
                    <div class="satusehat-table-note">Gunakan ini jika kunjungan tidak sedang tampil di tabel filter.</div>
                </form>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h3 style="margin-top:0;">Aksi Massal Hasil Filter</h3>
        <p class="muted">Tombol di bawah ini hanya mengirim data yang belum terkirim dari hasil filter aktif saat ini.</p>
        <form method="post" action="<?= htmlspecialchars($buildQuery($currentQuery) . '#native-encounter', ENT_QUOTES, 'UTF-8') ?>" class="satusehat-inline-actions" style="margin-top:10px;">
            <input type="hidden" name="tab" value="encounter">
            <input type="hidden" name="date_from" value="<?= htmlspecialchars((string)($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="date_to" value="<?= htmlspecialchars((string)($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="q" value="<?= htmlspecialchars((string)($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="status_sync" value="<?= htmlspecialchars((string)($filters['status_sync'] ?? 'all'), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="satusehat_action" value="bulk-send-filtered">
            <button type="submit" class="success" <?= ($satuSehatAvailable && $unsentFilteredCount > 0) ? '' : 'disabled' ?>>Kirim Semua Terfilter (<?= (int)$unsentFilteredCount ?>)</button>
        </form>
        <div class="satusehat-table-note">Untuk kebutuhan Anda, gunakan filter <code>Belum Terkirim</code> lalu klik tombol ini. Yang diproses hanya baris sesuai filter tersebut.</div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h3 style="margin-top:0;">Daftar Kunjungan Encounter</h3>
        <div class="muted" style="margin-bottom:10px;">Tabel ini menampilkan maksimal 100 kunjungan sesuai filter. Tombol <code>Kirim</code> akan membuat resource baru, sedangkan <code>Update</code> akan melakukan <code>PUT</code> ke Encounter yang sudah tersimpan di tabel <code>satu_sehat_encounter</code>.</div>
        <table>
            <thead>
                <tr>
                    <th>No. Rawat</th>
                    <th>Pasien</th>
                    <th>Dokter</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th>Kesiapan</th>
                    <th>ID Encounter</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($encounterRows === []): ?>
                <tr><td colspan="8" class="muted">Belum ada data kunjungan yang sesuai filter.</td></tr>
            <?php else: ?>
                <?php foreach ($encounterRows as $row): ?>
                    <?php
                        $hasEncounter = trim((string)($row['id_encounter'] ?? '')) !== '';
                        $isReady = !empty($row['ready']);
                        $issues = is_array($row['issues'] ?? null) ? $row['issues'] : [];
                        $actionName = $hasEncounter ? 'update-encounter' : 'send-encounter';
                        $buttonClass = $hasEncounter ? 'warn' : 'success';
                        $buttonLabel = $hasEncounter ? 'Update Encounter' : 'Kirim Encounter';
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string)($row['no_rawat'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                            <div class="satusehat-table-note">No. RM: <?= htmlspecialchars((string)($row['no_rkm_medis'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td>
                            <?= htmlspecialchars((string)($row['nm_pasien'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            <div class="satusehat-table-note">NIK: <?= htmlspecialchars((string)($row['no_ktp_pasien'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td>
                            <?= htmlspecialchars((string)($row['nm_dokter'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            <div class="satusehat-table-note">NIK: <?= htmlspecialchars((string)($row['no_ktp_dokter'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td>
                            <?= htmlspecialchars((string)($row['unit_nama'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            <div class="satusehat-table-note"><?= htmlspecialchars((string)($row['jenis_kunjungan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> | Lokasi: <?= htmlspecialchars((string)($row['id_lokasi_satusehat'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td>
                            <?php if ($hasEncounter): ?>
                                <span class="satusehat-badge ok">Terkirim</span>
                            <?php elseif ($isReady): ?>
                                <span class="satusehat-badge warn">Siap Dikirim</span>
                            <?php else: ?>
                                <span class="satusehat-badge err">Belum Siap</span>
                            <?php endif; ?>
                            <div class="satusehat-table-note">Mulai: <?= htmlspecialchars((string)($row['mulai'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td>
                            <?php if ($issues === []): ?>
                                <span class="satusehat-badge ok">Lengkap</span>
                            <?php else: ?>
                                <ul class="satusehat-list">
                                    <?php foreach ($issues as $issue): ?>
                                        <li><?= htmlspecialchars((string)$issue, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($hasEncounter): ?>
                                <code><?= htmlspecialchars((string)$row['id_encounter'], ENT_QUOTES, 'UTF-8') ?></code>
                            <?php else: ?>
                                <span class="muted">Belum ada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="satusehat-inline-actions">
                                <form method="post" action="<?= htmlspecialchars($buildQuery($currentQuery) . '#native-encounter', ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="tab" value="encounter">
                                    <input type="hidden" name="date_from" value="<?= htmlspecialchars((string)($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="date_to" value="<?= htmlspecialchars((string)($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="q" value="<?= htmlspecialchars((string)($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="status_sync" value="<?= htmlspecialchars((string)($filters['status_sync'] ?? 'all'), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="satusehat_action" value="<?= htmlspecialchars($actionName, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="no_rawat" value="<?= htmlspecialchars((string)($row['no_rawat'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="<?= htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8') ?>" <?= ($isReady && $satuSehatAvailable) ? '' : 'disabled' ?>><?= htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="card" id="satusehat-resource-actions" style="margin-top:14px;">
    <h3 style="margin-top:0;">Aksi Resource <?= htmlspecialchars((string)($tabDefs[$activeTab] ?? 'Encounter'), ENT_QUOTES, 'UTF-8') ?></h3>
    <p class="muted">
        <?php if ($activeTab === 'encounter'): ?>
            Tab ini memuat pengiriman native web untuk <code>Encounter</code> dan juga shortcut ke modul legacy jika dibutuhkan.
        <?php else: ?>
            Tab ini memuat shortcut untuk resource <?= htmlspecialchars((string)($tabDefs[$activeTab] ?? '-'), ENT_QUOTES, 'UTF-8') ?>. Native web untuk resource ini belum saya aktifkan.
        <?php endif; ?>
    </p>
    <table>
        <thead>
            <tr>
                <th>Data/Aksi</th>
                <th>Permission</th>
                <th>Button</th>
                <th>Kelompok</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($selectedTabItems === []): ?>
            <tr><td colspan="5" class="muted">Belum ada menu legacy yang terpetakan ke tab ini.</td></tr>
        <?php else: ?>
            <?php foreach ($selectedTabItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($item['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($item['permission'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($item['button'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($item['group_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="satusehat-inline-actions">
                            <?php if (!empty($item['native_supported']) && $activeTab === 'encounter'): ?>
                                <a href="#native-encounter" class="success">Native Web</a>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars((string)($item['legacy_url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" class="secondary">Buka Legacy</a>
                            <a href="<?= htmlspecialchars((string)($item['catalog_url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" class="secondary">Cari di Katalog</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>