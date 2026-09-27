<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="card card-info">
  <span class="ci-ico"><?= icon('info', 17) ?></span>
  <div>
    <strong>Log Aktivitas</strong> mencatat setiap tindakan penting: login/logout, transaksi,
    pelanggan, paket, pembayaran, pengaturan, dan kelola akun — lengkap dengan waktu, pelaku, dan IP.
    Untuk memeriksa siapa melakukan apa di sistem.
  </div>
</div>

<div class="card">
  <div class="card-head">
    <h2><?= icon('riwayat', 17) ?> Riwayat Aktivitas</h2>
    <span class="hint">Terbaru 200 catatan</span>
  </div>

  <?php if (!$list): ?>
    <p class="empty"><?= icon('riwayat', 30) ?>Belum ada aktivitas tercatat.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th style="min-width:150px">Waktu</th><th>Pengguna</th><th>Peran</th><th>Aksi</th><th>Keterangan</th><th>IP</th></tr>
        </thead>
        <tbody>
          <?php foreach ($list as $a): ?>
            <tr>
              <td class="hint"><?= tglIndo($a['created_at'], true) ?></td>
              <td>
                <?php if ($a['nama_user']): ?>
                  <strong><?= e($a['nama_user']) ?></strong>
                  <div class="hint">@<?= e($a['username']) ?></div>
                <?php else: ?>
                  <span class="hint">(sistem)</span>
                <?php endif; ?>
              </td>
              <td><?= $a['role_user'] ? badgeRole((string)$a['role_user']) : '<span class="hint">-</span>' ?></td>
              <td><span class="badge badge-ok"><?= e($a['aksi']) ?></span></td>
              <td><?= e($a['keterangan'] ?: '-') ?></td>
              <td class="hint"><?= e($a['ip'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>