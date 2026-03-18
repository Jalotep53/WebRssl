<div class="card">
    <h2 style="margin-top:0;">Konfigurasi Aplikasi Legacy</h2>
    <p class="muted">Total key konfigurasi terdeteksi: <strong><?= (int)$count ?></strong>. Nilai sensitif tidak ditampilkan.</p>
</div>

<?php foreach ($groups as $name => $keys): ?>
    <div class="card" style="margin-top:12px;">
        <h3 style="margin-top:0;"><?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') ?> <span class="muted">(<?= count($keys) ?>)</span></h3>
        <div class="row">
            <?php foreach ($keys as $k): ?>
                <span class="pill"><?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

