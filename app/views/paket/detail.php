<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="card">
  <div class="card-head">
    <h2><?= icon('paket', 17) ?> Detail Paket Layanan</h2>
    <div class="inline">
      <a class="btn btn-outline btn-small" href="<?= url('paket') ?>"><?= icon('kembali', 14) ?> Kembali</a>
      <a class="btn btn-outline btn-small" href="<?= url('paket', ['edit' => (int)$k['id']]) ?>"><?= icon('edit', 14) ?> Edit</a>
    </div>
  </div>

  <div class="grid-2">
    <div class="card" style="box-shadow:none">
      <div class="card-head"><h2><?= icon('layanan', 17) ?> Info Layanan</h2></div>
      <div class="table-wrap">
        <table class="table table-kv">
          <tr><td>Nama Paket</td><td class="r"><strong><?= e($k['nama']) ?></strong></td></tr>
          <tr><td>Harga per <?= e(pengaturan('satuan_berat', 'kg')) ?></td><td class="r"><strong><?= rupiah($k['harga_per_kg']) ?></strong></td></tr>
          <tr><td>Estimasi Selesai</td><td class="r"><?= (int)$k['estimasi_hari'] ?> hari</td></tr>
          <tr><td>Keterangan</td><td class="r"><?= e($k['keterangan']) ?: '-' ?></td></tr>
        </table>
      </div>
    </div>
    <div class="card" style="box-shadow:none">
      <div class="card-head"><h2><?= icon('transaksi', 17) ?> Statistik Pemakaian</h2></div>
      <?php
      $totBerat = $totSub = 0;
      foreach ($list as $r) {
          $totBerat += (float)$r['berat'];
          $totSub   += (float)$r['subtotal'];
      }
      ?>
      <div class="table-wrap">
        <table class="table table-kv">
          <tr><td>Sering Dipakai</td><td class="r"><strong><?= count($list) ?>×</strong></td></tr>
          <tr><td>Total Berat</td><td class="r"><?= number_format($totBerat, 2, ',', '.') ?> <?= e(pengaturan('satuan_berat', 'kg')) ?></td></tr>
          <tr><td>Total Nilai</td><td class="r"><strong><?= rupiah($totSub) ?></strong></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2><?= icon('transaksi', 17) ?> Riwayat Pemakaian (<?= count($list) ?>)</h2></div>

  <?php if (!$list): ?>
    <p class="empty"><?= icon('transaksi', 30) ?>Paket ini belum pernah dipakai pada transaksi mana pun.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>No Nota</th><th>Pelanggan</th><th>Masuk</th>
            <th class="r">Berat</th><th class="r">Subtotal</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $r): ?>
            <tr>
              <td><a href="<?= url('transaksi/detail/' . (int)$r['trx_id']) ?>"><?= e($r['no_nota']) ?></a></td>
              <td><?= e($r['pelanggan']) ?></td>
              <td><?= tglIndo($r['tanggal_masuk']) ?></td>
              <td class="r num"><?= (float)$r['berat'] ?> <?= e(pengaturan('satuan_berat', 'kg')) ?></td>
              <td class="r num"><?= rupiah($r['subtotal']) ?></td>
              <td>
                <div class="inline">
                  <a class="btn btn-small btn-outline" href="<?= url('transaksi/detail/' . (int)$r['trx_id']) ?>"><?= icon('lihat', 13) ?> Detail</a>
                  <a class="btn btn-small btn-outline" href="<?= url('transaksi/cetak/' . (int)$r['trx_id']) ?>" target="_blank"><?= icon('printer', 13) ?> Nota</a>
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