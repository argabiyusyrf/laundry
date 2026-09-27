<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model Pengaturan aplikasi.
 * Menyimpan identitas usaha & preferensi struk dalam tabel `pengaturan` (kunci/nilai).
 * Tabel dibuat otomatis bila belum ada, jadi tidak perlu instal ulang.
 */
class Pengaturan extends Model
{
    private static bool $tabelSiap = false;

    /** Pastikan tabel pengaturan ada. */
    private function pastikanTabel(): void
    {
        if (self::$tabelSiap) {
            return;
        }
        $this->db()->exec(
            "CREATE TABLE IF NOT EXISTS pengaturan (
                kunci VARCHAR(60) PRIMARY KEY,
                nilai TEXT NOT NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        self::$tabelSiap = true;
    }

    /** Ambil semua pengaturan sebagai array kunci => nilai. */
    public function semua(): array
    {
        $this->pastikanTabel();
        $out = [];
        foreach ($this->db()->query('SELECT kunci, nilai FROM pengaturan') as $r) {
            $out[$r['kunci']] = $r['nilai'];
        }
        return $out;
    }

    /** Ambil satu nilai pengaturan (atau default bila kosong). */
    public function nilai(string $kunci, string $default = ''): string
    {
        $s = $this->semua();
        return isset($s[$kunci]) && $s[$kunci] !== '' ? (string)$s[$kunci] : $default;
    }

    /** Simpan / perbarui satu atau beberapa pengaturan sekaligus. */
    public function simpan(array $data): void
    {
        $this->pastikanTabel();
        $st = $this->db()->prepare(
            'INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)'
        );
        foreach ($data as $kunci => $nilai) {
            $st->execute([$kunci, trim((string)$nilai)]);
        }
    }

    /**
     * Kunci pengaturan teks (ditampilkan di form & disimpan dari input).
     * Kelompok: identitas & kontak, struk & promo, pajak & diskon,
     * format nota, satuan & mata uang, keamanan akun.
     * Catatan: kunci "logo" ditangani terpisah (upload berkas gambar).
     */
    public function identitasDefault(): array
    {
        return [
            // Identitas & kontak
            'nama_usaha'    => APP_NAME,
            'alamat_usaha'  => '',
            'telepon_usaha' => '',
            'whatsapp'      => '',
            'instagram'     => '',
            'email_usaha'   => '',
            'jam_buka'      => '',
            'jam_tutup'     => '',
            // Struk & nota
            'header_struk'  => '',
            'footer_struk'  => 'Barang yang sudah selesai mohon diambil maksimal 7 hari setelah konfirmasi.',
            'promo_struk'   => '',
            'catatan_struk' => '',
            // Pajak & diskon
            'pajak_persen'         => '0',
            'diskon_maks_persen'   => '0',
            'dp_minimal_persen'    => '0',
            // Format nomor nota
            'prefix_nota'   => 'LND',
            // Satuan & mata uang
            'satuan_berat'  => 'kg',
            'mata_uang'     => 'Rp',
            // Keamanan akun
            'min_password'          => '6',
            'sesi_kedaluwarsa_menit'=> '0',
            'batas_login_gagal'     => '5',
            // Notifikasi & suara
            'notif_suara'   => '1',
            'notif_volume'  => '80',
        ];
    }

    /**
     * Kunci yang WAJIB berupa angka valid (untuk validasi form pengaturan).
     * Mengembalikan [kunci => batas bawah, batas atas].
     */
    public function validasiAngka(): array
    {
        return [
            'pajak_persen'          => [0, 100],
            'diskon_maks_persen'    => [0, 100],
            'dp_minimal_persen'     => [0, 100],
            'min_password'          => [4, 64],
            'sesi_kedaluwarsa_menit'=> [0, 1440],
            'batas_login_gagal'     => [1, 50],
            'notif_volume'          => [0, 100],
        ];
    }
}