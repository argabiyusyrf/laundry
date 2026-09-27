<?php
$lama    = $usernameLama ?? '';
$hasil   = $hasilCek ?? null;
$pesan   = $pesan ?? '';
$bulan   = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/* Presentasi status permintaan. */
$st      = $hasil['status'] ?? '';
$ikonSt  = ['menunggu' => 'jam', 'disetujui' => 'check', 'ditolak' => 'peringatan'][$st] ?? 'info';
$clsSt   = ['menunggu' => 'st-menunggu', 'disetujui' => 'st-disetujui', 'ditolak' => 'st-ditolak'][$st] ?? 'st-menunggu';
$labelSt = ['menunggu' => 'Menunggu Persetujuan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'][$st] ?? ucfirst($st);

$ts = $hasil ? strtotime((string)$hasil['created_at']) : false;
$tgl = $ts ? date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y H:i', $ts) : '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cek Status Permintaan — <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/x-icon" href="<?= base_path() ?>/favicon.ico">
<link rel="stylesheet" href="<?= asset_url('style.css') ?>">
<style>
  .login-card.stat-card { max-width: 540px; width: 100%; }
  .perm-note { background: var(--ink-50); border: 1px solid var(--ink-200); border-radius: 12px; padding: 10px 12px; font-size: 13px; color: var(--ink-600); display: flex; gap: 8px; align-items: flex-start; margin-bottom: 16px; }
  .perm-note .alert-ico { color: var(--brand-600); }
  .st-card { border: 1px solid var(--ink-200); border-radius: 12px; padding: 16px; background: #fff; margin-top: 4px; }
  .st-top { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  .st-toko { font-size: 17px; font-weight: 800; color: var(--ink-900); }
  .st-no { font-size: 12px; color: var(--ink-400); white-space: nowrap; }
  .st-rows { margin-top: 12px; border-top: 1px dashed var(--ink-200); padding-top: 10px; display: grid; gap: 8px; }
  .st-row { display: flex; gap: 8px; font-size: 13.5px; }
  .st-k { width: 122px; color: var(--ink-500); flex-shrink: 0; }
  .st-v { color: var(--ink-800); }
  .st-pesan { margin-top: 12px; }
  .st-pesan .alert { margin-top: 0; }
  .st-menunggu  { background: linear-gradient(180deg, var(--warn-50), var(--warn-100)); color: var(--warn-700); border: 1px solid #fde68a; }
  .st-disetujui { background: linear-gradient(180deg, var(--ok-50), var(--ok-100)); color: var(--ok-700); border: 1px solid #86efac; }
  .st-ditolak   { background: linear-gradient(180deg, var(--bad-50), var(--bad-100)); color: var(--bad-700); border: 1px solid #fecaca; }
  .st-hint { text-align: center; color: var(--ink-400); font-size: 12.5px; margin-top: 2px; }
</style>
</head>
<body class="login-body">
  <div class="login-bg" aria-hidden="true"></div>
  <main class="login-card stat-card">
    <div class="login-logo"><?= icon('cari', 30) ?></div>
    <h1>Cek Status Permintaan</h1>
    <p class="login-sub">Sudah mengajukan permintaan akun toko? Masukkan username yang didaftarkan.</p>

    <div class="perm-note">
      <span class="alert-ico"><?= icon('info', 16) ?></span>
      <div>
        Permintaan akun toko <strong>bukan fitur register</strong>. Setiap permintaan ditinjau pemilik
        sistem (root) terlebih dahulu; status di sini menunjukkan <strong>menunggu, disetujui,
        atau ditolak</strong>. Setelah disetujui, silakan login dengan username &amp; password yang
        didaftarkan.
      </div>
    </div>

    <?php foreach (getFlashes() as $f):
      $jenis = in_array($f['jenis'], ['success', 'error', 'info'], true) ? $f['jenis'] : 'info';
      $ikonAlert = ['success' => 'check', 'error' => 'peringatan', 'info' => 'info'];
    ?>
      <div class="alert alert-<?= e($jenis) ?>">
        <span class="alert-ico"><?= icon($ikonAlert[$jenis], 17) ?></span>
        <span><?= e($f['pesan']) ?></span>
      </div>
    <?php endforeach; ?>

    <?php if ($pesan !== ''): ?>
      <div class="alert alert-info">
        <span class="alert-ico"><?= icon('info', 17) ?></span>
        <span><?= e($pesan) ?></span>
      </div>
    <?php endif; ?>

    <form method="get" action="<?= e(form_action()) ?>" autocomplete="off">
      <input type="hidden" name="r" value="permintaan/status">
      <div class="field-row">
        <div class="input-icon">
          <span><?= icon('user', 16) ?></span>
          <input type="text" id="cek_username" name="username" maxlength="50" required
                 value="<?= e($lama) ?>" placeholder="cth: dewi.laundry">
        </div>
        <button class="btn btn-primary" type="submit"><?= icon('cari', 15) ?> Cek Status</button>
      </div>
    </form>
    <p class="st-hint">Gunakan username persis seperti saat mengajukan (huruf kecil otomatis).</p>

    <?php if ($hasil): ?>
      <div class="st-card">
        <div class="st-top">
          <span class="st-toko"><?= e($hasil['nama_toko']) ?></span>
          <span class="st-no">No. #<?= (int)$hasil['id'] ?></span>
          <span style="margin-left:auto"><span class="badge <?= e($clsSt) ?>"><?= icon($ikonSt, 12) ?> <?= e($labelSt) ?></span></span>
        </div>
        <div class="st-rows">
          <div class="st-row"><span class="st-k">Nama Pemohon</span><span class="st-v"><?= e($hasil['nama_pemohon'] ?? '-') ?></span></div>
          <div class="st-row"><span class="st-k">Username</span><span class="st-v"><?= e($hasil['username']) ?></span></div>
          <div class="st-row"><span class="st-k">Tanggal Pengajuan</span><span class="st-v"><?= e($tgl) ?></span></div>
          <div class="st-row"><span class="st-k">Status</span><span class="st-v">
            <?php if ($st === 'menunggu'): ?>
              Masih menunggu persetujuan pemilik sistem. Silakan cek kembali beberapa saat lagi.
            <?php elseif ($st === 'disetujui'): ?>
              <strong>DISETUJUI</strong> — akun role manager Anda sudah aktif. Silakan
              <a href="<?= url('login') ?>">login</a> dengan username &amp; password yang didaftarkan.
            <?php else: ?>
              <strong>DITOLAK</strong><?= !empty($hasil['catatan']) ? ' — ' . e($hasil['catatan']) : '.' ?>
            <?php endif; ?>
          </span></div>
        </div>
        <?php if ($st === 'ditolak' && empty($hasil['catatan'])): ?>
          <div class="st-pesan">
            <div class="alert alert-error">
              <span class="alert-ico"><?= icon('peringatan', 17) ?></span>
              <span>Permintaan ditolak tanpa catatan. Hubungi pemilik sistem bila merasa ini keliru.</span>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php elseif ($pesan === ''): ?>
      <p class="st-hint">Belum ada hasil. Isi username di atas lalu tekan "Cek Status".</p>
    <?php endif; ?>

    <p style="text-align:center;margin-top:16px">
      <a href="<?= url('permintaan') ?>" class="btn-link"><?= icon('toko', 14) ?> Minta Akun Toko</a>
      <span style="color:var(--ink-300);margin:0 6px">|</span>
      <a href="<?= url('login') ?>" class="btn-link"><?= icon('kembali', 14) ?> Login</a>
    </p>
  </main>
</body>
</html>