<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Notifikasi;
use App\Models\User;

/**
 * Notifikasi in-app: halaman penuh + endpoint JSON untuk polling realtime
 * + kirim notifikasi bertarget (root -> toko/staff, manager -> staff).
 *
 * Route:
 *   index.php?r=notifikasi            -> halaman penuh
 *   index.php?r=notifikasi/data       -> JSON { belum: N, items: [...] }
 *   index.php?r=notifikasi/kirim      -> (GET) form / (POST) kirim notifikasi
 *   index.php?r=notifikasi/baca/ID    -> tandai dibaca + ke tautan notifikasi
 *   index.php?r=notifikasi/baca-semua -> tandai semua dibaca
 *   index.php?r=notifikasi/edit/ID    -> (GET) form edit / (POST) simpan judul & pesan
 *   index.php?r=notifikasi/hapus/ID   -> (POST) hapus notifikasi
 */
class NotifikasiController extends Controller
{
    /** Halaman penuh daftar notifikasi (broadcast + milik user). */
    public function index(): void
    {
        $this->requireHak('notifikasi');

        $model = new Notifikasi();
        $ku    = (int)($_SESSION['user']['id'] ?? 0);
        $list  = $model->terbaru(200, $ku);

        foreach ($list as &$n) {
            $n['bisaKelola'] = $this->bolehKelola($n);
        }
        unset($n);

        $this->view('notifikasi/index', [
            'pageTitle' => 'Notifikasi',
            'active'    => 'notifikasi',
            'list'      => $list,
            'belum'     => $model->belumDibaca($ku),
        ]);
    }

    /** Form edit judul/pesan notifikasi (root, atau pengirim notifikasi tsb). */
    /** Halaman form ubah notifikasi (hanya GET; penyimpanan lewat ubah()). */
    public function edit(int $id): void
    {
        $this->requireHak('notifikasi');

        $n = (new Notifikasi())->find($id);
        if (!$n) {
            setFlash('error', 'Notifikasi tidak ditemukan.');
            $this->redirect('notifikasi');
        }
        if (!$this->bolehKelola($n)) {
            setFlash('error', 'Anda tidak berhak mengubah notifikasi ini.');
            $this->redirect('notifikasi');
        }

        $this->view('notifikasi/edit', [
            'pageTitle' => 'Edit Notifikasi',
            'active'    => 'notifikasi',
            'n'         => $n,
        ]);
    }

    /** Simpan perubahan judul & pesan notifikasi (POST). */
    public function ubah(int $id): void
    {
        $this->requireHak('notifikasi');
        $this->wajibPost();

        $n = (new Notifikasi())->find($id);
        if (!$n) {
            setFlash('error', 'Notifikasi tidak ditemukan.');
            $this->redirect('notifikasi');
        }
        if (!$this->bolehKelola($n)) {
            setFlash('error', 'Anda tidak berhak mengubah notifikasi ini.');
            $this->redirect('notifikasi');
        }

        $judul = trim((string)($_POST['judul'] ?? ''));
        $pesan = trim((string)($_POST['pesan'] ?? ''));

        if ($judul === '' || $pesan === '') {
            setFlash('error', 'Judul dan pesan wajib diisi.');
            $this->redirect('notifikasi/edit/' . $id);
        }
        $judul = mb_substr($judul, 0, 120);
        $pesan = mb_substr($pesan, 0, 255);

        (new Notifikasi())->update($id, $judul, $pesan);
        setFlash('success', 'Notifikasi berhasil diperbarui.');
        logAktivitas('notifikasi_edit', 'Mengubah notifikasi ' . $id . ': ' . $judul);
        $this->redirect('notifikasi');
    }

    /** Hapus notifikasi (root, atau pengirim notifikasi tsb). */
    public function hapus(int $id): void
    {
        $this->requireHak('notifikasi');
        $this->wajibPost();

        $n = (new Notifikasi())->find($id);
        if (!$n) {
            setFlash('error', 'Notifikasi tidak ditemukan.');
            $this->redirect('notifikasi');
        }
        if (!$this->bolehKelola($n)) {
            setFlash('error', 'Anda tidak berhak menghapus notifikasi ini.');
            $this->redirect('notifikasi');
        }

        (new Notifikasi())->hapus($id);
        setFlash('success', 'Notifikasi berhasil dihapus.');
        logAktivitas('notifikasi_hapus', 'Menghapus notifikasi ' . $id . ' (' . $n['judul'] . ')');
        $this->redirect('notifikasi');
    }

    /**
     * Hak kelola notifikasi: root boleh semua; selainnya hanya notifikasi
     * yang dikirim sendiri (kolom pengirim berisi nama & bukan notifikasi otomatis).
     */
    private function bolehKelola(array $n): bool
    {
        $role = (string)($_SESSION['user']['role'] ?? '');
        if ($role === 'root') {
            return true;
        }
        $nama     = (string)($_SESSION['user']['nama'] ?? '');
        $pengirim = (string)($n['pengirim'] ?? '');
        return $pengirim !== '' && $pengirim === $nama;
    }

