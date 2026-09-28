<?php
$user = $_SESSION['user'] ?? [];
$notif = notifRingkas(6, isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? APP_NAME) ?> — <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/x-icon" href="<?= base_path() ?>/favicon.ico">
<link rel="stylesheet" href="<?= asset_url('style.css') ?>">
<script>
/* Path dasar aplikasi (mis. "" di domain root, "/laundry" bila dipasang di
   subfolder). JavaScript memakai nilai ini supaya alamat yang dibentuknya
   selalu mengarah ke index.php yang benar, bukan ke folder tempat file .js
   berada. */
window.LK_BASE = <?= json_encode(base_path()) ?>;
</script>
<?php if (defined('BASE_PATH') && is_file(BASE_PATH . '/asset/sound/notification.mp3')): ?>
<script>
window.LK_NOTIF_SOUND = "<?= e(asset_url('sound/notification.mp3')) ?>";
window.LK_NOTIF_SUARA = <?= (string)pengaturan('notif_suara', '1') === '1' ? '1' : '0' ?>;
window.LK_NOTIF_VOLUME = <?= (int)pengaturan('notif_volume', '80') ?>;
</script>
<?php endif; ?>
</head>
<body>
<!-- Tautan lompat: pengguna keyboard tidak harus menelusuri seluruh sidebar
     sebelum sampai ke isi halaman. Hanya terlihat saat receives fokus. -->
