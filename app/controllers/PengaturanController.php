<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Pengaturan;

/**
 * Halaman Pengaturan: identitas usaha, logo, & pesan struk (ala Majoo).
 */
class PengaturanController extends Controller
{
    private const LOGO_DIR  = '/asset/logo';
    private const LOGO_MAX  = 1048576; // 1 MB
    private const LOGO_MIME = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /** Tab halaman pengaturan (panel yang aktif ditampilkan di form). */
    private const TAB = ['umum', 'struk', 'keuangan', 'keamanan', 'notifikasi'];

    public function index(): void
    {
        $this->requireHak('pengaturan');

        $model = new Pengaturan();
        $data  = $model->semua();

        // Jamin kunci default selalu tampil di form
        foreach (array_keys($model->identitasDefault()) as $k) {
            if (!isset($data[$k])) {
                $data[$k] = '';
            }
        }
        $data['logo'] = $data['logo'] ?? '';

        $tab = (string)($_GET['tab'] ?? 'umum');
        if (!in_array($tab, self::TAB, true)) {
            $tab = 'umum';
        }

        $this->view('pengaturan/index', [
            'pageTitle' => 'Pengaturan',
            'active'    => 'pengaturan',
            's'         => $data,
            'tab'       => $tab,
            'logoUrl'   => $data['logo'] ? self::logoUrl($data['logo']) : '',
        ]);
    }

    /**
     * Simpan pengaturan + logo (bila diunggah).
     *
     * Catatan keamanan:
     * - Hanya POST yang diproses. Tanpa ini, ?r=pengaturan/simpan via GET akan
     *   menimpa semua pengaturan teks dengan kosong dan memaksa angka ke minimum.
     * - Kunci yang tidak ikut terkirim tidak ditimpa, sehingga nilai lama aman.
     * - Kunci keamanan akun (panjang password, batas login, masa berlaku sesi)
     *   hanya boleh diubah oleh root, bukan oleh manager biasa.
     */
    public function simpan(): void
    {
        $this->requireHak('pengaturan');
        $this->wajibPost();

        $model   = new Pengaturan();
        $payload = [];

        $bolehKelolaKeamanan = cekHak('pengaturan_keamanan');
        $kunciKeamanan = ['min_password', 'sesi_kedaluwarsa_menit', 'batas_login_gagal'];
        // Kunci yang boleh berdesimal (persen), sisanya bilangan bulat.
        $kunciDesimal = ['pajak_persen', 'diskon_maks_persen', 'dp_minimal_persen'];

        foreach (array_keys($model->identitasDefault()) as $k) {
            if ($k === 'notif_suara') {
                continue; // checkbox, ditangani terpisah
            }
            if (!$bolehKelolaKeamanan && in_array($k, $kunciKeamanan, true)) {
                continue; // hanya root yang boleh mengubahnya
            }
            if (array_key_exists($k, $_POST)) {
                $payload[$k] = (string)$_POST[$k];
            }
        }

        foreach ($model->validasiAngka() as $k => [$min, $max]) {
            if (!array_key_exists($k, $payload)) {
                continue;
            }
            $nilai = in_array($k, $kunciDesimal, true)
                ? (float)str_replace(',', '.', $payload[$k])
                : (int)$payload[$k];
            if ($nilai < $min) {
                $nilai = $min;
            }
            if ($nilai > $max) {
                $nilai = $max;
            }
            $payload[$k] = in_array($k, $kunciDesimal, true)
                ? rtrim(rtrim(number_format((float)$nilai, 2, '.', ''), '0'), '.')
                : (string)(int)$nilai;
        }

        /* Perataan di bawah hanya untuk kunci yang benar-benar ikut terkirim.
           Form Pengaturan hanya mengirim field tab yang sedang aktif, jadi kunci
           dari tab lain tidak boleh dibaca atau ditimpanya nilai bawaan. */
        if (array_key_exists('prefix_nota', $payload)) {
            // Prefix nota: huruf besar, hanya huruf/angka, maks 8 karakter
            $prefix = strtoupper(trim($payload['prefix_nota']));
            $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix);
            $payload['prefix_nota'] = ($prefix === '' ? 'LND' : mb_substr($prefix, 0, 8));
        }

