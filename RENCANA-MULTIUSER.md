# RENCANA MULTI-USER — LaundryKu

Dokumen perencanaan & implementasi fitur **multi-user** aplikasi LaundryKu
(kasir/manajemen laundry, PHP murni + MySQL, MVC custom).

- **Bab 1–9**: rencana awal multi-user yang terwujud di **v2.0** (3 role + hak akses matriks).
- **Bab 10**: **Addendum v2.1** — permintaan akun toko, kelola karyawan, pengaturan 5 tab,
  beserta dampaknya pada skema database, matriks hak, dan langkah deploy.
- **Bab 11**: **Addendum v2.2** — pengujian ulang menyeluruh beserta perbaikan keamanan
  (CSRF terpusat, router default-deny, rate limit formulir publik, header keamanan).

Status dokumen: **TERAPKAN** (v2.0 + v2.1 + v2.2).

---

## Daftar Isi

1. Latar Belakang & Tujuan
2. Peran Pengguna (3 Role)
3. Matriks Hak Akses (v2.0)
4. Skema Database & Migrasi
5. Alur Kerja (Workflow)
6. Keamanan Multi-User
7. Strategi Pengujian
8. Deploy & Instalasi
9. Rencana Lanjutan (Roadmap)
10. **Addendum v2.1** (10.1–10.5)
11. **Addendum v2.2** — Pengujian Ulang & Perbaikan Keamanan (11.1–11.5)

---

## Bab 1. Latar Belakang & Tujuan

LaundryKu awalnya aplikasi admin tunggal (satu akun, satu toko). Kebutuhan nyata di lapangan:

- Pemilik sistem (developer/penjual) menyerahkan aplikasi ke **klien** yang mengelola toko.
- Klien punya **karyawan counter** yang hanya perlu melayani transaksi harian.
- Tidak semua pengguna boleh melihat keuangan, mengubah pengaturan, atau menghapus nota.

Tujuan v2.0: memperkenalkan **3 peran** dengan **hak akses per menu + aksi kunci**,
dikontrol oleh satu matriks `HAK_AKSES` di `app/config/config.php`. Sistem tetap
**satu outlet** (belum multi-cabang).

## Bab 2. Peran Pengguna (3 Role)

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

## Bab 3. Matriks Hak Akses (v2.0)

Matriks final di `app/config/config.php` pada konstanta `HAK_AKSES` berisi
**18 kunci hak**. Daftar lengkap kunci dan pemetaan per role ada di `README.md`;
ringkasan per menu:

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
| Pengaturan usaha (5 tab) | ✓ | ✓ | ✗ |
| Karyawan (tambah staff / ubah / reset / hapus) | ✓ | ✓ | ✗ |
| Permintaan Akun Toko (setujui / tolak) | ✓ | ✗ | ✗ |
| Akun & Pengguna (tambah/ubah role/aktif/reset/hapus) | ✓ | ✗ | ✗ |
| Log Aktivitas | ✓ | ✓ | ✗ |
| Notifikasi (lonceng, baca) | ✓ | ✓ | ✓ |
| Kirim Notifikasi | ✓ | ✓ (hanya ke staff) | ✗ |
| Profil (nama tampilan, ganti password sendiri) | ✓ | ✓ | ✓ |

Kunci `pengaturan_keamanan` hanya dimiliki root: manager boleh menyimpan
pengaturan usaha, tetapi tiga kunci keamanan akun diabaikan bila ia
sengaja mengirimkannya.

18 kunci tersebut: `dashboard`, `transaksi`, `transaksi_edit`, `transaksi_hapus`,
`notifikasi`, `notifikasi_kirim`, `pelanggan`, `pelanggan_edit`, `pelanggan_hapus`,
`paket`, `paket_kelola`, `laporan`, `pengaturan`, `pengaturan_keamanan`,
`karyawan`, `akun`, `aktivitas`. (Menu akun & permintaan memakai kunci `akun`.)

## Bab 4. Skema Database & Migrasi

Sembilan tabel: `users`, `pelanggan`, `paket`, `transaksi`, `transaksi_detail`,
`notifikasi`, `pengaturan`, `permintaan_akun`, `log_aktivitas`.

- `users` + kolom `role`, `aktif`, `last_login`, `gagal_login`, `terkunci_hingga`.
- Tabel baru `log_aktivitas` (waktu, pelaku, aksi, keterangan, IP).
- `install.php` melakukan **migrasi idempotent**: menambah kolom/relasi pada database
  lama secara otomatis (admin lama menjadi `root`, akun tetap aktif).
