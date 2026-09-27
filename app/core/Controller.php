<?php
namespace App\Core;

/**
 * Controller dasar: memuat view, redirect, dan proteksi login.
 */
abstract class Controller
{
    /**
     * Render view dengan data. View dipanggil dalam satu scope agar variabel tersedia.
     *
     * Keluaran view ditangkap dulu supaya setiap <form method="post"> otomatis
     * mendapat hidden input token anti-CSRF (lihat csrfSuntik()). Dengan begitu
     * semua formulir terlindungi tanpa menulis token satu per satu di tiap view.
     */
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $file = VIEW_PATH . '/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View tidak ditemukan: ' . $view);
        }

        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        echo csrfSuntik((string)ob_get_clean());
    }

    /**
     * Wajib POST + token anti-CSRF yang valid.
     *
     * Dipakai untuk aksi merusak atau mengubah data yang TIDAK punya halaman
     * render di atasnya (hapus, bayar, setujui, tolak, reset, dan sejenisnya).
     * Tanpa penjaga ini aksi tersebut bisa dipicu hanya lewat tautan atau
     * <img src="..."> dari situs lain (serangan CSRF).
     */
    protected function wajibPost(string $pesan = 'Aksi ini harus dikirim dari formulir di dalam aplikasi.'): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            tampilkanError(405, $pesan);
            return;
        }
        if (!csrfValid()) {
            /* 403 (bukan 419): 419 tidak baku di RFC dan Apache mengubahnya
               menjadi 500, sehingga pesan "token kedaluwarsa" sampai ke
               pengguna sebagai "server error" yang menyesatkan. */
            tampilkanError(403, 'Token keamanan formulir tidak valid atau sudah kedaluwarsa. '
                . 'Muat ulang halaman lalu ulangi aksinya.');
        }
    }

    /** Redirect ke route lain: ?r=route&params */
    protected function redirect(string $route, array $params = []): void
    {
        header('Location: ' . url($route, $params));
        exit;
    }

    /**
     * Redirect kembali ke halaman asal bila form membawa field "kembali"
     * (URL internal aplikasi), sehingga filter pencarian tetap dipertahankan.
     */
    protected function redirectKembali(string $fallbackRoute, array $params = []): void
    {
        $tujuan = trim((string)($_POST['kembali'] ?? ($_GET['kembali'] ?? '')));
        $base   = rtrim(BASE_REL, '/') . '/';

        /* Terima hanya URL internal aplikasi (mis. /laundry/index.php?r=...) untuk
           mencegah open redirect. urlInternal() juga menolak "//evil.tld". */
        if ($tujuan !== '' && strpos($tujuan, $base) === 0 && urlInternal($tujuan)) {
            header('Location: ' . $tujuan);
            exit;
        }

        $this->redirect($fallbackRoute, $params);
    }

    /** Wajib login, jika belum login arahkan ke halaman login. */
    protected function requireLogin(): void
    {
        if (empty($_SESSION['user'])) {
            /* Simpan halaman yang dituju agar setelah login kembali ke sana (bukan dashboard).
               Hanya untuk GET; form POST tidak bisa diulang dengan GET setelah login. */
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
                && is_string($uri) && $uri !== '' && strpos($uri, '://') === false) {
                $base = rtrim(BASE_REL, '/');
                if ($base === '' || $uri === $base || strpos($uri, $base . '/') === 0) {
                    $_SESSION['login_back'] = $uri;
                }
            }
            $this->redirect('login');
        }

        /* Sesi kedaluwarsa: bila diaktifkan di Pengaturan -> Keamanan Akun.
           User yang terlalu lama tidak beraktivitas otomatis logout. */
        $menit = (int)pengaturan('sesi_kedaluwarsa_menit', '0');
        if ($menit > 0) {
            $terakhir = (int)($_SESSION['last_activity'] ?? 0);
            if ($terakhir > 0 && time() - $terakhir > $menit * 60) {
                logAktivitas('logout', 'Sesi berakhir otomatis (tidak aktif ' . $menit . ' menit)');
                logoutSesi();
                setFlash('info', 'Sesi Anda berakhir karena terlalu lama tidak aktif. Silakan login kembali.');
                $this->redirect('login');
            }
        }
        /* Anti-CSRF: setiap POST dari pengguna yang sudah login wajib membawa
           token formulir yang cocok dengan sesinya. Tanpa ini, situs lain bisa
           mengirim formulir atas nama pengguna yang sedang login. */
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrfValid()) {
            /* Lihat catatan di wajibPost(): 419 diubah Apache menjadi 500. */
            tampilkanError(403, 'Token keamanan formulir tidak valid atau sudah kedaluwarsa. '
                . 'Muat ulang halaman lalu ulangi aksinya.');
        }

        $_SESSION['last_activity'] = time();
    }

    /** Data user yang sedang login. */
    protected function currentUser(): array
    {
        return $_SESSION['user'] ?? [];
    }

    /**
     * Wajib login + memiliki izin menu (lihat HAK_AKSES di config.php).
     * Bila role tidak berhak, tampilkan halaman 403 (larangan akses).
     */
    protected function requireHak(string $menu): void
    {
        $this->requireLogin();
        if (!cekHak($menu)) {
            tampilkanError(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }
    }
}