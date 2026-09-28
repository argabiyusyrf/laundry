<?php
include __DIR__ . '/../partials/header.php';
$minPw     = minLengthPassword();
$hurufAwal = mb_strtoupper(mb_substr($u['nama'] ?? 'A', 0, 1, 'UTF-8'));
$aktif     = (int)($u['aktif'] ?? 1) === 1;
?>

<!-- Kartu identitas: ringkasan akun yang sedang dipakai. -->
<div class="card">
  <div class="idcard">
    <span class="id-ava" aria-hidden="true"><?= e($hurufAwal) ?></span>
    <div class="id-meta">
      <div class="id-nama"><?= e($u['nama']) ?></div>
      <div class="id-sub">@<?= e($u['username']) ?></div>
      <div class="id-tag">
        <?= badgeRole($u['role']) ?>
        <span class="badge <?= $aktif ? 'badge-lunas' : 'badge-belum' ?>">
          <?= $aktif ? 'Akun Aktif' : 'Akun Nonaktif' ?>
        </span>
      </div>
    </div>
  </div>

  <dl class="id-fakta">
    <div>
      <dt>Peran</dt>
      <dd><?= e(roleLabel($u['role'])) ?></dd>
    </div>
    <div>
      <dt>Terdaftar</dt>
      <dd><?= tglIndo($u['created_at'], true) ?></dd>
    </div>
    <div>
      <dt>Login Terakhir</dt>
      <dd><?= $u['last_login'] ? e(waktuRelatif($u['last_login'])) : 'Belum pernah' ?></dd>
    </div>
    <div>
      <dt>Nomor Akun</dt>
      <dd>#<?= (int)$u['id'] ?></dd>
    </div>
  </dl>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head">
      <h2><?= icon('pengguna', 17) ?> Data Akun</h2>
    </div>

    <form method="post" action="<?= url('profil/simpan') ?>" data-form-profil>
      <div class="form-group">
        <label for="nama">Nama Lengkap <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('user', 16) ?></span>
          <input type="text" id="nama" name="nama" required maxlength="100"
                 value="<?= e($u['nama']) ?>" placeholder="cth: Budi Santoso">
        </div>
      </div>
      <div class="form-group">
        <label for="username">Username <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('kunci', 16) ?></span>
          <input type="text" id="username" name="username" required maxlength="50"
                 value="<?= e($u['username']) ?>" placeholder="username">
        </div>
        <p class="field-hint">Username dipakai untuk login. Hanya huruf, angka, titik, garis bawah, dan strip.</p>
      </div>
      <div class="form-group">
        <label>Tanggal Terdaftar</label>
        <div class="input-icon is-readonly">
          <span><?= icon('kalender', 16) ?></span>
          <input type="text" value="<?= tglIndo($u['created_at'], true) ?>" disabled>
        </div>
        <p class="field-hint">Tanggal ini diisi sistem dan tidak bisa diubah.</p>
      </div>

      <p class="field-hint" data-catatan-profil>Belum ada perubahan pada isian di atas.</p>

      <div class="actions no-print">
        <button class="btn btn-primary" type="submit"><?= icon('simpan', 15) ?> Simpan Profil</button>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="card-head">
      <h2><?= icon('pengaturan', 17) ?> Ganti Password</h2>
    </div>

    <form method="post" action="<?= url('profil/password') ?>" data-form-password data-min-pw="<?= $minPw ?>">
      <div class="form-group">
        <label for="password_lama">Password Lama <span class="required-mark">*</span></label>
        <div class="input-icon pw-kolom">
          <span><?= icon('kunci', 16) ?></span>
          <input type="password" id="password_lama" name="password_lama" required
                 autocomplete="current-password" placeholder="Password saat ini"
                 data-nama-kolom="password lama">
          <button class="pw-toggle" type="button" data-pw-toggle="password_lama"
                  aria-label="Tampilkan password lama" aria-pressed="false" title="Tampilkan"><?= icon('mata', 16) ?></button>
        </div>
      </div>

      <div class="form-group">
        <label for="password_baru">Password Baru <span class="required-mark">*</span></label>
        <div class="input-icon pw-kolom">
          <span><?= icon('kunci', 16) ?></span>
          <input type="password" id="password_baru" name="password_baru" required
                 minlength="<?= $minPw ?>" autocomplete="new-password"
                 placeholder="Minimal <?= $minPw ?> karakter"
                 aria-describedby="pwKeterangan"
                 data-nama-kolom="password baru">
          <button class="pw-toggle" type="button" data-pw-toggle="password_baru"
                  aria-label="Tampilkan password baru" aria-pressed="false" title="Tampilkan"><?= icon('mata', 16) ?></button>
        </div>

        <div class="pw-meter" data-level="0" aria-hidden="true"><i></i></div>
        <p class="pw-kuat" id="pwKeterangan" data-level="0" aria-live="polite">
          <b>Kekuatan: belum dinilai</b> <span data-pw-sisa></span>
        </p>

        <ul class="pw-syarat" data-pw-syarat>
          <li data-wajib="1" data-syarat="panjang">Panjang minimal <?= $minPw ?> karakter</li>
          <li data-syarat="kecil">Huruf kecil (a-z)</li>
          <li data-syarat="besar">Huruf besar (A-Z)</li>
          <li data-syarat="angka">Angka (0-9)</li>
          <li data-syarat="simbol">Simbol, misalnya <span class="mono"># . - _</span></li>
        </ul>
        <p class="field-hint">
          Yang dihitung server hanya panjang minimum. Syarat lain adalah saran
          agar password Anda sulit ditebak orang lain.
        </p>
      </div>

      <div class="form-group">
        <label for="password_baru_ulang">Ulangi Password Baru <span class="required-mark">*</span></label>
        <div class="input-icon pw-kolom">
          <span><?= icon('kunci', 16) ?></span>
          <input type="password" id="password_baru_ulang" name="password_baru_ulang" required
                 minlength="<?= $minPw ?>" autocomplete="new-password"
                 placeholder="Ketik ulang password baru"
                 data-nama-kolom="pengulangan password baru">
          <button class="pw-toggle" type="button" data-pw-toggle="password_baru_ulang"
                  aria-label="Tampilkan pengulangan password" aria-pressed="false" title="Tampilkan"><?= icon('mata', 16) ?></button>
        </div>
        <p class="field-hint" data-pw-cocok hidden>Pengulangan password belum sama dengan password baru.</p>
      </div>

      <div class="actions no-print">
        <button class="btn btn-primary" type="submit"><?= icon('kunci', 15) ?> Ganti Password</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
