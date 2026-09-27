<?php
/**
 * Halaman error (error boundary): 400, 401, 403, 404, 405, 500, 502, 503, dst.
 * Standalone — tidak memakai layout sidebar, agar tetap tampil walau
 * sesi berakhir / route tidak ditemukan / terjadi exception tak terduga.
 *
 * Variabel masuk dari helper tampilkanError():
 *   $kode   (int)    kode status HTTP
 *   $pesan  (string) pesan khusus (opsional, fallback ke teks bawaan)
 *   $detail (string) detail teknis (hanya ditampilkan saat APP_DEBUG=true)
 *   $judul  (string) judul khusus (opsional)
 */
$kode  = (int)($kode ?? 500);
$judul = trim((string)($judul ?? ''));
$pesan = trim((string)($pesan ?? ''));
$detail = trim((string)($detail ?? ''));

$def = [
    400 => ['Permintaan Tidak Valid', 'Permintaan ke server tidak dapat dipahami. Periksa kembali alamat tautan.'],
    401 => ['Belum Masuk', 'Silakan masuk ke akun terlebih dahulu untuk melanjutkan.'],
    403 => ['Akses Ditolak', 'Kamu tidak memiliki izin untuk membuka halaman ini.'],
    404 => ['Halaman Tidak Ditemukan', 'Halaman yang kamu cari mungkin telah dipindahkan, dihapus, atau alamatnya salah ketik.'],
    405 => ['Metode Tidak Diizinkan', 'Aksi yang diminta tidak diizinkan untuk halaman ini.'],
    500 => ['Terjadi Kesalahan Server', 'Sistem mengalami gangguan tak terduga. Silakan coba lagi beberapa saat.'],
    502 => ['Gateway Tidak Valid', 'Server menerima respons yang tidak valid. Coba lagi beberapa saat.'],
    503 => ['Layanan Sedang Sibuk', 'Sistem sedang sibuk atau dalam pemeliharaan. Coba lagi beberapa saat.'],
    504 => ['Waktu Permintaan Habis', 'Server terlalu lama merespons. Silakan coba lagi.'],
];

$judulFinal = $judul !== '' ? $judul : ($def[$kode][0] ?? 'Terjadi Kesalahan');
$pesanFinal = $pesan !== '' ? $pesan : ($def[$kode][1] ?? 'Terjadi kesalahan tak terduga.');

/* Ikon sesuai kelompok kode */
$ikonError = 'info';
if ($kode === 401) {
    $ikonError = 'user';
} elseif ($kode === 403) {
    $ikonError = 'kunci';
} elseif ($kode === 404) {
    $ikonError = 'cari';
} elseif (in_array($kode, [400, 405], true) || $kode >= 500) {
    $ikonError = 'peringatan';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= (int)$kode ?> — <?= e($judulFinal) ?> | <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/x-icon" href="<?= base_path() ?>/favicon.ico">
<link rel="stylesheet" href="<?= asset_url('style.css') ?>">
</head>
<body class="login-body">
  <div class="login-bg" aria-hidden="true"></div>
  <div class="login-card err-card">
    <div class="err-ico"><?= icon($ikonError, 22) ?></div>
    <div class="err-code"><?= (int)$kode ?></div>
    <h1><?= e($judulFinal) ?></h1>
    <p class="err-desc"><?= e($pesanFinal) ?></p>

    <?php if ($detail !== '' && defined('APP_DEBUG') && APP_DEBUG): ?>
      <div class="err-detail" title="Detail teknis (mode pengembangan)"><?= e($detail) ?></div>
    <?php endif; ?>

    <div class="err-actions">
      <div class="row">
        <button class="btn btn-outline" type="button" onclick="history.length > 1 ? history.back() : location.href='<?= e(url('dashboard')) ?>'"><?= icon('kembali', 15) ?> Kembali</button>
        <a class="btn btn-primary" href="<?= e(url('dashboard')) ?>"><?= icon('dashboard', 15) ?> Ke Beranda</a>
      </div>
      <p class="err-hint">Masalah berlanjut? Hubungi administrator sistem.</p>
    </div>
  </div>
</body>
</html>