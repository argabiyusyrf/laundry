<?php
namespace App\Core;

/**
 * Pencatatan log aplikasi ke file (storage/logs/).
 *
 * Dipakai agar error di hosting tetap bisa diperiksa meskipun
 * APP_DEBUG=false (mode produksi menyembunyikan detail error).
 * File log ditulis per hari: laundryku-YYYY-MM-DD.log
 */
class Log
{
    /**
     * Folder tempat menyimpan log.
     */
    public static function folder(): string
    {
        return dirname(__DIR__, 2) . '/storage/logs';
    }

    /**
     * File log untuk hari ini.
     */
    public static function fileHariIni(): string
    {
        return self::folder() . '/laundryku-' . date('Y-m-d') . '.log';
    }

    /**
     * Tulis satu baris log.
     *
     * @param string $level INFO | WARNING | ERROR | FATAL
     * @param string $pesan Isi pesan.
     */
    public static function tulis(string $level, string $pesan): void
    {
        try {
            $folder = self::folder();
            if (!is_dir($folder)) {
                @mkdir($folder, 0775, true);
            }
            $baris = sprintf(
                "[%s] %s %s%s",
                date('Y-m-d H:i:s'),
                strtoupper($level),
                $pesan,
                PHP_EOL
            );
            @file_put_contents(self::fileHariIni(), $baris, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            /* Gagal menulis log tidak boleh mematikan aplikasi. */
        }
    }

    /**
     * Baca baris terakhir dari log hari ini.
     *
     * @param int $jumlah Banyak baris terbaca (dari akhir).
     * @return array Daftar baris (kosong bila belum ada log).
     */
    public static function bacaTerbaru(int $jumlah = 30): array
    {
        $file = self::fileHariIni();
        if (!is_file($file)) {
            return [];
        }
        $isi = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($isi === false || $isi === []) {
            return [];
        }
        return array_slice($isi, -$jumlah);
    }
}