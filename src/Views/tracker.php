<div class="card">
    <h2 style="margin-top:0;">Tracker SQL</h2>
    <form method="get" class="row" style="margin:10px 0 12px;">
        <input type="hidden" name="page" value="tracker">
        <div class="field">
            <label>Dari Tanggal</label>
            <input type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>Sampai Tanggal</label>
            <input type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label>User</label>
            <input type="text" name="user" value="<?= htmlspecialchars((string)$user, ENT_QUOTES, 'UTF-8') ?>" placeholder="kode user">
        </div>
        <div class="field">
            <label>Kata Kunci SQL</label>
            <input type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="contoh: insert into billing">
        </div>
        <div class="field">
            <label>Limit</label>
            <input type="number" min="20" max="1000" name="limit" value="<?= (int)$limit ?>">
        </div>
        <button type="submit">Filter</button>
    </form>
    <div class="row">
        <span class="pill">Total SQL sesuai filter: <strong><?= (int)$totalSql ?></strong></span>
        <span class="pill">URL: <strong>?page=tracker</strong></span>
    </div>
    <?php if (!empty($errorSql)): ?>
        <p class="muted">Gagal baca trackersql: <?= htmlspecialchars((string)$errorSql, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;">Log SQL (trackersql)</h3>
    <table>
        <thead>
        <tr>
            <th style="width:180px;">Tanggal</th>
            <th style="width:160px;">User</th>
            <th>SQL</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($rowsSql)): ?>
            <tr><td colspan="3" class="muted">Belum ada data SQL</td></tr>
        <?php else: ?>
            <?php foreach ($rowsSql as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$r['tanggal'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['usere'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="white-space:pre-wrap;word-break:break-word;max-width:0;"><?= htmlspecialchars((string)$r['sqle'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top:12px;">
    <h3 style="margin-top:0;">Log Login (tracker)</h3>
    <?php if (!empty($errorLogin)): ?>
        <p class="muted">Gagal baca tracker login: <?= htmlspecialchars((string)$errorLogin, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <table>
        <thead>
        <tr>
            <th style="width:180px;">Tanggal</th>
            <th style="width:120px;">Jam</th>
            <th>User</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($rowsLogin)): ?>
            <tr><td colspan="3" class="muted">Belum ada data login</td></tr>
        <?php else: ?>
            <?php foreach ($rowsLogin as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$r['tgl_login'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['jam_login'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['nip'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