- Semua query memakai **prepared statement** (PDO) dengan `ERRMODE_EXCEPTION`.

## Bab 5. Alur Kerja (Workflow)

- **W1 — Membuat akun (root):** menu Akun & Pengguna → tambah akun → akun langsung aktif.
- **W2 — Login:** cek status aktif + password bcrypt; role masuk session; sidebar
  menampilkan menu yang diizinkan; login/logout tercatat di log aktivitas.
- **W3 — Transaksi harian (staff):** daftar/cari pelanggan → buat nota → cetak →
  update status → terima pembayaran.
- **W4 — Koreksi nota (manager):** ubah (sebelum lunas) atau hapus (nota dobel) → tercatat.
- **W5 — Keuangan (manager):** Laporan per rentang tanggal → ringkasan + export CSV →
  cek Log Aktivitas bila ada selisih.

## Bab 6. Keamanan Multi-User

- Menu disembunyikan **dan** route divalidasi server-side (`requireHak`) — URL manual
  tidak bisa membobol; pelanggaran menampilkan 403.
- Staff tidak bisa mengubah role/status akun sendiri; root tidak bisa menghapus akun
  diri sendiri atau mematikan root terakhir (sistem menjaga jangan sampai terkunci keluar).
- Akun nonaktif langsung ditolak saat login; login bruto-force dibatasi (kunci sementara).
- Semua aksi penting tercatat di `log_aktivitas` (waktu, pelaku, IP).

## Bab 7. Strategi Pengujian

- Pengujian E2E otomatis di dalam repo: `uji-lokal.php` (skrip di luar paket
  deploy) menjalankan alur nyata lewat HTTP terhadap server lokal.
- Cakupannya: halaman publik, guard formulir (GET ditolak 405, token salah
  ditolak 403), rate limit, login tiap role, 16 rute utama, keberadaan token
  CSRF di setiap form POST, guard rute & parameter, CSRF pada 28 aksi
  destruktif/ubah, penyimpanan tiap modul, laporan + ekspor CSV, endpoint JSON
  notifikasi, alur transaksi lengkap (buat → status → cetak → bayar → hapus),
  pengelolaan akun, kelima tab Pengaturan, logout, serta pemeriksaan aset.
- Hasil terakhir: **220/220 pemeriksaan lulus**, tanpa satu pun baris di
  `storage/logs/php-errors.log`, dan data uji dibersihkan kembali sehingga
  database kembali seperti semula.
- Verifikasi aturan `.htaccess` dijalankan dengan instance Apache kedua
  (port 8091, `AllowOverride All`) karena Apache utama dikonfigurasi
  `AllowOverride None`. Hasilnya: `app/config/config.php`,
  `install.php`, `inspeksi.php`, seluruh `storage/`, dan berkas `.md` → 403;
  aset `style.css`/`app.js` tetap 200.
- Verifikasi manual: render halaman publik, flash, badge status, struktur zip
  deploy, serta keberadaan file & tabel di server hosting.

## Bab 8. Deploy & Instalasi

1. Buat token acak, simpan di `INSTALL_TOKEN` pada `app/config/config.php`.
2. Upload seluruh isi folder `laundry` ke docroot hosting
   (`/laundryku.kesug.com/htdocs`, **bukan** `/htdocs` di akar FTP yang hanya
   folder bersama).
3. Buka `install.php?token=...` sekali (migrasi + seed otomatis; hosting =
   database bersih, lokal = ikut data contoh).
4. Hapus `install.php` dan `inspeksi.php` dari server.
5. Pastikan `https://namadomain/laundry/app/config/config.php` menjawab 403
   (bukan kode PHP) — ini bukti `.htaccess` benar-benar berlaku.
6. Login `admin` / `admin123` (root) → ganti password.
7. `doc.docx`, `RENCANA-MULTIUSER.md`, `README.md`, dan `database.sql` adalah
   dokumen/bekas — **tidak di-upload**, hanya disimpan dalam `laundry.zip`.

## Bab 9. Rencana Lanjutan (Roadmap)

- Laporan per kasir, grafik pendapatan.
- Aplikasi customer untuk cek status cucian.
- Multi-cabang (setiap cabang punya manager sendiri).
- Notifikasi realtime lintas perangkat.

---

# Bab 10. Addendum v2.1

