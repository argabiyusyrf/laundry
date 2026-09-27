<?php
/**
 * ============================================================
 *  LaundryKu — INSTALLER OTOMATIS
 *
 *  Jalankan SEKALI lewat browser:
 *      http://localhost/laundry/install.php?token=TOKEN
 *
 *  TOKEN dihitung dari isi file app/config/config.php supaya hanya
 *  orang yang punya akses ke file konfigurasi yang bisa menjalankannya:
 *      buka app/config/config.php, salin baris:
 *          define('INSTALL_TOKEN', '...');
 *
 *  Tanpa token yang cocok, installer menolak dibuka (HTTP 403).
 *  Ini penting karena installer bisa membuat akun root, menambah kolom,
 *  dan menjalankan migrasi.
 *
 *  Setelah sukses, HAPUS file ini demi keamanan.
 * ============================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

/* Muat helper ikon supaya tampilan installer sesuai design system */
require __DIR__ . '/app/config/config.php';
require __DIR__ . '/app/helpers/helpers.php';

/* ---- Penjaga akses: token wajib cocok, dan hanya sekali jalan ---- */
$tokenDiminta = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenBenar   = defined('INSTALL_TOKEN') ? (string)INSTALL_TOKEN : '';

if ($tokenBenar === '' || $tokenDiminta === '' || !hash_equals($tokenBenar, $tokenDiminta)) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Installer LaundryKu</title></head>'
       . '<body style="font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;display:grid;place-items:center;min-height:100vh;margin:0">'
       . '<div style="max-width:560px;padding:24px;line-height:1.7">'
       . '<h1 style="margin:0 0 12px;font-size:22px">Installer terkunci</h1>'
       . '<p>File <code>install.php</code> hanya bisa dijalankan dengan token yang benar.</p>'
       . '<p>Buka <code>app/config/config.php</code>, cari baris berlabel <code>INSTALL_TOKEN</code>, '
       . 'lalu jalankan: <code>install.php?token=TOKEN_ANDA</code></p>'
       . '<p style="color:#94a3b8">Setelah instalasi selesai, hapus file ini dari server.</p>'
       . '</div></body></html>';
    exit;
}

/* Kredensial DB mengikuti app/config/config.php (satu tempat edit saja).
   Di hosting seperti InfinityFree isi dengan nilai dari panel MySQL Database:
     dbHost = hostname MySQL (mis. sql123.infinityfree.com)
     dbName = nama database   (mis. if0_12345678_laundry)
     dbUser = user MySQL      (mis. if0_12345678)
     dbPass = password MySQL */
$dbHost = DB_HOST;
$dbUser = DB_USER;
$dbPass = DB_PASS;
$dbName = DB_NAME;

/* Mode berjalan otomatis mengikuti config.php (via APP_DEBUG):
     'lokal'   -> komputer sendiri (XAMPP/Laragon): ikut data contoh.
     'hosting' -> InfinityFree / hosting lain: database BERSIH,
                  tanpa data contoh (pelanggan/transaksi/notifikasi). */
$mode = (defined('APP_DEBUG') && APP_DEBUG) ? 'lokal' : 'hosting';

/* Strategi koneksi dua langkah:
   1) Coba konek LANGSUNG ke database. Di hosting (mis. InfinityFree) database
      memang dibuat lewat panel dulu, jadi pendekatan ini yang dipakai — user
      MySQL di sana TIDAK punya hak CREATE DATABASE.
   2) Bila database belum ada (komputer lokal), buat dulu, lalu pakai. */

$hasil  = [];   // langkah yang benar-benar dilakukan (hijau / centang)
$info   = [];   // keterangan & langkah yang dilewati (netral / info)
$error  = null;
$deleted = false;
$jmlPel = 0;

