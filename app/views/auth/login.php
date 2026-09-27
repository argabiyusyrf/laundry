<?php $error = $error ?? ''; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/x-icon" href="<?= base_path() ?>/favicon.ico">
<link rel="stylesheet" href="<?= asset_url('style.css') ?>">
</head>
<body class="login-body">
  <div class="login-bg" aria-hidden="true"></div>
  <div class="login-card">
    <div class="login-logo"><?= icon('logo', 30) ?></div>
    <h1><?= e(APP_NAME) ?></h1>
    <p class="login-sub">Aplikasi Manajemen Laundry — Kiloan &amp; Satuan</p>

    <?php if ($error): ?>
      <div class="alert alert-error">
        <span class="alert-ico"><?= icon('peringatan', 17) ?></span>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= url('login') ?>" autocomplete="off">
      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-icon">
          <span><?= icon('user', 16) ?></span>
          <input type="text" id="username" name="username" required autofocus placeholder="username">
        </div>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-icon">
          <span><?= icon('kunci', 16) ?></span>
          <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>
      </div>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Masuk</button>
    </form>

    <div class="login-request">
      <span class="lr-ico"><?= icon('toko', 16) ?></span>
      <span>Belum punya akun toko?
        <a class="btn-link" href="<?= url('permintaan') ?>">Ajukan permintaan akun di sini</a>
        (disetujui oleh pemilik sistem)</span>
    </div>

    <div class="login-fiturs">
      <div class="fitur"><?= icon('truk', 18) ?><span>Antar-jemput</span></div>
      <div class="fitur"><?= icon('logo', 18) ?><span>Cuci Kiloan</span></div>
      <div class="fitur"><?= icon('kartu', 18) ?><span>Bisa DP</span></div>
    </div>
  </div>
</body>
</html>