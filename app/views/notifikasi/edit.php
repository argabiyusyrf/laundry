<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="card">
  <div class="card-head">
    <h2><?= icon('edit', 17) ?> Edit Notifikasi</h2>
    <a class="btn btn-small btn-outline" href="<?= url('notifikasi') ?>"><?= icon('kembali', 13) ?> Kembali</a>
  </div>

  <div class="card card-info">
    <span class="ci-ico"><?= icon(notifIkonJenis($n['jenis']), 17) ?></span>
    <div>
      <strong>Dikirim <?= waktuRelatif($n['created_at']) ?>.</strong>
      <?php if (!empty($n['pengirim'])): ?>
        Pengirim: <strong><?= e($n['pengirim']) ?></strong>.
      <?php endif; ?>
      Perubahan langsung disimpan dan tampil untuk penerima notifikasi ini.
    </div>
  </div>

  <form method="post" action="<?= url('notifikasi/ubah/' . (int)$n['id']) ?>">
    <div class="form-group">
      <label for="judul">Judul <span class="required-mark">*</span></label>
      <div class="input-icon">
        <span><?= icon('info', 16) ?></span>
        <input type="text" id="judul" name="judul" required maxlength="120"
               value="<?= e($n['judul']) ?>" placeholder="cth: Cucian Siap Diambil">
      </div>
    </div>
    <div class="form-group">
      <label for="pesan">Pesan <span class="required-mark">*</span></label>
      <div class="input-icon">
        <span><?= icon('catatan', 16) ?></span>
        <textarea id="pesan" name="pesan" required rows="4" maxlength="255"
                  placeholder="Tulis pesan yang jelas untuk penerima."><?= e($n['pesan']) ?></textarea>
      </div>
    </div>
    <div class="actions no-print">
      <a href="<?= url('notifikasi') ?>" class="btn btn-outline"><?= icon('kembali', 15) ?> Batal</a>
      <button class="btn btn-primary" type="submit"><?= icon('simpan', 15) ?> Simpan Perubahan</button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>