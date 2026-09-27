<?php
namespace App\Controllers;

/**
 * Preview / status error (error boundary).
 *
 * Dipakai lewat URL:  index.php?r=error/kode/404
 * Juga menjadi target ErrorDocument .htaccess:
 *   ErrorDocument 404 /index.php?r=error/kode/404
 *
 * Halaman ini sengaja TIDAK memakai requireLogin — harus tampil
 * walau sesi habis / pengunjung belum login.
 */
class ErrorController extends \App\Core\Controller
{
    /** Tampilkan halaman error dengan kode tertentu. */
    public function kode($kode = 404): void
    {
        tampilkanError((int)$kode);
    }

    /** Default: 404 (fallback bila route tidak dikenal). */
    public function index(): void
    {
        tampilkanError(404);
    }
}