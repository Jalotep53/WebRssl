<div class="card" style="max-width:760px;margin:12px auto;">
    <h2 style="margin-top:0;">Akses Ditolak (403)</h2>
    <p class="muted"><?= htmlspecialchars((string)($message ?? 'Anda tidak memiliki izin untuk membuka halaman ini.'), ENT_QUOTES, 'UTF-8') ?></p>
    <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
        <a href="?page=dashboard" style="display:inline-block;padding:10px 14px;border-radius:8px;border:1px solid #d7e6ef;background:#fff;color:#1d2b36;text-decoration:none;">Kembali ke Dashboard</a>
        <a href="?page=logout" style="display:inline-block;padding:10px 14px;border-radius:8px;border:1px solid #d7e6ef;background:#fff;color:#1d2b36;text-decoration:none;">Ganti User</a>
    </div>
</div>
