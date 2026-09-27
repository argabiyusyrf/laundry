<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model Pelanggan.
 */
class Pelanggan extends Model
{
    public function count(): int
    {
        return (int)$this->db()->query('SELECT COUNT(*) FROM pelanggan')->fetchColumn();
    }

    public function all(string $q = ''): array
    {
        $sql = 'SELECT p.*,
                       (SELECT COUNT(*) FROM transaksi t WHERE t.pelanggan_id = p.id) AS jml_trx
                FROM pelanggan p';
        $params = [];
        if ($q !== '') {
            /* Wildcard LIKE di-escape lewat polaLike(), supaya "%" atau "_"
               yang diketik pengguna tidak jadi wildcard yang menyapu tabel. */
            $cari = polaLike($q);
            $sql .= ' WHERE p.nama LIKE ? OR p.telepon LIKE ? OR p.alamat LIKE ?';
            $params = [$cari, $cari, $cari];
        }
        $sql .= ' ORDER BY p.nama ASC';

        $st = $this->db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db()->prepare('SELECT * FROM pelanggan WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function create(string $nama, string $telepon, string $alamat): int
    {
        $st = $this->db()->prepare('INSERT INTO pelanggan (nama, telepon, alamat) VALUES (?, ?, ?)');
        $st->execute([$nama, $telepon, $alamat]);
        return (int)$this->db()->lastInsertId();
    }

    public function update(int $id, string $nama, string $telepon, string $alamat): void
    {
        $st = $this->db()->prepare('UPDATE pelanggan SET nama = ?, telepon = ?, alamat = ? WHERE id = ?');
        $st->execute([$nama, $telepon, $alamat, $id]);
    }

    public function jumlahTransaksi(int $id): int
    {
        $st = $this->db()->prepare('SELECT COUNT(*) FROM transaksi WHERE pelanggan_id = ?');
        $st->execute([$id]);
        return (int)$st->fetchColumn();
    }

    /** Riwayat transaksi milik pelanggan tertentu (untuk halaman detail). */
    public function transaksi(int $id): array
    {
        $st = $this->db()->prepare(
            'SELECT * FROM transaksi WHERE pelanggan_id = ? ORDER BY tanggal_masuk DESC, id DESC'
        );
        $st->execute([$id]);
        return $st->fetchAll();
    }

    public function delete(int $id): void
    {
        $st = $this->db()->prepare('DELETE FROM pelanggan WHERE id = ?');
        $st->execute([$id]);
    }
}