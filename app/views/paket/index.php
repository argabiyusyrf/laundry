<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="grid-2">
  <?php if ($bolehKelola): ?>
  <div class="card">
    <div class="card-head">
      <h2><?= $dataEdit ? icon('edit', 17) . ' Edit Paket' : icon('plus', 17) . ' Tambah Paket' ?></h2>
      <?php if ($dataEdit): ?>
        <a class="btn btn-small" href="<?= url('paket') ?>"><?= icon('kembali', 13) ?> Batal edit</a>
      <?php endif; ?>
    </div>
    <form method="post"
          action="<?= $dataEdit ? url('paket/ubah/' . (int)$dataEdit['id']) : url('paket/tambah') ?>">
      <div class="form-group">
        <label for="nama">Nama Layanan <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('layanan', 16) ?></span>
          <input type="text" id="nama" name="nama" required
                 value="<?= e($dataEdit['nama'] ?? '') ?>" placeholder="cth: Cuci + Setrika">
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label for="harga">Harga per <?= e(pengaturan('satuan_berat', 'kg')) ?> <span class="required-mark">*</span></label>
          <div class="input-icon">
            <span><?= icon('uang', 16) ?></span>
            <input type="number" id="harga" name="harga_per_kg" min="0" step="500" required
                   value="<?= e($dataEdit['harga_per_kg'] ?? '') ?>" placeholder="12000">
          </div>
        </div>
        <div class="form-group">
          <label for="estimasi">Estimasi Selesai (hari) <span class="required-mark">*</span></label>
          <div class="input-icon">
            <span><?= icon('jam', 16) ?></span>
            <input type="number" id="estimasi" name="estimasi_hari" min="1" step="1" required
                   value="<?= e($dataEdit['estimasi_hari'] ?? '2') ?>">
          </div>
        </div>
      </div>
      <div class="form-group">
        <label for="keterangan">Keterangan <span class="hint" style="font-weight:400">(opsional)</span></label>
        <div class="input-icon">
          <span><?= icon('catatan', 16) ?></span>
          <textarea id="keterangan" name="keterangan" rows="2"
                    placeholder="cth: Cuci kering setrika, tanpa pewangi"><?= e($dataEdit['keterangan'] ?? '') ?></textarea>
        </div>
      </div>
      <button class="btn btn-primary" type="submit">
        <?= $dataEdit ? icon('simpan', 15) . ' Simpan Perubahan' : icon('plus', 15) . ' Simpan Paket' ?>
      </button>
    </form>
  </div>
  <?php else: ?>
  <div class="card card-info">
    <span class="ci-ico"><?= icon('info', 17) ?></span>
    <div>
      <strong>Daftar paket layanan bersifat hanya-baca untuk Anda.</strong>
      Hanya pemilik (root) &amp; manager yang dapat menambah, mengubah, atau menghapus paket.
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-head"><h2>Daftar Paket Layanan</h2></div>
    <?php if (!$list): ?>
      <p class="empty"><?= icon('paket', 30) ?>Belum ada paket layanan. Tambahkan lewat form di samping.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr><th>Layanan</th><th class="r">Harga/<?= e(pengaturan('satuan_berat', 'kg')) ?></th><th class="r">Estimasi</th><th>Dipakai</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($list as $k): ?>
              <tr>
                <td>
                  <strong><?= e($k['nama']) ?></strong>
                  <?php if ($k['keterangan']): ?>
                    <div class="hint"><?= e($k['keterangan']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="r num"><?= rupiah($k['harga_per_kg']) ?></td>
                <td class="r num"><?= (int)$k['estimasi_hari'] ?> hari</td>
                <td><?= (int)$k['jml_pakai'] ?>x</td>
                <td>
                  <div class="inline">
                    <a class="btn btn-small btn-outline" href="<?= url('paket/detail/' . (int)$k['id']) ?>"><?= icon('lihat', 13) ?> Detail</a>
                    <?php if ($bolehKelola): ?>
                    <a class="btn btn-small btn-outline" href="<?= url('paket', ['edit' => (int)$k['id']]) ?>"><?= icon('edit', 13) ?> Edit</a>
                    <?php if ((int)$k['jml_pakai'] === 0): ?>
                      <form method="post" action="<?= url('paket/hapus/' . (int)$k['id']) ?>" class="inline js-hapus">
                        <button class="btn btn-small btn-danger" type="submit"><?= icon('hapus', 13) ?> Hapus</button>
                      </form>
                    <?php endif; ?>
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