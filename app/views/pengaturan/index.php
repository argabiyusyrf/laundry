<?php
include __DIR__ . '/../partials/header.php';
$tabAktif = $tab ?? 'umum';
?>

<nav class="tabs" aria-label="Bagian pengaturan">
  <a class="tab<?= $tabAktif === 'umum' ? ' active' : '' ?>" href="<?= url('pengaturan') ?>">
    <?= icon('toko', 15) ?> Umum &amp; Kontak
  </a>
  <a class="tab<?= $tabAktif === 'struk' ? ' active' : '' ?>" href="<?= url('pengaturan', ['tab' => 'struk']) ?>">
    <?= icon('printer', 15) ?> Struk &amp; Nota
  </a>
  <a class="tab<?= $tabAktif === 'keuangan' ? ' active' : '' ?>" href="<?= url('pengaturan', ['tab' => 'keuangan']) ?>">
    <?= icon('koin', 15) ?> Keuangan
  </a>
  <a class="tab<?= $tabAktif === 'keamanan' ? ' active' : '' ?>" href="<?= url('pengaturan', ['tab' => 'keamanan']) ?>">
    <?= icon('keamanan', 15) ?> Keamanan
  </a>
  <a class="tab<?= $tabAktif === 'notifikasi' ? ' active' : '' ?>" href="<?= url('pengaturan', ['tab' => 'notifikasi']) ?>">
    <?= icon('lonceng', 15) ?> Notifikasi &amp; Suara
  </a>
</nav>

<form method="post" action="<?= url('pengaturan/simpan') ?>" enctype="multipart/form-data">
<input type="hidden" name="tab" value="<?= e($tabAktif) ?>">

<?php if ($tabAktif === 'umum'): ?>

<!-- ==================== TAB: UMUM & KONTAK ==================== -->
<div class="card tab-panel">
  <div class="card-head">
    <h2><?= icon('toko', 17) ?> Identitas &amp; Kontak Toko</h2>
  </div>

  <div class="form-group">
    <label for="nama_usaha">Nama Toko / Usaha <span class="required-mark">*</span></label>
    <div class="input-icon">
      <span><?= icon('logo', 16) ?></span>
      <input type="text" id="nama_usaha" name="nama_usaha" required maxlength="80"
             value="<?= e($s['nama_usaha'] ?? '') ?>" placeholder="cth: Laundry Sejahtera">
    </div>
  </div>
  <div class="form-grid">
    <div class="form-group">
      <label for="telepon_usaha">Telepon Toko</label>
      <div class="input-icon">
        <span><?= icon('telepon', 16) ?></span>
        <input type="text" id="telepon_usaha" name="telepon_usaha" maxlength="30"
               value="<?= e($s['telepon_usaha'] ?? '') ?>" placeholder="08xxxxxxxxxx">
      </div>
    </div>
    <div class="form-group">
      <label for="whatsapp">WhatsApp</label>
      <div class="input-icon">
        <span><?= icon('whatsapp', 16) ?></span>
        <input type="text" id="whatsapp" name="whatsapp" maxlength="30"
               value="<?= e($s['whatsapp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
      </div>
    </div>
  </div>
  <div class="form-grid">
    <div class="form-group">
      <label for="instagram">Instagram</label>
      <div class="input-icon">
        <span><?= icon('ig', 16) ?></span>
        <input type="text" id="instagram" name="instagram" maxlength="40"
               value="<?= e($s['instagram'] ?? '') ?>" placeholder="cth: @laundry.sejahtera">
      </div>
    </div>
    <div class="form-group">
      <label for="email_usaha">Email</label>
      <div class="input-icon">
        <span><?= icon('email', 16) ?></span>
        <input type="text" id="email_usaha" name="email_usaha" maxlength="80"
               value="<?= e($s['email_usaha'] ?? '') ?>" placeholder="cth: halo@laundry.id">
      </div>
    </div>
  </div>
  <div class="form-group">
    <label for="alamat_usaha">Alamat Toko</label>
    <div class="input-icon">
      <span><?= icon('peta', 16) ?></span>
      <input type="text" id="alamat_usaha" name="alamat_usaha" maxlength="255"
             value="<?= e($s['alamat_usaha'] ?? '') ?>" placeholder="cth: Jl. Melati No. 12, Kota Bandung">
    </div>
  </div>
  <div class="form-grid">
    <div class="form-group">
      <label for="jam_buka">Jam Buka</label>
      <div class="input-icon">
        <span><?= icon('jam', 16) ?></span>
        <input type="text" id="jam_buka" name="jam_buka" maxlength="10"
               value="<?= e($s['jam_buka'] ?? '') ?>" placeholder="08:00">
      </div>
    </div>
    <div class="form-group">
      <label for="jam_tutup">Jam Tutup</label>
      <div class="input-icon">
        <span><?= icon('jam', 16) ?></span>
        <input type="text" id="jam_tutup" name="jam_tutup" maxlength="10"
               value="<?= e($s['jam_tutup'] ?? '') ?>" placeholder="21:00">
      </div>
    </div>
  </div>
  <p class="field-hint">WhatsApp, Instagram, email, dan jam operasional ditampilkan di struk &amp; kontak.</p>
