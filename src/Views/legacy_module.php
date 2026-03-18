<div class="card">
    <?php if (!$found): ?>
        <h2 style="margin-top:0;">Modul Legacy tidak ditemukan</h2>
        <p class="muted">Parameter menu tidak valid. Kembali ke <a href="?page=menu-catalog">Katalog Menu</a>.</p>
    <?php else: ?>
        <h2 style="margin-top:0;"><?= htmlspecialchars((string)$selected['label'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="muted">Permission: <code><?= htmlspecialchars((string)$selected['permission'], ENT_QUOTES, 'UTF-8') ?></code> | Komponen: <code><?= htmlspecialchars((string)$selected['button'], ENT_QUOTES, 'UTF-8') ?></code></p>
        <?php if ($implemented): ?>
            <p>Modul ini <strong>sudah ada implementasi web</strong>.</p>
            <p><a href="?page=<?= htmlspecialchars((string)$targetPage, ENT_QUOTES, 'UTF-8') ?>">Buka modul web</a></p>
        <?php else: ?>
            <p>Modul ini <strong>belum diimplementasi penuh</strong> di web, tapi sudah terdaftar di peta migrasi.</p>
            <p class="muted">Langkah berikutnya: buat controller + view + query sesuai alur modul ini.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

