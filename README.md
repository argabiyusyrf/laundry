# LaundryKu — Aplikasi Manajemen Laundry (MVC)

Aplikasi kasir/manajemen untuk usaha laundry kiloan. Dibangun dengan **PHP murni + MySQL**
dengan arsitektur **MVC custom** — tanpa framework, tanpa Composer, tanpa CDN.
**Bisa jalan 100% offline** di komputer server lokal maupun hosting mana pun.

## Fitur

- **Multi-user 3 role** — root (pemilik), manager (pengelola toko), staff (kasir); hak akses per menu + aksi, menu dinamis, perlindungan 403
- **Minta Akun Toko** — calon pengguna toko mengisi formulir publik di halaman login; pemilik (root) menyetujui/menolak lewat menu **Permintaan Toko** → akun manager langsung aktif dengan kredensial dari pemohon (bukan self-register)
- **Kelola Akun & Pengguna** — halaman khusus root: buat akun manager/staff, atur role & status aktif, reset password, hapus akun (tanpa phpMyAdmin); permintaan akun toko ditinjau di halaman terpisah **Permintaan Toko**
- **Kelola Karyawan** — menu khusus manager & root: daftar staff, tambah akun staff langsung (tanpa lewat root), ubah data, reset password, hapus
- **Log Aktivitas** — catatan siapa melakukan apa (login/logout, transaksi, pembayaran, kelola akun, dsb) lengkap waktu, pelaku, dan IP — untuk audit & supervisi
- **Login aman** — password di-hash (bcrypt), cek status aktif akun, kunci sementara saat terlalu banyak percobaan gagal (batas & durasi dari pengaturan), kebijakan panjang minimum password, sesi bisa diatur kedaluwarsa otomatis
- **Perlindungan CSRF menyeluruh** — setiap `<form method="post">` otomatis diberi field `_csrf` (disuntik `Controller::view()`), dan setiap aksi yang mengubah data menolak POST tanpa token yang cocok (403). Aksi yang mengubah data juga menolak GET (405)
- **Router default-deny** — hanya controller + method yang tertulis di `App::ROUTE` yang boleh dipanggil; modul atau method di luar daftar itu berakhir 404, bukan eksekusi bebas
- **Batas pengiriman formulir publik** — Minta Akun Toko dibatasi 3 permintaan per jam per perangkat, Cek Status dibatasi 20 per 10 menit (disimpan di sesi, tanpa menambah tabel database)
- **Header keamanan** — `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Content-Security-Policy`, dan `Permissions-Policy` dikirim di setiap respons
- **Cookie sesi aman** — cookie sesi memakai flag `HttpOnly`, `SameSite=Lax`, dan `Secure` bila halaman diakses lewat HTTPS
- **Dashboard** — cucian siap diambil, pendapatan hari ini & bulan ini, statistik proses (versi ringkas untuk staff)
- **Manajemen Pelanggan** — tambah, edit, hapus, cari, lihat riwayat transaksi pelanggan
- **Paket Layanan** — harga per kg, estimasi hari, keterangan
- **Transaksi Multi-Layanan** — satu nota beberapa layanan, total otomatis, bisa diubah sebelum lunas
- **Pajak & Diskon** — pajak layanan (%) dihitung otomatis ke total nota, potongan (diskon) per nota dengan batas maksimal, keduanya tampil di detail & struk; diatur dari Pengaturan (default 0 = perilaku nota lama tidak berubah)
- **Alur Status** — Diterima → Dicuci → Disetrika → Siap Diambil → Selesai (sekali klik)
- **Uang Muka / DP** — pelacakan sisa bayar, tandai LUNAS; bisa diwajibkan minimal % dari total (per pengaturan)
- **Cetak Nota/Struk** — halaman khusus cetak, header/footer struk bisa diisi identitas usaha, promo & catatan struk, kontak (WA/IG/email) dan jam operasional tampil otomatis
- **Laporan + Export CSV** — per rentang tanggal, kompatibel Excel
- **Installer otomatis** — `install.php` membuat tabel & data sekali klik; otomatis mendeteksi tempat berjalan (hosting = database bersih, lokal = ikut data contoh)
- **Notifikasi realtime** — lonceng di topbar dengan badge belum-dibaca, panel dropdown, polling otomatis tiap 30 detik; muncul otomatis saat transaksi baru, status berubah, uang muka/pembayaran, dan pelanggan baru; halaman arsip + tandai semua dibaca; **suara notifikasi** (`asset/sound/notification.mp3`) berbunyi saat ada notifikasi baru (setelah pengguna berinteraksi dengan halaman — kebijakan autoplay browser) dan bisa diatur: nyalakan/matikan suara, volume, serta tombol **coba suara** dari Pengaturan → Notifikasi & Suara; **kirim notifikasi** — root dapat mengirim ke toko (manager), staff, atau semua pengguna; manager toko dapat mengirim ke staff; notifikasi bertarget hanya terlihat oleh penerima, dengan nama pengirim tercantum; notifikasi otomatis (transaksi/status/bayar/pelanggan/karyawan) juga bertarget ke pemilik (root & manager) — tidak melebar ke semua pengguna; notifikasi bisa diedit & dihapus (oleh root, atau pengirimnya)
- **Pengaturan Usaha (5 tab)** — Umum & Kontak (identitas, kontak, jam buka–tutup, logo), Struk & Nota (header, promo, footer, catatan struk, prefix nota, pratinjau struk), Keuangan (pajak, diskon maks, DP minimal, satuan berat, mata uang), Keamanan Akun (min. password, sesi, batas login), dan Notifikasi & Suara (suara on/off, volume, coba suara) — semuanya dari menu, tanpa ubah kode
- **Ganti Password** — dari menu Profil, tanpa perlu phpMyAdmin (mengikuti kebijakan min. panjang dari Pengaturan)
- **Halaman error (error boundary)** — 404, 403, 405, 500, dst. tampil cantik & konsisten, detail teknis hanya di mode pengembangan
- **File log otomatis** — semua error tersimpan ke `storage/logs/laundryku-YYYY-MM-DD.log` (dilindungi dari akses web); halaman `inspeksi.php` untuk diagnosis cepat (dilindungi token, hapus setelah dipakai)
- **Design system responsif** — ikon SVG inline (tanpa emoji/CDN), sidebar lipat di desktop, menu off-canvas di HP, tabel scrollable, tata letak berkedalaman (hero, gradien, mikro-interaksi), siap rebrand per klien