</div>

<!-- Logo -->
<div class="card tab-panel">
  <div class="card-head">
    <h2><?= icon('logo', 17) ?> Logo Toko</h2>
  </div>

  <div class="form-group">
    <div class="logo-upload">
      <!-- id="logoPreview" dipakai app.js untuk menampilkan pratinjau berkas
           yang baru dipilih, sehingga terlihat hasilnya sebelum disimpan. -->
      <div class="logo-upload-prev" id="logoPreview">
        <?php if ($s['logo']): ?>
          <img src="<?= e($logoUrl) ?>" alt="Logo toko">
        <?php else: ?>
          <span><?= icon('logo', 26) ?></span>
        <?php endif; ?>
      </div>
      <div class="logo-upload-ctrl">
        <div class="field-row">
          <label class="btn btn-outline btn-small" for="logo" style="cursor:pointer">
            <?= icon('unduh', 14) ?> <?= $s['logo'] ? 'Ganti Logo' : 'Pilih Gambar' ?>
          </label>
          <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp,image/gif" style="display:none">
          <?php if ($s['logo']): ?>
            <!-- Hanya ada satu kendali hapus, yaitu tombol di bawah.
                 Sebelumnya ada tombol sekaligus checkbox dengan nama
                 hapus_logo yang sama persis, sehingga tidak jelas mana yang
                 berlaku dan permintaan hapus bisa terkirim ganda. -->
            <button type="submit" name="hapus_logo" value="1" class="btn btn-small btn-danger"
                    onclick="return confirm('Hapus logo toko ini dan simpan pengaturan?')">
              <?= icon('hapus', 13) ?> Hapus Logo
            </button>
          <?php endif; ?>
        </div>
        <!-- id="logoInfo" diisi app.js dengan nama berkas, ukuran, dan
             peringatan bila ukuran melewati batas 1 MB. -->
        <p class="field-hint" id="logoInfo">PNG, JPG, WebP, atau GIF. Maksimal 1 MB. Logo tampil di bagian atas struk.</p>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<?php if ($tabAktif === 'struk'): ?>

