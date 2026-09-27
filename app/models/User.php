<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model User (akun pengguna multi-user: root / manager / staff).
 */
class User extends Model
{
    public function findByUsername(string $username): ?array
    {
        $st = $this->db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Cari user berdasarkan id. */
    public function find(int $id): ?array
    {
        $st = $this->db()->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Semua akun, urut dari yang paling lama dibuat. */
    public function semua(): array
    {
        return $this->db()->query('SELECT * FROM users ORDER BY id ASC')->fetchAll();
    }

    /** Buat akun baru (dengan role). */
    public function create(string $nama, string $username, string $hash, string $role): int
    {
        $st = $this->db()->prepare(
            'INSERT INTO users (nama, username, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $st->execute([$nama, $username, $hash, $role]);
        return (int)$this->db()->lastInsertId();
    }

    /** Perbarui data akun: nama, username, role, status aktif. */
    public function updateAkun(int $id, string $nama, string $username, string $role, bool $aktif): void
    {
        $st = $this->db()->prepare(
            'UPDATE users SET nama = ?, username = ?, role = ?, aktif = ? WHERE id = ?'
        );
        $st->execute([$nama, $username, $role, $aktif ? 1 : 0, $id]);
    }

    /** Hapus akun (transaksi terkait via log_aktivitas diatur ON DELETE SET NULL). */
    public function hapus(int $id): void
    {
        $st = $this->db()->prepare('DELETE FROM users WHERE id = ?');
        $st->execute([$id]);
    }

    /** Jumlah akun dengan role tertentu (mis. cek jumlah root terakhir). */
    public function countRole(string $role): int
    {
        $st = $this->db()->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
        $st->execute([$role]);
        return (int)$st->fetchColumn();
    }

    /** Catat waktu login terakhir. */
    public function lastLoginUpdate(int $id): void
    {
        $st = $this->db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $st->execute([$id]);
    }

    /** Apakah username dipakai user lain? */
    public function usernameDipakai(string $username, int $kecualiId = 0): bool
    {
        $st = $this->db()->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
        $st->execute([$username, $kecualiId]);
        return (int)$st->fetchColumn() > 0;
    }

    /** Perbarui nama & username. */
    public function updateProfil(int $id, string $nama, string $username): void
    {
        $st = $this->db()->prepare('UPDATE users SET nama = ?, username = ? WHERE id = ?');
        $st->execute([$nama, $username, $id]);
    }

    /** Perbarui password (sudah berupa hash). */
    public function updatePassword(int $id, string $hash): void
    {
        $st = $this->db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $st->execute([$hash, $id]);
    }

    /** Daftar akun karyawan (role staff), urut dari yang paling lama dibuat. */
    public function daftarKaryawan(): array
    {
        return $this->db()->query(
            "SELECT * FROM users WHERE role = 'staff' ORDER BY id ASC"
        )->fetchAll();
    }

    /** ID semua akun aktif dengan peran tertentu (untuk notifikasi bertarget). */
    public function idRole(string $role): array
    {
        $st = $this->db()->prepare(
            "SELECT id FROM users WHERE role = ? AND aktif = 1 ORDER BY id ASC"
        );
        $st->execute([$role]);
        return array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** ID pemilik (root + manager) yang aktif — penerima notifikasi transaksi dll. */
    public function idPemilik(): array
    {
        $out = [];
        foreach (['root', 'manager'] as $peran) {
            foreach ($this->idRole($peran) as $id) {
                $out[] = $id;
            }
        }
        return $out;
    }

    /** Catat jumlah gagal login (lockout dihitung di controller). */
    public function updateGagalLogin(int $id, int $gagal): void
    {
        $st = $this->db()->prepare('UPDATE users SET gagal_login = ? WHERE id = ?');
        $st->execute([$gagal, $id]);
    }

    /** Kunci akun sementara (berhenti di waktu saat ini + menit). */
    public function kunciSementara(int $id, int $menit = 5): void
    {
        $menit = max(1, $menit); // integer aman untuk inline INTERVAL
        $st = $this->db()->prepare(
            'UPDATE users SET terkunci_hingga = DATE_ADD(NOW(), INTERVAL ' . $menit . ' MINUTE) WHERE id = ?'
        );
        $st->execute([$id]);
    }

    /** Bersihkan penghitung gagal login & kunci (setelah login sukses). */
    public function resetGagalLogin(int $id): void
    {
        $st = $this->db()->prepare('UPDATE users SET gagal_login = 0, terkunci_hingga = NULL WHERE id = ?');
        $st->execute([$id]);
    }
}