<a class="skip-link" href="#kontenUtama">Lompat ke isi utama</a>
<div class="layout">
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-head">
      <div class="sidebar-brand">
        <span class="logo"><?= icon('logo', 24) ?></span>
        <span class="brand-wrap">
          <span class="brand-text"><?= e(APP_NAME) ?></span>
          <span class="brand-sub">Manajemen Laundry</span>
        </span>
      </div>
      <button class="sidebar-close" id="btnCloseSidebar" type="button" aria-label="Tutup menu"><?= icon('tutup', 18) ?></button>
    </div>

    <nav class="sidebar-nav">
      <?php
      /* Susunan menu didefinisikan di helpers.php -> menuSidebar(), bukan
         ditulis satu per satu di sini, supaya pengelompokan section cukup
         diubah dari satu tempat. Fungsi itu juga sudah menyaring menu
         menurut hak akses dan membuang section yang isinya habis. */
      $menuSidebar = menuSidebar((int)($notif['belum'] ?? 0));
      ?>
      <?php foreach ($menuSidebar as $grup): ?>
        <span class="nav-section"><?= e($grup['judul']) ?></span>
        <?php foreach ($grup['bagian'] as $bagian): ?>
          <?php if ($bagian['judul'] !== ''): ?>
            <span class="nav-subsection"><?= e($bagian['judul']) ?></span>
          <?php endif; ?>
          <?php foreach ($bagian['item'] as $item): ?>
            <a href="<?= url($item['rute']) ?>"<?= ($active ?? '') === ($item['aktif'] ?? $item['rute']) ? ' class="active"' : '' ?>>
              <span class="nav-ico"><?= icon($item['ikon'], 17) ?></span><span class="nav-text"><?= e($item['label']) ?></span>
              <?php if (!empty($item['jumlah'])): ?><span class="nav-badge"><?= badgeNilai((int)$item['jumlah']) ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-foot">
      <div class="user-dd" id="userDropdown">
        <button class="sidebar-user" id="btnUserMenu" type="button"
                aria-haspopup="true" aria-expanded="false">
          <div class="avatar"><?= e(mb_substr($user['nama'] ?? 'A', 0, 1, 'UTF-8')) ?></div>
          <div class="user-info">
            <div class="nama"><?= e($user['nama'] ?? '') ?></div>
            <div class="role"><?= e(roleLabel($user['role'] ?? '')) ?></div>
          </div>
          <span class="user-caret"><?= icon('chevron', 15) ?></span>
        </button>

        <div class="user-menu" id="userMenu" role="menu">
          <div class="user-menu-head">
            <span class="um-ico"><?= icon('user', 15) ?></span>
            <div class="um-txt">
              <div class="um-nama"><?= e($user['nama'] ?? '') ?></div>
              <div class="um-role"><?= e(roleLabel($user['role'] ?? '')) ?></div>
            </div>
          </div>
          <a class="user-menu-item" href="<?= url('profil') ?>" role="menuitem">
            <span class="nav-ico"><?= icon('user', 16) ?></span><span class="nav-text">Profil Saya</span>
          </a>
          <?php if (cekHak('akun')): ?>
          <a class="user-menu-item" href="<?= url('akun') ?>" role="menuitem">
            <span class="nav-ico"><?= icon('pengguna', 16) ?></span><span class="nav-text">Akun &amp; Pengguna</span>
          </a>
          <?php endif; ?>
          <?php if (cekHak('aktivitas')): ?>
          <a class="user-menu-item" href="<?= url('aktivitas') ?>" role="menuitem">
            <span class="nav-ico"><?= icon('riwayat', 16) ?></span><span class="nav-text">Log Aktivitas</span>
          </a>
          <?php endif; ?>
          <?php if (cekHak('pengaturan')): ?>
          <a class="user-menu-item" href="<?= url('pengaturan') ?>" role="menuitem">
            <span class="nav-ico"><?= icon('pengaturan', 16) ?></span><span class="nav-text">Pengaturan</span>
          </a>
          <?php endif; ?>
          <div class="user-menu-sep"></div>
          <a class="user-menu-item danger" href="<?= url('logout') ?>" role="menuitem">
            <span class="nav-ico"><?= icon('keluar', 16) ?></span><span class="nav-text">Keluar</span>
          </a>
        </div>
      </div>
    </div>
  </aside>

  <main class="main">
    <header class="topbar">
      <div class="topbar-left">
        <button class="topbar-toggle" id="btnMenu" type="button" aria-label="Buka menu" aria-expanded="false">
          <span class="bar"></span><span class="bar"></span><span class="bar"></span>
        </button>
        <h1><?= e($pageTitle ?? '') ?></h1>
      </div>
      <div class="topbar-right">
        <span class="topbar-date" title="Tanggal dan waktu perangkat">
          <?= icon('kalender', 14) ?>
          <span class="td-tanggal"><?= tglIndo(date('Y-m-d')) ?></span>
          <span class="td-jam" id="jamHeader">--:--:--</span>
        </span>

        <div class="notif-dd" id="notifDD">
          <button class="notif-btn" id="btnNotif" type="button"
                  aria-haspopup="true" aria-expanded="false" aria-label="Notifikasi">
            <?= icon($notif['belum'] > 0 ? 'lonceng_aktif' : 'lonceng', 19) ?>
            <span class="notif-badge" id="notifBadge"<?= $notif['belum'] > 0 ? '' : ' hidden' ?>><?= $notif['belum'] ?></span>
          </button>

          <div class="notif-menu" id="notifMenu" role="menu" aria-label="Daftar notifikasi">
            <div class="notif-head">
              <span class="nh-judul">Notifikasi</span>
              <?php if ($notif['belum'] > 0): ?>
                <form method="post" action="<?= url('notifikasi/baca-semua') ?>" class="inline nh-form">
                  <input type="hidden" name="kembali" value="<?= e(form_action()) ?>">
                  <button class="nh-aksi" type="submit"><?= icon('check', 13) ?> Tandai dibaca</button>
                </form>
              <?php endif; ?>
            </div>
            <div class="notif-list" id="notifList">
              <?php foreach ($notif['list'] as $n): ?>
                <a class="notif-item<?= $n['dibaca'] ? '' : ' baru' ?>" href="<?= url('notifikasi/baca/' . $n['id']) ?>">
                  <span class="notif-ico"><?= icon(notifIkonJenis($n['jenis']), 16) ?></span>
                  <span class="notif-txt">
                    <span class="notif-j"><?= e($n['judul']) ?></span>
                    <span class="notif-p"><?= e($n['pesan']) ?></span>
                  </span>
                  <span class="notif-w"><?= waktuRelatif($n['created_at']) ?></span>
                </a>
              <?php endforeach; ?>
              <?php if (!$notif['list']): ?>
                <span class="notif-empty"><?= icon('lonceng', 22) ?> Tidak ada notifikasi.</span>
              <?php endif; ?>
            </div>
            <a class="notif-foot" href="<?= url('notifikasi') ?>"><?= icon('daftar', 13) ?> Lihat Semua Notifikasi</a>
          </div>
        </div>

        <?php if (($active ?? '') !== 'transaksi_baru'): ?>
          <a class="btn btn-primary btn-small" href="<?= url('transaksi/baru') ?>"><?= icon('plus', 15) ?> Nota Baru</a>
        <?php endif; ?>
      </div>
    </header>

    <div class="content" id="kontenUtama" tabindex="-1">
      <?php
      $ikonAlert = ['success' => 'check', 'error' => 'peringatan', 'info' => 'info'];
      foreach (getFlashes() as $f):
        $jenis = in_array($f['jenis'], ['success', 'error', 'info'], true) ? $f['jenis'] : 'info';
      ?>
        <div class="alert alert-<?= e($jenis) ?>">
          <span class="alert-ico"><?= icon($ikonAlert[$jenis], 17) ?></span>
          <span><?= e($f['pesan']) ?></span>
        </div>
      <?php endforeach; ?>
