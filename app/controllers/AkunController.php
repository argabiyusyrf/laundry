<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\PermintaanAkun;
use App\Models\User;

/**
 * Manajemen akun pengguna (multi-user & role).
 *
 * HAK: khusus admin[root]. Root membuat akun manager/staff,
 * mengubah peran & status aktif, mereset password, dan menghapus akun.
 * Selain itu root menyetujui / menolak permintaan akun toko
 * (dari halaman publik ?r=permintaan) lewat halaman ?r=akun/permintaan.
 *
 * Route:
 *   index.php?r=akun                  -> daftar akun & kelola akun
 *   index.php?r=akun/permintaan       -> daftar permintaan akun toko
 *   index.php?r=akun/tambah           -> (POST) buat akun baru
 *   index.php?r=akun/ubah/ID          -> (POST) ubah nama/username/role/aktif
 *   index.php?r=akun/reset/ID         -> (POST) reset password
 *   index.php?r=akun/hapus/ID         -> (POST) hapus akun
 *   index.php?r=akun/setujui-permintaan/ID -> (POST) setujui permintaan akun toko
 *   index.php?r=akun/tolak-permintaan/ID   -> (POST) tolak permintaan akun toko
 */
class AkunController extends Controller
{
    public function index(): void
    {
        $this->requireHak('akun');

        $edit = (int)($_GET['edit'] ?? 0);

        $dataEdit = null;
        if ($edit) {
            $dataEdit = (new User())->find($edit);
            if (!$dataEdit) {
                $edit = 0;
            }
        }

        $this->view('akun/index', [
            'pageTitle' => 'Akun & Pengguna',
            'active'    => 'akun',
            'list'      => (new User())->semua(),
            'edit'      => $edit,
            'dataEdit'  => $dataEdit,
        ]);
    }

    /** Halaman terpisah: daftar permintaan akun toko (dipisah dari Akun & Pengguna). */
    public function permintaan(): void
    {
        $this->requireHak('akun');

        $this->view('permintaan/daftar', [
            'pageTitle'   => 'Permintaan Toko',
            'active'      => 'permintaan_admin',
            'permintaan'  => (new PermintaanAkun())->semua(),
            'jmlMenunggu' => (new PermintaanAkun())->jumlahMenunggu(),
        ]);
    }

