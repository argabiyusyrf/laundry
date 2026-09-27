<?php
namespace App\Core;

/**
 * Router + Dispatcher sederhana.
 * Route dibaca dari parameter "r":   ?r=controller/method/param1/param2
 *
 * Contoh:
 *   ?r=dashboard                 -> DashboardController::index()
 *   ?r=transaksi                 -> TransaksiController::index()
 *   ?r=transaksi/detail/4        -> TransaksiController::detail(4)
 *   ?r=notifikasi/baca-semua     -> NotifikasiController::bacaSemua()
 *
 * Pengaman:
 *   - controller & method hanya boleh yang terdaftar di daftar ROUTE (default deny),
 *     sehingga method bawaan PHP seperti __construct tidak bisa dipanggil lewat URL;
 *   - parameter yang dikettyakan sebagai "int" divalidasi lebih dulu, supaya request
 *     seperti ?r=akun/ubah/abc tidak memicu halaman error 500.
 */
class App
{
    /**
     * Daftar putih route: nama route => [nama class controller, method yang boleh dipanggil].
     * Method ditulis dalam bentuk camelCase; router tetap menerima bentuk kebab-case
     * (baca-semua, hapus-logo, setujui-permintaan, dan sejenisnya).
     */
    private const ROUTE = [
        'auth'         => ['AuthController',        ['index', 'login', 'logout']],
        'dashboard'    => ['DashboardController',   ['index']],
        'transaksi'    => ['TransaksiController',   ['index', 'baru', 'simpan', 'edit', 'ubah', 'detail', 'cetak', 'status', 'bayar', 'hapus']],
        'pelanggan'    => ['PelangganController',   ['index', 'tambah', 'ubah', 'hapus', 'detail']],
        'paket'        => ['PaketController',       ['index', 'tambah', 'ubah', 'hapus', 'detail']],
        'laporan'      => ['LaporanController',     ['index', 'export']],
        'karyawan'     => ['KaryawanController',    ['index', 'tambah', 'ubah', 'reset', 'hapus']],
        'akun'         => ['AkunController',        ['index', 'permintaan', 'tambah', 'ubah', 'reset', 'hapus', 'setujuiPermintaan', 'tolakPermintaan']],
        'permintaan'   => ['PermintaanController',  ['index', 'kirim', 'status']],
        'notifikasi'   => ['NotifikasiController',  ['index', 'edit', 'ubah', 'hapus', 'data', 'kirim', 'baca', 'bacaSemua']],
        'pengaturan'   => ['PengaturanController',  ['index', 'simpan', 'hapusLogo']],
        'profil'       => ['ProfilController',      ['index', 'simpan', 'password']],
        'aktivitas'    => ['AktivitasController',   ['index']],
        'error'        => ['ErrorController',       ['kode', 'index']],
    ];

    public function run(): void
    {
        $r = trim((string)($_GET['r'] ?? ''));

        /* Fallback PATH_INFO untuk server tanpa mod_rewrite, mis. /index.php/dashboard */
        if ($r === '' && !empty($_SERVER['PATH_INFO'])) {
            $r = ltrim(trim((string)$_SERVER['PATH_INFO']), '/');
        }

        if ($r === '') {
            $r = 'auth';
        }

        $segments = explode('/', $r);
        $namaRoute = (string)array_shift($segments);

        /* Alias route: ?r=login dan ?r=logout memanggil AuthController dengan
           method bernama sama. Nama method ikut dipasang ke dalam $segments
           supaya tidak terjatuh ke method "index" (yang hanya memantulkan
           pengunjung ke login, sehingga terjadi pengalihan berulang). */
        $alias = ['login' => 'login', 'logout' => 'logout'];
        if (isset($alias[$namaRoute])) {
            $segments = array_merge([$alias[$namaRoute]], $segments);
            $namaRoute = 'auth';
        }

        if (!isset(self::ROUTE[$namaRoute])) {
            $this->abort(404, 'Halaman tidak ditemukan.');
            return;
        }

        [$namaClass, $methodBoleh] = self::ROUTE[$namaRoute];
        $class = 'App\\Controllers\\' . $namaClass;

        $method = $segments !== [] ? (string)array_shift($segments) : 'index';
        /* Normalisasi kebab-case ke camelCase: hapus-logo -> hapusLogo */
        $method = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $method))));

        if (!in_array($method, $methodBoleh, true) || !class_exists($class)) {
            $this->abort(404, 'Halaman tidak ditemukan.');
            return;
        }

        $instance = new $class();

        if (!is_callable([$instance, $method])) {
            $this->abort(404, 'Halaman tidak ditemukan.');
            return;
        }

        /* rapikanParam() mengembalikan null kalau parameter tidak cocok; halaman
           404 sudah ditampilkan di sana, jadi jangan panggil method-nya. */
        $args = $this->rapikanParam($instance, $method, $segments);
        if ($args === null) {
            return;
        }

        call_user_func_array([$instance, $method], $args);
    }

    /**
     * Cocokkan parameter URL dengan tanda tangan method.
     * - Parameter wajib yang tidak ada di URL -> 404 (bukan 500 karena
     *   ArgumentCountError). Contoh: ?r=transaksi/status tanpa id.
     * - Terlalu banyak parameter -> dipangkas (tidak fatal).
     * - Parameter bertipe int: wajib angka, selain itu dianggap route tidak valid.
     * - Parameter bertipe string: dibersihkan dari karakter kontrol.
     *
     * @return array<int,mixed>|null Daftar argumen, atau null bila halaman 404
     *                            sudah ditampilkan dan pemanggil harus berhenti.
     */
    private function rapikanParam(object $objek, string $method, array $params): ?array
    {
        try {
            $refleksi = new \ReflectionMethod($objek, $method);
        } catch (\ReflectionException $e) {
            return null;
        }

        $out = [];

        foreach ($refleksi->getParameters() as $i => $def) {
            /* Parameter wajib = tidak punya nilai bawaan dan bukan variadik.
               Jangan panggil getDefaultValue() di sini: untuk parameter tanpa
               nilai bawaan fungsi itu melempar ReflectionException. */
            $wajib = !$def->isOptional() && !$def->isVariadic();

            if (!isset($params[$i])) {
                if ($wajib) {
                    /* Method-nya butuh id, tapi URL-nya tidak menyertakan. Dijalankan apa
                       adanya, pemanggilannya melempar ArgumentCountError yang tampil
                       sebagai "Kesalahan server" (500). */
                    $this->abort(404, 'Alamat tidak lengkap. Cek kembali tautan yang Anda buka.');
                    return null;
                }
                break;
            }

            $nilai = (string)$params[$i];
            $tipe  = $def->hasType() ? (string)$def->getType() : '';

            if ($tipe === 'int') {
                if (!preg_match('/^-?\d{1,10}$/', $nilai)) {
                    $this->abort(404, 'Alamat tidak valid.');
                    return null;
                }
                $out[] = (int)$nilai;
            } else {
                $out[] = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $nilai));
            }
        }

        return $out;
    }

    private function abort(int $kode, string $pesan): void
    {
        tampilkanError($kode, $pesan);
    }
}
