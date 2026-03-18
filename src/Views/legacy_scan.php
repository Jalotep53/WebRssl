<div class="card">
    <h2 style="margin-top:0;">Hasil Scan Keseluruhan SIMRS Legacy</h2>
    <p class="muted">Sumber scan: <code><?= htmlspecialchars($legacyRoot, ENT_QUOTES, 'UTF-8') ?></code></p>
    <div class="cards" style="margin-top:10px;">
        <div class="card"><div class="muted">Total Mapping Menu</div><div class="badge"><?= (int)$menuCount ?></div></div>
        <div class="card"><div class="muted">Total Permission</div><div class="badge"><?= (int)$permissionCount ?></div></div>
        <div class="card"><div class="muted">Config Keys</div><div class="badge"><?= (int)$configCount ?></div></div>
    </div>
</div>

<div class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;">Top Package Java</h3>
    <table>
        <thead>
            <tr><th>Package</th><th class="num">Jumlah File</th></tr>
        </thead>
        <tbody>
            <?php foreach ($packages as $p): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$p['package'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="num"><?= (int)$p['count'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;">Alur Utama yang Ditiru ke Web</h3>
    <ol>
        <li>Registrasi pasien dan pemilihan poli/cara bayar.</li>
        <li>Pelayanan rawat jalan (tindakan dokter/perawat, lab, radiologi).</li>
        <li>Farmasi: resep, pemberian obat, stok, dan kategori obat/BMHP/gas medis.</li>
        <li>Billing: agregasi tindakan + farmasi + PPN + pembayaran/piutang.</li>
        <li>Laporan operasional, keuangan, dan grafik analitik.</li>
    </ol>
</div>

