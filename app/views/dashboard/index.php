<?php
include __DIR__ . '/../partials/header.php';
?>

<?php $namaUser = $_SESSION['user']['nama'] ?? 'Administrator'; ?>
<div class="hero">
  <div class="hero-info">
    <p class="hero-eyebrow">Dashboard</p>
    <h1 class="hero-title">Selamat datang, <?= e($namaUser) ?></h1>
    <p class="hero-sub"><?= tglIndo(date('Y-m-d')) ?> &nbsp;•&nbsp; Ringkasan operasional LaundryKu hari ini.</p>
  </div>
  <div class="hero-actions">
    <?php if ($bolehLaporan): ?>
    <a class="btn btn-light" href="<?= url('laporan') ?>"><?= icon('laporan', 15) ?> Lihat Laporan</a>
    <?php endif; ?>
    <a class="btn btn-primary" href="<?= url('transaksi/baru') ?>"><?= icon('plus', 15) ?> Buat Nota Baru</a>
  </div>
</div>

<div class="stats">
  <div class="stat">
    <div class="stat-icon"><?= icon('pelanggan', 18) ?></div>
    <div class="stat-label">Pelanggan Terdaftar</div>
    <div class="stat-value"><?= $stat['pelanggan'] ?></div>
    <div class="stat-sub"><a href="<?= url('pelanggan') ?>">Kelola pelanggan →</a></div>
  </div>
  <div class="stat">
    <div class="stat-icon"><?= icon('transaksi', 18) ?></div>
    <div class="stat-label">Nota Hari Ini</div>
    <div class="stat-value"><?= $stat['nota_hari_ini'] ?></div>
    <div class="stat-sub"><?= $bolehLaporan ? 'Nilai: ' . rupiah($stat['nilai_hari_ini']) : 'Nota masuk hari ini' ?></div>
  </div>
  <div class="stat">
    <div class="stat-icon"><?= icon('proses', 18) ?></div>
    <div class="stat-label">Dalam Proses</div>
    <div class="stat-value"><?= $stat['proses'] ?></div>
    <div class="stat-sub">Belum selesai</div>
  </div>
  <div class="stat">
    <div class="stat-icon"><?= icon('siap', 18) ?></div>
    <div class="stat-label">Siap Diambil</div>
    <div class="stat-value"><?= $stat['siap_diambil'] ?></div>
    <div class="stat-sub"><a href="<?= url('transaksi', ['status' => 'siap_diambil']) ?>">Lihat detail →</a></div>
  </div>
  <?php if ($bolehLaporan): ?>
  <div class="stat">
    <div class="stat-icon"><?= icon('uang', 18) ?></div>
    <div class="stat-label">Pendapatan Bulan Ini</div>
    <div class="stat-value money"><?= rupiah($stat['nilai_bulan_ini']) ?></div>
    <div class="stat-sub">Dari total nota masuk bulan ini</div>
  </div>
  <?php endif; ?>
</div>

<div class="grid-2 dash-grid">
  <div class="card">
    <div class="card-head">
      <h2><?= icon('siap', 17) ?> Cucian Siap Diambil</h2>
      <a class="btn btn-small" href="<?= url('transaksi', ['status' => 'siap_diambil']) ?>">Lihat semua</a>
    </div>
    <?php if (!$siapDiambil): ?>
      <p class="empty"><?= icon('siap', 30) ?>Belum ada cucian yang menunggu diambil.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table table-dash">
          <thead>
            <tr><th>No Nota</th><th>Pelanggan</th><th>Masuk</th><th class="r">Total</th></tr>
          </thead>
          <tbody>
            <?php foreach ($siapDiambil as $t): ?>
              <tr>
                <td><a href="<?= url('transaksi/detail/' . $t['id']) ?>"><?= e($t['no_nota']) ?></a></td>
                <td><?= e($t['pelanggan']) ?></td>
                <td><?= tglIndo($t['tanggal_masuk']) ?></td>
                <td class="r num"><?= rupiah($t['total_harga']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head">
      <h2><?= icon('transaksi', 17) ?> Transaksi Terbaru</h2>
      <a class="btn btn-small btn-primary" href="<?= url('transaksi/baru') ?>"><?= icon('plus', 14) ?> Buat Nota</a>
    </div>
    <?php if (!$terbaru): ?>
      <p class="empty"><?= icon('transaksi', 30) ?>Belum ada transaksi. Mulai dengan membuat nota baru.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table table-dash">
          <thead>
            <tr><th>No Nota</th><th>Pelanggan</th><th class="r">Total</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($terbaru as $t): ?>
              <tr>
                <td><a href="<?= url('transaksi/detail/' . $t['id']) ?>"><?= e($t['no_nota']) ?></a></td>
                <td><?= e($t['pelanggan']) ?></td>
                <td class="r num"><?= rupiah($t['total_harga']) ?></td>
                <td><?= badgeStatus($t['status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>