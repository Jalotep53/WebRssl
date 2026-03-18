<div class="grid cols-3">
    <div class="card"><div class="muted">Total User RBAC</div><div class="stat"><?= (int)($rbacSummary['users'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Total Role</div><div class="stat"><?= (int)($rbacSummary['roles'] ?? 0) ?></div></div>
    <div class="card"><div class="muted">Total Permission</div><div class="stat"><?= (int)($rbacSummary['permissions'] ?? 0) ?></div></div>
</div>

<?php if (empty($rbacTableReady)): ?>
    <div class="card" style="margin-top:14px;">
        <h3 style="margin-top:0;">Schema RBAC Belum Siap</h3>
        <p class="muted">Tabel <code>web_users</code>, <code>web_roles</code>, <code>web_permissions</code>, <code>web_role_permissions</code>, <code>web_user_roles</code> belum lengkap.</p>
        <p class="muted">Jalankan script setup: <code>php WebBaru/tools/setup_web_rbac.php</code></p>
    </div>
<?php else: ?>
    <?php if (!empty($rbacFlash['message'])): ?>
        <div class="card" style="margin-top:14px;border-color:<?= (($rbacFlash['type'] ?? '') === 'error') ? '#f2c3c3' : '#b7e3d1' ?>;background:<?= (($rbacFlash['type'] ?? '') === 'error') ? '#fff4f4' : '#f2fcf6' ?>;">
            <strong><?= (($rbacFlash['type'] ?? '') === 'error') ? 'Error' : 'Sukses' ?>:</strong>
            <?= htmlspecialchars((string)$rbacFlash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="grid cols-2" style="margin-top:14px;">
        <div class="card">
            <h3 style="margin-top:0;">Cari User SIMRS</h3>
            <form method="get" class="row" style="margin-top:6px;">
                <input type="hidden" name="module" value="rbac">
                <input type="text" name="u" value="<?= htmlspecialchars((string)($rbacUserSearch ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="ketik id_user/nik" style="border:1px solid #d7e6ef;border-radius:8px;padding:9px 10px;min-width:260px;">
                <button type="submit">Cari</button>
            </form>
            <?php if (!empty($rbacUserCandidates)): ?>
                <table style="margin-top:10px;">
                    <thead><tr><th>Username</th><th>Nama Pegawai</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($rbacUserCandidates as $cand): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($cand['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($cand['nama'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><a href="?module=rbac&edit=<?= urlencode((string)($cand['username'] ?? '')) ?>" class="pill" style="text-decoration:none;">Pilih</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (trim((string)($rbacUserSearch ?? '')) !== ''): ?>
                <p class="muted" style="margin-top:8px;">Tidak ada user sesuai pencarian.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 style="margin-top:0;"><?= !empty($rbacEdit) ? 'Edit RBAC User' : 'Tambah RBAC User' ?></h3>
            <form method="post">
                <input type="hidden" name="rbac_action" value="save_user">
                <div class="row" style="margin-top:8px;">
                    <div style="min-width:260px;">
                        <label>Username</label>
                        <input type="text" name="username" required value="<?= htmlspecialchars((string)($rbacEdit['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="border:1px solid #d7e6ef;border-radius:8px;padding:9px 10px;min-width:260px;">
                    </div>
                    <div style="min-width:260px;">
                        <label>Nama Tampilan</label>
                        <input type="text" name="display_name" value="<?= htmlspecialchars((string)($rbacEdit['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="border:1px solid #d7e6ef;border-radius:8px;padding:9px 10px;min-width:260px;">
                    </div>
                    <div style="min-width:180px;">
                        <label>Status</label>
                        <select name="is_active" style="border:1px solid #d7e6ef;border-radius:8px;padding:9px 10px;min-width:180px;">
                            <option value="1" <?= ((string)($rbacEdit['is_active'] ?? '1') === '1') ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= ((string)($rbacEdit['is_active'] ?? '1') === '0') ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top:10px;">
                    <div><strong>Pilih Role</strong></div>
                    <div class="row" style="margin-top:8px;">
                        <?php $selectedRoleIds = array_map('intval', (array)($rbacEdit['role_ids'] ?? [])); ?>
                        <?php foreach (($rbacRoles ?? []) as $role): ?>
                            <label style="display:flex;align-items:center;gap:6px;border:1px solid #d7e6ef;border-radius:8px;padding:8px 10px;background:#fbfdff;">
                                <input type="checkbox" name="role_ids[]" value="<?= (int)$role['id'] ?>" <?= in_array((int)$role['id'], $selectedRoleIds, true) ? 'checked' : '' ?> >
                                <span><?= htmlspecialchars((string)$role['role_code'], ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="row" style="margin-top:12px;">
                    <button type="submit">Simpan RBAC User</button>
                    <?php if (!empty($rbacEdit)): ?>
                        <a href="?module=rbac" class="pill" style="text-decoration:none;">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h3 style="margin-top:0;">Daftar User RBAC</h3>
        <table>
            <thead><tr><th>Username</th><th>Nama</th><th>Status</th><th>Roles</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach (($rbacUsers ?? []) as $u): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$u['username'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$u['display_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= ((int)$u['is_active'] === 1) ? 'Aktif' : 'Nonaktif' ?></td>
                    <td><?= htmlspecialchars((string)$u['roles'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="white-space:nowrap;">
                        <a href="?module=rbac&edit=<?= urlencode((string)$u['username']) ?>" class="pill" style="text-decoration:none;">Edit</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Hapus user RBAC ini?');">
                            <input type="hidden" name="rbac_action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <button type="submit" style="background:#b23b3b;color:#fff;border:0;border-radius:8px;padding:7px 10px;">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="grid cols-2" style="margin-top:14px;">
        <div class="card">
            <h3 style="margin-top:0;">Role & Permission</h3>
            <table>
                <thead><tr><th>Role Code</th><th>Role Name</th><th>Status</th><th>Permission</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach (($rbacRoles ?? []) as $role): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$role['role_code'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$role['role_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= ((int)$role['is_active'] === 1) ? 'Aktif' : 'Nonaktif' ?></td>
                        <td><?= (int)$role['permission_count'] ?></td>
                        <td><a href="?module=rbac&edit_role=<?= (int)$role['id'] ?>" class="pill" style="text-decoration:none;">Atur Permission</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <?php $roleEdit = is_array($rbacRoleEdit ?? null) ? $rbacRoleEdit : null; ?>
            <h3 style="margin-top:0;"><?= !empty($roleEdit) ? 'Atur Permission Role: ' . htmlspecialchars((string)$roleEdit['role_code'], ENT_QUOTES, 'UTF-8') : 'Pilih Role untuk Atur Permission' ?></h3>
            <?php if (!empty($roleEdit)): ?>
                <form method="post">
                    <input type="hidden" name="rbac_action" value="save_role_permissions">
                    <input type="hidden" name="role_id" value="<?= (int)$roleEdit['id'] ?>">
                    <?php $selectedPermissionIds = array_map('intval', (array)($roleEdit['permission_ids'] ?? [])); ?>

                    <div style="margin:8px 0;">
                        <input type="text" id="permissionSearchInput" placeholder="Cari permission code / nama..." style="border:1px solid #d7e6ef;border-radius:8px;padding:9px 10px;width:100%;">
                    </div>

                    <div id="permissionListWrap" style="max-height:360px;overflow:auto;border:1px solid #d7e6ef;border-radius:10px;padding:8px;background:#fbfdff;">
                        <?php foreach (($rbacPermissions ?? []) as $perm): ?>
                            <label data-perm-item="1" data-perm-search="<?= htmlspecialchars(strtolower((string)$perm['permission_code'] . ' ' . (string)$perm['permission_name']), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;align-items:flex-start;gap:8px;padding:6px 4px;border-bottom:1px solid #e8eff5;">
                                <input type="checkbox" name="permission_ids[]" value="<?= (int)$perm['id'] ?>" <?= in_array((int)$perm['id'], $selectedPermissionIds, true) ? 'checked' : '' ?> >
                                <span>
                                    <strong><?= htmlspecialchars((string)$perm['permission_code'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                    <span class="muted"><?= htmlspecialchars((string)$perm['permission_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                        <div id="permissionEmptyState" class="muted" style="display:none;padding:8px 4px;">Permission tidak ditemukan.</div>
                    </div>
                    <div class="row" style="margin-top:12px;">
                        <button type="submit">Simpan Permission Role</button>
                        <a href="?module=rbac" class="pill" style="text-decoration:none;">Batal</a>
                    </div>
                </form>
                <script>
                    (function () {
                        var input = document.getElementById('permissionSearchInput');
                        var wrap = document.getElementById('permissionListWrap');
                        var empty = document.getElementById('permissionEmptyState');
                        if (!input || !wrap || !empty) return;
                        var items = Array.prototype.slice.call(wrap.querySelectorAll('[data-perm-item="1"]'));
                        function filter() {
                            var q = String(input.value || '').toLowerCase().trim();
                            var visible = 0;
                            items.forEach(function (el) {
                                var text = String(el.getAttribute('data-perm-search') || '');
                                var show = q === '' || text.indexOf(q) !== -1;
                                el.style.display = show ? 'flex' : 'none';
                                if (show) visible++;
                            });
                            empty.style.display = visible === 0 ? 'block' : 'none';
                        }
                        input.addEventListener('input', filter);
                    })();
                </script>
            <?php else: ?>
                <p class="muted">Klik tombol <strong>Atur Permission</strong> pada tabel role di kiri.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

