<?php
/**
 * Autoloader PSR-4 sederhana untuk namespace "App\".
 * Folder app/ dipetakan sesuai namespace: App\Controllers\X -> app/controllers/X.php
 *
 * Catatan: namespace memakai huruf kapital (App\Core, App\Controllers), sedangkan
 * folder fisik di dalam paket ini ditulis huruf kecil (app/core, app/controllers).
 * Windows tidak membedakan besar/kecilnya huruf, tetapi hosting Linux (mis.
 * InfinityFree) membedakannya — karena itu autoloader punya pencarian cadangan
 * yang memadankan nama tanpa peduli besar/kecilnya huruf.
 */

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    $base     = dirname(__DIR__);

    /* Jalur normal (sama persis). */
    $file = $base . '/' . $relative;
    if (is_file($file)) {
        require $file;
        return;
    }

    /* Pencarian cadangan: samakan nama folder/file tanpa peduli besar/kecil huruf.
       Berguna di hosting Linux bila folder fisik tertulis lower-case. */
    $dir       = $base;
    $segmen    = explode('/', $relative);
    $total     = count($segmen);
    $diproses  = 0;

    foreach ($segmen as $bagian) {
        $cocok = null;
        $daftar = glob($dir . '/*');
        if (is_array($daftar)) {
            foreach ($daftar as $entri) {
                if (strcasecmp(basename($entri), $bagian) === 0) {
                    $cocok = $entri;
                    break;
                }
            }
        }
        if ($cocok === null) {
            return; /* Tidak ditemukan. */
        }
        $dir = $cocok;
        $diproses++;
    }

    if ($diproses === $total && is_file($dir)) {
        require $dir;
    }
});