    /** Buat akun baru. */
    public function tambah(): void
    {
        $this->requireHak('akun');
        $this->wajibPost();

        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $role     = (string)($_POST['role'] ?? 'staff');

        if ($nama === '' || $username === '' || $password === '') {
            setFlash('error', 'Nama, username, dan password wajib diisi.');
            $this->redirect('akun');
        }
        if (strlen($password) < minLengthPassword()) {
            setFlash('error', 'Password minimal ' . minLengthPassword() . ' karakter.');
            $this->redirect('akun');
        }
        if (mb_strlen($nama) > 100) {
            setFlash('error', 'Nama terlalu panjang (maksimal 100 karakter).');
            $this->redirect('akun');
        }
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username) || mb_strlen($username) > 50) {
            setFlash('error', 'Username hanya boleh huruf, angka, titik, garis bawah, dan strip (maks 50).');
            $this->redirect('akun');
        }
        if (!in_array($role, DAFTAR_ROLE, true)) {
            $role = 'staff';
        }

        $model = new User();
        if ($model->usernameDipakai($username)) {
            setFlash('error', 'Username "' . e($username) . '" sudah dipakai akun lain.');
            $this->redirect('akun');
        }

        $model->create($nama, $username, password_hash($password, PASSWORD_DEFAULT), $role);

        setFlash('success', 'Akun "' . e($nama) . '" berhasil dibuat sebagai ' . roleLabel($role) . '.');
        logAktivitas('akun_tambah', 'Membuat akun ' . $username . ' (' . $role . ')');
        $this->redirect('akun');
    }

    /** Ubah nama, username, role, status aktif, dan (opsional) password baru. */
    public function ubah(int $id): void
    {
        $this->requireHak('akun');
        $this->wajibPost();

        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role     = (string)($_POST['role'] ?? '');
        $aktif    = !empty($_POST['aktif']);
        $password = (string)($_POST['password'] ?? '');

        if ($nama === '' || $username === '') {
            setFlash('error', 'Nama dan username tidak boleh kosong.');
            $this->redirect('akun');
        }
        if (!in_array($role, DAFTAR_ROLE, true)) {
            setFlash('error', 'Role tidak dikenal.');
            $this->redirect('akun');
        }
        if ($password !== '' && strlen($password) < minLengthPassword()) {
            setFlash('error', 'Password baru minimal ' . minLengthPassword() . ' karakter.');
            $this->redirect('akun');
        }

        $model = new User();
        $target = $model->find($id);
        if (!$target) {
            setFlash('error', 'Akun tidak ditemukan.');
            $this->redirect('akun');
        }
        if ($model->usernameDipakai($username, $id)) {
            setFlash('error', 'Username "' . e($username) . '" sudah dipakai akun lain.');
            $this->redirect('akun');
        }

        /* Perlindungan: akun sendiri tidak boleh diubah role/aktif
           (mencegah terkunci keluar / akun root terakhir tidak aktif). */
        $saya = (int)($_SESSION['user']['id'] ?? 0);
        if ($id === $saya) {
            $role  = $target['role'];          // role tetap
            $aktif = (int)$target['aktif'] === 1; // aktif tetap
        }

        $model->updateAkun($id, $nama, $username, $role, $aktif);
        if ($password !== '') {
            $model->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
        }

        setFlash('success', 'Akun "' . e($nama) . '" berhasil diperbarui.');
        logAktivitas('akun_ubah', 'Mengubah akun ' . $username . ' -> role: ' . $role
            . ', aktif: ' . ($aktif ? 'ya' : 'tidak')
            . ($password !== '' ? ', password direset' : ''));
        $this->redirect('akun');
    }

    /** Reset password akun (tanpa perlu password lama). */
    public function reset(int $id): void
    {
        $this->requireHak('akun');
        $this->wajibPost();

        $password = (string)($_POST['password'] ?? '');
        if (strlen($password) < minLengthPassword()) {
            setFlash('error', 'Password baru minimal ' . minLengthPassword() . ' karakter.');
            $this->redirect('akun');
        }

        $model = new User();
        $target = $model->find($id);
        if (!$target) {
            setFlash('error', 'Akun tidak ditemukan.');
            $this->redirect('akun');
        }

        $model->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));

        setFlash('success', 'Password akun "' . e($target['username']) . '" berhasil direset.');
        logAktivitas('akun_reset', 'Reset password akun ' . $target['username']);
        $this->redirect('akun');
    }

    /** Hapus akun (dengan perlindungan: bukan diri sendiri & bukan root terakhir). */
    public function hapus(int $id): void
    {
        $this->requireHak('akun');
        $this->wajibPost();

        $model  = new User();
        $target = $model->find($id);
        if (!$target) {
            setFlash('error', 'Akun tidak ditemukan.');
            $this->redirect('akun');
        }

        $saya = (int)($_SESSION['user']['id'] ?? 0);
        if ($id === $saya) {
            setFlash('error', 'Tidak bisa menghapus akun yang sedang dipakai (diri sendiri).');
            $this->redirect('akun');
        }

        if ($target['role'] === 'root' && $model->countRole('root') <= 1) {
            setFlash('error', 'Tidak bisa menghapus root terakhir. Buat root lain dulu sebelum menghapus akun ini.');
            $this->redirect('akun');
        }

        $model->hapus($id);

        setFlash('success', 'Akun "' . e($target['username']) . '" berhasil dihapus.');
        logAktivitas('akun_hapus', 'Menghapus akun ' . $target['username'] . ' (' . $target['role'] . ')');
        $this->redirect('akun');
    }

    /**
     * Setujui permintaan akun toko -> buat akun manager (kepala toko)
     * dengan kredensial yang didaftarkan pemohon; langsung aktif.
     *
     * Seluruh proses dibungkus satu transaksi database: pembuatan akun dan
     * penandaan permintaan "disetujui" harus berhasil berdua. Tanpa itu, bila
     * langkah kedua gagal, sudah ada akun manager aktif sementara permintaannya
     * masih berstatus menunggu sehingga bisa disetujui dua kali.
     */
    public function setujuiPermintaan(int $id): void
    {
        $this->requireHak('akun');
        $this->wajibPost();

        $modelPerm = new PermintaanAkun();
        $p = $modelPerm->find($id);
        if (!$p || $p['status'] !== 'menunggu') {
            setFlash('error', 'Permintaan tidak ditemukan atau sudah diproses.');
            $this->redirect('akun', ['tab' => 'permintaan']);
        }

        $modelUser = new User();
        if ($modelUser->usernameDipakai($p['username'])) {
            setFlash('error', 'Username "' . e($p['username']) . '" sudah dipakai akun lain. Tolak '
                . 'permintaan ini dan minta pemohon mengajukan ulang dengan username lain.');
            $this->redirect('akun', ['tab' => 'permintaan']);
        }

        $db      = Database::conn();
        $userId  = 0;
        $berhasil = false;

        try {
            $db->beginTransaction();

            $userId = $modelUser->create(
                $p['nama_pemohon'],
                $p['username'],
                $p['password_hash'],
                'manager'
            );

            $modelPerm->setujui($id, (int)$_SESSION['user']['id']);

            $db->commit();
            $berhasil = true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setFlash('error', 'Gagal menyetujui permintaan: ' . $e->getMessage()
                . ' Tidak ada akun yang dibuat, silakan coba lagi.');
            $this->redirect('akun/permintaan');
        }

        if (!$berhasil) {
            setFlash('error', 'Permintaan tidak berhasil disetujui.');
            $this->redirect('akun/permintaan');
        }

        setFlash('success', 'Permintaan "' . e($p['nama_toko']) . '" disetujui. Akun manager "'
            . e($p['username']) . '" dibuat dan siap login.');
        logAktivitas('permintaan_setujui', 'Menyetujui akun toko ' . $p['nama_toko']
            . ' -> akun manager ' . $p['username'] . ' (id ' . $userId . ')');
        notifKirimKe([$userId], 'info', 'Permintaan Akun Disetujui',
            'Akun toko ' . $p['nama_toko'] . ' disetujui. Silakan login dengan username yang didaftarkan.', 'login');
        $this->redirect('akun/permintaan');
    }

    /** Tolak permintaan akun toko (dengan catatan alasan). */
    public function tolakPermintaan(int $id): void
    {
        $this->requireHak('akun');
        $this->wajibPost();

        $modelPerm = new PermintaanAkun();
        $p = $modelPerm->find($id);
        if (!$p || $p['status'] !== 'menunggu') {
            setFlash('error', 'Permintaan tidak ditemukan atau sudah diproses.');
            $this->redirect('akun', ['tab' => 'permintaan']);
        }

        $catatan = trim((string)($_POST['catatan'] ?? ''));
        if (mb_strlen($catatan) > 255) {
            $catatan = mb_substr($catatan, 0, 255);
        }

        $modelPerm->tolak($id, (int)$_SESSION['user']['id'], $catatan);

        setFlash('success', 'Permintaan "' . e($p['nama_toko']) . '" ditolak.');
        logAktivitas('permintaan_tolak', 'Menolak akun toko ' . $p['nama_toko']
            . ' (' . $p['username'] . ')' . ($catatan !== '' ? ' -> ' . $catatan : ''));

        /* Pemohon belum punya akun, jadi tidak ada notifikasi in-app;
           ia dapat melihat status & alasan lewat halaman publik Cek Status. */
        $this->redirect('akun/permintaan');
    }
}
