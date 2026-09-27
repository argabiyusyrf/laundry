<?php
/**
 * ============================================================
 *  Konfigurasi aplikasi LaundryKu
 * ============================================================
 *  Ada DUA kredensial database:
 *
 *   1) LOKAL     → dipakai saat aplikasi jalan di komputer sendiri
 *                  (XAMPP / Laragon / Apache bawaan), host localhost.
 *   2) HOSTING   → dipakai saat aplikasi sudah di-upload ke hosting
 *                  (mis. InfinityFree). INI YANG WAJIB DIISI.
 *
 *  Aplikasi memilihnya secara OTOMATIS:
 *   - dijalankan dari localhost / 127.0.0.1  → pakai kredensial LOKAL
 *   - dijalankan dari domain hosting         → pakai kredensial HOSTING
 *
 *  Kalau mau memaksa salah satu mode, ubah $LOKASI di bawah menjadi
 *  'lokal' atau 'hosting' (kosongkan kembali untuk otomatis).
 */

date_default_timezone_set('Asia/Jakarta');

/* ===== Pemilih lokasi (jangan diubah kecuali perlu) ===== */
$LOKASI = ''; // '' = otomatis, 'lokal' = paksa lokal, 'hosting' = paksa hosting

$httpHost = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
if ($LOKASI === '') {
    $LOKASI = ($httpHost === ''
        || substr($httpHost, 0, 9) === 'localhost'
        || substr($httpHost, 0, 9) === '127.0.0.1'
        || substr($httpHost, 0, 5) === '[::1]') ? 'lokal' : 'hosting';
}

/* ===== Kredensial #1 — LOKAL (localhost / XAMPP / Laragon) ===== */
$kredensial = $LOKASI === 'hosting' ? [] : [
    'host' => '127.0.0.1',
    'name' => 'laundry',
    'user' => 'root',
    'pass' => 'root',
    'port' => '3306',
];

/* ===== Kredensial #2 — HOSTING (untuk InfinityFree / hosting lain) =====
 * Sudah terisi dengan akun hosting ini. Bila dipakai di hosting LAIN,
 * ganti 5 nilai di bawah dengan data dari panel host kamu
 * (menu "MySQL Databases"): Hostname, Database Name, Username, Password.
 */
if ($LOKASI === 'hosting') {
    $kredensial = [
        'host' => 'sql102.infinityfree.com',
        'name' => 'if0_42820753_laundryku',
        'user' => 'if0_42820753',
        'pass' => 'Argaazel1606',
        'port' => '3306',
    ];
}

define('DB_HOST', $kredensial['host']);
define('DB_NAME', $kredensial['name']);
define('DB_USER', $kredensial['user']);
define('DB_PASS', $kredensial['pass']);
define('DB_PORT', $kredensial['port']);
unset($kredensial, $httpHost);

/* ===== Identitas aplikasi ===== */
define('APP_NAME', 'LaundryKu');

/* ===== Token installer =====
 * install.php (dan inspeksi.php) hanya bisa dibuka bila parameter ?token= pada
 * URL sama persis dengan nilai di bawah. Tanpa ini, siapa pun yang mengetahui
 * alamat file tersebut bisa menjalankan installer, membuat akun root, atau
 * membaca isi log.
 *
 * GANTI NILAI INI dengan token buatan Anda sendiri sebelum meng-upload
 * (mis. 32 karakter huruf+angka acak), lalu jalankan:
 *     install.php?token=NILAI_YANG_ANDA_TULIS
 * Setelah instalasi selesai, HAPUS install.php dari server.
 */
define('INSTALL_TOKEN', 'ganti-dengan-token-acak-anda-sebelum-upload');

/* ===== Mode debug =====
 * Otomatis: server dijalankan di komputer sendiri (alamat loopback) → true,
 * di hosting → false (detail error disembunyikan dari pengunjung).
 *
 * Penting: penentuan ini memakai alamat SERVER, bukan header "Host" yang
 * bisa dipalsukan oleh siapa pun, sehingga request dari luar tidak
 * bisa mengaktifkan mode debug di server produksi.
 */
$alamatServer = (string)($_SERVER['SERVER_ADDR'] ?? '');
$diKomputerSendiri = ($alamatServer !== '' && in_array($alamatServer, ['127.0.0.1', '::1'], true));
define('APP_DEBUG', $LOKASI === 'hosting' ? false : $diKomputerSendiri);
unset($LOKASI, $alamatServer, $diKomputerSendiri);

/* ===== Alur status layanan ===== */
const STATUS_LAYANAN = ['diterima', 'dicuci', 'disetrika', 'siap_diambil', 'selesai'];
const STATUS_LABEL = [
    'diterima'     => 'Diterima',
    'dicuci'       => 'Dicuci',
    'disetrika'    => 'Disetrika',
    'siap_diambil' => 'Siap Diambil',
    'selesai'      => 'Selesai',
];

/* ===== Multi-user & role =====
 * Tiga role dengan hak berbeda:
 *   root   -> Pemilik Sistem: kelola semua akun + akses penuh.
 *   manager-> Manager Toko:   operasional, keuangan, koreksi, pengaturan.
 *   staff  -> Staff / Kasir:  transaksi harian (nota, status, pembayaran).
 */
const ROLE_ROOT    = 'root';
const ROLE_MANAGER = 'manager';
const ROLE_STAFF   = 'staff';

const ROLE_LABEL = [
    'root'    => 'Pemilik (Root)',
    'manager' => 'Manager Toko',
    'staff'   => 'Staff / Kasir',
];

const DAFTAR_ROLE = ['root', 'manager', 'staff'];

/* Matriks izin per menu + aksi kunci. Kunci dipakai oleh helper cekHak()
 * dan Controller::requireHak(). Menu yang tidak terdaftar = tanpa izin
 * (403). Semua izin dicek di sisi server, bukan hanya sembunyi di menu. */
const HAK_AKSES = [
    'dashboard'      => ['root', 'manager', 'staff'],
    'transaksi'      => ['root', 'manager', 'staff'], // lihat, buat, status, bayar, cetak
    'transaksi_edit' => ['root', 'manager'],           // ubah nota
    'transaksi_hapus'=> ['root', 'manager'],           // hapus nota
    'pelanggan'      => ['root', 'manager', 'staff'],  // lihat, tambah, detail
    'pelanggan_edit' => ['root', 'manager'],           // ubah data pelanggan
    'pelanggan_hapus'=> ['root', 'manager'],           // hapus pelanggan
    'paket'          => ['root', 'manager', 'staff'],  // lihat (dipakai saat buat nota)
    'paket_kelola'   => ['root', 'manager'],           // tambah, ubah, hapus paket
    'laporan'        => ['root', 'manager'],
    'pengaturan'     => ['root', 'manager'],
    'pengaturan_keamanan' => ['root'],  // hanya root: batas login, panjang password, masa berlaku sesi
    'karyawan'       => ['root', 'manager'],  // kelola akun karyawan/staff (dibuat manager)
    'akun'           => ['root'],
    'aktivitas'      => ['root', 'manager'],
    'notifikasi'     => ['root', 'manager', 'staff'],
    'notifikasi_kirim' => ['root', 'manager'], // kirim notifikasi (root->toko/staff, manager->staff)
    'profil'         => ['root', 'manager', 'staff'],
];