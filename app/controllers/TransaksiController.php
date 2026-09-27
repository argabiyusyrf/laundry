<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Pelanggan;
use App\Models\Paket;
use App\Models\Transaksi;
use App\Models\User;

/**
 * Alur transaksi/nota: daftar, buat baru, detail, cetak, status, pembayaran, hapus.
 */
class TransaksiController extends Controller
{
    public function index(): void
    {
        $this->requireHak('transaksi');

        $q      = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';

        $this->view('transaksi/index', [
            'pageTitle' => 'Data Transaksi',
            'active'    => 'transaksi',
            'list'      => (new Transaksi())->all($q, $status),
            'q'         => $q,
            'sStatus'   => $status,
            'bolehEdit' => cekHak('transaksi_edit'),
            'bolehHapus'=> cekHak('transaksi_hapus'),
        ]);
    }

    public function baru(): void
    {
        $this->requireHak('transaksi');

        $this->view('transaksi/baru', [
            'pageTitle'    => 'Transaksi Baru',
            'active'       => 'transaksi_baru',
            'pelangganList'=> (new Pelanggan())->all(),
            'paketList'    => (new Paket())->all(),
        ]);
    }

    public function simpan(): void
    {
        $this->requireHak('transaksi');
        $this->wajibPost();

        try {
            $id = (new Transaksi())->simpan($_POST);
        } catch (\RuntimeException $e) {
            setFlash('error', $e->getMessage());
            $this->redirect('transaksi/baru');
        } catch (\Throwable $e) {
            setFlash('error', 'Gagal menyimpan transaksi: ' . $e->getMessage());
            $this->redirect('transaksi/baru');
        }

        $noNota = (new Transaksi())->find($id)['no_nota'] ?? '';
        setFlash('success', 'Transaksi ' . $noNota . ' berhasil disimpan.');
        logAktivitas('transaksi_tambah', 'Membuat nota ' . $noNota . ' untuk ' . ((new Transaksi())->find($id)['pelanggan'] ?? '-'));

        $tBaru = (new Transaksi())->find($id);
        notifKirimKe(
            array_diff((new User())->idPemilik(), [(int)($_SESSION['user']['id'] ?? 0)]),
            'transaksi_baru',
            'Transaksi Baru',
            'Nota ' . ($tBaru['no_nota'] ?? $noNota) . ' untuk ' . ($tBaru['pelanggan'] ?? '-') . ' diterima.',
            'transaksi/detail/' . $id
        );
        $this->redirect('transaksi/detail/' . $id);
    }

    public function edit(int $id): void
    {
        $this->requireHak('transaksi_edit');

        $t = (new Transaksi())->find($id);
        if (!$t) {
            setFlash('error', 'Transaksi tidak ditemukan.');
            $this->redirect('transaksi');
            return;
        }

        $this->view('transaksi/baru', [
            'pageTitle'    => 'Edit Transaksi',
            'active'       => 'transaksi_baru',
            'pelangganList'=> (new Pelanggan())->all(),
            'paketList'    => (new Paket())->all(),
            'editTransaksi'=> $t,
            'editDetail'   => (new Transaksi())->detailItems($id),
        ]);
    }

    public function ubah(int $id): void
    {
        $this->requireHak('transaksi_edit');
        $this->wajibPost();

        try {
            (new Transaksi())->simpan($_POST, $id);
        } catch (\RuntimeException $e) {
            setFlash('error', $e->getMessage());
            $this->redirect('transaksi/edit/' . $id);
            return;
        } catch (\Throwable $e) {
            setFlash('error', 'Gagal memperbarui transaksi: ' . $e->getMessage());
            $this->redirect('transaksi/edit/' . $id);
            return;
        }

        $noNota = (new Transaksi())->find($id)['no_nota'] ?? '';
        setFlash('success', 'Transaksi ' . $noNota . ' berhasil diperbarui.');
        logAktivitas('transaksi_ubah', 'Mengubah nota ' . $noNota);
        notifKirimKe(
            array_diff((new User())->idPemilik(), [(int)($_SESSION['user']['id'] ?? 0)]),
            'info',
            'Transaksi Diperbarui',
            'Data nota ' . $noNota . ' berhasil diperbarui.',
            'transaksi/detail/' . $id
        );
        $this->redirect('transaksi/detail/' . $id);
    }