## Arsitektur (MVC custom)

```
laundry/
├── index.php               → Front controller (bootstrap + router + autoloader + header keamanan)
├── .htaccess               → pretty URL opsional, pemetaan halaman error, blokir file berisiko
├── install.php             → installer otomatis (dilindungi token, WAJIB DIHAPUS setelah dipakai)
├── inspeksi.php            → halaman diagnosis (PHP, koneksi DB, tabel, log terakhir; dilindungi token, hapus setelah dipakai)
├── database.sql            → cadangan skema + data awal (opsional, alternatif install)
├── asset/                  → style.css (design system) + app.js (offline) + logo/
├── storage/                → log harian & berkas unggahan, seluruhnya .htaccess "deny all"
│   └── .htaccess           → menutup storage beserta seluruh subfoldernya
│
└── app/
    ├── .htaccess           → blokir akses langsung folder app dari web
    ├── config/config.php   → konstanta aplikasi + 2 blok kredensial database (LOKAL / HOSTING) + matriks HAK_AKSES + daftar route
    ├── core/               → Database, App (router), Controller, Model, Autoload, Log
    ├── helpers/helpers.php → fungsi bantu: e(), rupiah(), url(), icon(), flash, badge, cekHak(), logAktivitas(), tampilkanError(), pengaturan(), throttle(), minLengthPassword()
    ├── controllers/        → Auth, Dashboard, Pelanggan, Paket, Transaksi, Laporan, Notifikasi, Pengaturan, Profil, Akun, Permintaan, Karyawan, Aktivitas, Error
    ├── models/             → User, Pelanggan, Paket, Transaksi, Notifikasi, Pengaturan, PermintaanAkun, Aktivitas
    └── views/              → HTML tampilan (partials + per halaman)
```

### Alur request

```
Browser → index.php?r=transaksi/detail/4
        → router (App) mencocokkan "transaksi/detail/4" dengan App::ROUTE
        → cek apakah 4 boleh dipakai sebagai parameter id
        → TransaksiController::detail(4)
        → Transaksi::find(4)  (model, prepared statement PDO)
        → views/transaksi/detail.php  (render HTML)
```

Route dibaca dari parameter `r`: `?r=controller/method/param1/param2`.

Router bersifat **default-deny**: nama controller dan method harus terdaftar di
`App::ROUTE` (`app/core/App.php`). Di luar daftar itu jawabannya 404. Parameter
wajib yang tidak disertakan di URL juga 404 ("Alamat tidak lengkap"), bukan
kesalahan server 500.