Perubahan besar v2.1: **alur permintaan akun toko (bukan self-register)**,
**kelola karyawan oleh manager**, dan **pengaturan usaha diperluas jadi 6 kelompok**
— semuanya ter-wire ke sistem (pajak, diskon, DP, prefix nota, satuan/mata uang,
keamanan akun).

## 10.1 Minta Akun Toko (bukan self-register)

Calon pengelola toko **tidak bisa mendaftar akun sendiri**. Alur:

```
Halaman login → tautan "Minta Akun Toko" → isi formulir publik
   (nama toko, nama pemohon, username & password pilihan, kontak)
        │
        ▼
Permintaan masuk ke root (notifikasi lonceng + tab "Permintaan" di menu Akun)
        │
        ├─ DISETUJUI → akun MANAGER dibuat otomatis dengan kredensial pemohon
        │              → pemohon langsung bisa login
        └─ DITOLAK   → beri alasan → pemohon bisa cek status & mengulang
```

- Kredensial berasal dari **pemohon** (username/password pilihan) — bukan dibuat root.
- Akun hanya menjadi aktif (manager) **setelah disetujui root**.
- Halaman publik **Cek Status Permintaan** (`?r=permintaan/status`) menampilkan form
  "Sudah mengajukan? Cek status" terpisah dari form Minta Akun: pemohon memasukkan
  username → kartu hasil berisi nama toko, pemohon, tanggal pengajuan, dan badge
  status (Menunggu Persetujuan / Disetujui / Ditolak + catatan).
- Username yang sama tidak boleh diminta dua kali (status menunggu) atau dipakai akun lain.
- Data tersimpan di tabel baru `permintaan_akun`:
  `jenis, nama_toko, nama_pemohon, username, password_hash, telepon, alamat, catatan,
  status('menunggu','disetujui','ditolak'), diproses_oleh → users, diproses_at, created_at`.

## 10.2 Kelola Karyawan (manager langsung buat staff)

- Menu **Karyawan** tersedia untuk **root & manager**.
- Manager membuat akun **staff langsung** (nama, username, password) tanpa lewat root,
  mengubah data, reset password, dan menghapus akun.
- Ini "persetujuan toko" untuk karyawan: manajer yang menyetujui; **root tidak ikut
  persetujuan toko** (root hanya mengelola level manager ke atas & seluruh sistem).
- Akun karyawan/staff **tidak** lewat tabel `permintaan_akun`.

## 10.3 Pengaturan diperluas — 5 tab

| Tab | Isi |
|---|---|
| **Umum** | nama usaha, telepon/WA, Instagram, email, alamat, jam buka–tutup, logo |
| **Struk** | header struk, promo struk, footer struk, catatan struk, prefix nota, pratinjau struk |
| **Keuangan** | pajak layanan (%), batas maks. diskon (%), DP minimal (%), satuan berat, simbol mata uang |
| **Keamanan** | min. panjang password, jeda sesi kedaluwarsa (menit, 0 = tanpa batas), batas percobaan login gagal |
| **Notifikasi** | suara notifikasi nyala/mati, volume (0–100%), tombol coba suara |

Satu form dengan lima tab. Tab aktif menentukan field mana yang ikut terkirim,
dan tab yang sama dibuka kembali setelah penyimpanan berhasil.

Yang langsung terpakai di sistem:

- **Pajak** dihitung otomatis: `subtotal × pajak%` ditambahkan ke total nota
  (tampil di ringkasan, detail, dan struk).
- **Diskon** per nota: nilai dibatasi `diskon_maks_persen`% dari subtotal;
  default 0% = tanpa batas.
- **DP minimal**: bila diisi > 0, nota wajib uang muka minimal `dp_minimal_persen`%
  dari total (ditolak bila kurang).
- **Prefix nota** dipakai generator `no_nota` (`PREFIX-YYYYMMDD-XXX`).
- **Satuan & mata uang** dipakai di seluruh tampilan — tidak ada teks `kg`/`Rp` yang
  diketik manual.
- **Keamanan akun**: login terkunci sementara setelah melewati `batas_login_gagal`;
  password wajib ≥ `min_password` karakter; sesi otomatis logout bila
  `sesi_kedaluwarsa_menit` > 0.

> Default `pajak_persen = 0`, `diskon = 0`, `dp_minimal_persen = 0` sehingga perilaku
> nota lama tidak berubah; baris pajak/diskon hanya tampil bila nilainya > 0.

## 10.4 Perubahan skema database v2.1

