<?php
/**
 * ============================================================
 *  Helper global LaundryKu (fungsi-fungsi kecil yang dipakai
 *  di seluruh Controller & View).
 * ============================================================
 */

/* ---------- URL ---------- */

/** Path dasar aplikasi, mis. "/laundry" atau "" bila di domain root. */
function base_path(): string
{
    return BASE_REL;
}

/**
 * Membuat URL ke route: /laundry/index.php?r=dashboard&...
 *
 * Nama route disanitasi supaya karakter aneh (termasuk CR/LF) tidak
 * bisa masuk ke query string maupun ke header Location.
 */
function url(string $route = '', array $params = []): string
{
    $route = bersihkanRoute($route);
    $out = BASE_REL . '/index.php?r=' . $route;
    if ($params) {
        $out .= '&' . http_build_query($params);
    }
    return $out;
}

/**
 * Sanitasi nama route: hanya izinkan huruf, angka, garis bawah, garis,
 * garis miring, dan titik. Semua karakter lain dibuang.
 */
function bersihkanRoute(string $route): string
{
    return (string)preg_replace('/[^A-Za-z0-9_\/\-.]/', '', $route);
}

/**
 * Pastikan URL tujuan berada di dalam aplikasi, bukan situs luar.
 * Menolak "//evil.tld" (protocol-relative) dan URL ber-skema, sehingga
 * field "kembali" tidak bisa dipakai untuk open redirect.
 */
function urlInternal(string $tujuan): bool
{
    if ($tujuan === '' || strpos($tujuan, '://') !== false) {
        return false;
    }
    if (strpos($tujuan, '//') === 0 || strpos($tujuan, '/\\') === 0) {
        return false;
    }
    $parts = parse_url($tujuan);
    if (!is_array($parts) || isset($parts['host']) || isset($parts['scheme'])) {
        return false;
    }
    return isset($parts['path']) && strpos($parts['path'], '/') === 0;
}

/* ---------- Token keamanan formulir (anti-CSRF) ---------- */

/**
 * Token unik per sesi untuk melindungi aksi POST dari pemicu lintas situs.
 * Disimpan di session agar tetap valid saat halaman di-refresh.
 */
function csrfToken(): string
{
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        try {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            $_SESSION['_csrf'] = hash('sha256', uniqid((string)mt_rand(), true));
        }
    }
    return $_SESSION['_csrf'];
}

/** Hidden input yang harus ada di setiap form method="post". */
function csrfInput(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrfToken()) . '">';
}

