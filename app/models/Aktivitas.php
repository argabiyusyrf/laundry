<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model Log Aktivitas: catatan "siapa melakukan apa, kapan, dari IP mana".
 * Dipakai untuk audit & laporan aktivitas user (menu Log Aktivitas).
 */
class Aktivitas extends Model
{
    /**
     * Catat satu aktivitas user yang sedang login.
     * Kolom user_id NULL bila sesi tidak punya user (mis. cron / gagal login).
     */
    public function catat(string $aksi, string $keterangan = ''): void
    {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $ip     = substr(trim((string)($_SERVER['REMOTE_ADDR'] ?? '')), 0, 45);

        $st = $this->db()->prepare(
            'INSERT INTO log_aktivitas (user_id, aksi, keterangan, ip) VALUES (?, ?, ?, ?)'
        );
        $st->execute([$userId > 0 ? $userId : null, $aksi, $keterangan, $ip]);
    }

    /** Log aktivitas terbaru beserta identitas pelaku (nama, username, role). */
    public function terbaru(int $limit = 200): array
    {
        $st = $this->db()->prepare(
            'SELECT la.*, u.nama AS nama_user, u.username, u.role AS role_user
             FROM log_aktivitas la
             LEFT JOIN users u ON u.id = la.user_id
             ORDER BY la.id DESC LIMIT ?'
        );
        $st->execute([$limit]);
        return $st->fetchAll();
    }
}