    /** Endpoint JSON untuk polling topbar (dipanggil app.js). */
    public function data(): void
    {
        $this->requireHak('notifikasi');

        $model = new Notifikasi();
        $ku    = (int)($_SESSION['user']['id'] ?? 0);
        $items = [];
        foreach ($model->terbaru(10, $ku) as $n) {
            $items[] = [
                'id'       => (int)$n['id'],
                'jenis'    => $n['jenis'],
                'ikon'     => icon(notifIkonJenis($n['jenis']), 15),
                'judul'    => $n['judul'],
                'pesan'    => $n['pesan'],
                'pengirim' => $n['pengirim'] ?? null,
                'waktu'    => waktuRelatif($n['created_at']),
                'tautan'   => url('notifikasi/baca/' . (int)$n['id']),
                'dibaca'   => (int)$n['dibaca'],
            ];
        }

        /* Jangan disimpan cache proxy/browser: isinya memuat data transaksi. */
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        echo json_encode([
            'belum' => $model->belumDibaca($ku),
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Form + proses kirim notifikasi (root -> toko/staff, manager -> staff). */
    public function kirim(): void
    {
        $this->requireHak('notifikasi_kirim');

        $ku   = (int)($_SESSION['user']['id'] ?? 0);
        $role = (string)($_SESSION['user']['role'] ?? '');

        /* Daftar penerima yang sah, disesuaikan peran pengirim.
           root -> manager (toko) & staff (+ opsi "semua"); manager -> staff. */
        $modelUser = new User();
        $daftar = [];
        if ($role === 'root') {
            foreach ($modelUser->semua() as $u) {
                if (in_array($u['role'], ['manager', 'staff'], true)) {
                    $daftar[] = $u;
                }
            }
        } else {
            $daftar = $modelUser->daftarKaryawan();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            /* Halaman ini sekaligus form kirim, jadi pemeriksaan POST + token
               cukup dilakukan di cabang ini. GET hanya menampilkan form. */
            $this->wajibPost();

            $penerima = trim((string)($_POST['penerima'] ?? ''));
            $judul    = trim((string)($_POST['judul'] ?? ''));
            $pesan    = trim((string)($_POST['pesan'] ?? ''));
            $kirimKe  = (int)$penerima; // 0 bila "semua" atau bukan angka

            if ($judul === '' || $pesan === '') {
                setFlash('error', 'Judul dan pesan wajib diisi.');
                $this->redirect('notifikasi/kirim');
            }
            if (mb_strlen($judul) > 120) {
                setFlash('error', 'Judul maksimal 120 karakter.');
                $this->redirect('notifikasi/kirim');
            }
            if (mb_strlen($pesan) > 255) {
                setFlash('error', 'Pesan maksimal 255 karakter.');
                $this->redirect('notifikasi/kirim');
            }

            /* Validasi penerima di sisi server (bukan sekadar dari form). */
            $target = null;
            $sah = false;
            if ($penerima === 'semua' && $role === 'root') {
                $sah = true; // root boleh broadcast ke semua pengguna
            } elseif ($kirimKe > 0) {
                $target = $modelUser->find($kirimKe);
                if ($target) {
                    if ($role === 'root' && in_array($target['role'], ['manager', 'staff'], true)) {
                        $sah = true;
                    } elseif ($role !== 'root' && $target['role'] === 'staff') {
                        $sah = true; // manager hanya bisa ke staff
                    }
                }
            }
            if (!$sah) {
                setFlash('error', 'Penerima tidak valid untuk akun Anda.');
                $this->redirect('notifikasi/kirim');
            }

            $namaPengirim = (string)($_SESSION['user']['nama'] ?? '');
            $modelNotif   = new Notifikasi();

            if ($penerima === 'semua') {
                $modelNotif->buat('pesan', $judul, $pesan, '', $namaPengirim);
                logAktivitas('notifikasi_kirim', 'Kirim notifikasi (semua pengguna): ' . $judul);
                setFlash('success', 'Notifikasi terkirim untuk semua pengguna.');
            } else {
                $modelNotif->kirimKe((int)$target['id'], $namaPengirim, $judul, $pesan);
                logAktivitas('notifikasi_kirim', 'Kirim notifikasi ke ' . $target['nama'] . ' (' . roleLabel($target['role']) . '): ' . $judul);
                setFlash('success', 'Notifikasi terkirim ke ' . $target['nama'] . '.');
            }

            $this->redirect('notifikasi/kirim');
        }

        /* Pisahkan menurut jenis penerima untuk dipilih di form. */
        $daftarToko  = [];
        $daftarStaff = [];
        foreach ($daftar as $u) {
            if ($u['role'] === 'manager') {
                $daftarToko[] = $u;
            } elseif ($u['role'] === 'staff') {
                $daftarStaff[] = $u;
            }
        }

        $this->view('notifikasi/kirim', [
            'pageTitle'   => 'Kirim Notifikasi',
            'active'      => 'notifikasi_kirim',
            'daftarToko'  => $daftarToko,
            'daftarStaff' => $daftarStaff,
            'role'        => $role,
        ]);
    }

    /** Tandai satu notifikasi dibaca lalu buka tautannya. */
    public function baca(int $id): void
    {
        $this->requireHak('notifikasi');

        $model = new Notifikasi();
        $ku    = (int)($_SESSION['user']['id'] ?? 0);
        $n = $model->find($id);
        if ($n) {
            $model->tandaiBaca($id, $ku);
        }

        /* Hanya buka tautan milik pengguna ini (notifikasi broadcast boleh
           dibaca semua orang). Tautan diambil dari database, jadi tetap
           disanitasi agar tidak jadi open redirect. */
        $bolehBuka = $n && ((int)($n['user_id'] ?? 0) === 0 || (int)$n['user_id'] === $ku);
        $tujuan    = $bolehBuka ? bersihkanRoute((string)$n['tautan']) : '';
        $this->redirect($tujuan !== '' ? $tujuan : 'notifikasi');
    }

    /** Tandai semua notifikasi sudah dibaca, kembali ke halaman asal. */
    public function bacaSemua(): void
    {
        $this->requireHak('notifikasi');
        $this->wajibPost();

        (new Notifikasi())->bacaSemua((int)($_SESSION['user']['id'] ?? 0));

        setFlash('success', 'Semua notifikasi ditandai sudah dibaca.');
        $this->redirectKembali('notifikasi');
    }
}