$optsPdo = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    try {
        $conn = new PDO("mysql:host=$dbHost;port=" . DB_PORT . ";dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, $optsPdo);
        $hasil[] = 'Koneksi ke database <code>' . $dbName . '</code> berhasil.';
    } catch (Throwable $eConn) {
        $conn = new PDO("mysql:host=$dbHost;port=" . DB_PORT . ";charset=utf8mb4", $dbUser, $dbPass, $optsPdo);
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->exec("USE `$dbName`");
        $hasil[] = 'Database <code>' . $dbName . '</code> berhasil dibuat / dipastikan ada.';
    }

    /* --- Buat tabel --- */
    $ddl = [
        'users' => 'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            nama VARCHAR(100) NOT NULL,
            role ENUM("root","manager","staff") NOT NULL DEFAULT "staff",
            aktif TINYINT(1) NOT NULL DEFAULT 1,
            gagal_login INT NOT NULL DEFAULT 0,
            terkunci_hingga DATETIME NULL DEFAULT NULL,
            last_login TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB',

        'log_aktivitas' => 'CREATE TABLE IF NOT EXISTS log_aktivitas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED DEFAULT NULL,
            aksi VARCHAR(60) NOT NULL,
            keterangan VARCHAR(255) DEFAULT NULL,
            ip VARCHAR(45) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB',

        'pengaturan' => 'CREATE TABLE IF NOT EXISTS pengaturan (
            kunci VARCHAR(60) PRIMARY KEY,
            nilai TEXT NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB',

        'pelanggan' => 'CREATE TABLE IF NOT EXISTS pelanggan (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(120) NOT NULL,
            telepon VARCHAR(30) DEFAULT NULL,
            alamat VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB',

        'paket' => 'CREATE TABLE IF NOT EXISTS paket (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(120) NOT NULL,
            harga_per_kg DECIMAL(12,2) NOT NULL DEFAULT 0,
            estimasi_hari INT NOT NULL DEFAULT 2,
            keterangan VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB',

        'transaksi' => 'CREATE TABLE IF NOT EXISTS transaksi (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            no_nota VARCHAR(30) NOT NULL UNIQUE,
            pelanggan_id INT UNSIGNED NOT NULL,
            tanggal_masuk DATE NOT NULL,
            estimasi_selesai DATE DEFAULT NULL,
            tanggal_selesai DATE DEFAULT NULL,
            total_berat DECIMAL(10,2) NOT NULL DEFAULT 0,
            total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
            diskon DECIMAL(12,2) NOT NULL DEFAULT 0,
            pajak DECIMAL(12,2) NOT NULL DEFAULT 0,
            uang_muka DECIMAL(12,2) NOT NULL DEFAULT 0,
            status ENUM("diterima","dicuci","disetrika","siap_diambil","selesai") NOT NULL DEFAULT "diterima",
            status_bayar ENUM("belum","lunas") NOT NULL DEFAULT "belum",
            keterangan VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_trx_pelanggan FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id)
        ) ENGINE=InnoDB',

        'transaksi_detail' => 'CREATE TABLE IF NOT EXISTS transaksi_detail (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            transaksi_id INT UNSIGNED NOT NULL,
            paket_id INT UNSIGNED NOT NULL,
            berat DECIMAL(10,2) NOT NULL DEFAULT 0,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            CONSTRAINT fk_det_trx FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
            CONSTRAINT fk_det_paket FOREIGN KEY (paket_id) REFERENCES paket(id)
        ) ENGINE=InnoDB',

        'notifikasi' => 'CREATE TABLE IF NOT EXISTS notifikasi (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            jenis VARCHAR(20) NOT NULL DEFAULT "info",
            judul VARCHAR(120) NOT NULL,
            pesan VARCHAR(255) NOT NULL,
            user_id INT UNSIGNED DEFAULT NULL,
            pengirim VARCHAR(100) DEFAULT NULL,
            tautan VARCHAR(120) NOT NULL DEFAULT "",
            dibaca TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notif_penerima (user_id),
            CONSTRAINT fk_notif_penerima FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB',

        'permintaan_akun' => 'CREATE TABLE IF NOT EXISTS permintaan_akun (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            jenis ENUM("toko") NOT NULL DEFAULT "toko",
            nama_toko VARCHAR(120) NOT NULL,
            nama_pemohon VARCHAR(100) NOT NULL,
            username VARCHAR(50) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            telepon VARCHAR(30) DEFAULT NULL,
            alamat VARCHAR(255) DEFAULT NULL,
            catatan VARCHAR(255) DEFAULT NULL,
            status ENUM("menunggu","disetujui","ditolak") NOT NULL DEFAULT "menunggu",
            diproses_oleh INT UNSIGNED DEFAULT NULL,
            diproses_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_perm_username (username),
            CONSTRAINT fk_perm_proses FOREIGN KEY (diproses_oleh) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB',
    ];
    foreach ($ddl as $namaTabel => $sql) {
        $conn->exec($sql);
    }
    $hasil[] = 'Seluruh tabel berhasil dibuat: <code>' . implode('</code>, <code>', array_keys($ddl)) . '</code>.';
    $info[] = 'Mode instalasi: <code>' . ($mode === 'hosting'
        ? 'HOSTING — database bersih (tanpa data contoh)'
        : 'LOKAL — ikut data contoh') . '</code>';
    $info[] = 'Kredensial database: blok <code>' . ($mode === 'hosting' ? 'HOSTING' : 'LOKAL') . '</code> dari app/config/config.php.';

    /* --- Migrasi multi-user (database lama): tambah kolom role & aktif --- */
    $kolom = [];
    foreach ($conn->query('SHOW COLUMNS FROM users') as $c) {
        $kolom[] = $c['Field'];
    }
    if (!in_array('role', $kolom, true)) {
        $conn->exec('ALTER TABLE users
            ADD COLUMN role ENUM("root","manager","staff") NOT NULL DEFAULT "staff" AFTER nama,
            ADD COLUMN aktif TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
            ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER aktif');
        /* Database lama dianggap milik pemilik: akun admin jadi root,
           akun lain yang bukan admin tidak dipaksa root. */
        $conn->exec("UPDATE users SET role = 'root' WHERE username = 'admin'");
        $adaRoot = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'root'")->fetchColumn();
        if ($adaRoot === 0) {
            $conn->exec('UPDATE users u JOIN (SELECT MIN(id) AS id FROM users) m ON u.id = m.id SET u.role = "root"');
        }
        $hasil[] = 'Migrasi multi-user: kolom <code>role</code>, <code>aktif</code>, <code>last_login</code> ditambahkan ke tabel users.';
    } else {
        $info[] = 'Migrasi multi-user: kolom role/aktif sudah ada, dilewati.';
    }

    /* --- Migrasi v2.1: keamanan login (users) --- */
    $kolom = [];
    foreach ($conn->query('SHOW COLUMNS FROM users') as $c) {
        $kolom[] = $c['Field'];
    }
    $tambahKolom = [];
    if (!in_array('gagal_login', $kolom, true)) {
        $tambahKolom[] = 'ADD COLUMN gagal_login INT NOT NULL DEFAULT 0 AFTER aktif';
    }
    if (!in_array('terkunci_hingga', $kolom, true)) {
        $tambahKolom[] = 'ADD COLUMN terkunci_hingga DATETIME NULL DEFAULT NULL AFTER gagal_login';
    }
    if ($tambahKolom) {
        $conn->exec('ALTER TABLE users ' . implode(', ', $tambahKolom));
        $hasil[] = 'Migrasi keamanan login: kolom <code>gagal_login</code> &amp; <code>terkunci_hingga</code> ditambahkan ke users.';
    } else {
        $info[] = 'Migrasi keamanan login: kolom gagal_login/terkunci_hingga sudah ada, dilewati.';
    }

    /* --- Migrasi v2.1: diskon & pajak (transaksi) --- */
    $kolom = [];
    foreach ($conn->query('SHOW COLUMNS FROM transaksi') as $c) {
        $kolom[] = $c['Field'];
    }
    $tambahKolom = [];
    if (!in_array('diskon', $kolom, true)) {
        $tambahKolom[] = 'ADD COLUMN diskon DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_harga';
    }
    if (!in_array('pajak', $kolom, true)) {
        $tambahKolom[] = 'ADD COLUMN pajak DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER diskon';
    }
    if ($tambahKolom) {
        $conn->exec('ALTER TABLE transaksi ' . implode(', ', $tambahKolom));
        $hasil[] = 'Migrasi nota: kolom <code>diskon</code> &amp; <code>pajak</code> ditambahkan ke transaksi.';
    } else {
        $info[] = 'Migrasi nota: kolom diskon/pajak sudah ada, dilewati.';
    }

    /* --- Migrasi v2.1: notifikasi bertarget (kirim notifikasi ke pengguna) ---
     * user_id  = penerima (NULL = untuk semua pengguna / broadcast).
     * pengirim = nama pengirim (root / manager), tampil di daftar notifikasi. */
    $kolomNotif = [];
    foreach ($conn->query('SHOW COLUMNS FROM notifikasi') as $c) {
        $kolomNotif[] = $c['Field'];
    }
    $tambahKolom = [];
    if (!in_array('user_id', $kolomNotif, true)) {
        $tambahKolom[] = 'ADD COLUMN user_id INT UNSIGNED DEFAULT NULL AFTER pesan';
    }
    if (!in_array('pengirim', $kolomNotif, true)) {
        $tambahKolom[] = 'ADD COLUMN pengirim VARCHAR(100) DEFAULT NULL AFTER user_id';
    }
    if ($tambahKolom) {
        $conn->exec('ALTER TABLE notifikasi ' . implode(', ', $tambahKolom));
        $hasil[] = 'Migrasi notifikasi: kolom <code>user_id</code> (penerima) &amp; <code>pengirim</code> ditambahkan.';
    } else {
        $info[] = 'Migrasi notifikasi: kolom penerima/pengirim sudah ada, dilewati.';
    }
    $adaIndexNf = (int)$conn->query(
        "SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE()
         AND table_name = 'notifikasi' AND index_name = 'idx_notif_penerima'"
    )->fetchColumn();
    if (!$adaIndexNf) {
        $conn->exec('ALTER TABLE notifikasi ADD INDEX idx_notif_penerima (user_id)');
        $hasil[] = 'Index <code>idx_notif_penerima</code> dibuat pada tabel notifikasi.';
    }
    $adaFkNf = (int)$conn->query(
        "SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema = DATABASE()
         AND table_name = 'notifikasi' AND constraint_name = 'fk_notif_penerima'"
    )->fetchColumn();
    if (!$adaFkNf) {
        $conn->exec('ALTER TABLE notifikasi ADD CONSTRAINT fk_notif_penerima FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
        $hasil[] = 'Relasi <code>fk_notif_penerima</code> (penerima -&gt; users) dibuat.';
    }

    /* --- Pengaturan default (lengkap; kunci baru ditambahkan/divalidasi tiap jalan) --- */
    $jmlPengaturan = (int)$conn->query('SELECT COUNT(*) FROM pengaturan')->fetchColumn();
    $conn->exec("INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
        ('nama_usaha', ''),
        ('alamat_usaha', ''),
        ('telepon_usaha', ''),
        ('whatsapp', ''),
        ('instagram', ''),
        ('email_usaha', ''),
        ('jam_buka', ''),
        ('jam_tutup', ''),
        ('logo', ''),
        ('header_struk', ''),
        ('footer_struk', 'Barang yang sudah selesai mohon diambil maksimal 7 hari setelah konfirmasi.'),
        ('promo_struk', ''),
        ('catatan_struk', ''),
        ('pajak_persen', '0'),
        ('diskon_maks_persen', '0'),
        ('dp_minimal_persen', '0'),
        ('prefix_nota', 'LND'),
        ('satuan_berat', 'kg'),
        ('mata_uang', 'Rp'),
        ('min_password', '6'),
        ('sesi_kedaluwarsa_menit', '0'),
        ('batas_login_gagal', '5'),
        ('notif_suara', '1'),
        ('notif_volume', '80')");
    if ($jmlPengaturan === 0) {
        $hasil[] = '24 pengaturan default ditambahkan (identitas, struk, pajak/diskon, nomor nota, satuan, keamanan, notifikasi & suara).';
    } else {
        $info[] = 'Pengaturan sudah ada; kunci baru (pajak/diskon, nota, satuan, keamanan, notifikasi & suara) ditambahkan bila belum ada.';
    }

    /* --- Paket default --- */
    $jmlPaket = (int)$conn->query('SELECT COUNT(*) FROM paket')->fetchColumn();
    if ($jmlPaket === 0) {
        $conn->exec("INSERT INTO paket (nama, harga_per_kg, estimasi_hari, keterangan) VALUES
            ('Cuci Reguler', 7000, 2, 'Cuci + pengering, tanpa setrika'),
            ('Cuci Express', 10000, 1, 'Selesai 1 hari, prioritas'),
            ('Cuci + Setrika', 12000, 3, 'Cuci dan setrika rapi'),
            ('Setrika Saja', 8000, 1, 'Khusus setrika'),
            ('Karpet / Bed Cover', 15000, 3, 'Layanan khusus karpet'),
            ('Selimut / Boneka', 12000, 2, 'Untuk barang besar')");
        $hasil[] = '6 paket layanan default ditambahkan.';
    } else {
        $info[] = 'Paket layanan sudah ada, dilewati.';
    }

    /* --- Contoh pelanggan (hanya mode LOKAL) --- */
    $jmlPel = (int)$conn->query('SELECT COUNT(*) FROM pelanggan')->fetchColumn();
    if ($mode === 'lokal' && $jmlPel === 0) {
        $conn->exec("INSERT INTO pelanggan (nama, telepon, alamat) VALUES
            ('Budi Santoso', '081234567890', 'Jl. Melati No. 12, Jakarta'),
            ('Siti Rahmawati', '081298765432', 'Jl. Flamboyan No. 3, Depok'),
            ('Andi Wijaya', '082123456789', 'Perum Griya Asri Blok C5, Bekasi')");
        $jmlPel = 3;
        $hasil[] = '3 pelanggan contoh ditambahkan.';
    } elseif ($jmlPel === 0) {
        $info[] = 'Mode hosting: tabel pelanggan dibiarkan kosong (bersih).';
    } else {
        $info[] = 'Data pelanggan sudah ada, dilewati.';
    }

    /* --- Akun root (pemilik) --- */
    $jmlUser = (int)$conn->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($jmlUser === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $st = $conn->prepare('INSERT INTO users (username, password_hash, nama, role) VALUES (?, ?, ?, ?)');
        $st->execute(['admin', $hash, 'Administrator', 'root']);
        $hasil[] = 'Akun root dibuat: <code>admin</code> / <code>admin123</code> (pemilik sistem).';
    } else {
        $info[] = 'Akun sudah ada, dilewati.';
    }

    /* --- Akun contoh manager & staff (hanya mode LOKAL) --- */
    if ($mode === 'lokal') {
        if ((int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'manager'")->fetchColumn() === 0) {
            $conn->prepare('INSERT INTO users (username, password_hash, nama, role) VALUES (?, ?, ?, ?)')
                ->execute(['manager', password_hash('manager123', PASSWORD_DEFAULT), 'Manager Toko', 'manager']);
            $hasil[] = 'Akun contoh manager: <code>manager</code> / <code>manager123</code>.';
        }
        if ((int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn() === 0) {
            $conn->prepare('INSERT INTO users (username, password_hash, nama, role) VALUES (?, ?, ?, ?)')
                ->execute(['staff', password_hash('staff123', PASSWORD_DEFAULT), 'Staff Kasir', 'staff']);
            $hasil[] = 'Akun contoh staff: <code>staff</code> / <code>staff123</code>.';
        }
    }

    /* --- Contoh transaksi (hanya mode LOKAL) --- */
    $jmlTrx = (int)$conn->query('SELECT COUNT(*) FROM transaksi')->fetchColumn();
    if ($mode === 'lokal' && $jmlTrx === 0 && $jmlPel >= 1) {
        $conn->exec("INSERT INTO transaksi
            (no_nota, pelanggan_id, tanggal_masuk, estimasi_selesai, tanggal_selesai,
             total_berat, total_harga, uang_muka, status, status_bayar) VALUES
            (CONCAT('LND-', DATE_FORMAT(CURDATE(), '%Y%m%d'), '-001'), 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 5.0, 35000, 35000, 'diterima', 'lunas'),
            (CONCAT('LND-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '%Y%m%d'), '-001'), 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 8.0, 64000, 20000, 'dicuci', 'belum'),
            (CONCAT('LND-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '%Y%m%d'), '-001'), 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 3.5, 42000, 42000, 'selesai', 'lunas')");
        $conn->exec('INSERT INTO transaksi_detail (transaksi_id, paket_id, berat, subtotal) VALUES
            (1, 1, 5.0, 35000),
            (2, 4, 8.0, 64000),
            (3, 3, 3.5, 42000)');
        $hasil[] = '3 transaksi contoh ditambahkan (agar dashboard tidak kosong).';
    } elseif ($mode === 'hosting') {
        $info[] = 'Mode hosting: tabel transaksi dibiarkan kosong (bersih).';
    } else {
        $info[] = 'Data transaksi sudah ada / perlu data pelanggan, dilewati.';
    }

    /* --- Notifikasi sambutan (hanya mode LOKAL) --- */
    $jmlNotif = (int)$conn->query('SELECT COUNT(*) FROM notifikasi')->fetchColumn();
    if ($mode === 'lokal' && $jmlNotif === 0) {
        $conn->exec("INSERT INTO notifikasi (jenis, judul, pesan, tautan) VALUES
            ('info', 'Selamat Datang di LaundryKu', 'Aplikasi siap dipakai. Mulai dengan menambah pelanggan baru dan membuat nota transaksi.', 'dashboard'),
            ('info', 'Fitur Notifikasi Aktif', 'Lonceng di pojok kanan atas menampilkan notifikasi transaksi baru, perubahan status, dan pembayaran secara realtime.', 'notifikasi')");
        $hasil[] = 'Notifikasi sambutan ditambahkan.';
    } elseif ($mode === 'hosting') {
        $info[] = 'Mode hosting: notifikasi dibiarkan kosong (muncul otomatis saat ada aktivitas).';
    } else {
        $info[] = 'Notifikasi sudah ada, dilewati.';
    }
} catch (Throwable $th) {
    $error = $th->getMessage();
}

/* --- Hapus file installer --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    @unlink(__FILE__);
    $deleted = !file_exists(__FILE__);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalasi — LaundryKu</title>
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="stylesheet" href="asset/style.css">
</head>
<body class="login-body">
  <div class="login-bg" aria-hidden="true"></div>

  <main class="login-card install-card">
    <div class="install-head">
      <div class="login-logo"><?= icon('logo', 30) ?></div>
      <h1>Instalasi LaundryKu</h1>
      <p class="login-sub">Penyiapan database, tabel, dan akun pengguna</p>
      <span class="install-badge<?= $mode === 'hosting' ? ' is-host' : '' ?>">
        <span class="ib-dot"></span>
        Mode <?= $mode === 'hosting' ? 'HOSTING — database bersih' : 'LOKAL — ikut data contoh' ?>
      </span>
    </div>

    <?php if ($deleted): ?>
      <div class="alert alert-success">
        <span class="alert-ico"><?= icon('check', 17) ?></span>
        <span>File <code>install.php</code> sudah dihapus. Aplikasi siap dipakai.</span>
      </div>
      <a class="btn btn-primary btn-lg btn-block" href="index.php">Buka Aplikasi →</a>
    <?php else: ?>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <span class="alert-ico"><?= icon('peringatan', 17) ?></span>
          <span>Instalasi gagal: <code><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></code></span>
        </div>
        <?php if (stripos($error, 'access denied') !== false): ?>
          <div class="install-note">
            <span class="in-ico"><?= icon('info', 16) ?></span>
            <div>Cek user/password MySQL di <code>app/config/config.php</code>.
            Di hosting (InfinityFree): gunakan data dari panel “MySQL Databases”
            (hostname seperti <code>sql123.infinityfree.com</code>, bukan localhost).
            XAMPP/Laragon default: <code>root</code> tanpa password.</div>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="alert alert-success">
          <span class="alert-ico"><?= icon('check', 17) ?></span>
          <span>Instalasi selesai — LaundryKu siap dipakai.</span>
        </div>
      <?php endif; ?>

      <?php if ($info): ?>
        <div class="step-list">
          <?php foreach ($info as $i): ?>
            <div class="step-item is-info">
              <span class="st-ico"><?= icon('info', 13) ?></span>
              <span><?= $i ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($hasil): ?>
        <div class="step-list">
          <?php foreach ($hasil as $h): ?>
            <div class="step-item is-ok">
              <span class="st-ico"><?= icon('check', 13) ?></span>
              <span><?= $h ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="install-actions">
        <a class="btn btn-primary btn-lg btn-block" href="index.php">Masuk ke Aplikasi →</a>
        <form method="post">
          <button class="btn btn-danger btn-block" name="hapus" value="1"
                  onclick="return confirm('Hapus install.php sekarang? Sangat disarankan demi keamanan.')"><?= icon('hapus', 15) ?> Hapus install.php</button>
        </form>
        <div class="install-note">
          <span class="in-ico"><?= icon('kunci', 16) ?></span>
          <div>
            <strong>Akun default:</strong> <code>admin</code> / <code>admin123</code> (root/pemilik).
            Ganti password root melalui menu <strong>Akun &amp; Pengguna</strong> setelah login pertama.
          </div>
        </div>
      </div>

    <?php endif; ?>
  </main>
</body>
</html>