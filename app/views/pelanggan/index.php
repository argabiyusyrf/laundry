<?php
include __DIR__ . '/../partials/header.php';
/* Nilai isian untuk mode tambah, dibaca app.js lewat atribut data-kosong
   agar tombol Tambah bisa membuka panel dalam keadaan kosong tanpa perlu
   memuat ulang halaman. Field "kembali" ikut disertakan supaya tombol
   Tambah setelah mode ubah tetap mengembalikan pengguna ke daftar
   yang sedang disaring. */
$kosongPelanggan = [
    'nama'     => '',
    'telepon'  => '',
    'alamat'   => '',
    'kembali'  => url('pelanggan', ['q' => $q]),
];
?>

<div class="card">
  <div class="card-head">
    <h2><?= icon('pelanggan', 17) ?> Daftar Pelanggan</h2>
    <div class="inline">
      <form class="filter" method="get" action="<?= e(form_action()) ?>">
        <input type="hidden" name="r" value="pelanggan">
        <div class="input-icon" style="max-width:none">
          <span><?= icon('cari', 16) ?></span>
          <label class="sr-only" for="q">Cari pelanggan</label>
          <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Cari nama / telepon...">
        </div>
        <button class="btn btn-primary btn-small" type="submit"><?= icon('cari', 14) ?> Cari</button>
      </form>
      <?php if ($bolehEdit): ?>
        <button class="btn btn-small" type="button"
                data-drawer-buka="formPelanggan" data-drawer-mode="tambah">
          <?= icon('plus', 14) ?> Tambah Pelanggan
        </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$list): ?>
    <p class="empty"><?= icon('pelanggan', 30) ?><?= $q ? 'Tidak ada hasil untuk "' . e($q) . '".' : 'Belum ada pelanggan. Tekan Tambah Pelanggan untuk menambah yang pertama.' ?></p>
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

<?php if ($bolehEdit): ?>
  <div class="drawer-overlay" aria-hidden="true"></div>

  <aside class="drawer" data-drawer="formPelanggan" role="dialog" aria-modal="true"
         aria-labelledby="judulDrawerPelanggan" tabindex="-1">
    <div class="drawer-head">
      <div style="min-width:0">
        <div class="drawer-judul">
          <span class="ico" data-ikon-tambah<?= $dataEdit ? ' hidden' : '' ?>><?= icon('plus', 17) ?></span>
          <span class="ico" data-ikon-edit<?= $dataEdit ? '' : ' hidden' ?>><?= icon('edit', 17) ?></span>
          <span data-teks-judul id="judulDrawerPelanggan"><?= $dataEdit ? 'Edit Pelanggan' : 'Tambah Pelanggan' ?></span>
        </div>
        <div class="drawer-sub">Nomor telepon bisa dipakai untuk mengirim pembaruan status.</div>
      </div>
      <?php if ($dataEdit): ?>
        <a class="drawer-tutup" href="<?= e(url('pelanggan', ['q' => $q])) ?>" data-drawer-tutup
           aria-label="Tutup panel" title="Tutup"><?= icon('tutup', 15) ?></a>
      <?php else: ?>
        <button class="drawer-tutup" type="button" data-drawer-tutup
           aria-label="Tutup panel" title="Tutup"><?= icon('tutup', 15) ?></button>
      <?php endif; ?>
    </div>

    <form method="post"
          action="<?= $dataEdit ? url('pelanggan/ubah/' . (int)$dataEdit['id']) : url('pelanggan/tambah') ?>"
          data-kosong='<?= json_encode($kosongPelanggan, JSON_UNESCAPED_UNICODE) ?>'
          data-tambah-action="<?= e(url('pelanggan/tambah')) ?>"
          data-judul-tambah="Tambah Pelanggan"
          data-label-tambah="Simpan Pelanggan"
          <?= $dataEdit ? 'data-dari-edit="1"' : '' ?>>
      <input type="hidden" name="kembali" value="<?= e(url('pelanggan', ['q' => $q])) ?>">
      <div class="drawer-body">
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
      </div>

      <div class="drawer-foot">
        <?php if ($dataEdit): ?>
          <a class="btn btn-outline" href="<?= e(url('pelanggan', ['q' => $q])) ?>" data-drawer-tutup>Batal</a>
        <?php else: ?>
          <button class="btn btn-outline" type="button" data-drawer-tutup>Batal</button>
        <?php endif; ?>
        <button class="btn btn-primary" type="submit">
          <?= icon('simpan', 15) ?> <span data-teks-kirim><?= $dataEdit ? 'Simpan Perubahan' : 'Simpan Pelanggan' ?></span>
        </button>
      </div>
    </form>
  </aside>
<?php endif; ?>

<?php include __DIR__ . '/../partials/footer.php'; ?>
