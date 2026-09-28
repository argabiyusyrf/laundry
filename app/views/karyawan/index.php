<?php
include __DIR__ . '/../partials/header.php';
$roleSaya = userRole();
/* Nilai isian untuk mode tambah, dibaca app.js lewat atribut data-kosong
   agar tombol Tambah bisa membuka panel dalam keadaan kosong tanpa perlu
   memuat ulang halaman. */
$kosongKaryawan = [
    'nama'     => '',
    'username' => '',
    'password' => '',
    'aktif'    => 1,
];
?>

<?php if ($roleSaya === 'manager'): ?>
  <div class="card card-info">
    <span class="ci-ico"><?= icon('karyawan', 17) ?></span>
    <div>
      <strong>Kelola Karyawan Toko.</strong>
      Sebagai <strong><?= e(roleLabel('manager')) ?></strong>, Anda menambah akun karyawan
      (staff) langsung dari sini — akun langsung aktif, tanpa persetujuan tambahan
      (inilah bentuk "persetujuan toko"). Reset password bila lupa, nonaktifkan bila
      berhenti, atau hapus akunnya.
    </div>
  </div>
<?php else: ?>
  <div class="card card-info">
    <span class="ci-ico"><?= icon('info', 17) ?></span>
    <div>
      <strong>Karyawan (root).</strong> Daftar akun staff yang dikelola kepala toko. Root
      tetap bisa menambah/memperbaiki akun karyawan dari sini; kelola semua akun (termasuk
      manager &amp; root sendiri) tetap lewat menu <strong>Akun &amp; Pengguna</strong>.
    </div>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-head">
    <h2><?= icon('karyawan', 17) ?> Daftar Karyawan</h2>
    <button class="btn btn-primary btn-small" type="button"
            data-drawer-buka="formKaryawan" data-drawer-mode="tambah">
      <?= icon('plus', 14) ?> Tambah Karyawan
    </button>
  </div>

  <?php if (!$list): ?>
    <p class="empty"><?= icon('karyawan', 30) ?>Belum ada karyawan. Tekan Tambah Karyawan untuk menambah yang pertama.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Karyawan</th><th>Status</th><th>Login Terakhir</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          <?php foreach ($list as $u): ?>
            <tr>
              <td>
                <div class="cell-user">
                  <span class="cell-ava"><?= e(mb_substr($u['nama'], 0, 1, 'UTF-8')) ?></span>
                  <div>
                    <strong><?= e($u['nama']) ?></strong>
                    <div class="hint">@<?= e($u['username']) ?> — <?= e(roleLabel($u['role'])) ?></div>
                  </div>
                </div>
              </td>
              <td>
                <?php if ((int)$u['aktif'] === 1): ?>
                  <span class="badge badge-lunas">Aktif</span>
                <?php else: ?>
                  <span class="badge badge-belum">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td class="hint"><?= $u['last_login'] ? tglIndo($u['last_login'], true) : 'Belum pernah' ?></td>
              <td>
                <div class="inline" style="flex-wrap:wrap;gap:6px">
                  <a class="btn btn-small btn-outline" href="<?= url('karyawan', ['edit' => (int)$u['id']]) ?>"><?= icon('edit', 13) ?> Edit</a>
                  <details class="tolak-details">
                    <summary class="btn btn-small btn-outline"><?= icon('kunci', 13) ?> Reset</summary>
                    <form method="post" action="<?= url('karyawan/reset/' . (int)$u['id']) ?>" class="tolak-form">
                      <input type="password" name="password" minlength="<?= minLengthPassword() ?>"
                             placeholder="Password baru min. <?= minLengthPassword() ?>" required>
                      <button class="btn btn-small" type="submit">Reset</button>
                    </form>
                  </details>
                  <form method="post" action="<?= url('karyawan/hapus/' . (int)$u['id']) ?>" class="inline js-hapus">
                    <button class="btn btn-small btn-danger" type="submit"><?= icon('hapus', 13) ?> Hapus</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="drawer-overlay" aria-hidden="true"></div>

