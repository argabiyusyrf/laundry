<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="card no-print">
  <div class="card-head">
    <h2><?= icon('transaksi', 17) ?> Nota <?= e($t['no_nota']) ?> <span style="font-weight:400;color:var(--ink-500);font-size:13px"><?= badgeStatus($t['status']) ?></span></h2>
    <div class="inline">
      <a class="btn btn-outline btn-small" href="<?= url('transaksi') ?>"><?= icon('kembali', 14) ?> Kembali</a>
      <?php if ($bolehEdit): ?>
      <a class="btn btn-outline btn-small" href="<?= url('transaksi/edit/' . (int)$t['id']) ?>"><?= icon('edit', 14) ?> Edit</a>
      <?php endif; ?>
      <a class="btn btn-outline btn-small" href="<?= url('transaksi/cetak/' . (int)$t['id']) ?>" target="_blank"><?= icon('printer', 14) ?> Cetak Nota</a>
      <?php if ($next): ?>
        <form method="post" action="<?= url('transaksi/status/' . (int)$t['id'], ['ke' => 'detail']) ?>" class="inline js-status">
          <input type="hidden" name="kembali" value="<?= e(url('transaksi/detail/' . (int)$t['id'])) ?>">
          <input type="hidden" name="status" value="<?= e($next) ?>">
          <button class="btn btn-primary btn-small" type="submit">Tandai: <?= e(STATUS_LABEL[$next]) ?> →</button>
        </form>
      <?php endif; ?>
      <?php if ($bolehHapus): ?>
      <form method="post" action="<?= url('transaksi/hapus/' . (int)$t['id']) ?>" class="inline js-hapus">
        <input type="hidden" name="kembali" value="<?= e(url('transaksi/detail/' . (int)$t['id'])) ?>">
        <button class="btn btn-danger btn-small" type="submit"><?= icon('hapus', 14) ?> Hapus</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="progress">
    <?php foreach (STATUS_LAYANAN as $i => $s): ?>
      <span class="step <?= $i < $idx ? 'done' : ($i === $idx ? 'now' : 'upcoming') ?>" data-label="Saat ini"><?= e(STATUS_LABEL[$s]) ?></span>
    <?php endforeach; ?>
  </div>

  <div class="grid-2">
    <div class="card" style="box-shadow:none">
      <div class="card-head"><h2><?= icon('user', 17) ?> Pelanggan</h2></div>
      <p><strong><?= e($t['pelanggan']) ?></strong><br>
      <?= e($t['telepon']) ?: '-' ?><br>
      <?= e($t['alamat']) ?: '-' ?></p>
    </div>
    <div class="card" style="box-shadow:none">
      <div class="card-head"><h2><?= icon('jam', 17) ?> Jadwal</h2></div>
      <div class="table-wrap">
        <table class="table table-kv">
          <tr><td>Tanggal Masuk</td><td class="r"><?= tglIndo($t['tanggal_masuk']) ?></td></tr>
          <tr><td>Estimasi Selesai</td><td class="r"><?= tglIndo($t['estimasi_selesai']) ?></td></tr>
          <tr><td>Tanggal Selesai</td><td class="r"><?= $t['tanggal_selesai'] ? tglIndo($t['tanggal_selesai']) : '-' ?></td></tr>
          <tr><td>Status</td><td class="r"><?= badgeStatus($t['status']) ?></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2><?= icon('layanan', 17) ?> Detail Layanan</h2></div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>Layanan</th><th class="r">Berat</th><th class="r">Subtotal</th></tr>
      </thead>
      <tbody>
        <?php foreach ($detail as $d): ?>
          <tr>
            <td><?= e($d['paket']) ?></td>
            <td class="r num"><?= (float)$d['berat'] ?> <?= e(pengaturan('satuan_berat', 'kg')) ?></td>
            <td class="r num"><?= rupiah($d['subtotal']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($t['keterangan']): ?>
    <p class="hint" style="margin-top:10px">Keterangan: <?= e($t['keterangan']) ?></p>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-head"><h2><?= icon('kartu', 17) ?> Pembayaran</h2></div>
  <div class="grid-2">
    <div class="table-wrap">
      <table class="table table-kv">
        <?php if (((float)($t['pajak'] ?? 0)) > 0 || ((float)($t['diskon'] ?? 0)) > 0): ?>
          <tr><td>Subtotal Layanan</td><td class="r"><?= rupiah((float)$t['total_harga'] - (float)$t['pajak'] + (float)$t['diskon']) ?></td></tr>
        <?php endif; ?>
        <?php if ((float)($t['pajak'] ?? 0) > 0): ?>
          <tr><td>Pajak Layanan (<?= round((float)pengaturan('pajak_persen', '0'), 1) ?>%)</td><td class="r"><?= rupiah($t['pajak']) ?></td></tr>
        <?php endif; ?>
        <?php if ((float)($t['diskon'] ?? 0) > 0): ?>
          <tr><td>Diskon</td><td class="r">- <?= rupiah($t['diskon']) ?></td></tr>
        <?php endif; ?>
        <tr><td>Total Harga</td><td class="r"><strong><?= rupiah($t['total_harga']) ?></strong></td></tr>
        <tr><td>Uang Muka</td><td class="r"><?= rupiah($t['uang_muka']) ?></td></tr>
        <tr><td>Sisa Bayar</td><td class="r"><strong><?= rupiah($sisa) ?></strong></td></tr>
        <tr><td>Status Pembayaran</td><td class="r"><?= badgeBayar($t['status_bayar']) ?></td></tr>
      </table>
    </div>

    <?php if ($sisa > 0): ?>
      <form method="post" action="<?= url('transaksi/bayar/' . (int)$t['id']) ?>" class="js-kirim">
        <div class="form-group">
          <label for="uang_muka">Uang Muka / Pembayaran (Rp)</label>
          <div class="input-icon">
            <span><?= icon('uang', 16) ?></span>
            <input type="number" id="uang_muka" name="uang_muka" min="0" step="1"
                   value="<?= e(number_format((float)$t['uang_muka'], 0, '.', '')) ?>">
          </div>
        </div>
        <div class="inline">
          <button class="btn btn-primary" type="submit"><?= icon('simpan', 15) ?> Update Pembayaran</button>
          <button class="btn btn-success" type="submit"
                  onclick="document.getElementById('uang_muka').value = <?= e(number_format((float)$t['total_harga'], 2, '.', '')) ?>"><?= icon('check', 15) ?> Terima Lunas</button>
        </div>
        <p class="hint">Klik "Terima Lunas" untuk menandai seluruh tagihan dibayar.</p>
      </form>
    <?php else: ?>
      <div class="alert alert-success" style="align-self:center">
        <span class="alert-ico"><?= icon('check', 17) ?></span>
        <span>Pembayaran LUNAS</span>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>