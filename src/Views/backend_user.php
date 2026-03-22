<div class="grid cols-3">
    <div class="card"><div class="muted">Total User Legacy</div><div class="stat"><?= (int)($userSummary['total_users'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Terhubung ke Pegawai</div><div class="stat"><?= (int)($userSummary['linked_pegawai'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Rata-rata Akses Aktif</div><div class="stat"><?= (int)($userSummary['avg_access'] ?? 0) ?></div></div>
</div>

<?php if (!empty($userFlash['message'])): ?>
    <div class="card" style="margin-top:14px;border-color:<?= (($userFlash['type'] ?? '') === 'error') ? '#f2c3c3' : '#b7e3d1' ?>;background:<?= (($userFlash['type'] ?? '') === 'error') ? '#fff4f4' : '#f2fcf6' ?>;">
        <strong><?= (($userFlash['type'] ?? '') === 'error') ? 'Error' : 'Sukses' ?>:</strong>
        <?= htmlspecialchars((string)$userFlash['message'], ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (empty($userModuleReady)): ?>
    <div class="card" style="margin-top:14px;border-color:#f2c3c3;background:#fff4f4;">
        <strong>Error:</strong>
        <?= htmlspecialchars((string)($userModuleError ?? 'Modul user tidak bisa dimuat.'), ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php else: ?>
    <div class="grid cols-2" style="margin-top:14px;">
        <div class="card">
            <h3 style="margin-top:0;">Pencarian User</h3>
            <form method="get" class="row" style="margin-top:6px;">
                <input type="hidden" name="module" value="user">
                <input type="text" name="q" value="<?= htmlspecialchars((string)($userSearch ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="username / nama pegawai / nik" style="border:1px solid #d7e6ef;border-radius:8px;padding:9px 10px;min-width:320px;">
                <button type="submit">Cari</button>
                <?php if (trim((string)($userSearch ?? '')) !== ''): ?>
                    <a href="?module=user" class="pill" style="text-decoration:none;">Reset</a>
                <?php endif; ?>
            </form>
            <p class="muted" style="margin-bottom:0;">Menampilkan maksimal 100 user. Badge akses penting diambil dari <?= (int)($userSummary['important_access_cols'] ?? 0) ?> kolom akses utama.</p>
        </div>

        <?php $editingUser = is_array($userEdit ?? null) ? $userEdit : null; ?>
        <?php $selectedPermissions = is_array($editingUser['important_permissions'] ?? null) ? $editingUser['important_permissions'] : []; ?>
        <div class="card">
            <h3 style="margin-top:0;"><?= $editingUser ? 'Edit User Legacy' : 'Tambah User Legacy' ?></h3>
            <form method="post">
                <input type="hidden" name="user_action" value="save_user">
                <input type="hidden" name="original_username" value="<?= htmlspecialchars((string)($editingUser['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

                <div style="margin-top:8px;">
                    <label>Username</label>
                    <input type="text" name="username" required value="<?= htmlspecialchars((string)($editingUser['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="border:1px solid #d7e6ef;border-radius:8px;padding:9px 10px;width:100%;">
                </div>

                <div style="margin-top:10px;">
                    <label>Password <?= $editingUser ? '<span class="muted">(kosongkan jika tidak diubah)</span>' : '' ?></label>
                    <input type="password" name="password" <?= $editingUser ? '' : 'required' ?> style="border:1px solid #d7e6ef;border-radius:8px;padding:9px 10px;width:100%;">
                </div>

                <div style="margin-top:12px;">
                    <div><strong>Akses Penting</strong></div>
                    <div class="row" style="margin-top:8px;">
                        <?php foreach (($userImportantColumns ?? []) as $column): ?>
                            <label style="display:flex;align-items:center;gap:6px;border:1px solid #d7e6ef;border-radius:8px;padding:8px 10px;background:#fbfdff;">
                                <input type="checkbox" name="important_permissions[]" value="<?= htmlspecialchars((string)$column, ENT_QUOTES, 'UTF-8') ?>" <?= !empty($selectedPermissions[$column]) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars((string)$column, ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="row" style="margin-top:12px;">
                    <button type="submit"><?= $editingUser ? 'Simpan Perubahan' : 'Tambah User' ?></button>
                    <?php if ($editingUser): ?>
                        <a href="?module=user" class="pill" style="text-decoration:none;">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h3 style="margin-top:0;">Daftar User Legacy</h3>
        <table>
            <thead>
            <tr>
                <th>Username</th>
                <th>Nama Pegawai</th>
                <th>Jabatan</th>
                <th>Akses Aktif</th>
                <th>Akses Penting</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($userList)): ?>
                <tr><td colspan="6" class="muted">Tidak ada user yang cocok.</td></tr>
            <?php else: ?>
                <?php foreach ($userList as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($user['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($user['nama_pegawai'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($user['jabatan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($user['akses_aktif'] ?? 0) ?></td>
                        <td>
                            <?php $aksesPenting = is_array($user['akses_penting'] ?? null) ? $user['akses_penting'] : []; ?>
                            <?php if (empty($aksesPenting)): ?>
                                <span class="muted">-</span>
                            <?php else: ?>
                                <?php foreach ($aksesPenting as $badge): ?>
                                    <span class="pill"><?= htmlspecialchars((string)$badge, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="?module=user&edit=<?= urlencode((string)($user['username'] ?? '')) ?>" class="pill" style="text-decoration:none;">Edit</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Hapus user legacy ini?');">
                                <input type="hidden" name="user_action" value="delete_user">
                                <input type="hidden" name="username" value="<?= htmlspecialchars((string)($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" style="background:#b23b3b;color:#fff;border:0;border-radius:8px;padding:7px 10px;">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
