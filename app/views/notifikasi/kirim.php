<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="card">
  <div class="card-head">
    <h2><?= icon('kirim', 16) ?> Kirim Notifikasi</h2>
    <a class="btn btn-small" href="<?= url('notifikasi') ?>"><?= icon('lonceng', 13) ?> Lihat Notifikasi</a>
  </div>

  <form method="post" action="<?= url('notifikasi/kirim') ?>">
    <div class="form-group">
      <label for="penerima">Penerima <span class="required-mark">*</span></label>
      <?php if ($role === 'root'): ?>
        <select id="penerima" name="penerima" required>
          <option value="semua">Semua Pengguna (broadcast)</option>
          <?php if ($daftarToko): ?>
            <optgroup label="Toko (Manager)">
              <?php foreach ($daftarToko as $u): ?>
                <option value="<?= (int)$u['id'] ?>"><?= e($u['nama']) ?> (<?= e($u['username']) ?>)</option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
          <?php if ($daftarStaff): ?>
            <optgroup label="Staff">
              <?php foreach ($daftarStaff as $u): ?>
                <option value="<?= (int)$u['id'] ?>"><?= e($u['nama']) ?> (<?= e($u['username']) ?>)</option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
        </select>
        <p class="hint">Anda dapat mengirim ke toko (manager), staff, atau semua pengguna sekaligus.</p>
      <?php else: ?>
        <select id="penerima" name="penerima" required>
          <option value="" disabled selected>Pilih staff penerima</option>
          <?php if ($daftarStaff): ?>
            <?php foreach ($daftarStaff as $u): ?>
              <option value="<?= (int)$u['id'] ?>"><?= e($u['nama']) ?> (<?= e($u['username']) ?>)</option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <p class="hint">Anda dapat mengirim notifikasi ke staff toko Anda.</p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="judul">Judul <span class="required-mark">*</span></label>
      <div class="input-icon">
        <span><?= icon('info', 16) ?></span>
        <input type="text" id="judul" name="judul" required maxlength="120"
               placeholder="cth: Jadwal libur akhir pekan" autocomplete="off">
      </div>
    </div>

    <div class="form-group">
      <label for="pesan">Pesan <span class="required-mark">*</span></label>
      <textarea id="pesan" name="pesan" rows="4" required maxlength="255"
                placeholder="Tulis isi notifikasi yang ingin disampaikan..."></textarea>
      <p class="hint">Maksimal 255 karakter. Penerima melihat notifikasi ini di lonceng dan halaman Notifikasi.</p>
    </div>

    <button class="btn btn-primary" type="submit">
      <?= icon('kirim', 15) ?> Kirim Notifikasi
    </button>
  </form>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>