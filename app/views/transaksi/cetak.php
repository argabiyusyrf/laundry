<?php /* Nota cetak — layout standalone (tanpa sidebar) */ ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nota <?= e($t['no_nota']) ?> — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= asset_url('style.css') ?>">
</head>
<body class="cetak-body" data-cetak="1">
<div class="nota">

  <div class="nota-head">
    <div>
      <div class="nota-brand">
        <?php $logoStruk = pengaturan('logo', ''); ?>
        <?php if ($logoStruk && is_file(BASE_PATH . '/asset/logo/' . basename($logoStruk))): ?>
          <img class="nota-logo" src="<?= e(base_path() . '/asset/logo/' . rawurlencode(basename($logoStruk))) ?>" alt="Logo">
        <?php else: ?>
          <?= icon('logo', 22) ?>
        <?php endif; ?>
        <?= e(pengaturan('nama_usaha', APP_NAME)) ?>
      </div>
      <?php
      $alamatUsaha = pengaturan('alamat_usaha', '');
      $telpUsaha   = pengaturan('telepon_usaha', '');
      $waUsaha     = pengaturan('whatsapp', '');
      $igUsaha     = pengaturan('instagram', '');
      $emailUsaha  = pengaturan('email_usaha', '');
      $jamBuka     = pengaturan('jam_buka', '');
      $jamTutup    = pengaturan('jam_tutup', '');
      ?>
      <?php if ($alamatUsaha || $telpUsaha): ?>
        <div class="nota-sub"><?= e($alamatUsaha) ?><?= $alamatUsaha && $telpUsaha ? ' — ' : '' ?><?= $telpUsaha ? 'Telp: ' . e($telpUsaha) : '' ?></div>
      <?php endif; ?>
      <?php $kontakExtra = array_values(array_filter([
        $waUsaha ? 'WA ' . e($waUsaha) : '',
        $igUsaha ? 'IG ' . e($igUsaha) : '',
        $emailUsaha ? e($emailUsaha) : '',
      ])); ?>
      <?php if ($kontakExtra): ?>
        <div class="nota-sub"><?= implode(' • ', $kontakExtra) ?></div>
      <?php endif; ?>
      <?php if ($jamBuka || $jamTutup): ?>
        <div class="nota-sub">Buka <?= e($jamBuka) ?><?= $jamTutup ? ' - ' . e($jamTutup) : '' ?></div>
      <?php endif; ?>
    </div>
    <div class="nota-no">
      <div class="nota-label">Nota</div>
      <div class="nota-nomor"><?= e($t['no_nota']) ?></div>
    </div>
  </div>

  <?php if (pengaturan('header_struk', '')): ?>
    <div class="nota-pesan"><?= nl2br(e(pengaturan('header_struk'))) ?></div>
  <?php endif; ?>

  <div class="nota-info">
    <div><span>Tanggal Masuk</span><?= tglIndo($t['tanggal_masuk']) ?></div>
    <div><span>Estimasi Selesai</span><?= tglIndo($t['estimasi_selesai']) ?></div>
    <div><span>Status</span><?= badgeStatus($t['status']) ?></div>
    <div><span>Pembayaran</span><?= badgeBayar($t['status_bayar']) ?></div>
  </div>

  <div class="nota-pelanggan">
    <div class="nota-label">Untuk</div>
    <div class="nama"><?= e($t['pelanggan']) ?></div>
    <div><?= e($t['telepon']) ?: '-' ?> • <?= e($t['alamat']) ?: '-' ?></div>
  </div>

  <table class="table">
    <thead>
      <tr><th>Layanan</th><th class="r">Berat</th><th class="r">Subtotal</th></tr>
    </thead>
    <tbody>
      <?php foreach ($detail as $d): ?>
        <tr>
          <td><?= e($d['paket']) ?></td>
          <td class="r"><?= (float)$d['berat'] ?> <?= e(pengaturan('satuan_berat', 'kg')) ?></td>
          <td class="r"><?= rupiah($d['subtotal']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="nota-total">
    <div><span>Total Berat</span><span><?= (float)$t['total_berat'] ?> <?= e(pengaturan('satuan_berat', 'kg')) ?></span></div>
    <?php if (((float)($t['pajak'] ?? 0)) > 0 || ((float)($t['diskon'] ?? 0)) > 0): ?>
      <div><span>Subtotal</span><span><?= rupiah((float)$t['total_harga'] - (float)$t['pajak'] + (float)$t['diskon']) ?></span></div>
    <?php endif; ?>
    <?php if ((float)($t['pajak'] ?? 0) > 0): ?>
      <div><span>Pajak Layanan (<?= round((float)pengaturan('pajak_persen', '0'), 1) ?>%)</span><span><?= rupiah($t['pajak']) ?></span></div>
    <?php endif; ?>
    <?php if ((float)($t['diskon'] ?? 0) > 0): ?>
      <div><span>Diskon</span><span>- <?= rupiah($t['diskon']) ?></span></div>
    <?php endif; ?>
    <div><span>Total Harga</span><span><?= rupiah($t['total_harga']) ?></span></div>
    <div><span>Uang Muka</span><span><?= rupiah($t['uang_muka']) ?></span></div>
    <div class="sisa"><span>Sisa Bayar</span><span><?= rupiah($sisa) ?></span></div>
  </div>

  <div class="nota-foot">
    <?php if (pengaturan('promo_struk', '')): ?>
      <p class="nota-promo"><?= nl2br(e(pengaturan('promo_struk'))) ?></p>
    <?php endif; ?>
    <?php if (pengaturan('footer_struk', '')): ?>
      <p><?= nl2br(e(pengaturan('footer_struk'))) ?></p>
    <?php endif; ?>
    <?php if (pengaturan('catatan_struk', '')): ?>
      <p><?= nl2br(e(pengaturan('catatan_struk'))) ?></p>
    <?php endif; ?>
    <p>Struk ini dicetak otomatis oleh sistem — <?= tglIndo(date('Y-m-d'), true) ?></p>
    <div class="no-print">
      <a class="btn btn-outline" href="<?= url('transaksi/detail/' . (int)$t['id']) ?>"><?= icon('kembali', 15) ?> Kembali</a>
      <button class="btn btn-primary" onclick="window.print()"><?= icon('printer', 15) ?> Cetak</button>
    </div>
  </div>

</div>
</body>
</html>