<?php
declare(strict_types=1);

/**
 * ============================================================
 *  LaundryKu — Front Controller (bootstrap + router)
 *  Semua request masuk ke file ini:
 *      http://localhost/laundry/index.php?r=dashboard
 *      http://localhost/laundry/index.php?r=transaksi/detail/4
 * ============================================================
 */

error_reporting(E_ALL);

/* --- Jalur & URL dasar --- */
define('APP_PATH', __DIR__ . '/app');
define('VIEW_PATH', APP_PATH . '/views');
define('BASE_PATH', __DIR__);
define('BASE_REL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'));
define('LOG_PATH', __DIR__ . '/storage/logs');

/* --- Cookie sesi yang aman ---
 * HttpOnly  : cookie tidak bisa dibaca JavaScript (lindungi dari pencurian via XSS)
 * SameSite  : tidak ikut terkirim pada request dari situs lain (lapisan anti-CSRF)
 * Secure    : hanya lewat HTTPS bila situsnya memakai HTTPS
 * Mode development HTTP tetap bisa login karena Secure otomatis dimatikan. */
$skemaHttps = (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string)$_SERVER['HTTPS']) !== 'off')
    || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443
    || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $skemaHttps ? '1' : '0');
/* Cookie sesi hanya untuk folder aplikasi ini, bukan seluruh domain.
   Cookie di /foo/ tidak perlu ikut terkirim ke /foo/public/ dan sebaliknya. */
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => BASE_REL !== '' ? BASE_REL . '/' : '/',
    'domain'   => '',
    'secure'   => $skemaHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('LAUNDRYKUSESSID');
session_start();

/* --- Header keamanan (semua halaman, termasuk halaman error) --- */
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'unsafe-inline'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data:; "
        . "media-src 'self'; "
        . "font-src 'self'; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'none'");
    /* Matikan fitur browser yang tidak dipakai LaundryKu (kamera, mikrofon,
       geolokasi, pembayaran, dan unduhan otomatis) supaya halaman yang
       disisipkan pihak lain tidak bisa memakainya. */
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), '
        . 'payment=(), usb=(), interest-cohort=()');
}

/* --- Log error ke file ---------------------------------------------------------
 * Semua kesalahan (exception, fatal error PHP, warning) dicatat ke folder
 * storage/logs/. File log tidak bisa dibuka lewat browser (dilindungi .htaccess),
 * hanya terbaca lewat File Manager hosting atau halaman inspeksi.php.
 * ----------------------------------------------------------------------------- */
if (!is_dir(LOG_PATH)) {
    @mkdir(LOG_PATH, 0775, true);
}
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/php-errors.log');

/* Tangkap error fatal yang terjadi SEBELUM blok try (mis. saat require config).
   Bila file config.php bermasalah, error tetap tercatat ke file log. */
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($err['type'], $fatal, true)) {
        return;
    }
    if (defined('LOG_PATH')) {
        $baris = sprintf(
            "[%s] FATAL %s di %s:%d%s",
            date('Y-m-d H:i:s'),
            $err['message'],
            $err['file'],
            $err['line'],
            PHP_EOL
        );
        @file_put_contents(LOG_PATH . '/laundryku-' . date('Y-m-d') . '.log', $baris, FILE_APPEND | LOCK_EX);
    }
});

require APP_PATH . '/config/config.php';
require APP_PATH . '/core/Autoload.php';
require APP_PATH . '/helpers/helpers.php';

/* Tampil detail error di localhost (APP_DEBUG=true), disembunyikan di produksi.
   Yang disembunyikan hanya TAMPILAN di layar — pencatatan ke file log tetap jalan. */
ini_set('display_errors', APP_DEBUG ? '1' : '0');

use App\Core\App;

try {
    (new App())->run();
} catch (Throwable $e) {
    /* Catat ke file log (detail penuh) sebelum merender halaman error.
       Dibungkus try/catch agar kegagalan menulis log tidak menimbulkan fatal baru. */
    try {
        \App\Core\Log::tulis('ERROR', $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine());
    } catch (\Throwable $logE) {
        /* Abaikan: halaman error tetap ditampilkan. */
    }

    /* Error boundary: halaman 500 yang utuh (berikut detail di mode pengembangan). */
    $detail = '';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $detail = $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine();
    }
    tampilkanError(500, 'Sistem mengalami gangguan tak terduga saat memproses permintaan.', $detail);
}