<aside class="drawer" data-drawer="formKaryawan" role="dialog" aria-modal="true"
       aria-labelledby="judulDrawerKaryawan" tabindex="-1">
  <div class="drawer-head">
    <div style="min-width:0">
      <div class="drawer-judul">
        <span class="ico" data-ikon-tambah<?= $dataEdit ? ' hidden' : '' ?>><?= icon('plus', 17) ?></span>
        <span class="ico" data-ikon-edit<?= $dataEdit ? '' : ' hidden' ?>><?= icon('edit', 17) ?></span>
        <span data-teks-judul id="judulDrawerKaryawan"><?= $dataEdit ? 'Edit Karyawan' : 'Tambah Karyawan' ?></span>
      </div>
      <div class="drawer-sub">Akun staf ini langsung aktif dan bisa masuk ke sistem.</div>
    </div>
    <?php if ($dataEdit): ?>
      <a class="drawer-tutup" href="<?= url('karyawan') ?>" data-drawer-tutup
         aria-label="Tutup panel" title="Tutup"><?= icon('tutup', 15) ?></a>
    <?php else: ?>
      <button class="drawer-tutup" type="button" data-drawer-tutup
         aria-label="Tutup panel" title="Tutup"><?= icon('tutup', 15) ?></button>
    <?php endif; ?>
  </div>

  <form method="post"
        action="<?= $dataEdit ? url('karyawan/ubah/' . (int)$dataEdit['id']) : url('karyawan/tambah') ?>"
        data-kosong='<?= json_encode($kosongKaryawan, JSON_UNESCAPED_UNICODE) ?>'
        data-tambah-action="<?= e(url('karyawan/tambah')) ?>"
        data-judul-tambah="Tambah Karyawan"
        data-label-tambah="Buat Karyawan"
        <?= $dataEdit ? 'data-dari-edit="1"' : '' ?>>
    <div class="drawer-body">
      <div class="form-group">
        <label for="nama">Nama Karyawan <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('user', 16) ?></span>
          <input type="text" id="nama" name="nama" required
                 value="<?= e($dataEdit['nama'] ?? '') ?>" placeholder="cth: Rina Putri">
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label for="username">Username <span class="required-mark">*</span></label>
          <div class="input-icon">
            <span><?= icon('pengguna', 16) ?></span>
            <input type="text" id="username" name="username" required
                   value="<?= e($dataEdit['username'] ?? '') ?>" placeholder="cth: rina">
          </div>
        </div>
        <div class="form-group">
          <label for="password"><?= $dataEdit ? 'Password Baru' : 'Password' ?> <span class="required-mark"><?= $dataEdit ? '(opsional)' : '*' ?></span></label>
          <div class="input-icon">
            <span><?= icon('kunci', 16) ?></span>
            <input type="password" id="password" name="password"
                   <?= $dataEdit ? '' : 'required' ?> autocomplete="new-password"
                   placeholder="<?= $dataEdit ? 'Kosongkan bila tetap' : 'Min. ' . minLengthPassword() . ' karakter' ?>">
          </div>
        </div>
      </div>
      <div class="form-group" data-grup-edit<?= $dataEdit ? '' : ' hidden' ?>>
        <label>Status Akun</label>
        <div class="check-line">
          <input type="checkbox" id="aktif" name="aktif" value="1"
                 <?= (int)($dataEdit['aktif'] ?? 1) === 1 ? 'checked' : '' ?>>
          <label for="aktif">Akun aktif</label>
          <span class="hint">(nonaktif = tidak bisa login)</span>
        </div>
      </div>
    </div>

    <div class="drawer-foot">
      <?php if ($dataEdit): ?>
        <a class="btn btn-outline" href="<?= url('karyawan') ?>" data-drawer-tutup>Batal</a>
      <?php else: ?>
        <button class="btn btn-outline" type="button" data-drawer-tutup>Batal</button>
      <?php endif; ?>
      <button class="btn btn-primary" type="submit">
        <?= icon('simpan', 15) ?> <span data-teks-kirim><?= $dataEdit ? 'Simpan Perubahan' : 'Buat Karyawan' ?></span>
      </button>
    </div>
  </form>
</aside>

<?php include __DIR__ . '/../partials/footer.php'; ?>
