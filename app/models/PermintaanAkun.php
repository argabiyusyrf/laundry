<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model Permintaan Akun: permintaan pembuatan akun toko (owner -> root).
 *
 * Alur (bukan fitur register — semua butuh persetujuan):
 *   1. Pemohon mengisi formulir di halaman publik ?r=permintaan
 *      (nama toko, pemohon, username, password, kontak).
 *   2. Root menyetujui / menolak lewat menu Akun & Pengguna -> tab Permintaan Toko.
 *   3. Disetujui  -> dibuatkan akun role "manager" (kepala toko), langsung aktif.
 *      Ditolak     -> status ditolak + catatan alasan (pemohon bisa cek via halaman publik).
 * Akun karyawan/staff TIDAK lewat tabel ini: dibuat langsung oleh manager
 * dari menu Karyawan (persetujuan toko = aksi manager itu sendiri).
 */
class PermintaanAkun extends Model
{

    /** Ambil semua permintaan, terbaru dulu (untuk menu root). */
    public function semua(int $limit = 100): array
    {
        $st = $this->db()->prepare('SELECT * FROM permintaan_akun ORDER BY id DESC LIMIT ?');
        $st->execute([$limit]);
        return $st->fetchAll();
    }

    /** Permintaan dengan status tertentu (mis. menunggu). */
    public function byStatus(string $status): array
    {
        $st = $this->db()->prepare('SELECT * FROM permintaan_akun WHERE status = ? ORDER BY id DESC');
        $st->execute([$status]);
        return $st->fetchAll();
    }

    /** Ambil satu permintaan berdasarkan id. */
    public function find(int $id): ?array
    {
        $st = $this->db()->prepare('SELECT * FROM permintaan_akun WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Mencari status terakhir sebuah username (untuk cek persetujuan publik). */
    public function cekStatus(string $username): ?array
    {
        $st = $this->db()->prepare(
            'SELECT id, nama_toko, username, status, catatan, created_at
             FROM permintaan_akun WHERE username = ? ORDER BY id DESC LIMIT 1'
        );
        $st->execute([$username]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Jumlah permintaan yang masih menunggu (badge di menu root). */
    public function jumlahMenunggu(): int
    {
        return (int)$this->db()->query(
            "SELECT COUNT(*) FROM permintaan_akun WHERE status = 'menunggu'"
        )->fetchColumn();
    }

    /** Cek apakah username sudah dipakai permintaan yang masih menunggu. */
    public function usernameMenunggu(string $username): bool
    {
        $st = $this->db()->prepare(
            "SELECT COUNT(*) FROM permintaan_akun
             WHERE username = ? AND status = 'menunggu'"
        );
        $st->execute([$username]);
        return (int)$st->fetchColumn() > 0;
    }

    /**
     * Simpan permintaan baru (status menunggu).
     * Mengembalikan id permintaan.
     */
    public function kirim(
        string $namaToko,
        string $namaPemohon,
        string $username,
        string $hash,
        string $telepon,
        string $alamat
    ): int {
        $st = $this->db()->prepare(
            'INSERT INTO permintaan_akun
               (jenis, nama_toko, nama_pemohon, username, password_hash, telepon, alamat, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute(['toko', $namaToko, $namaPemohon, $username, $hash, $telepon, $alamat, 'menunggu']);
        return (int)$this->db()->lastInsertId();
    }

    /** Setujui permintaan (diproses root): tandai disetujui + catat pemroses. */
    public function setujui(int $id, int $diprosesOleh): void
    {
        $st = $this->db()->prepare(
            'UPDATE permintaan_akun SET status = ?, diproses_oleh = ?, diproses_at = NOW() WHERE id = ?'
        );
        $st->execute(['disetujui', $diprosesOleh, $id]);
    }

    /** Tolak permintaan dengan catatan alasan. */
    public function tolak(int $id, int $diprosesOleh, string $catatan): void
    {
        $st = $this->db()->prepare(
            'UPDATE permintaan_akun
             SET status = ?, catatan = ?, diproses_oleh = ?, diproses_at = NOW() WHERE id = ?'
        );
        $st->execute(['ditolak', $catatan, $diprosesOleh, $id]);
    }
}