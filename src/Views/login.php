<div class="card" style="max-width:460px;margin:8vh auto;">
    <h2 style="margin-top:0;">Login SIMRS Web</h2>
    <p class="muted">Gunakan user/password yang sama seperti aplikasi Java Khanza.</p>
    <?php if (!empty($error)): ?>
        <p class="pill" style="border-color:#f4b4b4;background:#ffecec;color:#8b1b1b;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
    <form method="post">
        <div class="field" style="margin-bottom:10px;">
            <label>ID User</label>
            <input type="text" name="username" value="<?= htmlspecialchars((string)($username ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" required>
        </div>
        <div class="field" style="margin-bottom:12px;">
            <label>Password</label>
            <input type="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit">Masuk</button>
    </form>
</div>