Tanda method pada tabel di bawah:

| Tanda | Arti |
|---|---|
| (GET) | hanya boleh dibuka lewat GET; dipasang lewat tautan |
| (POST) | hanya boleh dipanggil lewat POST + token CSRF; dibuka GET mendapat 405 |
| (GET+POST) | GET menampilkan halaman, POST memproses form (pemeriksaan token di cabang POST) |

| Route | Controller / Method | Cara |
|---|---|---|
| `?r=` atau `?r=auth` | AuthController::index | (GET) |
| `?r=login` / `?r=logout` | AuthController::login / logout | (GET+POST) / (POST) |
| `?r=dashboard` | DashboardController::index | (GET) |
| `?r=pelanggan` , `?r=pelanggan/detail/5` | PelangganController::index / detail | (GET) |
| `?r=pelanggan/tambah` , `?r=pelanggan/ubah/5` , `?r=pelanggan/hapus/5` | PelangganController | (POST) |
| `?r=paket` , `?r=paket/detail/5` | PaketController::index / detail | (GET) |
| `?r=paket/tambah` , `?r=paket/ubah/5` , `?r=paket/hapus/5` | PaketController | (POST) |
| `?r=transaksi` , `?r=transaksi/baru` , `?r=transaksi/edit/4` , `?r=transaksi/detail/4` , `?r=transaksi/cetak/4` | TransaksiController | (GET) |
| `?r=transaksi/simpan` , `?r=transaksi/ubah/4` , `?r=transaksi/status/4` , `?r=transaksi/bayar/4` , `?r=transaksi/hapus/4` | TransaksiController | (POST) |
| `?r=laporan` , `?r=laporan/export` | LaporanController | (GET) |
| `?r=notifikasi` , `?r=notifikasi/data` (JSON polling) , `?r=notifikasi/edit/5` , `?r=notifikasi/baca/5` | NotifikasiController | (GET) |
| `?r=notifikasi/kirim` , `?r=notifikasi/ubah/5` , `?r=notifikasi/bacaSemua` , `?r=notifikasi/hapus/5` | NotifikasiController | (GET+POST) / (POST) |
| `?r=pengaturan` | PengaturanController::index | (GET) |
| `?r=pengaturan/simpan` , `?r=pengaturan/hapusLogo` | PengaturanController | (POST) |
| `?r=profil` | ProfilController::index | (GET) |
| `?r=profil/simpan` , `?r=profil/password` | ProfilController | (POST) |
| `?r=akun` , `?r=akun/permintaan` | AkunController::index / permintaan (khusus root) | (GET) |
| `?r=akun/tambah` , `?r=akun/ubah/3` , `?r=akun/reset/3` , `?r=akun/hapus/3` | AkunController | (POST) |
| `?r=akun/setujui-permintaan/4` , `?r=akun/tolak-permintaan/4` | AkunController — tinjau & proses permintaan akun toko | (POST) |
| `?r=karyawan` | KaryawanController::index (root & manager) | (GET) |
| `?r=karyawan/tambah` , `?r=karyawan/ubah/3` , `?r=karyawan/reset/3` , `?r=karyawan/hapus/3` | KaryawanController | (POST) |
| `?r=permintaan` (form minta akun), `?r=permintaan/status` (cek status, form publik) | PermintaanController (tanpa login) | (GET) |
| `?r=permintaan/kirim` | PermintaanController — kirim permintaan & cek status | (POST) |
| `?r=aktivitas` | AktivitasController::index (root & manager) | (GET) |
| `?r=error/kode/404` (atau kode lain) | ErrorController::kode | (GET) |

Catatan tentang bentuk URL:

- Membuka form ubah memakai parameter `?edit=ID` pada halaman daftar
  (mis. `?r=paket&edit=5`), kecuali notifikasi dan transaksi yang punya URL
  form sendiri (`?r=notifikasi/edit/5`, `?r=transaksi/edit/4`).
- Membuka notifikasi dari lonceng tetap memakai `?r=notifikasi/baca/ID` (GET)
  karena tautan yang diklik pembaca akan menandai notifikasi sebagai terbaca.
  Tombol "Tandai Semua Dibaca" memakai form POST, bukan tautan.
- Menyimpan notifikasi memakai `?r=notifikasi/ubah/ID` (POST), mengikuti pola
  modul lain: `.../edit/ID` untuk membuka form, `.../ubah/ID` untuk menyimpan.