- `users` + `gagal_login`, `terkunci_hingga`.
- `transaksi` + `diskon`, `pajak` (DECIMAL(12,2), default 0).
- Tabel baru `permintaan_akun` (lihat 10.1).
- Tabel baru `log_aktivitas`.
- `pengaturan` + 16 kunci baru (total **24 kunci**) — `install.php` melakukan migrasi
  idempotent pada database lama secara otomatis (seed 24 kunci dengan nilai netral).
- Migrasi aman dijalankan berulang (CREATE TABLE IF NOT EXISTS / cek kolom).

## 10.5 Matriks hak akses v2.1 + dampak deploy

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
| Pengaturan usaha (5 tab) | ✓ | ✓ | ✗ |
| **Karyawan** (tambah staff / ubah / reset / hapus) | ✓ | ✓ | ✗ |
| **Permintaan Akun Toko** (setujui / tolak) | ✓ | ✗ | ✗ |
| **Akun & Pengguna** (tambah/ubah role/aktif/reset/hapus) | ✓ | ✗ | ✗ |
| **Log Aktivitas** | ✓ | ✓ | ✗ |
| Notifikasi (lonceng, baca) | ✓ | ✓ | ✓ |
| Kirim Notifikasi (root → toko/staff/semua, manager → staff) | ✓ | ✓ (hanya ke staff) | ✗ |
| Profil (nama tampilan, ganti password sendiri) | ✓ | ✓ | ✓ |

Dampak deploy:

1. Upload seluruh isi folder `laundry` (zip berisi `doc.docx` + `RENCANA-MULTIUSER.md`
   sebagai dokumen; `.docx` **tidak di-upload** ke server).
2. Buka `install.php?token=...` sekali → migrasi + seed 24 kunci pengaturan.
3. Hapus `install.php` + `inspeksi.php` dari server.
4. Verifikasi: login root → tab "Permintaan" di menu Akun muncul; submit formulir
   publik diuji; `?r=permintaan/status` menampilkan kartu status.
5. Catatan keamanan: ganti password root segera, ganti `INSTALL_TOKEN`, dan
   kredensial DB hanya di `config.php`.

---

# Bab 11. Addendum v2.2 — Pengujian Ulang & Perbaikan Keamanan

v2.2 tidak menambah fitur baru. Bab ini mencatat hasil audit menyeluruh terhadap
kode yang benar-benar di-deploy, perbaikan yang dilakukan, dan bukti pengujiannya.

## 11.1 Yang diperbaiki

