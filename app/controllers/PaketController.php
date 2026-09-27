<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Paket;

/**
 * CRUD Paket Layanan.
 */
class PaketController extends Controller
{
    public function index(): void
    {
        $this->requireHak('paket');

        $bolehKelola = cekHak('paket_kelola');

        $edit = $bolehKelola ? (int)($_GET['edit'] ?? 0) : 0;
        $dataEdit = null;
        if ($edit) {
            $dataEdit = (new Paket())->find($edit);
            if (!$dataEdit) {
                $edit = 0;
            }
        }

        $this->view('paket/index', [
            'pageTitle' => 'Paket Layanan',
            'active'    => 'paket',
            'list'      => (new Paket())->all(),
            'edit'      => $edit,
            'dataEdit'  => $dataEdit,
            'bolehKelola' => $bolehKelola,
        ]);
    }

    public function tambah(): void
    {
        $this->requireHak('paket_kelola');
        $this->wajibPost();

        $nama     = trim($_POST['nama'] ?? '');
        $harga    = (float)($_POST['harga_per_kg'] ?? 0);
        $estimasi = (int)($_POST['estimasi_hari'] ?? 0);

        if ($nama === '' || $harga <= 0 || $estimasi <= 0) {
            setFlash('error', 'Nama, harga, dan estimasi hari wajib diisi dengan benar.');
            $this->redirect('paket');
        }

        (new Paket())->create(
            $nama,
            $harga,
            $estimasi,
            trim($_POST['keterangan'] ?? '')
        );

        setFlash('success', 'Paket "' . $nama . '" berhasil ditambahkan.');
        logAktivitas('paket_tambah', 'Menambah paket "' . $nama . '" Rp' . (int)$harga . '/kg');
        $this->redirect('paket');
    }

    public function ubah(int $id): void
    {
        $this->requireHak('paket_kelola');
        $this->wajibPost();

        $nama     = trim($_POST['nama'] ?? '');
        $harga    = (float)($_POST['harga_per_kg'] ?? 0);
        $estimasi = (int)($_POST['estimasi_hari'] ?? 0);

        if ($nama === '' || $harga <= 0 || $estimasi <= 0) {
            setFlash('error', 'Nama, harga, dan estimasi hari wajib diisi dengan benar.');
            $this->redirect('paket', ['edit' => $id]);
        }

        (new Paket())->update(
            $id,
            $nama,
            $harga,
            $estimasi,
            trim($_POST['keterangan'] ?? '')
        );

        setFlash('success', 'Paket layanan berhasil diperbarui.');
        logAktivitas('paket_ubah', 'Mengubah paket #' . $id . ' "' . $nama . '"');
        $this->redirect('paket');
    }

    public function hapus(int $id): void
    {
        $this->requireHak('paket_kelola');
        $this->wajibPost();

        $model = new Paket();
        $jml   = $model->jumlahDipakai($id);

        if ($jml > 0) {
            setFlash('error', 'Paket tidak bisa dihapus karena sudah dipakai pada ' . $jml . ' transaksi.');
        } else {
            $model->delete($id);
            setFlash('success', 'Paket layanan berhasil dihapus.');
            logAktivitas('paket_hapus', 'Menghapus paket #' . $id);
        }

        $this->redirect('paket');
    }

    /** Halaman detail paket: info + riwayat pemakaian. */
    public function detail(int $id): void
    {
        $this->requireHak('paket');

        $k = (new Paket())->find($id);
        if (!$k) {
            setFlash('error', 'Paket tidak ditemukan.');
            $this->redirect('paket');
            return;
        }

        $this->view('paket/detail', [
            'pageTitle' => 'Detail Paket Layanan',
            'active'    => 'paket',
            'k'         => $k,
            'list'      => (new Paket())->riwayat($id),
        ]);
    }
}