- `?r=logout` adalah route logout (bukan `?r=akun/logout`), dan membuat pengguna
  baru adalah `?r=akun/tambah` (bukan `?r=akun/simpan-akun`).
- Parameter `ke=detail` pada `?r=transaksi/status/4` membuat sistem kembali ke
  halaman detail nota setelah status diubah, bukan ke daftar.

> Catatan: di server dengan `mod_rewrite`, URL cantik juga bekerja
> (mis. `transaksi/detail/4` via `.htaccess`). Bila `mod_rewrite` tidak aktif,
> aplikasi memakai `index.php?r=...` — tetap rapi & pasti jalan. Semua
> JavaScript dan tautan internal memakai `LK_BASE` yang dikirim server, sehingga
> base URL benar tanpa perlu menulis `index.php?r=` secara manual.

## Kebutuhan

- Apache + PHP 7.4+ (disarankan PHP 8)
- MySQL / MariaDB
- Teruji dengan XAMPP / Laragon / Apache + MySQL mandiri

## Cara Install

1. Copy folder `laundry` ke `htdocs` server kamu
2. Pastikan MySQL menyala
3. Buka `INSTALL_TOKEN` di `app/config/config.php`, lalu buat token acak
   sendiri, misalnya dengan perintah PowerShell:

   ```powershell
   -join ((48..57) + (65..90) + (97..122) | Get-Random -Count 32 | ForEach-Object { [char]$_ })
   ```

   Salin hasilnya ke konstanta `INSTALL_TOKEN`, menggantikan nilai contoh.
4. Buka **http://localhost/laundry/install.php?token=NILAI_TOKEN_KAMU**
   (tanpa token yang benar, halaman installer menolak dengan 403)
5. Ikuti installer. Installer otomatis mendeteksi tempat berjalan:
   - di **hosting** → database bersih (tanpa data contoh)
   - di **localhost** → ikut data contoh agar dashboard langsung bisa dilihat
6. Klik **Hapus install.php** (wajib demi keamanan), hapus juga `inspeksi.php`
7. Buka **http://localhost/laundry/** lalu login.

> `install.php` dan `inspeksi.php` sama-sama dilindungi `INSTALL_TOKEN`, jadi
> keduanya tidak lagi bisa dipakai orang lain yang tahu alamatnya. Tetap hapus
> keduanya setelah instalasi selesai supaya tidak bergantung pada-keepingan token.

> **Akun bawaan:**
> - `admin` / `admin123` → **root (pemilik sistem)** — akses penuh semua menu + kelola akun
> - Mode **lokal** (install di komputer sendiri) juga membuat akun contoh:
>   `manager` / `manager123` (manager) dan `staff` / `staff123` (kasir) — akun contoh ini bisa dihapus lewat **Akun & Pengguna**
> - Mode **hosting** hanya membuat akun root (bersih, tanpa data contoh)
> - Sebelum ada root, calon toko tidak bisa mendaftar sendiri — pemohon mengisi **Minta Akun Toko** di halaman login, lalu root menyetujui/menolak
> - Segera ganti password root lewat menu **Akun & Pengguna** setelah login pertama

> Alternatif manual: import `database.sql` di phpMyAdmin (database dibuat otomatis).
> Untuk hosting, database dibuat lewat panel host lalu import file SQL ke dalamnya.

## Deploy ke Hosting (FTP)

```text
Host FTP  : ftpupload.net
Port      : 21 (FTP polos, tanpa FTPS)
User      : if0_42820753
Docroot   : /laundryku.kesug.com/htdocs      <- upload ke sini
```

> **Penting:** path di atas yang dipakai. Folder `/htdocs` di akar FTP adalah
> folder bersama dan **bukan** docroot situs LaundryKu.
>
> Password FTP sengaja tidak ditulis di dokumen ini. Simpan di password manager
> atau masukkan saat diminta oleh klien FTP.

Contoh upload satu berkas (PowerShell, tanpa FTPS). Ganti `PASSWORD` dengan
password FTP kamu sendiri:

```powershell
curl.exe -T C:\Apache24\htdocs\laundry\asset\style.css `
  -u if0_42820753:PASSWORD `
  ftp://ftpupload.net/laundryku.kesug.com/htdocs/asset/style.css
```

Berkas yang **tidak** ikut di-upload setelah instalasi selesai:

| Berkas | Alasan |
| --- | --- |
| `install.php` | bisa dijalankan ulang, menampilkan info database |
| `inspeksi.php` | memamerkan versi PHP, modul, dan status koneksi database |
| `doc.docx` | dokumen panduan, hanya tersedia di dalam `laundry.zip` |
| `laundry.zip` | artefak distribusi, bukan bagian aplikasi |

`database.sql`, `README.md`, dan `RENCANA-MULTIUSER.md` juga sebaiknya tidak
dibiarkan di docroot karena bisa diunduh publik. Semuanya tetap tersedia
di dalam `laundry.zip`.

Berkas lain yang juga diblokir `.htaccess` bila pernah ada di server:
`*.sql`, `*.md`, `*.docx`, `*.zip`, `*.ps1`, `*.psb`, `*.sh`, `*.bak`,
`*.log`, `*.ini`, `*.env`, dan berkas berawalan `.` (dotfile).

### Dua hal yang perlu dipastikan di sisi server

Tidak ada pengaturan permission yang perlu diubah manual. Yang perlu dipastikan
hanya dua hal:

1. **Hosting mengizinkan override `.htaccess`.** Bila `AllowOverride None`,
   seluruh aturan proteksi di `.htaccess` diabaikan. LaundryKu tetap berjalan,
   tetapi berkas sensitif bisa diunduh publik. Cara memastikan: buka
   `https://namadomain/laundry/app/config/config.php` di browser — jawabannya
   harus 403, dan **bukan** kode PHP-nya.
2. **Password root sudah diganti** dari `admin123`.

## Multi-User & Role (v2.0)

Sejak v2.0 aplikasi bukan lagi single-admin: ada **3 peran** dengan hak akses
**per menu + aksi kunci**, dikontrol lewat satu matriks di `app/config/config.php`
(`HAK_AKSES`). Sistem tetap **satu outlet** (belum multi-cabang).

## Fitur v2.1

### 1. Minta Akun Toko (bukan self-register)

Calon pengelola toko **tidak bisa mendaftar akun sendiri**. Alurnya:

```
Halaman login → tautan "Minta Akun Toko" → isi formulir publik
   (nama toko, nama pemohon, username & password pilihan, kontak)
        │
        ▼
Permintaan masuk ke root (notifikasi lonceng + menu "Permintaan Toko" di sidebar)
        │
        ├─ DISETUJUI → akun MANAGER dibuat otomatis dengan kredensial pemohon
        │              → pemohon langsung bisa login
        └─ DITOLAK   → beri alasan → pemohon bisa cek status & mengulang
```

- Pemohon bisa mengecek status (Menunggu / Disetujui / Ditolak) lewat form
  "Cek status" di halaman yang sama.
- Username yang sama tidak boleh diminta dua kali (menunggu) atau dipakai akun lain.
- Data permintaan tersimpan di tabel baru `permintaan_akun` (status, catatan tolak,
  siapa pemroses, waktu proses).

### 2. Kelola Karyawan (manager langsung buat staff)

Manager (dan root) membuka menu **Karyawan**: daftar seluruh staff, menambah akun
staff **langsung** (nama, username, password), mengubah data, reset password, dan
menghapus akun — tanpa harus lewat root. Ini "persetujuan toko" untuk karyawan:
manager yang menyetujui, root tidak ikut campur.

### 3. Pengaturan — 5 tab

Halaman Pengaturan memakai satu form dengan lima tab; tab aktif menentukan
field mana yang ikut terkirim, dan tab kembali dibuka setelah penyimpanan
berhasil.

| Tab | Isi | Kunci pengaturan |
|---|---|---|
| **Umum** | nama usaha, telepon/WA, Instagram, email, alamat, jam buka–tutup, logo (unggah / hapus) | `nama_usaha`, `telepon_usaha`, `whatsapp`, `instagram`, `email_usaha`, `alamat_usaha`, `jam_buka`, `jam_tutup`, `logo` |
| **Struk** | header struk, promo struk, footer struk, catatan struk, prefix nota, pratinjau struk | `header_struk`, `promo_struk`, `footer_struk`, `catatan_struk`, `prefix_nota` |
| **Keuangan** | pajak layanan (%), batas maks. diskon (%), DP minimal (%), satuan berat, simbol mata uang | `pajak_persen`, `diskon_maks_persen`, `dp_minimal_persen`, `satuan_berat`, `mata_uang` |
| **Keamanan** | min. panjang password, jeda sesi kedaluwarsa (menit, 0 = tanpa batas), batas percobaan login gagal | `min_password`, `sesi_kedaluwarsa_menit`, `batas_login_gagal` |
| **Notifikasi** | suara notifikasi nyala/mati, volume (0–100%), tombol coba suara | `notif_suara`, `notif_volume` |

