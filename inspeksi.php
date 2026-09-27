<?php
/**
 * ============================================================
 *  LaundryKu — INSPEKSI HOSTING (alat diagnostik)
 * ============================================================
 *  Buka file ini di browser:
 *      https://nama-domain-kamu/inspeksi.php?token=NILAI_INSTALL_TOKEN
 *
 *  Fungsinya menunjukkan:
 *   1. Versi PHP server + modul penting (pdo_mysql, mbstring).
 *   2. Hasil koneksi ke database (HOST & NAMA saja, PASSWORD TIDAK ditampilkan).
 *   3. Daftar tabel yang ada (untuk memastikan database sudah diimport).
 *
 *  Token diambil dari app/config/config.php (konstanta INSTALL_TOKEN).
 *  Tanpa token yang benar file ini menolak dibuka, karena isinya membocorkan
 *  detail infrastruktur dan isi file log.
 *
 *  SETELAH DIPAKAI, HAPUS file ini dari server (sama seperti install.php).
 * ============================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

/* ---- Penjaga akses: token wajib cocok ---- */
require __DIR__ . '/app/config/config.php';
$tokenDiminta = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenBenar   = defined('INSTALL_TOKEN') ? (string)INSTALL_TOKEN : '';

if ($tokenBenar === '' || $tokenDiminta === '' || !hash_equals($tokenBenar, $tokenDiminta)) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Inspeksi terkunci</title></head>'
       . '<body style="font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;display:grid;place-items:center;min-height:100vh;margin:0">'
       . '<div style="max-width:560px;padding:24px;line-height:1.7">'
       . '<h1 style="margin:0 0 12px;font-size:22px">Halaman inspeksi terkunci</h1>'
       . '<p>File ini hanya bisa dibuka dengan token yang benar:</p>'
       . '<p><code>inspeksi.php?token=NILAI_INSTALL_TOKEN</code></p>'
       . '<p style="color:#94a3b8">Token ada di <code>app/config/config.php</code>.</p>'
       . '</div></body></html>';
    exit;
}

echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">';
echo '<title>Inspeksi Hosting — LaundryKu</title>';
echo '<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f1f5f9;margin:0;padding:24px;color:#0f172a}
.wrap{max-width:720px;margin:0 auto}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px 24px;margin:14px 0;box-shadow:0 6px 18px -8px rgba(15,23,42,.15)}
h1{font-size:22px;margin:0 0 4px}
h2{font-size:15px;margin:0 0 12px;color:#4f46e5}
table{border-collapse:collapse;width:100%;font-size:14px}
td,th{border-bottom:1px solid #e2e8f0;padding:8px 6px;text-align:left}
th{color:#64748b;font-weight:600}
.ok{color:#16a34a;font-weight:700}
.gagal{color:#dc2626;font-weight:700}
.mut{background:#eef2ff;border-left:4px solid #6366f1;padding:10px 14px;border-radius:8px;font-size:13.5px;margin-top:12px}
</style></head><body><div class="wrap">';
echo '<div class="card"><h1>Inspeksi Hosting LaundryKu</h1><p style="color:#64748b;margin:4px 0 0">Halaman ini untuk diagnosa, jangan lupa dihapus setelah selesai.</p></div>';

/* ---------- 1. PHP & modul ---------- */
echo '<div class="card"><h2>1. Versi PHP &amp; Modul</h2><table>';
echo '<tr><th>Versi PHP</th><td>' . htmlspecialchars(PHP_VERSION) . '</td></tr>';
$bayi = (version_compare(PHP_VERSION, '7.1.0', '<'));
echo '<tr><th>Kebutuhan LaundryKu</th><td class="' . ($bayi ? 'gagal' : 'ok') . '">' . ($bayi
    ? 'PHP versi ini TERLALU TUA (minimal harus 7.4+, disarankan 8.x)'
    : 'Versi PHP memenuhi syarat (minimal 7.4)') . '</td></tr>';

$mods = ['pdo_mysql' => 'PDO MySQL', 'mbstring' => 'mbstring', 'session' => 'Session', 'json' => 'JSON'];
foreach ($mods as $ekst => $label) {
    $ada = extension_loaded($ekst);
    echo '<tr><th>' . $label . ' (' . $ekst . ')</th><td class="' . ($ada ? 'ok' : 'gagal') . '">'
        . ($ada ? 'Tersedia' : 'TIDAK ADA — minta diaktifkan di hosting') . '</td></tr>';
}
echo '</table>';

if ($bayi || !extension_loaded('pdo_mysql')) {
    echo '<div class="mut">Penyebab paling umum 500 di InfinityFree: versi PHP terlalu tua sehingga kode '
        . 'tidak bisa diparsing. Buka panel hosting kamu → menu <strong>PHP Settings / Select PHP Version</strong>, '
        . 'lalu pilih <strong>PHP 8.x</strong> (bila pilihan seperti PHP 5.6/7.x, pilih yang paling tinggi).</div>';
}
echo '</div>';

/* ---------- 2. Koneksi database ---------- */
echo '<div class="card"><h2>2. Koneksi Database</h2>';
/* config.php sudah dimuat di bagian penjaga token di atas, tidak perlu dua kali. */

echo '<table>';
echo '<tr><th>Mode terdeteksi</th><td>' . (APP_DEBUG ? 'LOKAL (localhost)' : 'HOSTING (produksi)') . '</td></tr>';
echo '<tr><th>Host MySQL</th><td>' . htmlspecialchars(DB_HOST) . ':' . htmlspecialchars(DB_PORT) . '</td></tr>';
echo '<tr><th>Nama database</th><td>' . htmlspecialchars(DB_NAME) . '</td></tr>';
echo '<tr><th>User MySQL</th><td>' . htmlspecialchars(DB_USER) . '</td></tr>';
echo '<tr><th>Password</th><td style="color:#94a3b8">(disembunyikan)</td></tr>';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 8,
    ]);
    echo '<tr><th>Status koneksi</th><td class="ok">SUKSES — kredensial database benar</td></tr>';

    $tabel = array();
    $q = $pdo->query('SHOW TABLES');
    while ($baris = $q->fetch(PDO::FETCH_NUM)) {
        $tabel[] = $baris[0];
    }
    $wajib = ['users', 'pengaturan', 'pelanggan', 'paket', 'transaksi', 'transaksi_detail', 'notifikasi', 'log_aktivitas', 'permintaan_akun'];
    $kurang = array_diff($wajib, $tabel);
    echo '<tr><th>Jumlah tabel</th><td>' . count($tabel) . '</td></tr>';
    echo '<tr><th>Tabel ditemukan</th><td>' . htmlspecialchars(implode(', ', $tabel)) . '</td></tr>';
    if ($kurang) {
        echo '<tr><th>Checklist</th><td class="gagal">Masih kurang tabel: ' . htmlspecialchars(implode(', ', $kurang))
            . '. Import <strong>laundryku-database.sql</strong> lewat phpMyAdmin, atau buka install.php sekali lalu hapus.</td></tr>';
    } else {
        echo '<tr><th>Checklist</th><td class="ok">Semua tabel ada — database siap pakai.</td></tr>';
    }
} catch (Throwable $e) {
    echo '<tr><th>Status koneksi</th><td class="gagal">GAGAL — ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
    echo '<tr><th>Saran</th><td>Periksa nama database, username, password, dan hostname pada panel '
        . 'MySQL Databases. Pastikan database sudah dibuat di panel (bukan hanya nama yang kamu inginkan).</td></tr>';
}
echo '</table></div>';

/* ---------- 3. Log error terakhir ---------- */
echo '<div class="card"><h2>3. Log Error Terakhir</h2>';
$folderLog = __DIR__ . '/storage/logs';
$logFile = $folderLog . '/laundryku-' . date('Y-m-d') . '.log';
$phpLog = $folderLog . '/php-errors.log';
$barisLog = array();

if (is_dir($folderLog)) {
    if (is_file($logFile)) {
        $isi = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($isi !== false) {
            $isi = array_slice($isi, -25);
            foreach ($isi as $b) {
                $barisLog[] = $b;
            }
        }
    }
    if (is_file($phpLog)) {
        $isi2 = @file($phpLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($isi2 !== false) {
            $isi2 = array_slice($isi2, -15);
            foreach ($isi2 as $b) {
                $barisLog[] = '[php] ' . $b;
            }
        }
    }
}

if ($barisLog) {
    echo '<table><tr><th>File log</th><td>' . htmlspecialchars($logFile) . '</td></tr></table>';
    echo '<pre style="background:#0f172a;color:#e2e8f0;padding:14px;border-radius:10px;font-size:12.5px;line-height:1.6;overflow:auto;max-height:340px">'
        . htmlspecialchars(implode(PHP_EOL, array_reverse($barisLog))) . '</pre>';
    echo '<p style="color:#64748b;font-size:13px">Isi lengkap bisa diperiksa lewat File Manager hosting '
        . 'di folder <code>storage/logs/</code> (folder ini dilindungi agar tidak bisa dibuka browser).</p>';
} else {
    echo '<p class="ok" style="font-weight:700">Belum ada error yang tercatat.</p>';
    echo '<p style="color:#64748b;font-size:13px">File log akan dibuat otomatis di '
        . '<code>storage/logs/laundryku-YYYY-MM-DD.log</code> begitu terjadi kesalahan '
        . '(exception, fatal error, atau HTTP 5xx).</p>';
}
echo '</div>';

echo '<div class="card"><h2>4. Kesimpulan</h2>';
echo '<ol style="font-size:14px;line-height:1.8;padding-left:20px">';
echo '<li>Kalau <strong>Versi PHP</strong> di atas merah / tua → ganti PHP ke 8.x di panel hosting, lalu muat ulang halaman ini.</li>';
echo '<li>Kalau <strong>koneksi DB</strong> GAGAL → cek kredensial di <code>app/config/config.php</code> (blok HOSTING).</li>';
echo '<li>Kalau tabel kurang → import <code>laundryku-database.sql</code> atau jalankan <code>install.php</code> lalu hapus.</li>';
echo '<li>Kalau situs 500 → buka bagian <strong>Log Error Terakhir</strong> di atas: isinya menunjukkan persis apa yang gagal.</li>';
echo '<li>Kalau semuanya hijau tapi situs tetap 500 → cek log error di panel hosting (menu Error Logs) dan kirim isinya.</li>';
echo '</ol></div>';

echo '</div></body></html>';