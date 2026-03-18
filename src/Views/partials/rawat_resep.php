<?php
$resepObatUrl = (string)($resepObatUrl ?? '?page=rawatjalan&ajax=search_obat');
$aturanPakaiOptions = is_array($aturanPakaiOptions ?? null) ? $aturanPakaiOptions : [];
$metodeRacikOptions = is_array($metodeRacikOptions ?? null) ? $metodeRacikOptions : [];
$detailResep = is_array($detailResep ?? null) ? $detailResep : [];
$detailNoRawat = (string)($detailNoRawat ?? '');
?>
<div class="card" id="section-resep">
    <style>
        .resep-composer { border: 1px solid #d8e3ec; border-radius: 12px; background: #fcfeff; padding: 14px; }
        .resep-grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
        .resep-panel { border: 1px solid #d7e4ed; border-radius: 12px; background: #fff; padding: 12px; }
        .resep-panel h5 { margin: 0 0 6px; color: #174761; }
        .resep-panel p { margin: 0 0 10px; color: #5b7083; font-size: 12px; }
        .resep-form-grid { display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }
        .resep-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .resep-temp-wrap { margin-top: 12px; border: 1px dashed #c7d8e5; border-radius: 10px; background: #f8fcff; padding: 10px; }
        .resep-temp-title { color: #234b64; font-size: 12px; margin-bottom: 8px; }
        .resep-temp-empty { color: #6b7f90; font-size: 12px; }
        .resep-mini-table { width: 100%; border-collapse: collapse; }
        .resep-mini-table th, .resep-mini-table td { border: 1px solid #dbe5ee; padding: 8px; vertical-align: top; }
        .resep-mini-table th { background: #f4f9fc; color: #294b63; font-size: 12px; text-align: left; }
        .resep-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }
        .resep-btn-secondary { background: #334155; }
        .resep-btn-danger { background: #b91c1c; }
        .resep-racikan-card { border: 1px solid #d7e4ed; border-radius: 10px; background: #fff; padding: 10px; margin-top: 10px; }
        .resep-racikan-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 8px; }
        .resep-racikan-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
        .resep-racikan-badge { border: 1px solid #cfe0eb; background: #eff8fc; color: #194862; border-radius: 999px; padding: 4px 10px; font-size: 12px; }
        .resep-summary { margin-top: 14px; }
        .resep-summary-type { font-weight: 600; color: #174761; }
        .resep-summary-block { white-space: pre-line; line-height: 1.45; }
        @media (max-width: 720px) {
            .resep-actions { justify-content: stretch; }
            .resep-actions button { flex: 1; }
        }
    </style>
    <div class="muted" style="margin-bottom:8px;">Form tetap satu. Item obat dan racikan masuk ke penampungan sementara sebelum disimpan.</div>
    <div id="resepComposer" class="resep-composer" data-obat-url="<?= htmlspecialchars($resepObatUrl, ENT_QUOTES, 'UTF-8') ?>">
        <form method="post" id="resepComposerForm">
            <input type="hidden" name="action" value="input_resep">
            <input type="hidden" name="no_rawat" value="<?= htmlspecialchars($detailNoRawat, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="racikan_payload" id="racikan_payload" value="[]">
            <div id="nr_hidden_inputs" hidden></div>
            <datalist id="aturan_pakai_master_list">
                <?php foreach ($aturanPakaiOptions as $ap): ?>
                    <option value="<?= htmlspecialchars((string)($ap['aturan'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <div class="resep-grid">
                <section class="resep-panel">
                    <h5>Resep Non Racikan</h5>
                    <p>Isi 1 form obat, lalu klik tambah untuk masuk ke penampungan sementara.</p>
                    <div class="resep-form-grid">
                        <div class="field">
                            <label>Cari Obat</label>
                            <input type="text" id="nr_search_single" placeholder="kode / nama / kandungan">
                        </div>
                        <div class="field" style="grid-column: span 2;">
                            <label>Obat</label>
                            <select id="nr_select_single" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:240px;">
                                <option value="">- pilih obat -</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Jumlah</label>
                            <input type="number" id="nr_jml_single" min="0.01" step="0.01" value="1">
                        </div>
                        <div class="field">
                            <label>Aturan Pakai</label>
                            <input type="text" id="nr_aturan_single" value="3 x 1" list="aturan_pakai_master_list">
                        </div>
                    </div>
                    <div class="resep-toolbar">
                        <button type="button" id="add_nr_temp" class="resep-btn-secondary" title="Masukkan ke penampungan">+ Obat</button>
                    </div>
                    <div class="resep-temp-wrap">
                        <div class="resep-temp-title">Penampungan Sementara Non Racikan</div>
                        <div id="nr_temp_list"></div>
                    </div>
                </section>

                <section class="resep-panel">
                    <h5>Resep Racikan</h5>
                    <p>Isi racikan aktif, tambahkan item obat ke penampungan item, lalu masukkan racikan ke daftar sementara.</p>
                    <div class="resep-form-grid">
                        <div class="field">
                            <label>Nama Racik</label>
                            <input type="text" id="racik_nama" value="R1" placeholder="contoh: R BATUK">
                        </div>
                        <div class="field">
                            <label>Metode Racik</label>
                            <select id="racik_metode" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:180px;">
                                <?php foreach ($metodeRacikOptions as $mr): ?>
                                    <option value="<?= htmlspecialchars((string)($mr['kd_racik'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string)($mr['kd_racik'] ?? ''), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars((string)($mr['nm_racik'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Jml. Racik</label>
                            <input type="number" id="racik_jml" min="1" step="1" value="10">
                        </div>
                        <div class="field">
                            <label>Aturan Pakai</label>
                            <input type="text" id="racik_aturan" value="3 x 1" list="aturan_pakai_master_list">
                        </div>
                        <div class="field">
                            <label>Keterangan</label>
                            <input type="text" id="racik_keterangan" value="-" placeholder="contoh: HABISKAN">
                        </div>
                    </div>
                    <div class="resep-temp-wrap" style="margin-top:10px;">
                        <div class="resep-temp-title">Item Racikan Aktif</div>
                        <div class="resep-form-grid">
                            <div class="field">
                                <label>Cari Obat</label>
                                <input type="text" id="racik_item_search" placeholder="kode / nama / kandungan">
                            </div>
                            <div class="field" style="grid-column: span 2;">
                                <label>Obat</label>
                                <select id="racik_item_select" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:240px;">
                                    <option value="">- pilih obat -</option>
                                </select>
                            </div>
                            <div class="field">
                                <label>P1</label>
                                <input type="number" id="racik_item_p1" min="0" step="0.01" value="1">
                            </div>
                            <div class="field">
                                <label>P2</label>
                                <input type="number" id="racik_item_p2" min="0" step="0.01" value="1">
                            </div>
                            <div class="field">
                                <label>Kandungan</label>
                                <input type="text" id="racik_item_kandungan" placeholder="mg / ml / %">
                            </div>
                            <div class="field">
                                <label>Jumlah</label>
                                <input type="number" id="racik_item_jml" min="0.01" step="0.01" value="1">
                            </div>
                        </div>
                        <div class="resep-toolbar">
                            <button type="button" id="add_racik_item_temp" class="resep-btn-secondary" title="Masukkan item racikan">+ Obat</button>
                        </div>
                        <div id="racik_item_temp_list"></div>
                    </div>
                    <div class="resep-toolbar">
                        <button type="button" id="save_racikan_temp" class="resep-btn-secondary">Masukkan Racikan</button>
                    </div>
                    <div class="resep-temp-wrap">
                        <div class="resep-temp-title">Penampungan Sementara Racikan</div>
                        <div id="racikan_group_temp_list"></div>
                    </div>
                </section>
            </div>

            <div class="resep-actions">
                <button type="submit">Simpan Resep</button>
            </div>
        </form>

        <div class="resep-summary">
            <div class="muted" style="margin-bottom:6px;">Daftar Resep Tersimpan</div>
            <table>
                <thead>
                    <tr>
                        <th>No Resep</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Jenis</th>
                        <th>Non Racikan</th>
                        <th>Racikan</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($detailResep)): ?>
                    <tr><td colspan="6" class="muted">Belum ada resep tersimpan.</td></tr>
                <?php else: ?>
                    <?php foreach ($detailResep as $rp): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($rp['no_resep'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($rp['tgl_peresepan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($rp['jam_peresepan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="resep-summary-type"><?= htmlspecialchars((string)($rp['jenis_resep'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><div class="resep-summary-block"><?= htmlspecialchars((string)($rp['non_racikan_summary'] ?? '-'), ENT_QUOTES, 'UTF-8') ?: '-' ?></div></td>
                            <td><div class="resep-summary-block"><?= htmlspecialchars((string)($rp['racikan_summary'] ?? '-'), ENT_QUOTES, 'UTF-8') ?: '-' ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
(function () {
    var root = document.getElementById('resepComposer');
    if (!root) return;

    var obatUrl = root.getAttribute('data-obat-url') || '';
    var form = document.getElementById('resepComposerForm');
    var racikanPayload = document.getElementById('racikan_payload');
    var nrHiddenInputs = document.getElementById('nr_hidden_inputs');

    var nrSearch = document.getElementById('nr_search_single');
    var nrSelect = document.getElementById('nr_select_single');
    var nrJml = document.getElementById('nr_jml_single');
    var nrAturan = document.getElementById('nr_aturan_single');
    var nrTempList = document.getElementById('nr_temp_list');
    var addNrTempBtn = document.getElementById('add_nr_temp');

    var racikNama = document.getElementById('racik_nama');
    var racikMetode = document.getElementById('racik_metode');
    var racikJml = document.getElementById('racik_jml');
    var racikAturan = document.getElementById('racik_aturan');
    var racikKeterangan = document.getElementById('racik_keterangan');
    var racikItemSearch = document.getElementById('racik_item_search');
    var racikItemSelect = document.getElementById('racik_item_select');
    var racikItemP1 = document.getElementById('racik_item_p1');
    var racikItemP2 = document.getElementById('racik_item_p2');
    var racikItemKandungan = document.getElementById('racik_item_kandungan');
    var racikItemJml = document.getElementById('racik_item_jml');
    var racikItemTempList = document.getElementById('racik_item_temp_list');
    var addRacikItemTempBtn = document.getElementById('add_racik_item_temp');
    var saveRacikanTempBtn = document.getElementById('save_racikan_temp');
    var racikanGroupTempList = document.getElementById('racikan_group_temp_list');

    var nonRacikanItems = [];
    var currentRacikanItems = [];
    var racikanGroups = [];

    function debounce(fn, wait) {
        var timer;
        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(null, args); }, wait);
        };
    }

    function optionText(item) {
        var parts = [];
        if (item.kode_brng) parts.push(item.kode_brng);
        if (item.nama_brng) parts.push(item.nama_brng);
        if (item.kandungan_obat) parts.push(item.kandungan_obat);
        return parts.join(' | ');
    }

    function fillObatOptions(select, items, selected) {
        if (!select) return;
        select.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '- pilih obat -';
        select.appendChild(placeholder);
        (items || []).forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.kode_brng || '';
            opt.textContent = optionText(item);
            if (selected && selected === opt.value) opt.selected = true;
            select.appendChild(opt);
        });
    }

    function fetchObat(query, callback, currentValue) {
        var q = String(query || '').trim();
        if (q.length < 3 || !obatUrl) return;
        fetch(obatUrl + '&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json || !json.ok || !Array.isArray(json.data)) return;
                callback(json.data, currentValue || '');
            });
    }

    function bindObatLookup(searchInput, select) {
        if (!searchInput || !select) return;
        var doFetch = debounce(function (query) {
            fetchObat(query, function (items, selected) {
                fillObatOptions(select, items, selected || select.value || '');
            }, select.value || '');
        }, 220);
        searchInput.addEventListener('input', function () {
            doFetch(searchInput.value || '');
        });
    }

    function selectedLabel(select) {
        if (!select || select.selectedIndex < 0) return '';
        return (select.options[select.selectedIndex] || {}).text || '';
    }

    function renderNonRacikan() {
        nrHiddenInputs.innerHTML = '';
        if (!nonRacikanItems.length) {
            nrTempList.innerHTML = '<div class="resep-temp-empty">Belum ada item non racikan di penampungan.</div>';
            return;
        }
        var html = '<table class="resep-mini-table"><thead><tr><th>Obat</th><th>Jumlah</th><th>Aturan Pakai</th><th>Aksi</th></tr></thead><tbody>';
        nonRacikanItems.forEach(function (item, idx) {
            html += '<tr>' +
                '<td>' + escapeHtml(item.label || item.kode_brng) + '</td>' +
                '<td>' + escapeHtml(item.jml) + '</td>' +
                '<td>' + escapeHtml(item.aturan_pakai) + '</td>' +
                '<td><button type="button" class="resep-btn-danger remove-nr-temp" data-index="' + idx + '">Hapus</button></td>' +
            '</tr>';

            appendHiddenInput('nr_kode_brng[]', item.kode_brng);
            appendHiddenInput('nr_jml[]', item.jml);
            appendHiddenInput('nr_aturan_pakai[]', item.aturan_pakai);
        });
        html += '</tbody></table>';
        nrTempList.innerHTML = html;
        nrTempList.querySelectorAll('.remove-nr-temp').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(btn.getAttribute('data-index') || '-1', 10);
                if (idx >= 0) {
                    nonRacikanItems.splice(idx, 1);
                    renderNonRacikan();
                }
            });
        });
    }

    function renderCurrentRacikanItems() {
        if (!currentRacikanItems.length) {
            racikItemTempList.innerHTML = '<div class="resep-temp-empty">Belum ada item pada racikan aktif.</div>';
            return;
        }
        var html = '<table class="resep-mini-table"><thead><tr><th>Obat</th><th>P1</th><th>P2</th><th>Kandungan</th><th>Jumlah</th><th>Aksi</th></tr></thead><tbody>';
        currentRacikanItems.forEach(function (item, idx) {
            html += '<tr>' +
                '<td>' + escapeHtml(item.label || item.kode_brng) + '</td>' +
                '<td>' + escapeHtml(item.p1) + '</td>' +
                '<td>' + escapeHtml(item.p2) + '</td>' +
                '<td>' + escapeHtml(item.kandungan || '-') + '</td>' +
                '<td>' + escapeHtml(item.jml) + '</td>' +
                '<td><button type="button" class="resep-btn-danger remove-racik-item-temp" data-index="' + idx + '">Hapus</button></td>' +
            '</tr>';
        });
        html += '</tbody></table>';
        racikItemTempList.innerHTML = html;
        racikItemTempList.querySelectorAll('.remove-racik-item-temp').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(btn.getAttribute('data-index') || '-1', 10);
                if (idx >= 0) {
                    currentRacikanItems.splice(idx, 1);
                    renderCurrentRacikanItems();
                }
            });
        });
    }

    function renderRacikanGroups() {
        if (!racikanGroups.length) {
            racikanGroupTempList.innerHTML = '<div class="resep-temp-empty">Belum ada racikan di penampungan.</div>';
            return;
        }
        var html = '';
        racikanGroups.forEach(function (group, idx) {
            html += '<div class="resep-racikan-card">';
            html += '<div class="resep-racikan-head"><strong>Racikan ' + (idx + 1) + ': ' + escapeHtml(group.nama_racik) + '</strong>' +
                '<button type="button" class="resep-btn-danger remove-racikan-group-temp" data-index="' + idx + '">Hapus Racikan</button></div>';
            html += '<div class="resep-racikan-meta">' +
                '<span class="resep-racikan-badge">Metode: ' + escapeHtml(group.metode_label) + '</span>' +
                '<span class="resep-racikan-badge">Jml Racik: ' + escapeHtml(group.jml_dr) + '</span>' +
                '<span class="resep-racikan-badge">Aturan: ' + escapeHtml(group.aturan_pakai) + '</span>' +
                '<span class="resep-racikan-badge">Ket: ' + escapeHtml(group.keterangan) + '</span>' +
            '</div>';
            html += '<table class="resep-mini-table"><thead><tr><th>Obat</th><th>P1</th><th>P2</th><th>Kandungan</th><th>Jumlah</th></tr></thead><tbody>';
            group.items.forEach(function (item) {
                html += '<tr>' +
                    '<td>' + escapeHtml(item.label || item.kode_brng) + '</td>' +
                    '<td>' + escapeHtml(item.p1) + '</td>' +
                    '<td>' + escapeHtml(item.p2) + '</td>' +
                    '<td>' + escapeHtml(item.kandungan || '-') + '</td>' +
                    '<td>' + escapeHtml(item.jml) + '</td>' +
                '</tr>';
            });
            html += '</tbody></table></div>';
        });
        racikanGroupTempList.innerHTML = html;
        racikanGroupTempList.querySelectorAll('.remove-racikan-group-temp').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(btn.getAttribute('data-index') || '-1', 10);
                if (idx >= 0) {
                    racikanGroups.splice(idx, 1);
                    renderRacikanGroups();
                    refreshRacikNamaDefault();
                }
            });
        });
    }

    function appendHiddenInput(name, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        nrHiddenInputs.appendChild(input);
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resetNonRacikanForm() {
        nrSearch.value = '';
        fillObatOptions(nrSelect, [], '');
        nrJml.value = '1';
        nrAturan.value = '3 x 1';
    }

    function resetRacikItemForm() {
        racikItemSearch.value = '';
        fillObatOptions(racikItemSelect, [], '');
        racikItemP1.value = '1';
        racikItemP2.value = '1';
        racikItemKandungan.value = '';
        racikItemJml.value = '1';
    }

    function refreshRacikNamaDefault() {
        racikNama.value = 'R' + (racikanGroups.length + 1);
    }

    function resetRacikanForm() {
        refreshRacikNamaDefault();
        if (racikMetode.options.length > 0) {
            racikMetode.selectedIndex = 0;
        }
        racikJml.value = '10';
        racikAturan.value = '3 x 1';
        racikKeterangan.value = '-';
        currentRacikanItems = [];
        resetRacikItemForm();
        renderCurrentRacikanItems();
    }

    addNrTempBtn.addEventListener('click', function () {
        var kode = String(nrSelect.value || '').trim();
        var jml = parseFloat(nrJml.value || '0');
        var aturan = String(nrAturan.value || '').trim() || '-';
        if (!kode) {
            alert('Pilih obat non racikan terlebih dahulu.');
            return;
        }
        if (!(jml > 0)) {
            alert('Jumlah non racikan harus lebih dari 0.');
            return;
        }
        nonRacikanItems.push({
            kode_brng: kode,
            label: selectedLabel(nrSelect),
            jml: String(jml),
            aturan_pakai: aturan
        });
        renderNonRacikan();
        resetNonRacikanForm();
    });

    addRacikItemTempBtn.addEventListener('click', function () {
        var kode = String(racikItemSelect.value || '').trim();
        var jml = parseFloat(racikItemJml.value || '0');
        if (!kode) {
            alert('Pilih obat untuk racikan aktif terlebih dahulu.');
            return;
        }
        if (!(jml > 0)) {
            alert('Jumlah item racikan harus lebih dari 0.');
            return;
        }
        currentRacikanItems.push({
            kode_brng: kode,
            label: selectedLabel(racikItemSelect),
            p1: String(parseFloat(racikItemP1.value || '1') || 0),
            p2: String(parseFloat(racikItemP2.value || '1') || 0),
            kandungan: String(racikItemKandungan.value || '').trim(),
            jml: String(jml)
        });
        renderCurrentRacikanItems();
        resetRacikItemForm();
    });

    saveRacikanTempBtn.addEventListener('click', function () {
        var kdRacik = String(racikMetode.value || '').trim();
        var jmlDr = parseInt(racikJml.value || '0', 10);
        if (!currentRacikanItems.length) {
            alert('Tambahkan minimal 1 item ke racikan aktif.');
            return;
        }
        if (!kdRacik) {
            alert('Pilih metode racik terlebih dahulu.');
            return;
        }
        if (!(jmlDr > 0)) {
            alert('Jumlah racik harus lebih dari 0.');
            return;
        }
        racikanGroups.push({
            nama_racik: String(racikNama.value || '').trim() || ('R' + (racikanGroups.length + 1)),
            kd_racik: kdRacik,
            metode_label: selectedLabel(racikMetode),
            jml_dr: String(jmlDr),
            aturan_pakai: String(racikAturan.value || '').trim() || '-',
            keterangan: String(racikKeterangan.value || '').trim() || '-',
            items: currentRacikanItems.slice()
        });
        renderRacikanGroups();
        resetRacikanForm();
    });

    form.addEventListener('submit', function (event) {
        if (currentRacikanItems.length) {
            event.preventDefault();
            alert('Masukkan dulu racikan aktif ke penampungan sementara sebelum simpan resep.');
            return;
        }
        racikanPayload.value = JSON.stringify(racikanGroups.map(function (group) {
            return {
                nama_racik: group.nama_racik,
                kd_racik: group.kd_racik,
                jml_dr: group.jml_dr,
                aturan_pakai: group.aturan_pakai,
                keterangan: group.keterangan,
                items: group.items.map(function (item) {
                    return {
                        kode_brng: item.kode_brng,
                        p1: item.p1,
                        p2: item.p2,
                        kandungan: item.kandungan,
                        jml: item.jml
                    };
                })
            };
        }));
    });

    bindObatLookup(nrSearch, nrSelect);
    bindObatLookup(racikItemSearch, racikItemSelect);
    renderNonRacikan();
    renderCurrentRacikanItems();
    renderRacikanGroups();
    resetRacikanForm();
})();
</script>
