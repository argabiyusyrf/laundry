<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="card">
  <div class="card-head">
    <h2><?= icon('pelanggan', 17) ?> Detail Pelanggan</h2>
    <div class="inline">
      <a class="btn btn-outline btn-small" href="<?= url('pelanggan') ?>"><?= icon('kembali', 14) ?> Kembali</a>
      <a class="btn btn-outline btn-small" href="<?= url('pelanggan', ['edit' => (int)$p['id']]) ?>"><?= icon('edit', 14) ?> Edit</a>
    </div>
  </div>

  <div class="grid-2">
    <div class="card" style="box-shadow:none">
      <div class="card-head"><h2><?= icon('user', 17) ?> Profil</h2></div>
      <div class="table-wrap">
        <table class="table table-kv">
          <tr><td>Nama</td><td class="r"><strong><?= e($p['nama']) ?></strong></td></tr>
          <tr><td>Telepon / WA</td><td class="r"><?= e($p['telepon']) ?: '-' ?></td></tr>
          <tr><td>Alamat</td><td class="r"><?= e($p['alamat']) ?: '-' ?></td></tr>
          <tr><td>Terdaftar</td><td class="r"><?= tglIndo($p['created_at']) ?></td></tr>
        </table>
      </div>
    </div>
    <div class="card" style="box-shadow:none">
      <div class="card-head"><h2><?= icon('transaksi', 17) ?> Ringkasan</h2></div>
      <div class="table-wrap">
        <table class="table table-kv">
          <tr><td>Total Transaksi</td><td class="r"><strong><?= count($list) ?></strong></td></tr>
          <?php
          $totNilai = $totBerat = 0;
          foreach ($list as $t) {
              $totNilai += (float)$t['total_harga'];
              $totBerat += (float)$t['total_berat'];
          }
          ?>
          <tr><td>Total Belanja</td><td class="r"><strong><?= rupiah($totNilai) ?></strong></td></tr>
          <tr><td>Total Berat</td><td class="r"><?= number_format($totBerat, 2, ',', '.') ?> <?= e(pengaturan('satuan_berat', 'kg')) ?></td></tr>
          <tr><td>Nota Belum Lunas</td><td class="r"><?= count(array_filter($list, function ($t) { return $t['status_bayar'] !== 'lunas'; })) ?></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2><?= icon('transaksi', 17) ?> Riwayat Transaksi (<?= count($list) ?>)</h2></div>

  <?php if (!$list): ?>
    <p class="empty"><?= icon('transaksi', 30) ?>Belum ada transaksi atas nama pelanggan ini.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>No Nota</th><th>Masuk</th><th class="r">Berat</th>
            <th class="r">Total</th><th>Status</th><th>Bayar</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $t): ?>
            <tr>
              <td><a href="<?= url('transaksi/detail/' . (int)$t['id']) ?>"><?= e($t['no_nota']) ?></a></td>
              <td><?= tglIndo($t['tanggal_masuk']) ?></td>
              <td class="r num"><?= (float)$t['total_berat'] ?> <?= e(pengaturan('satuan_berat', 'kg')) ?></td>
              <td class="r num"><?= rupiah($t['total_harga']) ?></td>
              <td><?= badgeStatus($t['status']) ?></td>
              <td><?= badgeBayar($t['status_bayar']) ?></td>
              <td>
                <div class="inline">
                  <a class="btn btn-small btn-outline" href="<?= url('transaksi/detail/' . (int)$t['id']) ?>"><?= icon('lihat', 13) ?> Detail</a>
                  <a class="btn btn-small btn-outline" href="<?= url('transaksi/cetak/' . (int)$t['id']) ?>" target="_blank"><?= icon('printer', 13) ?> Nota</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>