/** Periksa token yang dikirim (_csrf) cocok dengan token sesi. */
function csrfValid(?string $token = null): bool
{
    if ($token === null) {
        $token = (string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    }
    $sessi = (string)($_SESSION['_csrf'] ?? '');
    return $sessi !== '' && $token !== '' && hash_equals($sessi, $token);
}

/**
 * Suntikkan hidden input token ke setiap <form method="post"> yang belum
 * punya token. Dipanggil sekali saat halaman dirakit, jadi setiap view
 * otomatis terlindungi tanpa menulis token satu per satu.
 */
function csrfSuntik(string $html): string
{
    if (stripos($html, '<form') === false) {
        return $html;
    }
    $needle = '<input type="hidden" name="_csrf"';
    return (string)preg_replace_callback(
        '/<form\b([^>]*)>/i',
        function ($m) use ($needle) {
            $attrs = $m[1];
            if (!preg_match('/method\s*=\s*["\']?post["\']?/i', $attrs)) {
                return $m[0];
            }
            if (stripos($m[0], $needle) !== false || stripos($m[0], '_csrf') !== false) {
                return $m[0];
            }
            return $m[0] . '<input type="hidden" name="_csrf" value="' . e(csrfToken()) . '">';
        },
        $html
    );
}

/** URL asset statis (css/js). */
function asset_url(string $file): string
{
    return BASE_REL . '/asset/' . $file;
}

/**
 * Action untuk form method="get"/"post" tanpa query string.
 * Route dikirim lewat hidden input "r" — penting untuk form GET,
 * karena browser mengganti query action dengan data form saat submit.
 */
function form_action(): string
{
    return BASE_REL . '/index.php';
}

/* ---------- Output & format ---------- */

/** Escape output HTML dengan aman. */
function e($nilai): string
{
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

/**
 * Bungkus kata kunci pencarian menjadi pola LIKE yang aman.
 *
 * Karakter wildcard LIKE (% dan _) dari pengguna di-escape dengan garis
 * miring terbalik, sehingga "%" yang diketik pengguna tidak berubah jadi
 * wildcard yang membuat seluruh tabel dipindai. MySQL memakai "\" sebagai
 * karakter escape bawaan, jadi tidak perlu klausa ESCAPE tambahan.
 */
function polaLike(string $kataKunci): string
{
    return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $kataKunci) . '%';
}

/** Format angka menjadi rupiah (simbol mengikuti pengaturan "mata_uang"). */
function rupiah($jumlah): string
{
    $simbol = trim((string)pengaturan('mata_uang', 'Rp'));
    if ($simbol === '') {
        $simbol = 'Rp';
    }
    return $simbol . number_format((float)$jumlah, 0, ',', '.');
}

/** Tanggal Indonesia: 24 Sep 2026 (opsional dengan jam). */
function tglIndo($tanggal, bool $denganJam = false): string
{
    $t = strtotime((string)$tanggal);
    if (!$t) {
        return '-';
    }
    $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $res = date('d', $t) . ' ' . $bulan[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
    if ($denganJam) {
        $res .= ' ' . date('H:i', $t);
    }
    return $res;
}

/* ---------- Flash message ---------- */

function setFlash(string $jenis, string $pesan): void
{
    $_SESSION['flash'][] = ['jenis' => $jenis, 'pesan' => $pesan];
}

function getFlashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Badge ---------- */

function badgeStatus(string $status): string
{
    $label = STATUS_LABEL[$status] ?? $status;
    $kelas = 'badge-' . e(str_replace('_', '-', $status));
    return '<span class="badge ' . $kelas . '">' . e($label) . '</span>';
}

/* ---------- Pembatas laju (anti spam) ---------- */

/**
 * Pembatas laju sederhana berbasis sesi.
 *
 * Dipakai untuk halaman/form publik yang bisa dipanggil siapa saja tanpa
 * login, supaya tidak bisa dipakai membanjiri database atau memindai data
 * dengan ribuan request (mis. spam permintaan akun, cek status bertubi-tubi).
 *
 * @param string $kunci  nama alat ukur, mis. "permintaan-kirim"
 * @param int    $maks   jumlah permintaan yang boleh dalam $detik terakhir
 * @param int    $detik  panjang jendela waktu dalam detik
 * @return bool true = masih boleh, false = sudah melewati batas
 */
function throttle(string $kunci, int $maks, int $detik): bool
{
    $kunci   = preg_replace('/[^a-z0-9_]/', '', strtolower($kunci));
    $sekarang = time();
    $riwayat  = $_SESSION['throttle'][$kunci] ?? [];
    $riwayat  = array_values(array_filter(
        (array)$riwayat,
        function ($t) use ($sekarang, $detik) {
            return is_numeric($t) && ($sekarang - (int)$t) < $detik;
        }
    ));

    if (count($riwayat) >= $maks) {
        $_SESSION['throttle'][$kunci] = $riwayat;
        return false;
    }

    $riwayat[] = $sekarang;
    $_SESSION['throttle'][$kunci] = $riwayat;
    return true;
}

/* ---------- Sesi ---------- */

/**
 * Akhiri sesi dengan benar: kosongkan data, hapus cookie sesi di browser,
 * lalu buat sesi baru dengan id berbeda. Dipakai saat logout manual maupun
 * saat sesi kedaluwarsa, supaya id lama tidak bisa dipakai ulang.
 */
function logoutSesi(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => (bool)$p['secure'],
            'httponly' => (bool)$p['httponly'],
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

function badgeBayar(string $status): string
{
    $label = $status === 'lunas' ? 'Lunas' : 'Belum Lunas';
    $kelas = $status === 'lunas' ? 'badge-lunas' : 'badge-belum';
    return '<span class="badge ' . $kelas . '">' . $label . '</span>';
}

/* ---------- Ikon SVG inline (offline, tanpa CDN) ---------- */

/**
 * Ikon SVG stroke 24×24 (gaya Feather/Lucide, data path statis).
 * Dipakai menggantikan emoji supaya tampilan lebih profesional.
 *
 * @param string $nama   Nama ikon (lihat registry di bawah).
 * @param int    $ukuran Ukuran dalam piksel.
 * @return string  Elemen <svg> inline, atau '' bila nama tidak dikenal.
 */
function icon(string $nama, int $ukuran = 18): string
{
    $ikon = [
        // Navigasi
        'dashboard'  => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'transaksi'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'plus'       => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'pelanggan'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'paket'      => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.83z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'laporan'    => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
        'keluar'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'kalender'   => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'cari'       => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',

        // Umum
        'user'       => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'pengguna'   => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'riwayat'    => '<path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><polyline points="12 7 12 12 15 15"/>',
        'kunci'      => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'siap'       => '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'proses'     => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
        'uang'       => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'kartu'      => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
        'tim'        => '<path d="M8.11 11.85a5 5 0 1 1 7.78 0"/><path d="M2 19a5 5 0 0 1 9-2.6"/><path d="M15.44 16.4A5 5 0 0 1 22 19"/>',

        // Aksi
        'printer'    => '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'hapus'      => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>',
        'edit'       => '<path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>',
        'simpan'     => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',
        'kembali'    => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
        'atas'       => '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>',
        'unduh'      => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'tutup'      => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'chevron'    => '<polyline points="6 9 12 15 18 9"/>',

        // Status & info
        'check'      => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'peringatan' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'info'       => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',

        // Identitas & layanan
        'logo'       => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>',
        'layanan'    => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
        'truk'       => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'jam'        => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'pengaturan' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',

        // Detail & data diri
        'lihat'      => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'telepon'    => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'peta'       => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'catatan'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',

        // Notifikasi
        'lonceng'    => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'lonceng_aktif' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/><circle cx="19" cy="4" r="2.6" fill="currentColor" stroke="none"/>',

        // Fitur baru v2.1: toko, karyawan, pajak/diskon, keamanan
        'toko'       => '<path d="M3 9l1-5h16l1 5"/><path d="M4 9v12h16V9"/><path d="M9 21v-7h6v7"/><path d="M3 9h18"/>',
        'karyawan'   => '<path d="M2 18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1z"/><path d="M10 10V5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5"/><path d="M4 15v-3a6 6 0 0 1 6-6"/><path d="M14 6a6 6 0 0 1 6 6v3"/>',
        'diskon'     => '<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        'koin'       => '<circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/>',
        'keamanan'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'email'      => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        'kirim'      => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
    ];

    // Alias nama supaya pemakaian bebas
    $alias = [
        'nota'   => 'transaksi',
        'daftar' => 'transaksi',
        'berat'  => 'layanan',
        'sampai' => 'siap',
        'dolar'  => 'uang',
        'detail' => 'lihat',
        'cetak'  => 'printer',
        'note'   => 'catatan',
        'telp'   => 'telepon',
        'alamat' => 'peta',
        'mata'   => 'lihat',
        'setelan' => 'pengaturan',
        'struk'  => 'printer',
        'kontak' => 'telepon',
        'jamkerja' => 'jam',
        'whatsapp' => 'telepon',
        'ig'     => 'user',
        'tag'    => 'paket',
        'pajak'  => 'diskon',
        'saldo'  => 'koin',
    ];
    if (isset($alias[$nama])) {
        $nama = $alias[$nama];
    }
    if (!isset($ikon[$nama])) {
        return '';
    }

    return '<svg class="ico" width="' . $ukuran . '" height="' . $ukuran
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
        . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $ikon[$nama] . '</svg>';
}

/**
 * Render halaman error (error boundary) dengan kode HTTP yang sesuai.
 * Dipanggil oleh router (404), front controller (500), dan ErrorController.
 *
 * @param int    $kode   Kode status HTTP: 400, 401, 403, 404, 405, 500, 502, 503, dst.
 * @param string $pesan  Pesan khusus (opsional; fallback ke teks bawaan).
 * @param string $detail Detail teknis — hanya dirender bila APP_DEBUG=true.
 * @param string $judul  Judul khusus (opsional).
 */
function tampilkanError(int $kode, string $pesan = '', string $detail = '', string $judul = ''): void
{
    http_response_code($kode);

    /* Header keamanan dasar */
    header('X-Content-Type-Options: nosniff');

    /* Catat ke file log: pesan yang tampil di layar boleh disembunyikan
       (mode produksi), tapi pemeriksaan error tetap dilakukan lewat file log. */
    if ($kode >= 500) {
        \App\Core\Log::tulis('ERROR', 'HTTP ' . $kode . ($pesan !== '' ? ' — ' . $pesan : '') . ($detail !== '' ? ' | ' . $detail : ''));
    }

    $file = VIEW_PATH . '/errors/index.php';
    if (is_file($file)) {
        require $file;
    } else {
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">';
        echo '<title>' . (int)$kode . ' — Kesalahan</title></head>';
        echo '<body style="font-family:sans-serif;background:#0f172a;color:#f1f5f9;display:grid;place-items:center;min-height:100vh;margin:0">';
        echo '<div style="text-align:center;padding:24px"><div style="font-size:64px;font-weight:800;color:#818cf8">' . (int)$kode . '</div>';
        echo '<p style="color:#cbd5e1">' . e($pesan !== '' ? $pesan : 'Terjadi kesalahan.') . '</p>';
        echo '<p><a href="' . e(BASE_REL . '/') . '" style="color:#a5b4fc">Kembali ke Beranda</a></p></div></body></html>';
    }
    exit;
}

/**
 * Ambil nilai pengaturan aplikasi (identitas usaha, catatan nota, dll).
 * Baca tabel `pengaturan` lewat model Pengaturan; hasil di-cache per request.
 */
function pengaturan(string $kunci, string $default = ''): string
{
    static $nilai = null;
    if ($nilai === null) {
        try {
            $nilai = (new \App\Models\Pengaturan())->semua();
        } catch (\Throwable $e) {
            $nilai = [];
        }
    }
    return isset($nilai[$kunci]) && $nilai[$kunci] !== '' ? (string)$nilai[$kunci] : $default;
}

/* ---------- Pengaturan aplikasi ---------- */

/**
 * Panjang minimum password yang diwajibkan, mengikuti pengaturan
 * "min_password" (menu Pengaturan -> Keamanan Akun). Default 6 karakter.
 */
function minLengthPassword(): int
{
    $min = (int)pengaturan('min_password', '6');
    return ($min >= 4 && $min <= 64) ? $min : 6;
}

/* ---------- Notifikasi ---------- */

/**
 * Buat notifikasi in-app dengan aman (gagal DB tidak menghentikan alur utama).
 */
function notifBuat(string $jenis, string $judul, string $pesan, string $tautan = '', ?string $pengirim = null): void
{
    try {
        (new \App\Models\Notifikasi())->buat($jenis, $judul, $pesan, $tautan, $pengirim);
    } catch (\Throwable $e) {
        // Jangan sampai notifikasi menggagalkan operasi utama (transaksi, dll).
    }
}

/**
 * Kirim notifikasi BERTARGET hanya ke daftar id pengguna tertentu
 * (mis. root & manager saat transaksi baru) supaya tidak melebar
 * ke setiap pengguna lainnya. Gagal DB tidak menggagalkan operasi utama.
 */
function notifKirimKe(array $userIds, string $jenis, string $judul, string $pesan, string $tautan = '', ?string $pengirim = null): void
{
    $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
    if (!$userIds) {
        return;
    }
    try {
        $m = new \App\Models\Notifikasi();
        foreach ($userIds as $id) {
            $m->kirimKe($id, $pengirim ?? '', $judul, $pesan, $jenis, $tautan);
        }
    } catch (\Throwable $e) {
        // Abaikan; notifikasi tidak boleh menggagalkan operasi utama.
    }
}

/**
 * Ringkasan notifikasi untuk topbar (badge + daftar cepat).
 * Dihitung sekali per request, diamankan bila DB belum siap.
 */
function notifRingkas(int $limit = 6, ?int $userId = null): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = ['belum' => 0, 'list' => []];
    try {
        $m = new \App\Models\Notifikasi();
        $cache = [
            'belum' => $m->belumDibaca($userId),
            'list'  => $m->terbaru($limit, $userId),
        ];
    } catch (\Throwable $e) {
        // DB belum siap -> tampil kosong tanpa error.
    }
    return $cache;
}

/** Nama ikon sesuai jenis notifikasi. */
function notifIkonJenis(string $jenis): string
{
    $peta = [
        'transaksi_baru' => 'transaksi',
        'status'         => 'proses',
        'pembayaran'     => 'uang',
        'pelanggan_baru' => 'pelanggan',
        'pesan'          => 'kirim',
        'info'           => 'info',
    ];
    return $peta[$jenis] ?? 'info';
}

/**
 * Badge "ada hal baru" untuk menu sidebar (selain Notifikasi).
 * Menghitung sekali per request; aman bila tabel belum tersedia.
 *   menuBadge('permintaan')    -> permintaan akun toko yang menunggu (root)
 *   menuBadge('transaksi_siap')-> jumlah cucian menunggu diambil
 */
function menuBadge(string $kunci): int
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $role = userRole();
            if ($role === 'root') {
                $cache['permintaan'] = (new \App\Models\PermintaanAkun())->jumlahMenunggu();
            }
            if (in_array($role, HAK_AKSES['transaksi'] ?? [], true)) {
                $cache['transaksi_siap'] = (new \App\Models\Transaksi())->jumlahSiapDiambil();
            }
        } catch (\Throwable $e) {
            // DB belum siap -> badge kosong.
        }
    }
    return (int)($cache[$kunci] ?? 0);
}

