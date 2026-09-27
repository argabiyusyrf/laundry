<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="card">
  <div class="card-head"><h2><?= icon('kalender', 17) ?> Pilih Periode Laporan</h2></div>
  <form class="filter" method="get" action="<?= e(form_action()) ?>">
    <input type="hidden" name="r" value="laporan">
    <div class="form-group" style="margin:0">
      <label for="from">Dari Tanggal</label>
      <div class="input-icon" style="max-width:none">
        <span><?= icon('kalender', 16) ?></span>
        <input type="date" id="from" name="from" value="<?= e($from) ?>">
      </div>
    </div>
    <div class="form-group" style="margin:0">
      <label for="to">Sampai Tanggal</label>
      <div class="input-icon" style="max-width:none">
        <span><?= icon('kalender', 16) ?></span>
        <input type="date" id="to" name="to" value="<?= e($to) ?>">
      </div>
    </div>
    <button class="btn btn-primary" type="submit"><?= icon('cari', 15) ?> Tampilkan</button>
    <a class="btn btn-outline" href="<?= url('laporan/export', ['from' => $from, 'to' => $to]) ?>"><?= icon('unduh', 15) ?> Export CSV</a>
  </form>
</div>

<div class="stats">
  <div class="stat">
    <div class="stat-icon"><?= icon('transaksi', 18) ?></div>
    <div class="stat-label">Jumlah Nota</div>
    <div class="stat-value"><?= count($list) ?></div>
    <div class="stat-sub"><?= tglIndo($from) ?> — <?= tglIndo($to) ?></div>
  </div>
  <div class="stat">
    <div class="stat-icon"><?= icon('layanan', 18) ?></div>
    <div class="stat-label">Total Berat</div>
    <div class="stat-value"><?= number_format($totBerat, 2, ',', '.') ?> <?= e(pengaturan('satuan_berat', 'kg')) ?></div>
    <div class="stat-sub">Keseluruhan cucian</div>
  </div>
  <div class="stat">
    <div class="stat-icon"><?= icon('uang', 18) ?></div>
    <div class="stat-label">Nilai Transaksi</div>
    <div class="stat-value money"><?= rupiah($totNilai) ?></div>
    <div class="stat-sub">Total tagihan</div>
  </div>
  <div class="stat">
    <div class="stat-icon"><?= icon('kartu', 18) ?></div>
    <div class="stat-label">Uang Diterima</div>
    <div class="stat-value money"><?= rupiah($totDibayar) ?></div>
    <div class="stat-sub">Pembayaran masuk</div>
  </div>
  <div class="stat">
    <div class="stat-icon"><?= icon('proses', 18) ?></div>
    <div class="stat-label">Belum Selesai</div>
    <div class="stat-value"><?= $belumSelesai ?></div>
    <div class="stat-sub">Masih dalam proses</div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2>Rincian Transaksi</h2></div>
  <?php if (!$list): ?>
    <p class="empty"><?= icon('laporan', 30) ?>Tidak ada transaksi pada rentang tanggal tersebut.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>No Nota</th><th>Tanggal Masuk</th><th>Pelanggan</th>
            <th class="r">Berat</th><th class="r">Total</th><th class="r">Dibayar</th>
            <th>Status</th><th>Bayar</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $t): ?>
            <?php $dibayar = $t['status_bayar'] === 'lunas' ? $t['total_harga'] : $t['uang_muka']; ?>
            <tr>
              <td><a href="<?= url('transaksi/detail/' . (int)$t['id']) ?>"><?= e($t['no_nota']) ?></a></td>
              <td><?= tglIndo($t['tanggal_masuk']) ?></td>
              <td><?= e($t['pelanggan']) ?></td>
              <td class="r num"><?= (float)$t['total_berat'] ?> <?= e(pengaturan('satuan_berat', 'kg')) ?></td>
              <td class="r num"><?= rupiah($t['total_harga']) ?></td>
              <td class="r num"><?= rupiah($dibayar) ?></td>
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