<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model Paket Layanan.
 */
class Paket extends Model
{
    public function count(): int
    {
        return (int)$this->db()->query('SELECT COUNT(*) FROM paket')->fetchColumn();
    }

    public function all(): array
    {
        return $this->db()->query(
            'SELECT k.*,
                    (SELECT COUNT(*) FROM transaksi_detail d WHERE d.paket_id = k.id) AS jml_pakai
             FROM paket k ORDER BY k.harga_per_kg ASC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db()->prepare('SELECT * FROM paket WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function create(string $nama, float $harga, int $estimasi, string $keterangan): int
    {
        $st = $this->db()->prepare('INSERT INTO paket (nama, harga_per_kg, estimasi_hari, keterangan) VALUES (?, ?, ?, ?)');
        $st->execute([$nama, $harga, $estimasi, $keterangan]);
        return (int)$this->db()->lastInsertId();
    }

    public function update(int $id, string $nama, float $harga, int $estimasi, string $keterangan): void
    {
        $st = $this->db()->prepare('UPDATE paket SET nama = ?, harga_per_kg = ?, estimasi_hari = ?, keterangan = ? WHERE id = ?');
        $st->execute([$nama, $harga, $estimasi, $keterangan, $id]);
    }

    public function jumlahDipakai(int $id): int
    {
        $st = $this->db()->prepare('SELECT COUNT(*) FROM transaksi_detail WHERE paket_id = ?');
        $st->execute([$id]);
        return (int)$st->fetchColumn();
    }

    /** Riwayat pemakaian paket pada transaksi (untuk halaman detail). */
    public function riwayat(int $id): array
    {
        $st = $this->db()->prepare(
            'SELECT t.id AS trx_id, t.no_nota, t.tanggal_masuk, t.status, t.status_bayar,
                    p.nama AS pelanggan, d.berat, d.subtotal
             FROM transaksi_detail d
             JOIN transaksi t ON t.id = d.transaksi_id
             JOIN pelanggan p ON p.id = t.pelanggan_id
             WHERE d.paket_id = ?
             ORDER BY t.tanggal_masuk DESC, t.id DESC'
        );
        $st->execute([$id]);
        return $st->fetchAll();
    }

    public function delete(int $id): void
    {
        $st = $this->db()->prepare('DELETE FROM paket WHERE id = ?');
        $st->execute([$id]);
    }
}