Total **24 kunci**. Kunci yang tidak ikut terkirim tidak ditimpa, sehingga
nilai lama tetap aman. Kunci keamanan akun (`min_password`,
`sesi_kedaluwarsa_menit`, `batas_login_gagal`) hanya boleh diubah oleh root —
manager biasa tidak ikut memprosesnya meski sengaja mengirimkannya.

Yang langsung terpakai di sistem:

- **Pajak** dihitung otomatis: `subtotal × pajak%` ditambahkan ke total nota
  (tampil di ringkasan, detail, dan struk).
- **Diskon** per nota: nilai maksimal dibatasi `diskon_maks_persen`% dari subtotal;
  default 0% = tanpa batas.
- **DP minimal**: bila diisi > 0, nota wajib uang muka minimal `dp_minimal_persen`%
  dari total (ditolak bila kurang).
- **Prefix nota** dipakai generator `no_nota` (`PREFIX-YYYYMMDD-XXX`); huruf
  besarnya dibersihkan dari karakter selain huruf dan angka, maksimal 8 karakter,
  dan tetap memakai nilai bawaan `LND` bila dikosongkan.
- **Satuan & mata uang** dipakai di seluruh tampilan (form nota, daftar, detail,
  laporan, paket, struk) — tidak ada lagi teks `kg`/`Rp` yang diketik manual.
- **Keamanan akun**: login terkunci sementara (5 menit) setelah melewati
  `batas_login_gagal`; password wajib ≥ `min_password` karakter di semua form
  pendaftaran/ganti; sesi otomatis logout bila `sesi_kedaluwarsa_menit` > 0.

> Default `pajak_persen = 0`, `diskon = 0`, `dp_minimal_persen = 0` sehingga
> perilaku nota lama tidak berubah; baris pajak/diskon hanya tampil bila nilainya > 0.

### 4. Skema database v2.1

Sembilan tabel: `users`, `pelanggan`, `paket`, `transaksi`, `transaksi_detail`,
`notifikasi`, `pengaturan`, `permintaan_akun`, `log_aktivitas`.

- `users` + `role`, `aktif`, `last_login`, `gagal_login`, `terkunci_hingga`
- `transaksi` + `diskon`, `pajak` (DECIMAL(12,2), default 0)
- Tabel baru `permintaan_akun` (jenis, nama toko/pemohon, username, password_hash,
  kontak, status, catatan, `diproses_oleh` → users, waktu proses)
- Tabel baru `log_aktivitas` (waktu, pelaku `user_id`, aksi, keterangan, IP)
- `pengaturan` + 16 kunci baru (total **24 kunci**) — `install.php` melakukan migrasi
  idempotent pada database lama secara otomatis

### Peran & Tugas

| Role | Label | Siapa | Inti Tugas |
|---|---|---|---|
| `root` | Pemilik Sistem | Penjual/pengembang yang mengatur siapa yang boleh memakai | Kelola semua akun + akses penuh semua menu |
| `manager` | Manager Toko | Pengelola toko (klien) | Operasional, keuangan, koreksi nota, laporan, pengaturan toko, lihat log aktivitas |
| `staff` | Staff / Kasir | Karyawan di counter | Melayani transaksi harian: nota, status cucian, pembayaran, daftar pelanggan baru |

```
root (Pemilik Sistem) —— membuat & mengontrol SEMUA akun
  │  akses penuh + kelola akun
  ▼
manager (Manager Toko) —— operasional + keuangan + koreksi + laporan
  │  membuat nota/hapus berdasarkan hak; mengawasi staff
  ▼
staff (Kasir) —— melayani counter: nota, status, pembayaran, pelanggan baru
```

Setiap request dicek **dua lapis**: menu yang tidak diizinkan disembunyikan dari
sidebar, dan route tetap divalidasi server-side (`requireHak()`); bila dipaksa,
muncul halaman **403 — Akses Ditolak**.

### Matriks Hak Akses