    public function detail(int $id): void
    {
        $this->requireHak('transaksi');

        $t = (new Transaksi())->find($id);
        if (!$t) {
            setFlash('error', 'Transaksi tidak ditemukan.');
            $this->redirect('transaksi');
        }

        $detail = (new Transaksi())->detailItems($id);

        $idx    = array_search($t['status'], STATUS_LAYANAN, true);
        $next   = ($idx !== false && isset(STATUS_LAYANAN[$idx + 1])) ? STATUS_LAYANAN[$idx + 1] : null;
        $sisa   = (float)$t['total_harga'] - (float)$t['uang_muka'];

        $this->view('transaksi/detail', [
            'pageTitle' => 'Detail Transaksi',
            'active'    => 'transaksi',
            't'         => $t,
            'detail'    => $detail,
            'idx'       => $idx === false ? -1 : $idx,
            'next'      => $next,
            'sisa'      => $sisa,
            'bolehEdit' => cekHak('transaksi_edit'),
            'bolehHapus'=> cekHak('transaksi_hapus'),
        ]);
    }

    public function cetak(int $id): void
    {
        $this->requireHak('transaksi');

        $t = (new Transaksi())->find($id);
        if (!$t) {
            /* Pakai penampil error yang sama dengan halaman lain, supaya nota yang
               tidak ditemukan ikut tercatat di log dan tampil dalam format baku. */
            setFlash('error', 'Transaksi tidak ditemukan.');
            $this->redirect('transaksi');
        }

        $this->view('transaksi/cetak', [
            't'      => $t,
            'detail' => (new Transaksi())->detailItems($id),
            'sisa'   => (float)$t['total_harga'] - (float)$t['uang_muka'],
        ]);
    }

    public function status(int $id): void
    {
        $this->requireHak('transaksi');
        $this->wajibPost();

        $status = $_POST['status'] ?? '';
        $ke     = $_GET['ke'] ?? 'transaksi';

        if (!in_array($status, STATUS_LAYANAN, true)) {
            setFlash('error', 'Status tidak valid.');
        } elseif (!(new Transaksi())->updateStatus($id, $status)) {
            setFlash('error', 'Transaksi tidak ditemukan, status tidak diubah.');
        } else {
            setFlash('success', 'Status transaksi diubah menjadi "' . STATUS_LABEL[$status] . '".');

            $t = (new Transaksi())->find($id);
            $noNota = $t['no_nota'] ?? '';
            logAktivitas('transaksi_status', 'Ubah status nota ' . $noNota . ' -> ' . STATUS_LABEL[$status]);
            notifKirimKe(
                array_diff((new User())->idPemilik(), [(int)($_SESSION['user']['id'] ?? 0)]),
                'status',
                $status === 'siap_diambil' ? 'Cucian Siap Diambil' : 'Status Diperbarui',
                'Nota ' . $noNota . ' sekarang "' . STATUS_LABEL[$status] . '".',
                'transaksi/detail/' . $id
            );
        }

        $this->redirectKembali($ke === 'detail' ? 'transaksi/detail/' . $id : 'transaksi');
    }

    public function bayar(int $id): void
    {
        $this->requireHak('transaksi');
        $this->wajibPost();

        $uangMuka = (float)($_POST['uang_muka'] ?? 0);
        $sisa     = (new Transaksi())->updatePembayaran($id, $uangMuka);

        if ($sisa === null) {
            setFlash('error', 'Transaksi tidak ditemukan.');
            $this->redirect('transaksi');
        }

        setFlash('success', $sisa <= 0
            ? 'Pembayaran diterima, transaksi dinyatakan LUNAS.'
            : 'Uang muka diperbarui. Sisa: ' . rupiah($sisa) . '.');

        $tBayar = (new Transaksi())->find($id);
        logAktivitas('transaksi_bayar', 'Pembayaran nota ' . ($tBayar['no_nota'] ?? '-') . ': ' .
            ($sisa <= 0 ? 'LUNAS' : 'uang muka sisa ' . rupiah($sisa)));
        notifKirimKe(
            array_diff((new User())->idPemilik(), [(int)($_SESSION['user']['id'] ?? 0)]),
            'pembayaran',
            $sisa <= 0 ? 'Pembayaran Lunas' : 'Uang Muka Diperbarui',
            'Nota ' . ($tBayar['no_nota'] ?? '-') . ($sisa <= 0 ? ' dinyatakan LUNAS.' : ' menerima uang muka. Sisa ' . rupiah($sisa) . '.'),
            'transaksi/detail/' . $id
        );

        $this->redirect('transaksi/detail/' . $id);
    }

    public function hapus(int $id): void
    {
        $this->requireHak('transaksi_hapus');
        $this->wajibPost();

        $noNota = (new Transaksi())->find($id)['no_nota'] ?? '';
        (new Transaksi())->hapus($id);
        setFlash('success', 'Transaksi berhasil dihapus.');
        logAktivitas('transaksi_hapus', 'Menghapus nota ' . $noNota);
        $this->redirectKembali('transaksi');
    }
}
