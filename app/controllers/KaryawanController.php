<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

/**
 * Manajemen karyawan (akun staff) - dibuat LANGSUNG oleh kepala toko (manager)
 * atau root. Tidak lewat alur permintaan; "persetujuan toko" = aksi manager ini.
 *
 * HAK: root & manager (lihat HAK_AKSES['karyawan']).
 * KaryawanController hanya mengelola akun ber-role "staff":
 *   - tidak bisa mengubah role,
 *   - tidak bisa mengelola akun manager/root,
 *   - reset password & nonaktif/aktif tersedia.
 *
 * Route:
 *   index.php?r=karyawan               -> daftar karyawan
 *   index.php?r=karyawan/tambah        -> (POST) buat akun staff baru
 *   index.php?r=karyawan/ubah/ID       -> (POST) ubah nama/username/aktif
 *   index.php?r=karyawan/reset/ID      -> (POST) reset password
 *   index.php?r=karyawan/hapus/ID      -> (POST) hapus karyawan
 */
class KaryawanController extends Controller
{
    public function index(): void
    {
        $this->requireHak('karyawan');

        $edit = (int)($_GET['edit'] ?? 0);

        $dataEdit = null;
        if ($edit) {
            $dataEdit = (new User())->find($edit);
            if (!$dataEdit || $dataEdit['role'] !== 'staff') {
                $edit = 0;
            }
        }

        $this->view('karyawan/index', [
            'pageTitle' => 'Karyawan',
            'active'    => 'karyawan',
            'list'      => (new User())->daftarKaryawan(),
            'edit'      => $edit,
            'dataEdit'  => $dataEdit,
        ]);
    }

    /** Buat akun karyawan (role staff) langsung aktif. */
    public function tambah(): void
    {
        $this->requireHak('karyawan');
        $this->wajibPost();

        $nama      = trim((string)($_POST['nama'] ?? ''));
        $username  = trim((string)($_POST['username'] ?? ''));
        $password  = (string)($_POST['password'] ?? '');
        $konfirmasi = (string)($_POST['password_ulang'] ?? '');

        if ($nama === '' || $username === '' || $password === '') {
            setFlash('error', 'Nama, username, dan password wajib diisi.');
            $this->redirect('karyawan');
        }
        if (mb_strlen($nama) > 100) {
            setFlash('error', 'Nama terlalu panjang (maksimal 100 karakter).');
            $this->redirect('karyawan');
        }
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username) || mb_strlen($username) > 50) {
            setFlash('error', 'Username hanya boleh huruf, angka, titik, garis bawah, dan strip (maks 50).');
            $this->redirect('karyawan');
        }
        if (strlen($password) < minLengthPassword()) {
            setFlash('error', 'Password minimal ' . minLengthPassword() . ' karakter.');
            $this->redirect('karyawan');
        }
        if ($password !== $konfirmasi) {
            setFlash('error', 'Konfirmasi password tidak cocok.');
            $this->redirect('karyawan');
        }

        $model = new User();
        if ($model->usernameDipakai($username)) {
            setFlash('error', 'Username "' . e($username) . '" sudah dipakai akun lain.');
            $this->redirect('karyawan');
        }

        $model->create($nama, $username, password_hash($password, PASSWORD_DEFAULT), 'staff');

        setFlash('success', 'Karyawan "' . e($nama) . '" (@' . e($username) . ') berhasil dibuat dan langsung aktif.');
        logAktivitas('karyawan_tambah', 'Menambah karyawan ' . $username . ' (' . $nama . ')');
        notifKirimKe(
            array_diff((new User())->idPemilik(), [(int)($_SESSION['user']['id'] ?? 0)]),
            'info',
            'Karyawan Baru',
            $nama . ' (' . $username . ') didaftarkan sebagai staff.',
            'karyawan'
        );
        $this->redirect('karyawan');
    }

    /** Ubah nama, username, dan status aktif karyawan (role staff). */
    public function ubah(int $id): void
    {
        $this->requireHak('karyawan');
        $this->wajibPost();

        $nama     = trim((string)($_POST['nama'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $aktif    = isset($_POST['aktif']) ? 1 : 0;

        if ($nama === '' || $username === '') {
            setFlash('error', 'Nama dan username tidak boleh kosong.');
            $this->redirect('karyawan', ['edit' => $id]);
        }
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username) || mb_strlen($username) > 50) {
            setFlash('error', 'Username hanya boleh huruf, angka, titik, garis bawah, dan strip (maks 50).');
            $this->redirect('karyawan', ['edit' => $id]);
        }

        $model  = new User();
        $target = $model->find($id);
        if (!$target || $target['role'] !== 'staff') {
            setFlash('error', 'Karyawan tidak ditemukan.');
            $this->redirect('karyawan');
        }
        if ($model->usernameDipakai($username, $id)) {
            setFlash('error', 'Username "' . e($username) . '" sudah dipakai akun lain.');
            $this->redirect('karyawan', ['edit' => $id]);
        }

        $model->updateAkun($id, $nama, $username, 'staff', $aktif === 1);

        setFlash('success', 'Karyawan "' . e($nama) . '" berhasil diperbarui.');
        logAktivitas('karyawan_ubah', 'Mengubah karyawan ' . $username
            . ', aktif: ' . ($aktif ? 'ya' : 'tidak'));
        $this->redirect('karyawan');
    }

    /** Reset password karyawan. */
    public function reset(int $id): void
    {
        $this->requireHak('karyawan');
        $this->wajibPost();

        $password = (string)($_POST['password'] ?? '');
        if (strlen($password) < minLengthPassword()) {
            setFlash('error', 'Password baru minimal ' . minLengthPassword() . ' karakter.');
            $this->redirect('karyawan');
        }

        $model  = new User();
        $target = $model->find($id);
        if (!$target || $target['role'] !== 'staff') {
            setFlash('error', 'Karyawan tidak ditemukan.');
            $this->redirect('karyawan');
        }

        $model->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));

        setFlash('success', 'Password karyawan "' . e($target['username']) . '" berhasil direset.');
        logAktivitas('karyawan_reset', 'Reset password karyawan ' . $target['username']);
        $this->redirect('karyawan');
    }

    /** Hapus akun karyawan (role staff). */
    public function hapus(int $id): void
    {
        $this->requireHak('karyawan');
        $this->wajibPost();

        $model  = new User();
        $target = $model->find($id);
        if (!$target || $target['role'] !== 'staff') {
            setFlash('error', 'Karyawan tidak ditemukan.');
            $this->redirect('karyawan');
        }

        $model->hapus($id);

        setFlash('success', 'Karyawan "' . e($target['nama']) . '" (@' . e($target['username']) . ') dihapus.');
        logAktivitas('karyawan_hapus', 'Menghapus karyawan ' . $target['username']);
        $this->redirect('karyawan');
    }
}