| Menu / Aksi | root | manager | staff |
|---|:---:|:---:|:---:|
| Dashboard (ringkasan) | ✓ | ✓ | ✓ (versi ringkas, tanpa nilai keuangan) |
| Pelanggan: daftar/cari/detail/tambah | ✓ | ✓ | ✓ |
| Pelanggan: ubah / hapus | ✓ | ✓ | ✗ |
| Paket: lihat daftar | ✓ | ✓ | ✓ (untuk buat nota) |
| Paket: tambah / ubah / hapus | ✓ | ✓ | ✗ |
| Transaksi: lihat / buat nota / status / bayar / cetak | ✓ | ✓ | ✓ |
| Transaksi: ubah / hapus nota | ✓ | ✓ | ✗ (koreksi oleh manager) |
| Laporan + export CSV | ✓ | ✓ | ✗ |
| Pengaturan usaha (5 tab: identitas, struk/nota, keuangan, keamanan, notifikasi & suara) | ✓ | ✓ | ✗ |
| **Karyawan** (tambah staff / ubah / reset / hapus) | ✓ | ✓ | ✗ |
| **Permintaan Akun Toko** (setujui / tolak) | ✓ | ✗ | ✗ |
| **Akun & Pengguna** (tambah/ubah role/aktif/reset/hapus) | ✓ | ✗ | ✗ |
| **Log Aktivitas** | ✓ | ✓ | ✗ |
| Notifikasi (lonceng, baca) | ✓ | ✓ | ✓ |
| Kirim Notifikasi (root → toko/staff/semua, manager → staff) | ✓ | ✓ (hanya ke staff) | ✗ |
| Profil (nama tampilan, ganti password sendiri) | ✓ | ✓ | ✓ |

### Kerja Sehari-hari (Workflow Ringkas)

- **W1 — Membuat akun (root):** Menu Akun → Tambah Akun → isi nama, username,
  password awal, pilih role → akun langsung aktif. Karyawan keluar → root
  **nonaktifkan** atau **hapus** akunnya; lupa password → root **reset** (tanpa phpMyAdmin).
- **W2 — Login:** sistem cek status aktif + password bcrypt, role masuk ke
  session, sidebar menampilkan hanya menu yang boleh, login/logout tercatat di log aktivitas.
- **W3 — Transaksi harian (staff):** daftar/cari pelanggan → buat nota
  (layanan + berat + DP) → cetak nota → update status → terima pembayaran.
- **W4 — Koreksi nota (manager):** staff melaporkan nota salah → manager ubah
  (sebelum lunas) atau hapus (nota dobel) → terekam di log aktivitas.
- **W5 — Keuangan (manager):** buka Laporan per rentang tanggal → ringkasan +
  export CSV → cek Log Aktivitas bila ada selisih.

### Keamanan Multi-User

- Menu disembunyikan **dan** route dicek server-side — URL manual tidak bisa membobol
- Staff tidak bisa mengubah role/status akun sendiri; root tidak bisa menghapus akun
  diri sendiri atau mematikan akun root terakhir (sistem menjaga jangan sampai terkunci keluar)
- Akun nonaktif langsung ditolak saat login
- Semua aksi penting tercatat di tabel `log_aktivitas` (waktu, pelaku, IP)
- `users` bertambah kolom `role`, `aktif`, `last_login`; tabel baru `log_aktivitas`
  (migrasi otomatis oleh installer — admin lama menjadi `root`)

## Konfigurasi

Semua konfigurasi di satu tempat: **`app/config/config.php`**

Aplikasi punya **dua blok kredensial database** dan memilihnya otomatis
berdasarkan alamat tempat dijalankan:

| Tempat aplikasi jalan | Blok yang dipakai   | Dikenali dari |
|---|---|---|
| Komputer sendiri | Blok **LOKAL** | host `localhost` / `127.0.0.1` |
| Hosting / domain | Blok **HOSTING** | selain localhost (mis. `subdomainkamu.kesug.com`) |

```php
/* Kredensial #1 — LOKAL (localhost / XAMPP / Laragon) */
// host/name/user/pass/port diisi sesuai MySQL di komputer kamu sendiri

/* Kredensial #2 — HOSTING (InfinityFree / hosting lain)
 * 5 nilai di bawah diambil dari panel host → menu "MySQL Databases":
 *   host = hostname MySQL          (mis. sqlXXX.infinityfree.com)
 *   name = nama database           (mis. if0_XXXXXXXX_laundryku)
 *   user = user MySQL              (mis. if0_XXXXXXXX)
 *   pass = password MySQL          (isi sendiri — jangan dibagikan)
 *   port = 3306 (default) */
define('DB_PORT', '3306');
```

> **Catatan keamanan**: isi kredensial hanya di `config.php`. Jangan pernah
> menaruh atau membagikan nilai aslinya di README, dokumentasi, atau file lainnya.

