<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model Notifikasi: notifikasi in-app (lonceng di topbar).
 *
 * Dua macam:
 *  - Broadcast  : user_id NULL  -> terlihat SEMUA pengguna (dibuat otomatis
 *    saat transaksi/status/pembayaran/pelanggan baru).
 *  - Bertarget  : user_id = id penerima -> hanya terlihat penerima tersebut
 *    (hasil "Kirim Notifikasi" dari root / manager).
 * Field pengirim mencatat nama pengirim notifikasi bertarget/broadcast manual.
 */
class Notifikasi extends Model
{
    /** Daftar notifikasi terbaru; filter per penerima bila userId diberikan. */
    public function terbaru(int $limit = 20, ?int $userId = null): array
    {
        if ($userId === null) {
            $st = $this->db()->prepare('SELECT * FROM notifikasi ORDER BY id DESC LIMIT ?');
            $st->execute([$limit]);
        } else {
            $st = $this->db()->prepare(
                'SELECT * FROM notifikasi WHERE (user_id IS NULL OR user_id = ?) ORDER BY id DESC LIMIT ?'
            );
            $st->execute([$userId, $limit]);
        }
        return $st->fetchAll();
    }

    /** Jumlah notifikasi belum dibaca (broadcast + untuk userId tersebut). */
    public function belumDibaca(?int $userId = null): int
    {
        if ($userId === null) {
            return (int)$this->db()->query('SELECT COUNT(*) FROM notifikasi WHERE dibaca = 0')->fetchColumn();
        }
        $st = $this->db()->prepare('SELECT COUNT(*) FROM notifikasi WHERE dibaca = 0 AND (user_id IS NULL OR user_id = ?)');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }

    /** Cari satu notifikasi berdasarkan id. */
    public function find(int $id): ?array
    {
        $st = $this->db()->prepare('SELECT * FROM notifikasi WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Buat notifikasi broadcast (semua pengguna); pengirim opsional. */
    public function buat(string $jenis, string $judul, string $pesan, string $tautan = '', ?string $pengirim = null): void
    {
        $st = $this->db()->prepare(
            'INSERT INTO notifikasi (jenis, judul, pesan, pengirim, tautan) VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([$jenis, $judul, $pesan, $pengirim, $tautan]);

        $this->pangkas();
    }

    /**
     * Kirim notifikasi bertarget ke satu pengguna (root -> toko/staff, manager -> staff).
     *
     * $tautan diisi dengan route internal aplikasi, mis. "transaksi/detail/12".
     * Nilai ini dipakai saat notifikasi diklik untuk langsung membuka halaman tujuan.
     */
    public function kirimKe(
        int $penerimaId,
        string $pengirim,
        string $judul,
        string $pesan,
        string $jenis = 'pesan',
        string $tautan = ''
    ): void {
        $st = $this->db()->prepare(
            'INSERT INTO notifikasi (jenis, judul, pesan, user_id, pengirim, tautan) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $jenis,
            mb_substr($judul, 0, 120),
            mb_substr($pesan, 0, 255),
            $penerimaId,
            mb_substr($pengirim, 0, 100),
            mb_substr($tautan, 0, 120),
        ]);

        $this->pangkas();
    }

    /**
     * Rapikan riwayat: sisakan 200 notifikasi terbaru, TAPI semua notifikasi
     * yang belum dibaca tetap dipertahankan supaya tidak hilang sebelum dibaca.
     */
    private function pangkas(): void
    {
        $this->db()->exec(
            'DELETE FROM notifikasi WHERE dibaca = 1 AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM notifikasi WHERE dibaca = 1 ORDER BY id DESC LIMIT 200
                ) x
            )'
        );
    }

    /** Tandai satu notifikasi sudah dibaca (hanya milik/broadcast untuk user tsb). */
    public function tandaiBaca(int $id, ?int $userId = null): void
    {
        if ($userId === null) {
            $st = $this->db()->prepare('UPDATE notifikasi SET dibaca = 1 WHERE id = ?');
            $st->execute([$id]);
            return;
        }
        $st = $this->db()->prepare(
            'UPDATE notifikasi SET dibaca = 1 WHERE id = ? AND (user_id IS NULL OR user_id = ?)'
        );
        $st->execute([$id, $userId]);
    }

    /** Tandai semua notifikasi sudah dibaca untuk user tsb (termasuk broadcast). */
    public function bacaSemua(?int $userId = null): void
    {
        if ($userId === null) {
            $this->db()->exec('UPDATE notifikasi SET dibaca = 1 WHERE dibaca = 0');
            return;
        }
        $st = $this->db()->prepare('UPDATE notifikasi SET dibaca = 1 WHERE dibaca = 0 AND (user_id IS NULL OR user_id = ?)');
        $st->execute([$userId]);
    }

    /** Ubah judul/pesan notifikasi (pengelolaan: root / pengirim). */
    public function update(int $id, string $judul, string $pesan): void
    {
        $st = $this->db()->prepare('UPDATE notifikasi SET judul = ?, pesan = ? WHERE id = ?');
        $st->execute([$judul, $pesan, $id]);
    }

    /** Hapus satu notifikasi. */
    public function hapus(int $id): void
    {
        $st = $this->db()->prepare('DELETE FROM notifikasi WHERE id = ?');
        $st->execute([$id]);
    }
}