<div class="card">
    <h2 style="margin-top:0;">Modul Registrasi</h2>
    <div style="margin:0 0 10px;">
        <a href="?page=vclaim-bpjs" style="display:inline-block;text-decoration:none;background:#0f6b8f;color:#fff;padding:9px 12px;border-radius:8px;">VClaim BPJS</a>
    </div>
    <?php if ($todayCount !== null): ?>
        <p class="muted">Total registrasi hari ini: <strong><?= (int)$todayCount ?></strong></p>
    <?php endif; ?>
    <?php if (!empty($msgDetail)): ?>
        <p class="pill" style="border-color:<?= ($msg === 'ok') ? '#cde6de' : '#f4b4b4' ?>;background:<?= ($msg === 'ok') ? '#edf8f4' : '#ffecec' ?>;color:<?= ($msg === 'ok') ? '#0f5132' : '#8b1b1b' ?>;">
            <?= htmlspecialchars((string)$msgDetail, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
    <?php $nikStatusVal = (string)($nikStatus ?? ''); ?>
    <?php if (($nikStatusVal === 'found' || $nikStatusVal === 'not_found') && !empty($msgDetail)): ?>
        <script>
            window.addEventListener('load', function () {
                var nikStatus = <?= json_encode((string)$nikStatusVal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                var targetId = nikStatus === 'found' ? 'panel-ralan' : 'panel-pasien-baru';
                var doneScroll = false;
                var okBtn = document.getElementById('appAlertOkBtn');
                var onOk = function () {
                    if (doneScroll) return;
                    doneScroll = true;
                    setTimeout(function () {
                        var target = document.getElementById(targetId);
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 0);
                };
                if (okBtn) {
                    okBtn.addEventListener('click', onOk, { once: true });
                }
                alert(<?= json_encode((string)$msgDetail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
            });
        </script>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p class="muted">Gagal mengambil data: <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;">Registrasi Dengan NIK</h3>
    <p class="muted">Petugas cukup masukkan NIK. Jika NIK ada di database, pasien langsung diarahkan ke pendaftaran rawat jalan. Jika belum ada, NIK otomatis dibawa ke form pasien baru.</p>
    <form method="post" class="row" style="margin-top:10px;">
        <input type="hidden" name="action" value="route_by_nik">
        <div class="field">
            <label>NIK</label>
            <input type="text" name="nik" value="<?= htmlspecialchars((string)($nikBaru ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Masukkan NIK pasien" required>
        </div>
        <button type="submit">Lanjutkan</button>
    </form>
</div>
<div class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;">Pencarian Pasien</h3>
    <style>
        .search-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            align-items: start;
        }
        .search-card {
            border: 1px solid #d7e4ed;
            border-radius: 10px;
            padding: 10px;
            background: #f8fbfd;
        }
        .bpjs-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .45);
            z-index: 85;
        }
        .bpjs-modal {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: min(780px, 95vw);
            max-height: 88vh;
            overflow: auto;
            border: 1px solid #d7deea;
            border-radius: 12px;
            background: #fff;
            padding: 14px;
            box-shadow: 0 24px 40px rgba(15, 23, 42, .25);
            z-index: 86;
        }
        .bpjs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 8px;
            margin-top: 10px;
        }
        .bpjs-item {
            border: 1px solid #d7e4ed;
            border-radius: 8px;
            background: #f8fbfd;
            padding: 8px 10px;
            font-size: 13px;
        }
        .bpjs-item b {
            display: block;
            color: #1a4963;
            margin-bottom: 3px;
        }
    </style>
    <div class="search-split">
        <div class="search-card">
            <form method="get" class="row">
                <input type="hidden" name="page" value="registrasi">
                <input type="hidden" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="kd_poli" value="<?= htmlspecialchars((string)$kdPoli, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="kd_pj" value="<?= htmlspecialchars((string)$kdPj, ENT_QUOTES, 'UTF-8') ?>">
                <div class="field">
                    <label>Cari RM / Nama / KTP</label>
                    <input type="text" name="q_pasien" value="<?= htmlspecialchars((string)$searchPasien, ENT_QUOTES, 'UTF-8') ?>" placeholder="contoh: 000055 atau RAHMAT">
                </div>
                <button type="submit">Cari Pasien</button>
            </form>
        </div>
        <div class="search-card">
            <form method="get" class="row">
                <input type="hidden" name="page" value="registrasi">
                <input type="hidden" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="kd_poli" value="<?= htmlspecialchars((string)$kdPoli, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="kd_pj" value="<?= htmlspecialchars((string)$kdPj, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="q_pasien" value="<?= htmlspecialchars((string)$searchPasien, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="bpjs_open" value="1">
                <div class="field">
                    <label>Cek Status Kepesertaan BPJS</label>
                    <input type="text" name="bpjs_peserta" value="<?= htmlspecialchars((string)($bpjsPesertaValue ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Isi NIK / No Peserta" required>
                </div>
                <div class="field">
                    <label>Tipe</label>
                    <select name="bpjs_tipe" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:160px;">
                        <option value="auto" <?= (($bpjsPesertaType ?? 'auto') === 'auto') ? 'selected' : '' ?>>Auto</option>
                        <option value="nik" <?= (($bpjsPesertaType ?? '') === 'nik') ? 'selected' : '' ?>>NIK</option>
                        <option value="nokartu" <?= (($bpjsPesertaType ?? '') === 'nokartu') ? 'selected' : '' ?>>No Peserta</option>
                    </select>
                </div>
                <button type="submit">Cek Kepesertaan</button>
            </form>
        </div>
    </div>
    <?php if (!empty($pasienCariError)): ?>
        <p class="muted">Gagal pencarian pasien: <?= htmlspecialchars((string)$pasienCariError, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if (!empty($pasienCari)): ?>
        <table style="margin-top:10px;">
            <thead>
            <tr>
                <th>No RM</th>
                <th>Nama</th>
                <th>JK</th>
                <th>Tgl Lahir</th>
                <th>Alamat</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pasienCari as $pc): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$pc['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$pc['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$pc['jk'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$pc['tgl_lahir'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$pc['alamat'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <a href="?page=registrasi&no_rkm_medis=<?= urlencode((string)$pc['no_rkm_medis']) ?>&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&kd_poli=<?= urlencode((string)$kdPoli) ?>&kd_pj=<?= urlencode((string)$kdPj) ?>&q_pasien=<?= urlencode((string)$searchPasien) ?>">Pilih</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if (!empty($bpjsOpen)): ?>
    <?php
    $closeModalUrl = '?page=registrasi'
        . '&from=' . urlencode((string)$from)
        . '&to=' . urlencode((string)$to)
        . '&q=' . urlencode((string)$q)
        . '&kd_poli=' . urlencode((string)$kdPoli)
        . '&kd_pj=' . urlencode((string)$kdPj)
        . '&q_pasien=' . urlencode((string)$searchPasien);
    ?>
    <a href="<?= htmlspecialchars($closeModalUrl, ENT_QUOTES, 'UTF-8') ?>" class="bpjs-modal-backdrop" aria-label="Tutup modal"></a>
    <div class="bpjs-modal">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
            <h3 style="margin:0;">Hasil Cek Kepesertaan BPJS</h3>
            <a href="<?= htmlspecialchars($closeModalUrl, ENT_QUOTES, 'UTF-8') ?>" style="border:1px solid #d7deea;border-radius:8px;padding:6px 10px;text-decoration:none;color:#1f2937;">Tutup</a>
        </div>
        <?php if (empty($bpjsPesertaResult)): ?>
            <p class="muted" style="margin-top:10px;">Tidak ada data yang dicek.</p>
        <?php elseif (!empty($bpjsPesertaResult['ok'])): ?>
            <?php $peserta = is_array($bpjsPesertaResult['data'] ?? null) ? $bpjsPesertaResult['data'] : []; ?>
            <p class="pill" style="margin-top:10px;border-color:#cde6de;background:#edf8f4;color:#0f5132;">
                <?= htmlspecialchars((string)($bpjsPesertaResult['message'] ?? 'Data ditemukan'), ENT_QUOTES, 'UTF-8') ?>
            </p>
            <div class="bpjs-grid">
                <div class="bpjs-item"><b>No Kartu</b><?= htmlspecialchars((string)($peserta['noKartu'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="bpjs-item"><b>NIK</b><?= htmlspecialchars((string)($peserta['nik'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="bpjs-item"><b>Nama</b><?= htmlspecialchars((string)($peserta['nama'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="bpjs-item"><b>Status Peserta</b><?= htmlspecialchars((string)($peserta['statusPeserta']['keterangan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="bpjs-item"><b>Jenis Peserta</b><?= htmlspecialchars((string)($peserta['jenisPeserta']['keterangan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="bpjs-item"><b>Hak Kelas</b><?= htmlspecialchars((string)($peserta['hakKelas']['keterangan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="bpjs-item"><b>FKTP</b><?= htmlspecialchars((string)($peserta['provUmum']['nmProvider'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="bpjs-item"><b>Tgl Lahir</b><?= htmlspecialchars((string)($peserta['tglLahir'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="bpjs-item"><b>Tgl TMT</b><?= htmlspecialchars((string)($peserta['tglTMT'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="bpjs-item"><b>Tgl TAT</b><?= htmlspecialchars((string)($peserta['tglTAT'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="bpjs-item"><b>No MR BPJS</b><?= htmlspecialchars((string)($peserta['mr']['noMR'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="bpjs-item"><b>No Telepon</b><?= htmlspecialchars((string)($peserta['mr']['noTelepon'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div style="margin-top:14px;">
                <h4 style="margin:0 0 6px 0;">Riwayat Kunjungan BPJS Peserta</h4>
                <p class="muted" style="margin:0 0 10px 0;">Menampilkan histori pelayanan 90 hari terakhir.</p>
                <?php $historiRows = is_array($bpjsHistoriResult['data'] ?? null) ? $bpjsHistoriResult['data'] : []; ?>
                <?php if (is_array($bpjsHistoriResult) && !empty($bpjsHistoriResult['ok']) && !empty($historiRows)): ?>
                    <div style="overflow:auto;max-height:320px;border:1px solid #d7deea;border-radius:10px;">
                        <table>
                            <thead>
                            <tr>
                                <th>Tgl SEP</th>
                                <th>Jenis</th>
                                <th>No SEP</th>
                                <th>No Rujukan</th>
                                <th>Poli</th>
                                <th>PPK Pelayanan</th>
                                <th>Diagnosa</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($historiRows as $histori): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($histori['tgl_sep'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($histori['jns_pelayanan_label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($histori['no_sep'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($histori['no_rujukan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($histori['poli'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($histori['ppk_pelayanan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($histori['diagnosa'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif (is_array($bpjsHistoriResult) && !empty($bpjsHistoriResult['ok'])): ?>
                    <p class="muted">Tidak ada riwayat kunjungan BPJS pada rentang 90 hari terakhir.</p>
                <?php elseif (is_array($bpjsHistoriResult)): ?>
                    <p class="pill" style="margin-top:10px;border-color:#f4d7b4;background:#fff7ec;color:#8a4b12;">
                        <?= htmlspecialchars((string)($bpjsHistoriResult['message'] ?? 'Riwayat kunjungan BPJS belum bisa diambil'), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="pill" style="margin-top:10px;border-color:#f4b4b4;background:#ffecec;color:#8b1b1b;">
                <?= htmlspecialchars((string)($bpjsPesertaResult['message'] ?? 'Gagal cek kepesertaan'), ENT_QUOTES, 'UTF-8') ?>
            </p>
            <?php if (!empty($bpjsPesertaResult['meta_code']) || !empty($bpjsPesertaResult['meta_message'])): ?>
                <p class="muted">Code: <?= htmlspecialchars((string)($bpjsPesertaResult['meta_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>,
                    Message: <?= htmlspecialchars((string)($bpjsPesertaResult['meta_message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="cards" style="margin-top:12px;">
    <div class="card" id="panel-pasien-baru">
        <h3 style="margin-top:0;">Pasien Baru (Cepat)</h3>
        <form method="post">
            <input type="hidden" name="action" value="create_patient">
            <div class="field"><label>No. Rekam Medik</label><input type="text" id="no_rkm_medis_baru" name="no_rkm_medis" required placeholder="isi manual sesuai format RS"></div>
            <div class="field"><label>Nama Pasien</label><input type="text" name="nm_pasien" required></div>
            <div class="field"><label>Jenis Kelamin</label>
                <select name="jk" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </div>
            <div class="field"><label>Tanggal Lahir</label><input type="date" name="tgl_lahir" required></div>
            <div class="field"><label>Tempat Lahir</label><input type="text" name="tmp_lahir" value="-"></div>
            <div class="field"><label>Nama Ibu</label><input type="text" name="nm_ibu" value="-"></div>
            <div class="field"><label>NIK / No KTP</label><input type="text" name="nik" value="<?= htmlspecialchars((string)(($nikBaru ?? '') !== '' ? $nikBaru : ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="16 digit NIK"></div>
            <div class="field"><label>Alamat</label><input type="text" name="alamat" value="-"></div>
            <div class="field"><label>No Telp</label><input type="text" name="no_tlp" value="-"></div>
            <div class="field">
                <label>Cara Bayar Default</label>
                <select name="kd_pj_pasien" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                    <?php foreach ($penjabList as $pj): ?>
                        <option value="<?= htmlspecialchars((string)$pj['kd_pj'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string)$pj['kd_pj'] === 'A09') ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$pj['png_jawab'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>Nomor Peserta</label><input type="text" name="no_peserta" value="-" placeholder="No peserta BPJS"></div>
            <button type="submit" style="margin-top:8px;">Simpan Pasien Baru</button>
        </form>
    </div>

    <div class="card" id="panel-ralan">
        <h3 style="margin-top:0;">Pendaftaran Rawat Jalan</h3>
        <?php if (!empty($pasienTerpilih)): ?>
            <p class="pill">Pasien dipilih: <?= htmlspecialchars((string)$pasienTerpilih['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)$pasienTerpilih['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <p class="muted">Pilih pasien dari pencarian atau isi No RM manual.</p>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="action" value="register_visit">
            <div class="field">
                <label>No RM</label>
                <input type="text" name="no_rkm_medis" value="<?= htmlspecialchars((string)$selectedNoRm, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="field">
                <label>Tanggal Registrasi</label>
                <input type="date" name="tgl_registrasi" value="<?= htmlspecialchars((string)date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="field">
                <label>Poliklinik</label>
                <select name="kd_poli" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:240px;" required>
                    <option value="">- pilih poli -</option>
                    <?php foreach ($poliList as $p): ?>
                        <option value="<?= htmlspecialchars((string)$p['kd_poli'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)$p['nm_poli'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Dokter</label>
                <select name="kd_dokter" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:240px;" required>
                    <option value="">- pilih dokter -</option>
                    <?php foreach ($dokterList as $d): ?>
                        <option value="<?= htmlspecialchars((string)$d['kd_dokter'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)$d['nm_dokter'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Cara Bayar</label>
                <select name="kd_pj" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:240px;" required>
                    <option value="">- pilih penjamin -</option>
                    <?php foreach ($penjabList as $pj): ?>
                        <option value="<?= htmlspecialchars((string)$pj['kd_pj'], ENT_QUOTES, 'UTF-8') ?>" <?= (!empty($pasienTerpilih) && (string)$pasienTerpilih['kd_pj'] === (string)$pj['kd_pj']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$pj['png_jawab'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label>Penanggung Jawab</label><input type="text" name="p_jawab" value="<?= htmlspecialchars((string)($pasienTerpilih['namakeluarga'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label>Alamat PJ</label><input type="text" name="almt_pj" value="<?= htmlspecialchars((string)($pasienTerpilih['alamatpj'] ?? ($pasienTerpilih['alamat'] ?? '-')), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field">
                <label>Hubungan PJ</label>
                <select name="hubunganpj" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                    <?php $hub = (string)($pasienTerpilih['keluarga'] ?? 'SAUDARA'); ?>
                    <?php foreach (['AYAH','IBU','ISTRI','SUAMI','SAUDARA','ANAK','DIRI SENDIRI','LAIN-LAIN','Tempat Kerja'] as $h): ?>
                        <option value="<?= htmlspecialchars($h, ENT_QUOTES, 'UTF-8') ?>" <?= ($hub === $h) ? 'selected' : '' ?>><?= htmlspecialchars($h, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" style="margin-top:8px;">Simpan Registrasi</button>
        </form>
    </div>
</div>

<script>
    (function () {
        var inputRm = document.getElementById('no_rkm_medis_baru');
        if (!inputRm) return;
        var isChecking = false;
        var lastValue = '';
        inputRm.addEventListener('blur', function () {
            var val = (inputRm.value || '').trim();
            if (!val || val === lastValue || isChecking) return;
            isChecking = true;
            fetch('?page=registrasi&ajax=cek-rm&no_rkm_medis=' + encodeURIComponent(val), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    isChecking = false;
                    if (!res || !res.ok) return;
                    lastValue = val;
                    if (res.exists) {
                        alert(res.message || 'No. Rekam Medik sudah terdaftar');
                        inputRm.value = '';
                        inputRm.focus();
                    }
                })
                .catch(function () {
                    isChecking = false;
                });
        });
    })();
</script>

<div class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;">Data Registrasi</h3>
    <form method="get" class="row" style="margin:10px 0 12px;">
        <input type="hidden" name="page" value="registrasi">
        <div class="field">
            <label>Dari Tanggal</label>
            <input type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>Sampai Tanggal</label>
            <input type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>Cari (No Rawat/RM/Nama)</label>
            <input type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="contoh: 2026/03, 000123, nama pasien">
        </div>
        <div class="field">
            <label>Poli</label>
            <select name="kd_poli" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                <option value="">Semua Poli</option>
                <?php foreach ($poliList as $p): ?>
                    <option value="<?= htmlspecialchars((string)$p['kd_poli'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string)$kdPoli === (string)$p['kd_poli']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$p['nm_poli'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Cara Bayar</label>
            <select name="kd_pj" style="border:1px solid #d7deea;border-radius:8px;padding:9px 10px;min-width:220px;">
                <option value="">Semua Cara Bayar</option>
                <?php foreach ($penjabList as $pj): ?>
                    <option value="<?= htmlspecialchars((string)$pj['kd_pj'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string)$kdPj === (string)$pj['kd_pj']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$pj['png_jawab'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Filter</button>
    </form>
    <table>
        <thead>
            <tr>
                <th>No Rawat</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>No RM</th>
                <th>Pasien</th>
                <th>Poli</th>
                <th>Cara Bayar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <a href="?page=registrasi&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&q=<?= urlencode((string)$q) ?>&kd_poli=<?= urlencode((string)$kdPoli) ?>&kd_pj=<?= urlencode((string)$kdPj) ?>&detail=<?= urlencode((string)$r['no_rawat']) ?>">
                            <?= htmlspecialchars((string)$r['no_rawat'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars((string)$r['tgl_registrasi'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['jam_reg'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['nm_poli'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['png_jawab'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['stts'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($detailNoRawat)): ?>
    <div class="card" style="margin-top:12px;">
        <h3 style="margin-top:0;">Detail Kunjungan: <?= htmlspecialchars((string)$detailNoRawat, ENT_QUOTES, 'UTF-8') ?></h3>
        <?php if (!empty($detailError)): ?>
            <p class="muted">Gagal mengambil detail: <?= htmlspecialchars((string)$detailError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif (!empty($detail)): ?>
            <div class="row" style="margin-bottom:10px;">
                <span class="pill">Pasien: <?= htmlspecialchars((string)$detail['nm_pasien'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">No RM: <?= htmlspecialchars((string)$detail['no_rkm_medis'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Poli: <?= htmlspecialchars((string)$detail['nm_poli'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Dokter: <?= htmlspecialchars((string)$detail['nm_dokter'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="pill">Penjamin: <?= htmlspecialchars((string)$detail['png_jawab'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="cards">
                <div class="card"><div class="muted">Tindakan Dokter</div><div class="badge"><?= (int)($detailStats['tindakan_dokter']['data'] ?? 0) ?></div></div>
                <div class="card"><div class="muted">Tindakan Perawat</div><div class="badge"><?= (int)($detailStats['tindakan_perawat']['data'] ?? 0) ?></div></div>
                <div class="card"><div class="muted">Tindakan Dr+Pr</div><div class="badge"><?= (int)($detailStats['tindakan_drpr']['data'] ?? 0) ?></div></div>
                <div class="card"><div class="muted">Pemeriksaan Lab</div><div class="badge"><?= (int)($detailStats['lab']['data'] ?? 0) ?></div></div>
                <div class="card"><div class="muted">Pemeriksaan Radiologi</div><div class="badge"><?= (int)($detailStats['radiologi']['data'] ?? 0) ?></div></div>
                <div class="card"><div class="muted">Total Obat</div><div class="badge"><?= number_format((float)($detailStats['obat_total']['data'] ?? 0), 0, ',', '.') ?></div></div>
                <div class="card"><div class="muted">Total Billing</div><div class="badge"><?= number_format((float)($detailStats['billing_total']['data'] ?? 0), 0, ',', '.') ?></div></div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