<!-- ==================== TAB: STRUK & NOTA ==================== -->
<div class="card tab-panel">
  <div class="card-head">
    <h2><?= icon('struk', 17) ?> Isi Struk &amp; Nomor Nota</h2>
  </div>

  <div class="form-grid">
    <div class="form-group">
      <label for="header_struk">Pesan Atas Struk</label>
      <div class="input-icon">
        <span><?= icon('info', 16) ?></span>
        <textarea id="header_struk" name="header_struk" rows="2"
                  placeholder="cth: Terima kasih telah mempercayakan cucian Anda kepada kami"><?= e($s['header_struk'] ?? '') ?></textarea>
      </div>
      <p class="field-hint">Tampil tepat di bawah nama toko pada struk.</p>
    </div>
    <div class="form-group">
      <label for="promo_struk">Promo / Penawaran</label>
      <div class="input-icon">
        <span><?= icon('tag', 16) ?></span>
        <textarea id="promo_struk" name="promo_struk" rows="2"
                  placeholder="cth: Paket 3 kg gratis 500 gram, khusus minggu ini"><?= e($s['promo_struk'] ?? '') ?></textarea>
      </div>
    </div>
  </div>
  <div class="form-grid">
    <div class="form-group">
      <label for="footer_struk">Pesan Bawah Struk</label>
      <div class="input-icon">
        <span><?= icon('catatan', 16) ?></span>
        <textarea id="footer_struk" name="footer_struk" rows="2"
                  placeholder="cth: Barang yang sudah selesai mohon diambil maksimal 7 hari"><?= e($s['footer_struk'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="form-group">
      <label for="catatan_struk">Catatan Penjemputan / Lainnya</label>
      <div class="input-icon">
        <span><?= icon('truk', 16) ?></span>
        <textarea id="catatan_struk" name="catatan_struk" rows="2"
                  placeholder="cth: Antar-jemput gratis untuk wilayah radius 3 km"><?= e($s['catatan_struk'] ?? '') ?></textarea>
      </div>
      <p class="field-hint">Tampil paling bawah struk, untuk informasi tambahan ke pelanggan.</p>
    </div>
  </div>

  <div class="form-group" style="max-width:260px">
    <label for="prefix_nota">Prefix Nomor Nota</label>
    <div class="input-icon">
      <span><?= icon('transaksi', 16) ?></span>
      <input type="text" id="prefix_nota" name="prefix_nota" maxlength="8"
             value="<?= e($s['prefix_nota'] ?? 'LND') ?>" placeholder="cth: LND">
    </div>
    <p class="field-hint">Format: <code>PREFIX-YYYYMMDD-001</code>. Huruf besar otomatis.</p>
  </div>
</div>

<div class="card tab-panel">
  <div class="card-head">
    <h2><?= icon('printer', 17) ?> Pratinjau Struk</h2>
    <span class="card-note">Tampilan sesuai pengaturan saat ini</span>
  </div>

  <div class="struk-preview">
    <div class="sp-head">
      <div class="sp-brand">
        <?php if ($s['logo']): ?>
          <img src="<?= e($logoUrl) ?>" alt="Logo">
        <?php else: ?>
          <span class="sp-logo-ico"><?= icon('logo', 18) ?></span>
        <?php endif; ?>
        <div>
          <div class="sp-nama"><?= e($s['nama_usaha'] ?? APP_NAME) ?></div>
          <?php if (($s['alamat_usaha'] ?? '') || ($s['telepon_usaha'] ?? '') || ($s['jam_buka'] ?? '')): ?>
            <div class="sp-sub">
              <?= e($s['alamat_usaha'] ?? '') ?>
              <?= ($s['alamat_usaha'] ?? '') && ($s['telepon_usaha'] ?? '') ? ' — ' : '' ?>
              <?= $s['telepon_usaha'] ? 'Telp: ' . e($s['telepon_usaha']) : '' ?>
              <?= ($s['jam_buka'] ?? '') ? (($s['telepon_usaha'] ?? '') || ($s['alamat_usaha'] ?? '') ? ' | ' : '') . 'Buka ' . e($s['jam_buka']) . '-' . e($s['jam_tutup'] ?? '') : '' ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="sp-no">
        <span>NOTA</span>
        <strong><?= e(strtoupper($s['prefix_nota'] ?? 'LND')) ?>-20260924-999</strong>
      </div>
    </div>

    <?php if ($s['header_struk'] ?? ''): ?>
      <p class="sp-pesan"><?= nl2br(e($s['header_struk'])) ?></p>
    <?php endif; ?>

    <div class="sp-row"><span>Tanggal Masuk</span><span>24 Sep 2026</span></div>
    <div class="sp-row"><span>Estimasi Selesai</span><span>26 Sep 2026</span></div>
    <div class="sp-row"><span>Pelanggan</span><span>Budi Santoso</span></div>
    <div class="sp-row"><span>Berat Total</span><span>5 <?= e($s['satuan_berat'] ?? 'kg') ?></span></div>
    <div class="sp-total">Contoh Total: <?= rupiah(35000) ?></div>

    <?php if ($s['promo_struk'] ?? ''): ?>
      <p class="sp-pesan"><?= nl2br(e($s['promo_struk'])) ?></p>
    <?php endif; ?>

    <?php if ($s['footer_struk'] ?? ''): ?>
      <p class="sp-pesan"><?= nl2br(e($s['footer_struk'])) ?></p>
    <?php endif; ?>

    <?php if ($s['catatan_struk'] ?? ''): ?>
      <p class="sp-pesan"><?= nl2br(e($s['catatan_struk'])) ?></p>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<?php if ($tabAktif === 'keuangan'): ?>

<!-- ==================== TAB: KEUANGAN ==================== -->
<div class="card tab-panel">
  <div class="card-head">
    <h2><?= icon('diskon', 17) ?> Pajak, Diskon &amp; Uang Muka</h2>
  </div>

  <div class="form-grid">
    <div class="form-group">
      <label for="pajak_persen">Pajak Layanan (%)</label>
      <div class="input-icon">
        <span><?= icon('diskon', 16) ?></span>
        <input type="number" id="pajak_persen" name="pajak_persen" min="0" max="100" step="0.1"
               value="<?= e($s['pajak_persen'] ?? '0') ?>">
      </div>
      <p class="field-hint">0 = tanpa pajak. Dihitung otomatis dari subtotal layanan saat membuat nota.</p>
    </div>
    <div class="form-group">
      <label for="diskon_maks_persen">Diskon Maksimum (%)</label>
      <div class="input-icon">
        <span><?= icon('diskon', 16) ?></span>
        <input type="number" id="diskon_maks_persen" name="diskon_maks_persen" min="0" max="100" step="0.1"
               value="<?= e($s['diskon_maks_persen'] ?? '0') ?>">
      </div>
      <p class="field-hint">0 = tanpa batas. Batas diskon per nota dalam persen subtotal.</p>
    </div>
  </div>
  <div class="form-group" style="max-width:260px">
    <label for="dp_minimal_persen">DP Minimal (%)</label>
    <div class="input-icon">
      <span><?= icon('kartu', 16) ?></span>
      <input type="number" id="dp_minimal_persen" name="dp_minimal_persen" min="0" max="100" step="1"
             value="<?= e($s['dp_minimal_persen'] ?? '0') ?>">
    </div>
    <p class="field-hint">0 = DP boleh 0 (bayar belakangan). &gt; 0 = uang muka wajib minimal sekian persen dari total.</p>
  </div>
</div>

<div class="card tab-panel">
  <div class="card-head">
    <h2><?= icon('koin', 17) ?> Satuan &amp; Mata Uang</h2>
  </div>

  <div class="form-grid">
    <div class="form-group">
      <label for="satuan_berat">Satuan Berat</label>
      <div class="input-icon">
        <span><?= icon('layanan', 16) ?></span>
        <input type="text" id="satuan_berat" name="satuan_berat" maxlength="10"
               value="<?= e($s['satuan_berat'] ?? 'kg') ?>" placeholder="cth: kg">
      </div>
      <p class="field-hint">Tampil di form nota, daftar transaksi, dan struk.</p>
    </div>
    <div class="form-group">
      <label for="mata_uang">Simbol Mata Uang</label>
      <div class="input-icon">
        <span><?= icon('uang', 16) ?></span>
        <input type="text" id="mata_uang" name="mata_uang" maxlength="6"
               value="<?= e($s['mata_uang'] ?? 'Rp') ?>" placeholder="cth: Rp / IDR">
      </div>
      <p class="field-hint">Tampil pada harga layanan, nota, dan struk.</p>
    </div>
  </div>
</div>

<?php endif; ?>

<?php if ($tabAktif === 'keamanan'): ?>

<!-- ==================== TAB: KEAMANAN ==================== -->
<?php $bolehKeamanan = cekHak('pengaturan_keamanan'); ?>
<div class="card tab-panel">
  <div class="card-head">
    <h2><?= icon('keamanan', 17) ?> Keamanan Akun</h2>
  </div>

  <?php if (!$bolehKeamanan): ?>
    <p class="field-hint" style="margin-bottom:14px">
      Pengaturan keamanan hanya dapat diubah oleh Pemilik (root). Nilai di bawah
      berlaku untuk seluruh pengguna, jadi manager tidak bisa mengubahnya.
    </p>
  <?php endif; ?>

  <div class="form-grid">
    <div class="form-group">
      <label for="min_password">Panjang Min. Password</label>
      <div class="input-icon">
        <span><?= icon('kunci', 16) ?></span>
        <input type="number" id="min_password" name="min_password" min="4" max="64"
               <?= $bolehKeamanan ? '' : 'disabled' ?>
               value="<?= e($s['min_password'] ?? '6') ?>">
      </div>
      <p class="field-hint">Berlaku saat buat akun, reset password, ganti password, dan permintaan akun toko.</p>
    </div>
    <div class="form-group">
      <label for="batas_login_gagal">Batas Login Gagal</label>
      <div class="input-icon">
        <span><?= icon('keamanan', 16) ?></span>
        <input type="number" id="batas_login_gagal" name="batas_login_gagal" min="1" max="50"
               <?= $bolehKeamanan ? '' : 'disabled' ?>
               value="<?= e($s['batas_login_gagal'] ?? '5') ?>">
      </div>
      <p class="field-hint">Lewati batas = akun terkunci sementara 5 menit.</p>
    </div>
  </div>
  <div class="form-group" style="max-width:260px">
    <label for="sesi_kedaluwarsa_menit">Sesi Kedaluwarsa (menit)</label>
    <div class="input-icon">
      <span><?= icon('jam', 16) ?></span>
      <input type="number" id="sesi_kedaluwarsa_menit" name="sesi_kedaluwarsa_menit" min="0" max="1440"
             <?= $bolehKeamanan ? '' : 'disabled' ?>
             value="<?= e($s['sesi_kedaluwarsa_menit'] ?? '0') ?>">
    </div>
    <p class="field-hint">0 = sesi tidak pernah berakhir. Contoh: 30 = logout otomatis setelah 30 menit tidak aktif.</p>
  </div>
</div>

<?php endif; ?>

<?php if ($tabAktif === 'notifikasi'): ?>

<!-- ==================== TAB: NOTIFIKASI & SUARA ==================== -->
<div class="card tab-panel">
  <div class="card-head">
    <h2><?= icon('lonceng', 17) ?> Notifikasi &amp; Suara</h2>
  </div>

  <div class="check-line" style="padding-bottom:4px">
    <input type="checkbox" id="notif_suara" name="notif_suara" value="1"
           <?= (string)($s['notif_suara'] ?? '1') === '1' ? 'checked' : '' ?>>
    <label for="notif_suara">Putar suara saat ada notifikasi baru</label>
    <span class="hint">(di halaman yang sedang terbuka)</span>
  </div>
  <p class="field-hint">Suara berbunyi setiap kali ada notifikasi masuk (transaksi, status, pembayaran, pesan). Browser baru mengizinkan audio setelah ada interaksi pertama — klik halaman sekali supaya suara aktif.</p>

  <div class="form-group" style="max-width:280px; margin-top:16px">
    <label for="notif_volume">Volume Suara</label>
    <div class="input-icon">
      <span><?= icon('lonceng_aktif', 16) ?></span>
      <select id="notif_volume" name="notif_volume">
        <?php foreach ([0, 25, 50, 75, 80, 100] as $v): ?>
          <option value="<?= $v ?>" <?= (string)($s['notif_volume'] ?? '80') === (string)$v ? 'selected' : '' ?>>
            <?= $v === 0 ? '0% (senyap)' : $v . '%' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <p class="field-hint">Kekerasan suara notifikasi (berlaku setelah disimpan).</p>
  </div>

  <div class="field-row" style="margin-top:16px">
    <button class="btn btn-outline" type="button" id="btnCobaSuara"><?= icon('lonceng', 15) ?> Coba Suara</button>
    <span class="hint">Uji bunyi notifikasi dengan volume di atas.</span>
  </div>
</div>

<?php endif; ?>

<div class="actions no-print">
  <a href="<?= url('dashboard') ?>" class="btn btn-outline"><?= icon('kembali', 15) ?> Batal</a>
  <button class="btn btn-primary" type="submit"><?= icon('simpan', 15) ?> Simpan Pengaturan</button>
</div>
</form>

<?php include __DIR__ . '/../partials/footer.php'; ?>
