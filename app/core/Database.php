<?php
namespace App\Core;

use PDO;

/**
 * Koneksi database (singleton PDO).
 * Semua akses database lewat class ini, dipakai oleh Model.
 */
class Database
{
    private static $conn = null;

    public static function conn(): PDO
    {
        if (self::$conn === null) {
            self::$conn = new PDO(
                'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );

            /* Samakan zona waktu MySQL dengan zona waktu PHP (Asia/Jakarta).
               Tanpa ini NOW(), CURDATE(), dan kolom DEFAULT CURRENT_TIMESTAMP
               memakai zona server database (biasanya UTC), sehingga tanggal
               bisa bergeser 7 jam: tanggal transaksi, "hari ini" di dashboard,
               dan lock login bisa salah. */
            try {
                $offset = (new \DateTime('now'))->format('P'); // contoh: +07:00
                self::$conn->exec("SET time_zone = '" . $offset . "'");
            } catch (\PDOException $e) {
                /* Server tanpa tabel zona waktu: biarkan, aplikasi tetap jalan. */
            }
        }
        return self::$conn;
    }
}