/** Nilai badge dengan batas tampilan "99+". */
function badgeNilai(int $n): string
{
    return $n > 99 ? '99+' : (string)max(0, $n);
}

/** Waktu relatif dalam Bahasa Indonesia ("baru saja", "5 mnt lalu", dst). */
function waktuRelatif(string $waktu): string
{
    $ts = strtotime($waktu);
    if (!$ts) {
        return '-';
    }

    $selisih = time() - $ts;
    if ($selisih < 60) {
        return 'baru saja';
    }
    if ($selisih < 3600) {
        return (int)floor($selisih / 60) . ' mnt lalu';
    }
    if ($selisih < 86400) {
        return (int)floor($selisih / 3600) . ' jam lalu';
    }
    if ($selisih < 604800) {
        return (int)floor($selisih / 86400) . ' hari lalu';
    }
    return tglIndo(date('Y-m-d', $ts));
}

/* ---------- Multi-user & role ---------- */

/** Role user yang sedang login ('' bila belum login). */
function userRole(): string
{
    return (string)($_SESSION['user']['role'] ?? '');
}

/** Label Indonesia untuk role (untuk tampilan). */
function roleLabel(string $role): string
{
    return ROLE_LABEL[$role] ?? $role;
}

/** Badge role siap render (root/manager/staff dengan warna berbeda). */
function badgeRole(string $role): string
{
    $kelas = in_array($role, DAFTAR_ROLE, true) ? 'badge-role-' . $role : 'badge-role-lain';
    return '<span class="badge ' . $kelas . '">' . e(roleLabel($role)) . '</span>';
}

/**
 * Cek apakah role user yang sedang login memiliki izin pada menu/aksi.
 * Matriks izin didefinisikan di config.php -> HAK_AKSES.
 */
function cekHak(string $menu): bool
{
    $role = userRole();
    if ($role === '') {
        return false;
    }
    $izin = HAK_AKSES[$menu] ?? [];
    return in_array($role, $izin, true);
}

/**
 * Catat aktivitas user ke tabel log_aktivitas (siapa, aksi, keterangan, IP).
 * Dipanggil di controller setelah aksi berhasil. Aman: bila DB gagal,
 * operasi utama tetap berjalan (kesalahan dicatat diam-diam).
 */
function logAktivitas(string $aksi, string $keterangan = ''): void
{
    try {
        (new \App\Models\Aktivitas())->catat($aksi, $keterangan);
    } catch (\Throwable $e) {
        // Log aktivitas tidak boleh menghentikan operasi utama.
    }
}