| Area | Masalah | Perbaikan |
|---|---|---|
| CSRF | token hanya ada di form yang remembering-nya, dan beberapa aksi lupa memeriksanya | `csrfSuntik()` menyuntik field `_csrf` ke **setiap** `<form method="post">` dari `Controller::view()`; `wajibPost()` dipakai 28 aksi destruktif/ubah; token juga dicek di `requireLogin()` |
| Router | module/method bebas dipanggil; parameter wajib yang hilang di URL memicu `ArgumentCountError` (500) | `App::ROUTE` sebagai daftar putih (**default-deny**); parameter divalidasi lewat refleksi; parameter wajib yang hilang → **404 "Alamat tidak lengkap"** |
| Kode status | token CSRF salah dikembalikan 419, yang diubah Apache 2.4 menjadi 500 | dikembalikan **403** |
| Notifikasi | "Tandai Semua Dibaca" berupa tautan GET (bisa dipicu tanpa sengaja) | jadi form POST `?r=notifikasi/bacaSemua`; `notifikasi/edit/ID` (buka form, GET) dipisah dari `notifikasi/ubah/ID` (simpan, POST) |
| Pengaturan | `satuan_berat` & `mata_uang` dibaca tanpa Dicek keberadaannya →_warning_ tiap tab selain Keuangan | perataan hanya dijalankan untuk kunci yang benar-benar terkirim |
| Pengaturan | checkbox `notif_suara` selalu ditulis ulang, sehingga tab lain diam-diam mematikan suara | hanya ditulis bila tab Notifikasi yang aktif |
| Formulir publik | tanpa batas pengiriman | `throttle()` berbasis sesi: 3 permintaan akun/jam, 20 cek status/10 menit — tanpa menambah tabel |
| Sesi & header | cookie sesi tanpa flag aman; tanpa header keamanan | `HttpOnly` + `SameSite=Lax` + `Secure` saat HTTPS; `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Content-Security-Policy`, `Permissions-Policy` |
| Berkas sensitif | `install.php` & `inspeksi.php` bisa dibuka siapa saja; `storage/` hanyaIRT proteksi per-file | keduanya dilindungi `INSTALL_TOKEN`; `storage/.htaccess` baru menutup seluruh folder beserta subfoldernya |
| Karelstaller | nilai `INSTALL_TOKEN` masih contoh yang(nebakable) | dokumentasi cara membuat token acak sendiri |
| Transaksi | subtotal memakai pembulatan 2 desimal, tidak sama dengan pembulatan rupiah penuh di browser; pencarian memakai `LIKE` tanpa escape wildcard | subtotal dibulatkan ke rupiah penuh; helper `polaLike()` meng-escape `%`, `_`, dan `\` |
| Laporan | ekspor CSV mentah, sel diawali `=`, `+`, `-`, `@` bisa jadi formula di Excel | dinetralkan dengan awalan teks |
| JavaScript | `index.php?r=` ditulis manual di beberapa tempat | `window.LK_BASE` dikirim server, dipakai lewat `lkUrl()` |
| Aksesibilitas | tidak ada skip link, target konten utama, atau teks khusus pembaca layar |Ditambahkan, beserta token warna `--r-md` & `--bad-500` di CSS |

## 11.2 Konvensi URL yang dipakai

| Maksud | Bentuk | Cara |
|---|---|---|
| Membuka form ubah | `?r=<modul>&edit=ID` | GET, dari halaman daftar |
| Membuka form ubah (modul dengan halaman sendiri) | `?r=notifikasi/edit/ID`, `?r=transaksi/edit/ID` | GET |
| Menyimpan perubahan | `?r=<modul>/ubah/ID` | POST + token |
| Menghapus | `?r=<modul>/hapus/ID` | POST + token |
| Menandai notifikasi terbaca | `?r=notifikasi/baca/ID` | GET (dipakai tautan) |
| Tandai semua terbaca | `?r=notifikasi/bacaSemua` | POST |
| Keluar | `?r=logout` | POST |
| Membuat pengguna | `?r=akun/tambah` | POST |

## 11.3 Bukti pengujian

Skrip uji E2E `uji-lokal.php` menjalankan **220 pemeriksaan** lewat HTTP nyata
terhadap server lokal, termasuk penyimpanan sungguhan ke database lalu
dibandingkannya kembali. Hasil terakhir:

```text
RINGKASAN: lulus 220, gagal 0
storage/logs/php-errors.log: kosong
```

Data uji dibersihkan sendiri di akhir skrip, sehingga jumlah baris di
`pelanggan`, `paket`, `transaksi`, `permintaan_akun`, `notifikasi`, dan `users`
kembali seperti semula.

Aturan `.htaccess` tidak bisa diuji lewat Apache utama karena `httpd.conf`
memakai `AllowOverride None`. Untuk memverifikasinya, instance Apache kedua
dijalankan pada port 8091 dengan `AllowOverride All` dan docroot yang sama.
Hasilnya:

```text
app/config/config.php          403
app/config/database.php        403
install.php                    403
inspeksi.php                   403
storage/... (seluruh isi)      403
README.md                      403
RENCANA-MULTIUSER.md           403
asset/style.css                200
asset/app.js                   200
```

## 11.4 Yang belum bisa diuji dari luar

- Menyimpan lewat form **Pengaturan** di hosting tidak diuji dengan mengirim
  POST, karena pengujian dilakukan terhadap server lokal. Yang dibuktikan
  bahwa kode di server identik dengan kode lokal (ukuran seluruh berkas,
  jumlah entri zip, dan status rute).
- Bila hosting ternyata memakai `AllowOverride None`, seluruh proteksi
  `.htaccess` menganggur. Cara memastikan: minta `403` (bukan kode PHP) saat
  membuka `https://namadomain/laundry/app/config/config.php`.

## 11.5 Tindakan yang masih menjadi tanggung jawab pemilik server

1. Ganti password root dari `admin123`.
2. Ganti `INSTALL_TOKEN` menjadi token acak.
3. Pastikan `install.php` dan `inspeksi.php` sudah dihapus dari server.
4. Bila suara notifikasi tidak berbunyi, ganti berkas
   `app/asset/sound/notification.mp3` dengan berkas yang namaunya sama
   (browser memblokir suara otomatis sebelum pengguna berinteraksi).
5. Tekan Ctrl+F5 di browser untuk memuat versi terbaru `style.css` dan
   `app.js` yang sama-named.

---

*Dokumen ini bagian dari deliverable LaundryKu v2.1. Terakhir diperbarui bersama rilis v2.1.*