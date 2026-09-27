<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\PermintaanAkun;
use App\Models\User;

/**
 * Permintaan akun toko (halaman publik, sebelum login).
 *
 * INI BUKAN FITUR REGISTER: permintaan tidak langsung membuat akun.
 * Alur:
 *   1. Calon pemilik toko mengisi formulir (nama toko, pemohon, username,
 *      password, kontak) -> tersimpan status "menunggu".
 *   2. Root menyetujui/menolak di menu Akun & Pengguna -> tab Permintaan Toko.
 *   3. Disetujui -> akun role "manager" dibuat & langsung aktif; pemohon login
 *      dengan username/password yang didaftarkan.
 *   4. Ditangguhkan/ditolak -> pemohon bisa cek status lewat halaman ini.
 *
 * Route:
 *   index.php?r=permintaan            -> form minta akun toko (tamu)
 *   index.php?r=permintaan/kirim      -> (POST) ajukan permintaan baru
 *   index.php?r=permintaan/status     -> halaman publik: cek status permintaan (GET/POST)
 */
class PermintaanController extends Controller
{
    public function index(): void
    {
        /* Sudah login -> tidak perlu meminta akun lagi. */
        if (!empty($_SESSION['user'])) {
            $this->redirect('dashboard');
        }

        $this->view('permintaan/index', [
            'pageTitle' => 'Minta Akun Toko',
            'hasilCek'  => null,
        ]);
    }

    /** Simpan permintaan akun toko baru (status menunggu). */
    public function kirim(): void
    {
        if (!empty($_SESSION['user'])) {
            $this->redirect('dashboard');
        }

        /* Halaman ini publik, jadi wajib POST + token formulir yang valid.
           Tanpa ini, form bisa dipicu dari situs lain atas nama pengunjung. */
        $this->wajibPost();

        $namaToko     = trim((string)($_POST['nama_toko'] ?? ''));
        $namaPemohon  = trim((string)($_POST['nama_pemohon'] ?? ''));
        $username     = trim((string)($_POST['username'] ?? ''));
        $password     = (string)($_POST['password'] ?? '');
        $konfirmasi   = (string)($_POST['password_ulang'] ?? '');
        $telepon      = trim((string)($_POST['telepon'] ?? ''));
        $alamat       = trim((string)($_POST['alamat'] ?? ''));

        $error = '';
        if ($namaToko === '' || $namaPemohon === '' || $username === '') {
            $error = 'Nama toko, nama pemohon, dan username wajib diisi.';
        } elseif (mb_strlen($namaToko) > 120 || mb_strlen($namaPemohon) > 100) {
            $error = 'Nama toko (maks. 120 karakter) atau nama pemohon (maks. 100 karakter) terlalu panjang.';
        } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username) || strlen($username) > 50) {
            $error = 'Username hanya boleh huruf, angka, titik, garis bawah, dan strip (maks. 50 karakter).';
        } elseif (strlen($password) < minLengthPassword()) {
            $error = 'Password minimal ' . minLengthPassword() . ' karakter.';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif (strlen($telepon) > 30 || strlen($alamat) > 255) {
            $error = 'Data kontak terlalu panjang.';
        } elseif (!throttle('permintaan-kirim', 3, 3600)) {
            /* Batas 3 permintaan per jam per pengunjung, supaya halaman publik ini
               tidak bisa dipakai membanjiri tabel permintaan_akun. */
            $error = 'Terlalu banyak permintaan dikirim dari perangkat ini. Coba lagi satu jam lagi.';
        }

        if ($error === '') {
            $modelUser = new User();
            $modelPerm = new PermintaanAkun();
            if ($modelUser->usernameDipakai($username)) {
                $error = 'Username "' . e($username) . '" sudah dipakai. Pilih username lain.';
            } elseif ($modelPerm->usernameMenunggu($username)) {
                $error = 'Sudah ada permintaan menunggu dengan username "' . e($username) . '". Hubungi admin bila sudah lama.';
            }
        }

        if ($error !== '') {
            setFlash('error', $error);
            $this->view('permintaan/index', [
                'pageTitle' => 'Minta Akun Toko',
                'hasilCek'  => null,
                'lama'      => [
                    'nama_toko'    => $namaToko,
                    'nama_pemohon' => $namaPemohon,
                    'username'     => $username,
                    'telepon'      => $telepon,
                    'alamat'       => $alamat,
                ],
            ]);
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $id   = (new PermintaanAkun())->kirim($namaToko, $namaPemohon, strtolower($username), $hash, $telepon, $alamat);

        /* Notifikasi BERTARGET khusus root: ada permintaan baru (tidak melebar ke semua). */
        notifKirimKe(
            (new User())->idRole('root'),
            'info',
            'Permintaan Akun Toko Baru',
            $namaToko . ' (' . $namaPemohon . ') meminta akun toko. Silakan tinjau.',
            'akun/permintaan'
        );
        logAktivitas('permintaan_kirim', 'Permintaan akun toko ' . $namaToko . ' (username: ' . $username . ')');

        setFlash('success', 'Permintaan terkirim (No. #' . $id . '). Tunggu persetujuan pemilik sistem '
            . '(root). Setelah disetujui, langsung login dengan username yang didaftarkan.');
        $this->redirect('permintaan');
    }

    /** Halaman publik "Cek Status": form + hasil ditampilkan di halaman yang sama. */
    public function status(): void
    {
        if (!empty($_SESSION['user'])) {
            $this->redirect('dashboard');
        }

        $username = trim((string)($_GET['username'] ?? $_POST['username'] ?? ''));
        $hasilCek = null;
        $pesan    = '';

        if ($username === '') {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                $pesan = 'Masukkan username yang didaftarkan.';
            }
        } elseif (!throttle('permintaan-status', 20, 600)) {
            /* Batas 20 pemeriksaan per 10 menit, supaya halaman ini tidak dipakai
               memindai username satu per satu untuk memetakan data pemohon. */
            $pesan = 'Terlalu banyak pemeriksaan status. Coba lagi beberapa menit lagi.';
        } else {
            $hasilCek = (new PermintaanAkun())->cekStatus(strtolower($username));
            if (!$hasilCek) {
                $pesan = 'Tidak ada permintaan dengan username "' . e($username) . '".';
            }
        }

        $this->view('permintaan/status', [
            'pageTitle'    => 'Cek Status Permintaan',
            'usernameLama' => $username,
            'hasilCek'     => $hasilCek,
            'pesan'        => $pesan,
        ]);
    }
}