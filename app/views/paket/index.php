<?php
include __DIR__ . '/../partials/header.php';
/* Nilai isian untuk mode tambah, dibaca app.js lewat atribut data-kosong
   agar tombol Tambah bisa membuka panel dalam keadaan kosong tanpa perlu
   memuat ulang halaman. */
$kosongPaket = [
    'nama'            => '',
    'harga_per_kg'    => '',
    'estimasi_hari'   => '2',
    'keterangan'      => '',
];
$satuan = pengaturan('satuan_berat', 'kg');
?>

<?php if (!$bolehKelola): ?>
  <div class="card card-info">
    <span class="ci-ico"><?= icon('info', 17) ?></span>
    <div>
      <strong>Daftar paket layanan bersifat hanya-baca untuk Anda.</strong>
      Hanya pemilik (root) &amp; manager yang dapat menambah, mengubah, atau menghapus paket.
    </div>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-head">
    <h2><?= icon('paket', 17) ?> Daftar Paket Layanan</h2>
    <?php if ($bolehKelola): ?>
      <button class="btn btn-primary btn-small" type="button"
              data-drawer-buka="formPaket" data-drawer-mode="tambah">
        <?= icon('plus', 14) ?> Tambah Paket
      </button>
    <?php endif; ?>
  </div>

  <?php if (!$list): ?>
    <p class="empty"><?= icon('paket', 30) ?>Belum ada paket layanan<?= $bolehKelola ? '. Tekan Tambah Paket untuk menambah yang pertama.' : '.' ?></p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Layanan</th><th class="r">Harga/<?= e($satuan) ?></th><th class="r">Estimasi</th><th>Dipakai</th><th>Aksi</th></tr>
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

<?php if ($bolehKelola): ?>
  <div class="drawer-overlay" aria-hidden="true"></div>

  <aside class="drawer" data-drawer="formPaket" role="dialog" aria-modal="true"
         aria-labelledby="judulDrawerPaket" tabindex="-1">
    <div class="drawer-head">
      <div style="min-width:0">
        <div class="drawer-judul">
          <span class="ico" data-ikon-tambah<?= $dataEdit ? ' hidden' : '' ?>><?= icon('plus', 17) ?></span>
          <span class="ico" data-ikon-edit<?= $dataEdit ? '' : ' hidden' ?>><?= icon('edit', 17) ?></span>
          <span data-teks-judul id="judulDrawerPaket"><?= $dataEdit ? 'Edit Paket' : 'Tambah Paket' ?></span>
        </div>
        <div class="drawer-sub">Harga dihitung per <?= e($satuan) ?>.</div>
      </div>
      <?php if ($dataEdit): ?>
        <a class="drawer-tutup" href="<?= url('paket') ?>" data-drawer-tutup
           aria-label="Tutup panel" title="Tutup"><?= icon('tutup', 15) ?></a>
      <?php else: ?>
        <button class="drawer-tutup" type="button" data-drawer-tutup
           aria-label="Tutup panel" title="Tutup"><?= icon('tutup', 15) ?></button>
      <?php endif; ?>
    </div>

    <form method="post"
          action="<?= $dataEdit ? url('paket/ubah/' . (int)$dataEdit['id']) : url('paket/tambah') ?>"
          data-kosong='<?= json_encode($kosongPaket, JSON_UNESCAPED_UNICODE) ?>'
          data-tambah-action="<?= e(url('paket/tambah')) ?>"
          data-judul-tambah="Tambah Paket"
          data-label-tambah="Simpan Paket"
          <?= $dataEdit ? 'data-dari-edit="1"' : '' ?>>
      <div class="drawer-body">
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
            <label for="harga">Harga per <?= e($satuan) ?> <span class="required-mark">*</span></label>
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
      </div>

      <div class="drawer-foot">
        <?php if ($dataEdit): ?>
          <a class="btn btn-outline" href="<?= url('paket') ?>" data-drawer-tutup>Batal</a>
        <?php else: ?>
          <button class="btn btn-outline" type="button" data-drawer-tutup>Batal</button>
        <?php endif; ?>
        <button class="btn btn-primary" type="submit">
          <?= icon('simpan', 15) ?> <span data-teks-kirim><?= $dataEdit ? 'Simpan Perubahan' : 'Simpan Paket' ?></span>
        </button>
      </div>
    </form>
  </aside>
<?php endif; ?>

<?php include __DIR__ . '/../partials/footer.php'; ?>