- Bila perlu memaksa salah satu mode, set variabel `$LOKASI` di atasnya menjadi
  `'lokal'` atau `'hosting'` (nilai `''` = otomatis).
- **APP_DEBUG otomatis**: `false` saat di hosting (detail error disembunyikan),
  `true` hanya di localhost.
- Bila port MySQL server berbeda (mis. XAMPP memakai `3307`), cukup ubah
  nilai `port` pada blok kredensial yang bersangkutan.

## Log & Diagnosis

- **File log**: error/peringatan aplikasi tersimpan otomatis ke
  `storage/logs/laundryku-YYYY-MM-DD.log` (satu file per hari).
  Seluruh folder `storage/` beserta subfoldernya dilindungi `.htaccess`
  (`Require all denied`), sehingga log maupun berkas unggahan tidak bisa
  diakses lewat browser.
- **inspeksi.php**: halaman diagnosis sekali-pakai untuk mengetahui versi PHP,
  modul yang aktif, status koneksi database, daftar tabel, dan baris log
  terakhir — berguna saat ada kendala di hosting. Dilindungi `INSTALL_TOKEN`
  (`inspeksi.php?token=...`), dan **hapus setelah selesai**.

### Rebrand / kustomisasi tampilan

- Warna & radius aplikasi: ganti token di bagian `:root` pada `asset/style.css`
- Ikon: daftar ikon SVG tersedia di fungsi `icon()` pada `app/helpers/helpers.php`
- Identitas usaha (nama, alamat, telepon, logo): menu **Pengaturan** di aplikasi
  (tersimpan di tabel `pengaturan`, tampil di struk cetak)

## Catatan Keamanan Produk

- Password default `admin123` **wajib diganti** sebelum diserahkan ke klien
  (menu **Profil → Ganti Password**, atau root set ulang lewat **Akun & Pengguna**)
- Ganti `INSTALL_TOKEN` di `app/config/config.php` menjadi token acak sebelum
  aplikasi dipakai; `install.php` dan `inspeksi.php` harus dihapus setelah instalasi
- Semua query memakai **prepared statement** (PDO) — bebas SQL injection
- Output di-escape dengan `htmlspecialchars` — bebas XSS
- Setiap `<form method="post">` membawa token `_csrf`; aksi ubah/hapus hanya
  berjalan lewat POST dengan token yang cocok (403 bila tidak)
- Aksi ubah/hapus menolak permintaan GET (405), sehingga tidak bisa dipicu lewat
  alamat yang diklik atau di-bookmark
- Router hanya menerima controller + method yang terdaftar di `App::ROUTE`;
  sisanya 404
- Header keamanan dikirim di setiap respons: `X-Content-Type-Options: nosniff`,
  `X-Frame-Options: DENY`, `Referrer-Policy: same-origin`,
  `Content-Security-Policy` (hanya sumber sendiri), `Permissions-Policy`
- Cookie sesi memakai `HttpOnly`, `SameSite=Lax`, dan `Secure` bila HTTPS
- Folder `app/` dan `storage/` dilindungi dari akses web lewat `.htaccess`,
  begitu juga berkas berisiko (`.sql`, `.md`, `.zip`, `.log`, `.bak`, dotfile)
- Ekspor CSV dinetralkan terhadap formula injection (sel yang diawali
  `=`, `+`, `-`, atau `@` diberi awalan teks)
- Pencarian memakai `LIKE` dengan `ESCAPE`, sehingga `%` dan `_` pada kata kunci
  tidak ikut ditafsirkan sebagai wildcard
- Kredensial database hanya di `config.php` — jangan dibagikan lewat dokumentasi
- Hak akses role divalidasi di server (`requireHak`), bukan hanya sembunyi menu

## Tips Menjual ke Klien

- Isi identitas usaha klien lewat menu **Pengaturan** (nama, alamat, telepon, logo) — tanpa menyentuh kode
- Buatkan akun **manager** untuk klien (menu Akun & Pengguna) — klien mengelola toko, kamu tetap pemegang akun root
- Jelaskan alur kerja 3 role (root/manager/staff) — cocok untuk toko dengan beberapa karyawan counter
- Ubah warna brand cukup lewat token CSS di `asset/style.css`
- Tawarkan tambahan (rilis berikutnya): laporan per kasir, grafik pendapatan, aplikasi customer cek status cucian, multi-cabang
- Karena MVC, menambah fitur = tambah method di Controller + method di Model + halaman View — tanpa menyentuh logika lain