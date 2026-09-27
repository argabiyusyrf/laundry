<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="card">
  <form class="filter" method="get" action="<?= e(form_action()) ?>">
    <input type="hidden" name="r" value="transaksi">
    <div class="form-group" style="margin:0">
      <label for="q">Cari</label>
      <div class="input-icon" style="max-width:none">
        <span><?= icon('cari', 16) ?></span>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="No nota / nama / telepon…">
      </div>
    </div>
    <div class="form-group" style="margin:0">
      <label for="status">Status</label>
      <select name="status" id="status">
        <option value="">Semua status</option>
        <?php foreach (STATUS_LAYANAN as $s): ?>
          <option value="<?= e($s) ?>" <?= $sStatus === $s ? 'selected' : '' ?>><?= e(STATUS_LABEL[$s]) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" type="submit"><?= icon('cari', 15) ?> Cari</button>
    <?php if ($q !== '' || $sStatus !== ''): ?>
      <a class="btn btn-outline" href="<?= url('transaksi') ?>">Reset</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <h2>Daftar Transaksi (<?= count($list) ?>)</h2>
    <a class="btn btn-primary btn-small" href="<?= url('transaksi/baru') ?>"><?= icon('plus', 14) ?> Buat Nota Baru</a>
  </div>

  <?php if (!$list): ?>
    <p class="empty"><?= icon('transaksi', 30) ?><?= ($q !== '' || $sStatus !== '') ? 'Tidak ada hasil yang cocok.' : 'Belum ada transaksi. Klik "Buat Nota Baru" untuk mulai.' ?></p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>No Nota</th>
            <th>Pelanggan</th>
            <th>Masuk</th>
            <th class="r">Berat</th>
            <th class="r">Total</th>
            <th>Status</th>
            <th>Bayar</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $t): ?>
            <tr>
              <td><a href="<?= url('transaksi/detail/' . (int)$t['id']) ?>"><?= e($t['no_nota']) ?></a></td>
              <td><?= e($t['pelanggan']) ?></td>
              <td><?= tglIndo($t['tanggal_masuk']) ?></td>
              <td class="r num"><?= (float)$t['total_berat'] ?> <?= e(pengaturan('satuan_berat', 'kg')) ?></td>
              <td class="r num"><strong><?= rupiah($t['total_harga']) ?></strong></td>
              <td>
                <form method="post" action="<?= url('transaksi/status/' . (int)$t['id']) ?>" class="inline js-status">
                  <input type="hidden" name="kembali" value="<?= e(url('transaksi', ['q' => $q, 'status' => $sStatus])) ?>">
                  <select name="status" class="select-status">
                    <?php foreach (STATUS_LAYANAN as $s): ?>
                      <option value="<?= e($s) ?>" <?= $t['status'] === $s ? 'selected' : '' ?>><?= e(STATUS_LABEL[$s]) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td><?= badgeBayar($t['status_bayar']) ?></td>
              <td>
                <div class="inline">
                  <a class="btn btn-small btn-outline" href="<?= url('transaksi/detail/' . (int)$t['id']) ?>"><?= icon('lihat', 13) ?> Detail</a>
                  <?php if ($bolehEdit): ?>
                  <a class="btn btn-small btn-outline" href="<?= url('transaksi/edit/' . (int)$t['id']) ?>"><?= icon('edit', 13) ?> Edit</a>
                  <?php endif; ?>
                  <a class="btn btn-small btn-outline" href="<?= url('transaksi/cetak/' . (int)$t['id']) ?>" target="_blank"><?= icon('printer', 13) ?> Nota</a>
                  <?php if ($bolehHapus): ?>
                  <form method="post" action="<?= url('transaksi/hapus/' . (int)$t['id']) ?>" class="inline js-hapus">
                    <input type="hidden" name="kembali" value="<?= e(url('transaksi', ['q' => $q, 'status' => $sStatus])) ?>">
                    <button class="btn btn-small btn-danger" type="submit"><?= icon('hapus', 13) ?> Hapus</button>
                  </form>
                  <?php endif; ?>
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