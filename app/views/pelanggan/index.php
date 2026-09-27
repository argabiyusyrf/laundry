<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="grid-2">
  <div class="card">
    <div class="card-head">
      <h2><?= $dataEdit ? icon('edit', 17) . ' Edit Pelanggan' : icon('plus', 17) . ' Tambah Pelanggan' ?></h2>
      <?php if ($dataEdit): ?>
        <a class="btn btn-small" href="<?= url('pelanggan') ?>"><?= icon('kembali', 13) ?> Batal edit</a>
      <?php endif; ?>
    </div>
    <form method="post"
          action="<?= $dataEdit ? url('pelanggan/ubah/' . (int)$dataEdit['id']) : url('pelanggan/tambah') ?>">
      <?php if (!$dataEdit): ?>
        <input type="hidden" name="kembali" value="<?= e(url('pelanggan', ['q' => $q])) ?>">
      <?php endif; ?>
      <div class="form-group">
        <label for="nama">Nama Lengkap <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('user', 16) ?></span>
          <input type="text" id="nama" name="nama" required
                 value="<?= e($dataEdit['nama'] ?? '') ?>" placeholder="cth: Budi Santoso">
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label for="telepon">No. Telepon / WA</label>
          <div class="input-icon">
            <span><?= icon('telepon', 16) ?></span>
            <input type="text" id="telepon" name="telepon"
                   value="<?= e($dataEdit['telepon'] ?? '') ?>" placeholder="08xxxxxxxxxx">
          </div>
        </div>
        <div class="form-group">
          <label for="alamat">Alamat</label>
          <div class="input-icon">
            <span><?= icon('peta', 16) ?></span>
            <input type="text" id="alamat" name="alamat"
                   value="<?= e($dataEdit['alamat'] ?? '') ?>" placeholder="cth: Jl. Melati No. 12">
          </div>
        </div>
      </div>
      <button class="btn btn-primary" type="submit">
        <?= $dataEdit ? icon('simpan', 15) . ' Simpan Perubahan' : icon('plus', 15) . ' Simpan Pelanggan' ?>
      </button>
    </form>
  </div>

  <div class="card">
    <div class="card-head">
      <h2>Daftar Pelanggan</h2>
      <form class="filter" method="get" action="<?= e(form_action()) ?>">
        <input type="hidden" name="r" value="pelanggan">
        <div class="input-icon" style="max-width:none">
          <span><?= icon('cari', 16) ?></span>
          <label class="sr-only" for="q">Cari pelanggan</label>
          <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Cari nama / telepon…">
        </div>
        <button class="btn btn-primary btn-small" type="submit"><?= icon('cari', 14) ?> Cari</button>
      </form>
    </div>

    <?php if (!$list): ?>
      <p class="empty"><?= icon('pelanggan', 30) ?><?= $q ? 'Tidak ada hasil untuk "' . e($q) . '".' : 'Belum ada pelanggan. Tambahkan lewat form di samping.' ?></p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr><th>Nama</th><th>Telepon</th><th>Alamat</th><th>Transaksi</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($list as $p): ?>
              <tr>
                <td><strong><?= e($p['nama']) ?></strong></td>
                <td><?= e($p['telepon']) ?: '-' ?></td>
                <td><?= e($p['alamat']) ?: '-' ?></td>
                <td><?= (int)$p['jml_trx'] ?></td>
                <td>
                  <div class="inline">
                    <a class="btn btn-small btn-outline" href="<?= url('pelanggan/detail/' . (int)$p['id']) ?>"><?= icon('lihat', 13) ?> Detail</a>
                    <?php if ($bolehEdit): ?>
                    <a class="btn btn-small btn-outline" href="<?= url('pelanggan', ['edit' => (int)$p['id']]) ?>"><?= icon('edit', 13) ?> Edit</a>
                    <?php endif; ?>
                    <?php if ($bolehHapus && (int)$p['jml_trx'] === 0): ?>
                      <form method="post" action="<?= url('pelanggan/hapus/' . (int)$p['id']) ?>" class="inline js-hapus">
                        <input type="hidden" name="kembali" value="<?= e(url('pelanggan', ['q' => $q])) ?>">
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
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>