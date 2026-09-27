<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

/**
 * Halaman Profil Saya: lihat & ubah data akun, ganti password.
 */
class ProfilController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $id = (int)($_SESSION['user']['id'] ?? 0);
        $u  = (new User())->find($id);
        if (!$u) {
            setFlash('error', 'Data akun tidak ditemukan.');
            $this->redirect('dashboard');
        }

        $this->view('profil/index', [
            'pageTitle' => 'Profil Saya',
            'active'    => 'profil',
            'u'         => $u,
        ]);
    }

    /** Simpan perubahan nama & username. */
    public function simpan(): void
    {
        $this->requireLogin();
        $this->wajibPost();

        $id       = (int)($_SESSION['user']['id'] ?? 0);
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');

        if ($nama === '' || $username === '') {
            setFlash('error', 'Nama dan username tidak boleh kosong.');
            $this->redirect('profil');
        }
        if (mb_strlen($nama) > 100) {
            setFlash('error', 'Nama terlalu panjang (maksimal 100 karakter).');
            $this->redirect('profil');
        }
        if (mb_strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            setFlash('error', 'Username hanya boleh huruf, angka, titik, garis bawah, dan strip.');
            $this->redirect('profil');
        }

        $model = new User();
        if ($model->usernameDipakai($username, $id)) {
            setFlash('error', 'Username "' . e($username) . '" sudah dipakai akun lain.');
            $this->redirect('profil');
        }

        $model->updateProfil($id, $nama, $username);

        // Sinkronkan session supaya header langsung menampilkan nama baru
        $_SESSION['user']['nama']     = $nama;
        $_SESSION['user']['username'] = $username;

        setFlash('success', 'Profil berhasil diperbarui.');
        logAktivitas('profil_ubah', 'Mengubah nama/username profil sendiri');
        $this->redirect('profil');
    }

    /** Ganti password akun. */
    public function password(): void
    {
        $this->requireLogin();
        $this->wajibPost();

        $id           = (int)($_SESSION['user']['id'] ?? 0);
        $lama         = (string)($_POST['password_lama'] ?? '');
        $baru         = (string)($_POST['password_baru'] ?? '');
        $baruUlang    = (string)($_POST['password_baru_ulang'] ?? '');

        $u = (new User())->find($id);
        if (!$u || !password_verify($lama, $u['password_hash'])) {
            setFlash('error', 'Password lama tidak sesuai.');
            $this->redirect('profil');
        }
        if (strlen($baru) < minLengthPassword()) {
            setFlash('error', 'Password baru minimal ' . minLengthPassword() . ' karakter.');
            $this->redirect('profil');
        }
        if ($baru !== $baruUlang) {
            setFlash('error', 'Konfirmasi password baru tidak cocok.');
            $this->redirect('profil');
        }

        (new User())->updatePassword($id, password_hash($baru, PASSWORD_DEFAULT));

        setFlash('success', 'Password berhasil diganti. Gunakan password baru saat login berikutnya.');
        logAktivitas('profil_password', 'Mengganti password sendiri');
        $this->redirect('profil');
    }
}