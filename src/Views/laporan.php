<div class="card">
    <h2 style="margin-top:0;">Modul Laporan</h2>
    <p class="muted">Sumber laporan legacy: <code><?= htmlspecialchars((string)$reportDir, ENT_QUOTES, 'UTF-8') ?></code></p>
    <p class="muted">Menampilkan maksimal 200 file report (`.jrxml`/`.jasper`).</p>
</div>

<div class="card" style="margin-top:12px;">
    <table>
        <thead>
            <tr><th>Nama File</th><th>Tipe</th><th class="num">Ukuran</th><th>Path</th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$r['nama'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['tipe'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="num"><?= number_format((float)$r['ukuran'], 0, ',', '.') ?> B</td>
                    <td><code><?= htmlspecialchars((string)$r['path'], ENT_QUOTES, 'UTF-8') ?></code></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

