<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="grid-2">
  <div class="card">
    <div class="card-head">
      <h2><?= icon('user', 17) ?> Data Akun</h2>
    </div>

    <form method="post" action="<?= url('profil/simpan') ?>">
      <div class="form-group">
        <label for="nama">Nama Lengkap <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('user', 16) ?></span>
          <input type="text" id="nama" name="nama" required maxlength="100"
                 value="<?= e($u['nama']) ?>" placeholder="cth: Budi Santoso">
        </div>
      </div>
      <div class="form-group">
        <label for="username">Username <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('kunci', 16) ?></span>
          <input type="text" id="username" name="username" required maxlength="50"
                 value="<?= e($u['username']) ?>" placeholder="username">
        </div>
        <p class="field-hint">Username dipakai untuk login. Hanya huruf, angka, titik, garis bawah, dan strip.</p>
      </div>
      <div class="form-group">
        <label>Tanggal Terdaftar</label>
        <div class="input-icon is-readonly">
          <span><?= icon('kalender', 16) ?></span>
          <input type="text" value="<?= tglIndo($u['created_at'], true) ?>" disabled>
        </div>
      </div>
      <button class="btn btn-primary" type="submit"><?= icon('simpan', 15) ?> Simpan Profil</button>
    </form>
  </div>

  <div class="card">
    <div class="card-head">
      <h2><?= icon('pengaturan', 17) ?> Ganti Password</h2>
    </div>

    <form method="post" action="<?= url('profil/password') ?>">
      <div class="form-group">
        <label for="password_lama">Password Lama <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('kunci', 16) ?></span>
          <input type="password" id="password_lama" name="password_lama" required autocomplete="current-password"
                 placeholder="Password saat ini">
        </div>
      </div>
      <div class="form-group">
        <label for="password_baru">Password Baru <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('kunci', 16) ?></span>
          <input type="password" id="password_baru" name="password_baru" required minlength="<?= minLengthPassword() ?>" autocomplete="new-password"
                 placeholder="Minimal <?= minLengthPassword() ?> karakter">
        </div>
      </div>
      <div class="form-group">
        <label for="password_baru_ulang">Ulangi Password Baru <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('kunci', 16) ?></span>
          <input type="password" id="password_baru_ulang" name="password_baru_ulang" required minlength="<?= minLengthPassword() ?>" autocomplete="new-password"
                 placeholder="Ketik ulang password baru">
        </div>
      </div>
      <button class="btn btn-primary" type="submit"><?= icon('kunci', 15) ?> Ganti Password</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>