        if (array_key_exists('satuan_berat', $payload)) {
            // Satuan berat dibatasi panjangnya
            $payload['satuan_berat'] = mb_substr(trim($payload['satuan_berat']), 0, 10) ?: 'kg';
        }

        if (array_key_exists('mata_uang', $payload)) {
            // Mata uang dibatasi panjangnya
            $payload['mata_uang'] = mb_substr(trim($payload['mata_uang']), 0, 6) ?: 'Rp';
        }

        /* Suara notifikasi: checkbox tidak terkirim saat tidak dicentang, jadi
           nilainya hanya boleh ditulis ulang bila tab Notifikasi yang aktif. */
        $tabKirim = (string)($_POST['tab'] ?? 'umum');
        if (!in_array($tabKirim, self::TAB, true)) {
            $tabKirim = 'umum';
        }
        if ($tabKirim === 'notifikasi') {
            $payload['notif_suara'] = empty($_POST['notif_suara']) ? '0' : '1';
        }

        // Hapus logo bila diminta
        if (!empty($_POST['hapus_logo'])) {
            $this->hapusBerkasLogo($model->nilai('logo'));
            $payload['logo'] = '';
        }

        // Unggah logo baru.
        //
        // Setiap kegagalan di blok ini SELALU disertai pesan yang menyebut
        // sebabnya. Sebelumnya, bila $_FILES kosong atau error-nya bukan
        // UPLOAD_ERR_OK, seluruh blok dilewati tanpa pesan apa pun sehingga
        // pengguna hanya melihat "Pengaturan berhasil disimpan" padahal
        // logonya tidak pernah tersimpan sama sekali.
        $alasanGagal = null;
        $file        = $_FILES['logo'] ?? null;

        if (is_array($file)) {
            $kodeGalat = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($kodeGalat === UPLOAD_ERR_OK && (int)($file['size'] ?? 0) > 0) {
                $nama = $this->simpanBerkasLogo($file);
                if ($nama === null) {
                    // Berkas ditolak: simpan teks lain, beri tahu sebabnya.
                    $alasanGagal = $this->alasanLogoDitolak($file);
                } else {
                    $this->hapusBerkasLogo($model->nilai('logo'));
                    $payload['logo'] = $nama;
                }
            } elseif ($kodeGalat !== UPLOAD_ERR_NO_FILE) {
                // Galat bawaan PHP, mis. berkas melebihi upload_max_filesize.
                $alasanGagal = $this->alasanGalatUnggah($kodeGalat);
            }
        }

        $model->simpan($payload);

        if ($alasanGagal !== null) {
            setFlash('error', 'Pengaturan lain disimpan, tetapi logo gagal diunggah. ' . $alasanGagal);
            logAktivitas('pengaturan_logo', 'Unggah logo gagal: ' . $alasanGagal);
        } else {
            setFlash('success', 'Pengaturan berhasil disimpan.');
        }
        logAktivitas('pengaturan_simpan', 'Menyimpan pengaturan usaha' .
            (!empty($_POST['hapus_logo']) ? ' + hapus logo' : ''));

