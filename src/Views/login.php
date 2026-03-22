<div style="min-height:100vh;display:grid;place-items:center;padding:24px;">
    <div class="card" style="width:min(980px,100%);overflow:hidden;padding:0;">
        <div style="display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);">
            <section style="padding:42px;background:linear-gradient(180deg,#0d6efd 0%,#4da3ff 100%);color:#fff;">
                <div class="pill" style="background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.2);color:#fff;">Portal Rumah Sakit</div>
                <h1 style="margin:18px 0 10px;color:#fff;font-size:34px;line-height:1.2;"><?= htmlspecialchars((string)($appName ?? 'SIMRS Web'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p style="margin:0;color:rgba(255,255,255,.88);max-width:460px;">Satu akses untuk operasional harian, registrasi pasien, rawat jalan, rawat inap, farmasi, dan modul administrasi.</p>
            </section>
            <section style="padding:42px 34px;">
                <h2 style="margin:0 0 8px;">Sign In</h2>
                <p class="muted" style="margin:0 0 18px;">Silakan masuk menggunakan akun Anda.</p>
                <?php if (!empty($error)): ?>
                    <p class="pill" style="border-color:#f4b4b4;background:#ffecec;color:#8b1b1b;margin-bottom:14px;">
                        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
                <form method="post">
                    <div class="field" style="margin-bottom:12px;">
                        <label>ID User</label>
                        <input type="text" name="username" value="<?= htmlspecialchars((string)($username ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" required>
                    </div>
                    <div class="field" style="margin-bottom:16px;">
                        <label>Password</label>
                        <input type="password" name="password" autocomplete="current-password" required>
                    </div>
                    <button type="submit" style="width:100%;">Masuk</button>
                </form>
            </section>
        </div>
    </div>
</div>
