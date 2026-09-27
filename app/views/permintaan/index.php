<?php
$lama = $lama ?? [];
setlocale(LC_ALL, 'id_ID');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Minta Akun Toko — <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/x-icon" href="<?= base_path() ?>/favicon.ico">
<link rel="stylesheet" href="<?= asset_url('style.css') ?>">
<style>
  .login-card.perm-card { max-width: 540px; width: 100%; }
  .perm-note { background: var(--ink-50); border: 1px solid var(--ink-200); border-radius: 12px; padding: 10px 12px; font-size: 13px; color: var(--ink-600); display: flex; gap: 8px; align-items: flex-start; margin-bottom: 16px; }
  .perm-note .alert-ico { color: var(--brand-600); }
  .perm-divider { display: flex; align-items: center; gap: 10px; color: var(--ink-400); font-size: 12px; margin: 18px 0 12px; }
  .perm-divider::before, .perm-divider::after { content: ""; flex: 1; height: 1px; background: var(--ink-200); }
</style>
</head>
<body class="login-body">
  <div class="login-bg" aria-hidden="true"></div>
  <main class="login-card perm-card">
    <div class="login-logo"><?= icon('toko', 30) ?></div>
    <h1>Minta Akun Toko</h1>
    <p class="login-sub"><?= e(APP_NAME) ?> — untuk calon pemilik / pengelola toko laundry</p>

    <div class="perm-note">
      <span class="alert-ico"><?= icon('info', 16) ?></span>
      <div>
        Ini <strong>bukan fitur register</strong>. Permintaan Anda dikirim ke pemilik sistem (root);
        akun toko baru aktif setelah <strong>disetujui root</strong>. Setelah disetujui, silakan
        login dengan username &amp; password yang didaftarkan.
      </div>
    </div>

    <?php foreach (getFlashes() as $f):
      $jenis = in_array($f['jenis'], ['success', 'error', 'info'], true) ? $f['jenis'] : 'info';
      $ikonAlert = ['success' => 'check', 'error' => 'peringatan', 'info' => 'info'];
    ?>
      <div class="alert alert-<?= e($jenis) ?>">
        <span class="alert-ico"><?= icon($ikonAlert[$jenis], 17) ?></span>
        <span><?= e($f['pesan']) ?></span>
      </div>
    <?php endforeach; ?>

    <form method="post" action="<?= url('permintaan/kirim') ?>" autocomplete="off">
      <div class="form-group">
        <label for="nama_toko">Nama Toko / Usaha <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('toko', 16) ?></span>
          <input type="text" id="nama_toko" name="nama_toko" required maxlength="120"
                 value="<?= e($lama['nama_toko'] ?? '') ?>" placeholder="cth: Laundry Sejahtera">
        </div>
      </div>
      <div class="form-group">
        <label for="nama_pemohon">Nama Pemohon <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('user', 16) ?></span>
          <input type="text" id="nama_pemohon" name="nama_pemohon" required maxlength="100"
                 value="<?= e($lama['nama_pemohon'] ?? '') ?>" placeholder="cth: Dewi Lestari">
        </div>
      </div>
      <div class="form-group">
        <label for="username">Username yang Diinginkan <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('pengguna', 16) ?></span>
          <input type="text" id="username" name="username" required maxlength="50" pattern="[a-zA-Z0-9._-]+"
                 value="<?= e($lama['username'] ?? '') ?>" placeholder="cth: dewi.laundry">
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label for="password">Password <span class="required-mark">*</span></label>
          <div class="input-icon">
            <span><?= icon('kunci', 16) ?></span>
            <input type="password" id="password" name="password" required autocomplete="new-password"
                   minlength="<?= minLengthPassword() ?>" placeholder="Min. <?= minLengthPassword() ?> karakter">
          </div>
        </div>
        <div class="form-group">
          <label for="password_ulang">Ulangi Password <span class="required-mark">*</span></label>
          <div class="input-icon">
            <span><?= icon('kunci', 16) ?></span>
            <input type="password" id="password_ulang" name="password_ulang" required autocomplete="new-password"
                   minlength="<?= minLengthPassword() ?>" placeholder="Ulangi password">
          </div>
        </div>
      </div>
      <div class="form-group">
        <label for="telepon">Telepon / WhatsApp <span class="hint" style="font-weight:400">(opsional)</span></label>
        <div class="input-icon">
          <span><?= icon('telepon', 16) ?></span>
          <input type="text" id="telepon" name="telepon" maxlength="30"
                 value="<?= e($lama['telepon'] ?? '') ?>" placeholder="08xxxxxxxxxx">
        </div>
      </div>
      <div class="form-group">
        <label for="alamat">Alamat Toko <span class="hint" style="font-weight:400">(opsional)</span></label>
        <div class="input-icon">
          <span><?= icon('peta', 16) ?></span>
          <input type="text" id="alamat" name="alamat" maxlength="255"
                 value="<?= e($lama['alamat'] ?? '') ?>" placeholder="cth: Jl. Melati No. 12, Jakarta">
        </div>
      </div>
      <button class="btn btn-primary btn-lg btn-block" type="submit"><?= icon('kirim', 16) ?> Kirim Permintaan</button>
    </form>

    <div class="perm-divider">Sudah mengajukan? Cek status</div>

    <div style="text-align:center">
      <a href="<?= url('permintaan/status') ?>" class="btn btn-outline btn-block"><?= icon('cari', 15) ?> Cek Status Permintaan</a>
    </div>

    <div class="login-fiturs">
      <div class="fitur"><?= icon('kunci', 18) ?><span>Perlu Persetujuan</span></div>
      <div class="fitur"><?= icon('check', 18) ?><span>Disetujui Root</span></div>
      <div class="fitur"><?= icon('karyawan', 18) ?><span>Staff Dibuat Toko</span></div>
    </div>

    <p style="text-align:center;margin-top:14px">
      <a href="<?= url('login') ?>" class="btn-link"><?= icon('kembali', 14) ?> Kembali ke Login</a>
    </p>
  </main>
</body>
</html>