        // Kembali ke tab yang sama setelah disimpan
        $this->redirect('pengaturan', $tabKirim === 'umum' ? [] : ['tab' => $tabKirim]);
    }

    /** Hapus logo dari penyimpanan & database. */
    public function hapusLogo(): void
    {
        $this->requireHak('pengaturan');
        $this->wajibPost();

        $model = new Pengaturan();
        $this->hapusBerkasLogo($model->nilai('logo'));
        $model->simpan(['logo' => '']);

        setFlash('success', 'Logo toko berhasil dihapus.');
        logAktivitas('pengaturan_logo', 'Menghapus logo toko');
        $this->redirect('pengaturan');
    }

    /**
     * Simpan berkas logo ke folder asset/logo, kembalikan nama berkas.
     *
     * Isi berkas SELALU diperiksa dengan getimagesize(), apa pun nilai
     * Content-Type yang dikirim browser. Bila hanya mempercayai Content-Type,
     * berkas polyglot (gambar + HTML/JavaScript) bisa lolos dan tersimpan
     * sebagai .png yang bisa dieksekusi lewat <img src="..."> di browser.
     */
    private function simpanBerkasLogo(array $file): ?string
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }
        if ((int)($file['size'] ?? 0) > self::LOGO_MAX) {
            return null;
        }

        $info = @getimagesize($file['tmp_name']);
        if (!$info || empty($info['mime']) || !isset(self::LOGO_MIME[$info['mime']])) {
            return null;
        }
        $mime = (string)$info['mime'];

        $dir = BASE_PATH . self::LOGO_DIR;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return null;
        }

        $ext  = self::LOGO_MIME[$mime];
        $nama = 'logo-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $nama)) {
            return null;
        }
        return $nama;
    }

    /** Hapus berkas logo lama dari disk bila ada. */
    /**
     * Jelaskan kenapa simpanBerkasLogo() menolak sebuah berkas.
     *
     * Dipanggil hanya setelah simpanBerkasLogo() mengembalikan null, jadi
     * tujuannya mengganti satu pesan umum dengan keterangan yang bisa
     * langsung ditindaklanjuti pengguna.
     */
    private function alasanLogoDitolak(array $file): string
    {
        $ukuran = (int)($file['size'] ?? 0);
        if ($ukuran > self::LOGO_MAX) {
            return sprintf(
                'Ukuran berkas %.2f MB melewati batas 1 MB.',
                $ukuran / 1048576
            );
        }
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return 'Berkas tidak dikenali sebagai hasil unggahan yang sah.';
        }
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return 'Isi berkas bukan gambar yang dapat dibaca.';
        }
        $mime = (string)($info['mime'] ?? '');
        if (!isset(self::LOGO_MIME[$mime])) {
            return sprintf(
                'Format %s tidak didukung. Gunakan PNG, JPG, WebP, atau GIF.',
                $mime !== '' ? $mime : 'tidak dikenal'
            );
        }
        $dir = BASE_PATH . self::LOGO_DIR;
        if (!is_dir($dir)) {
            return sprintf('Folder penyimpanan %s tidak dapat dibuat di server.', self::LOGO_DIR);
        }
        return sprintf('Server tidak dapat menyimpan berkas ke folder %s.', self::LOGO_DIR);
    }

    /**
     * Terjemahkan kode galat unggahan bawaan PHP menjadi kalimat yang
     * bisa dibaca pengguna.
     */
    private function alasanGalatUnggah(int $kode): string
    {
        switch ($kode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Ukuran berkas melewati batas 1 MB.';
            case UPLOAD_ERR_PARTIAL:
                return 'Berkas hanya terunggah sebagian. Coba lagi dengan koneksi yang lebih stabil.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Server tidak memiliki folder berkas sementara.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Server gagal menulis berkas ke penyimpanan.';
            case UPLOAD_ERR_EXTENSION:
                return 'Unggahan dihentikan oleh ekstensi PHP pada server.';
            default:
                return sprintf('PHP melaporkan galat unggahan dengan kode %d.', $kode);
        }
    }
    private function hapusBerkasLogo(string $nama): void
    {
        if ($nama === '') {
            return;
        }
        $berkas = BASE_PATH . self::LOGO_DIR . '/' . basename($nama);
        if (is_file($berkas)) {
            @unlink($berkas);
        }
    }

    /** URL lengkap ke berkas logo. */
    public static function logoUrl(string $nama): string
    {
        return base_path() . self::LOGO_DIR . '/' . rawurlencode($nama);
    }
}
