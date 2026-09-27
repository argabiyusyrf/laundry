<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="card">
  <div class="card-head">
    <h2><?= icon('lonceng', 16) ?> Semua Notifikasi (<?= count($list) ?>)</h2>
    <?php if ($belum > 0): ?>
      <form method="post" action="<?= url('notifikasi/baca-semua') ?>" class="inline">
        <input type="hidden" name="kembali" value="<?= e(form_action()) ?>">
        <button class="btn btn-primary btn-small" type="submit"><?= icon('check', 14) ?> Tandai Semua Dibaca</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!$list): ?>
    <p class="empty"><?= icon('lonceng', 30) ?> Belum ada notifikasi. Notifikasi muncul otomatis saat ada transaksi, status, atau pembayaran baru.</p>
  <?php else: ?>
    <div class="notif-page">
      <?php foreach ($list as $n): ?>
        <div class="notif-row">
          <a class="notif-item<?= $n['dibaca'] ? '' : ' baru' ?>" href="<?= url('notifikasi/baca/' . (int)$n['id']) ?>">
            <span class="notif-ico"><?= icon(notifIkonJenis($n['jenis']), 16) ?></span>
            <span class="notif-txt">
              <span class="notif-j"><?= e($n['judul']) ?></span>
              <span class="notif-p"><?= e($n['pesan']) ?></span>
              <span class="notif-meta">
                <?php if (!empty($n['pengirim'])): ?>
                  <span class="notif-s"><?= icon('kirim', 11) ?> Dari: <?= e($n['pengirim']) ?></span>
                <?php endif; ?>
                <span class="notif-w"><?= waktuRelatif($n['created_at']) ?></span>
                <?php if (!$n['dibaca']): ?>
                  <span class="notif-st">Belum dibaca</span>
                <?php endif; ?>
              </span>
            </span>
            <span class="notif-go"><?= icon('chevron', 15) ?></span>
          </a>
          <?php if (!empty($n['bisaKelola'])): ?>
            <span class="notif-ud">
              <a class="btn btn-small btn-outline" href="<?= url('notifikasi/edit/' . (int)$n['id']) ?>"><?= icon('edit', 13) ?> Edit</a>
              <form method="post" action="<?= url('notifikasi/hapus/' . (int)$n['id']) ?>" class="inline js-hapus">
                <button class="btn btn-small btn-danger" type="submit"><?= icon('hapus', 13) ?> Hapus</button>
              </form>
            </span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>