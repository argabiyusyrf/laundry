<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Pelanggan;
use App\Models\User;

/**
 * CRUD Pelanggan.
 */
class PelangganController extends Controller
{
    public function index(): void
    {
        $this->requireHak('pelanggan');

        $q      = trim($_GET['q'] ?? '');
        $bolehEdit  = cekHak('pelanggan_edit');
        $bolehHapus = cekHak('pelanggan_hapus');

        $edit = $bolehEdit ? (int)($_GET['edit'] ?? 0) : 0;
        $dataEdit = null;
        if ($edit) {
            $dataEdit = (new Pelanggan())->find($edit);
            if (!$dataEdit) {
                $edit = 0;
            }
        }

        $this->view('pelanggan/index', [
            'pageTitle' => 'Data Pelanggan',
            'active'    => 'pelanggan',
            'list'      => (new Pelanggan())->all($q),
            'q'         => $q,
            'edit'      => $edit,
            'dataEdit'  => $dataEdit,
            'bolehEdit' => $bolehEdit,
            'bolehHapus'=> $bolehHapus,
        ]);
    }

    public function tambah(): void
    {
        $this->requireHak('pelanggan');
        $this->wajibPost();

        $nama = trim($_POST['nama'] ?? '');

        if ($nama === '') {
            setFlash('error', 'Nama pelanggan wajib diisi.');
            $this->redirectKembali('pelanggan');
        }

        (new Pelanggan())->create(
            $nama,
            trim($_POST['telepon'] ?? ''),
            trim($_POST['alamat'] ?? '')
        );

        setFlash('success', 'Pelanggan "' . $nama . '" berhasil ditambahkan.');
        notifKirimKe(
            array_diff((new User())->idPemilik(), [(int)($_SESSION['user']['id'] ?? 0)]),
            'pelanggan_baru',
            'Pelanggan Baru',
            'Pelanggan "' . $nama . '" berhasil didaftarkan.',
            'pelanggan'
        );
        logAktivitas('pelanggan_tambah', 'Mendaftarkan pelanggan "' . $nama . '"');
        $this->redirectKembali('pelanggan');
    }

    public function ubah(int $id): void
    {
        $this->requireHak('pelanggan_edit');
        $this->wajibPost();

        $nama = trim($_POST['nama'] ?? '');

        if ($nama === '') {
            setFlash('error', 'Nama pelanggan wajib diisi.');
            $this->redirectKembali('pelanggan', ['edit' => $id]);
        }

        (new Pelanggan())->update(
            $id,
            $nama,
            trim($_POST['telepon'] ?? ''),
            trim($_POST['alamat'] ?? '')
        );

        setFlash('success', 'Data pelanggan berhasil diperbarui.');
        logAktivitas('pelanggan_ubah', 'Mengubah pelanggan #' . $id . ' "' . $nama . '"');
        $this->redirectKembali('pelanggan');
    }

    public function hapus(int $id): void
    {
        $this->requireHak('pelanggan_hapus');
        $this->wajibPost();

        $model = new Pelanggan();
        $jml   = $model->jumlahTransaksi($id);

        if ($jml > 0) {
            setFlash('error', 'Pelanggan tidak bisa dihapus karena memiliki ' . $jml . ' transaksi.');
        } else {
            $model->delete($id);
            setFlash('success', 'Pelanggan berhasil dihapus.');
            logAktivitas('pelanggan_hapus', 'Menghapus pelanggan #' . $id);
        }

        $this->redirectKembali('pelanggan');
    }

    /** Halaman detail pelanggan: profil + riwayat transaksi. */
    public function detail(int $id): void
    {
        $this->requireHak('pelanggan');

        $p = (new Pelanggan())->find($id);
        if (!$p) {
            setFlash('error', 'Pelanggan tidak ditemukan.');
            $this->redirect('pelanggan');
            return;
        }

        $this->view('pelanggan/detail', [
            'pageTitle' => 'Detail Pelanggan',
            'active'    => 'pelanggan',
            'p'         => $p,
            'list'      => (new Pelanggan())->transaksi($id),
            'bolehKelola' => cekHak('pelanggan_edit') || cekHak('pelanggan_hapus'),
        ]);
    }
}
