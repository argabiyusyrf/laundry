<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

/**
 * Kontrol autentikasi: login, logout, redirect awal.
 *
 * Multi-user: akun punya role (root/manager/staff) dan status aktif.
 * Akun nonaktif tidak bisa login. Waktu login terakhir dicatat.
 */
class AuthController extends Controller
{
    public function index(): void
    {
        $this->redirect(empty($_SESSION['user']) ? 'login' : 'dashboard');
    }

    public function login(): void
    {
        /* Sudah login -> langsung dashboard */
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' && !empty($_SESSION['user'])) {
            $this->redirect('dashboard');
        }

        $error = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            /* Token formulir wajib cocok, sama seperti aksi lain di aplikasi. */
            if (!csrfValid()) {
                $error = 'Sesi login sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
            } else {
                $username = trim((string)($_POST['username'] ?? ''));
                $password = (string)($_POST['password'] ?? '');

                $user = (new User())->findByUsername($username);
                $modelUser = new User();

                /* Semua kegagalan memakai pesan yang sama supaya halaman login tidak
                   membocorkan username mana yang terdaftar, terkunci, atau nonaktif.
                   Rincian tetap dicatat di log aktivitas. */
                $gagalUmum = 'Username atau password salah.';

                /* Akun terkunci sementara (terlalu sering gagal login). */
                $terkunci = $user ? (string)($user['terkunci_hingga'] ?? '') : '';
                if ($user && $terkunci !== '' && strtotime($terkunci) > time()) {
                    $error = $gagalUmum . ' Akun sedang dikunci sementara karena terlalu banyak percobaan gagal.';
                    logAktivitas('login_gagal', 'Percobaan login ke akun terkunci: ' . $user['username']);
                } elseif ($user && (int)($user['aktif'] ?? 1) === 0) {
                    $error = $gagalUmum . ' Hubungi pemilik sistem bila akun ini seharusnya aktif.';
                    logAktivitas('login_gagal', 'Percobaan login ke akun nonaktif: ' . $user['username']);
                } elseif ($user && password_verify($password, (string)$user['password_hash'])) {
                    session_regenerate_id(true);

                    $role = $user['role'] ?? 'staff';
                    $_SESSION['user'] = [
                        'id'       => (int)$user['id'],
                        'username' => (string)$user['username'],
                        'nama'     => (string)$user['nama'],
                        'role'     => $role,
                    ];
                    $_SESSION['last_activity'] = time();

                    $modelUser->resetGagalLogin((int)$user['id']);
                    $modelUser->lastLoginUpdate((int)$user['id']);
                    logAktivitas('login', 'Login sebagai ' . $user['username'] . ' (' . $role . ')');

                    /* Kembali ke halaman yang semula dituju (mis. hasil cari), bukan dashboard. */
                    $tujuan = (string)($_SESSION['login_back'] ?? '');
                    unset($_SESSION['login_back']);

                    $base = rtrim(BASE_REL, '/');
                    if ($tujuan !== '' && urlInternal($tujuan)
                        && ($base === '' || $tujuan === $base || strpos($tujuan, $base . '/') === 0)) {
                        header('Location: ' . $tujuan);
                        exit;
                    }

                    $this->redirect('dashboard');
                } else {
                    /* Password salah: catat percobaan & kunci akun bila melewati batas
                       (pengaturan "batas_login_gagal", default 5x -> terkunci 5 menit). */
                    if ($user) {
                        $batas = max(1, (int)pengaturan('batas_login_gagal', '5'));
                        $gagal = (int)($user['gagal_login'] ?? 0) + 1;
                        if ($gagal >= $batas) {
                            $modelUser->kunciSementara((int)$user['id'], 5);
                            $modelUser->updateGagalLogin((int)$user['id'], 0);
                            $error = $gagalUmum . ' Akun dikunci sementara 5 menit.';
                            logAktivitas('login_gagal', 'Akun ' . $user['username'] . ' dikunci sementara (' . $batas . 'x gagal)');
                        } else {
                            $modelUser->updateGagalLogin((int)$user['id'], $gagal);
                            $error = $gagalUmum;
                            logAktivitas('login_gagal', 'Password salah untuk ' . $user['username'] . ' (percobaan ' . $gagal . ')');
                        }
                    } else {
                        $error = $gagalUmum;
                    }
                }
            }
        }

        $this->view('auth/login', [
            'pageTitle' => 'Login',
            'error'     => $error,
        ]);
    }

    public function logout(): void
    {
        /* Catat logout SEBELUM sesi dibersihkan (butuh data user di session). */
        logAktivitas('logout', 'Logout dari akun ' . ($_SESSION['user']['username'] ?? ''));

        logoutSesi();
        $this->redirect('login');
    }
}