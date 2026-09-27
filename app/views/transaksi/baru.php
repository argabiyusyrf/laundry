<?php
include __DIR__ . '/../partials/header.php';

$editMode = !empty($editTransaksi);
$et = $editTransaksi ?? null;
?>

<?php if (!$pelangganList): ?>
  <div class="alert alert-error">
    <span class="alert-ico"><?= icon('peringatan', 17) ?></span>
    <span>Belum ada data pelanggan. <a href="<?= url('pelanggan') ?>">Tambahkan pelanggan dulu</a> sebelum membuat transaksi.</span>
  </div>
<?php endif; ?>

<?php if (!$paketList): ?>
  <div class="alert alert-error">
    <span class="alert-ico"><?= icon('peringatan', 17) ?></span>
    <span>Belum ada paket layanan. <a href="<?= url('paket') ?>">Tambahkan paket layanan dulu</a>.</span>
  </div>
<?php endif; ?>

<form method="post"
      action="<?= $editMode ? url('transaksi/ubah/' . (int)$et['id']) : url('transaksi/simpan') ?>"
      id="formTransaksi"
      class="js-kirim"
      data-satuan="<?= e(pengaturan('satuan_berat', 'kg')) ?>"
      data-uang="<?= e(pengaturan('mata_uang', 'Rp')) ?>"
      data-pajak="<?= (float)pengaturan('pajak_persen', '0') ?>">

  <div class="card">
    <div class="card-head">
      <h2><?= icon($editMode ? 'edit' : 'transaksi', 17) ?> <?= $editMode ? 'Edit Transaksi' : 'Data Nota' ?></h2>
      <span class="card-note"><?= $editMode ? 'No. Nota: ' . e($et['no_nota']) : 'Nomor nota dibuat otomatis' ?></span>
    </div>
    <div class="form-grid">
      <div class="form-group">
        <label for="pelanggan_id">Pelanggan <span class="required-mark">*</span></label>
        <div class="field-row">
          <div class="input-icon">
            <span><?= icon('user', 16) ?></span>
            <select id="pelanggan_id" name="pelanggan_id" required>
              <option value="">-- Pilih pelanggan --</option>
              <?php foreach ($pelangganList as $pl): ?>
                <option value="<?= (int)$pl['id'] ?>"
                  <?= $editMode && (int)$pl['id'] === (int)$et['pelanggan_id'] ? 'selected' : '' ?>>
                  <?= e($pl['nama']) ?><?= $pl['telepon'] ? ' — ' . e($pl['telepon']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <a class="btn btn-outline btn-icon btn-field" href="<?= url('pelanggan') ?>"
             title="Tambah pelanggan baru" aria-label="Tambah pelanggan baru"><?= icon('plus', 16) ?></a>
        </div>
      </div>
      <div class="form-group">
        <label for="tanggal_masuk">Tanggal Masuk <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('kalender', 16) ?></span>
          <input type="date" id="tanggal_masuk" name="tanggal_masuk"
                 value="<?= e($editMode ? $et['tanggal_masuk'] : date('Y-m-d')) ?>" required>
        </div>
      </div>
    </div>
    <div class="form-group" style="margin-bottom:0">
      <label for="keterangan">Keterangan <span class="hint" style="font-weight:400">(opsional)</span></label>
      <div class="input-icon">
        <span><?= icon('transaksi', 16) ?></span>
        <textarea id="keterangan" name="keterangan" rows="2" placeholder="cth: 3 helai kemeja putih, 1 celana jeans"><?= e($editMode ? $et['keterangan'] : '') ?></textarea>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <h2><?= icon('layanan', 17) ?> Detail Layanan <span class="required-mark">*</span></h2>
      <button type="button" class="btn btn-outline btn-small" id="btnTambahBaris"><?= icon('plus', 14) ?> Tambah Layanan</button>
    </div>

    <?php $satuan = e(pengaturan('satuan_berat', 'kg')); $pajakPersen = (float)pengaturan('pajak_persen', '0'); ?>
    <div id="barisPaket">
      <?php if ($editMode): ?>
        <?php foreach ($editDetail as $d): ?>
          <div class="baris-paket">
            <select name="paket_id[]" class="pilih-paket" required>
              <option value="">-- Pilih paket --</option>
              <?php foreach ($paketList as $p): ?>
                <option value="<?= (int)$p['id'] ?>" data-harga="<?= e($p['harga_per_kg']) ?>"
                  <?= (int)$d['paket_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                  <?= e($p['nama']) ?> — <?= rupiah($p['harga_per_kg']) ?>/<?= $satuan ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="berat[]" class="berat" min="0.1" step="0.01" placeholder="Berat (<?= $satuan ?>)" required
                   value="<?= e($d['berat']) ?>">
            <div class="subtotal"><?= rupiah($d['subtotal']) ?></div>
            <button type="button" class="btn btn-danger btn-icon hapus-baris" title="Hapus baris"><?= icon('hapus', 15) ?></button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <template id="tmplBaris">
      <div class="baris-paket">
        <select name="paket_id[]" class="pilih-paket" required>
          <option value="">-- Pilih paket --</option>
          <?php foreach ($paketList as $p): ?>
            <option value="<?= (int)$p['id'] ?>" data-harga="<?= e($p['harga_per_kg']) ?>">
              <?= e($p['nama']) ?> — <?= rupiah($p['harga_per_kg']) ?>/<?= $satuan ?>
            </option>
          <?php endforeach; ?>
        </select>
        <input type="number" name="berat[]" class="berat" min="0.1" step="0.01" placeholder="Berat (<?= $satuan ?>)" required>
        <div class="subtotal">Rp0</div>
        <button type="button" class="btn btn-danger btn-icon hapus-baris" title="Hapus baris"><?= icon('hapus', 15) ?></button>
      </div>
    </template>

    <div class="ringkasan">
      <div class="ringkasan-item">
        <span>Total Berat</span>
        <strong id="totalBerat">0 <?= $satuan ?></strong>
      </div>
      <?php if ($pajakPersen > 0): ?>
        <div class="ringkasan-item">
          <span>Pajak Layanan (<?= round($pajakPersen, 1) ?>%)</span>
          <strong id="pajakView">Rp0</strong>
        </div>
      <?php endif; ?>
      <div class="ringkasan-item">
        <span>Diskon</span>
        <strong id="diskonView">- Rp0</strong>
      </div>
      <div class="ringkasan-item">
        <span>Total Harga</span>
        <strong id="totalHargaView">Rp0</strong>
      </div>
    </div>

    <div class="form-grid" style="max-width:620px">
      <div class="form-group">
        <label for="diskon">Diskon (Rp) <span class="hint" style="font-weight:400">(opsional)</span></label>
        <div class="input-icon">
          <span><?= icon('diskon', 16) ?></span>
          <input type="number" id="diskon" name="diskon" min="0" step="1000"
                 value="<?= e($editMode ? $et['diskon'] : '0') ?>">
        </div>
        <?php if ((float)pengaturan('diskon_maks_persen', '0') > 0): ?>
          <span class="hint">Maks diskon <?= round((float)pengaturan('diskon_maks_persen', '0')) ?>% dari subtotal.</span>
        <?php else: ?>
          <span class="hint">Potongan harga nota ini (tidak wajib).</span>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label for="uang_muka">Uang Muka / DP</label>
        <div class="input-icon">
          <span><?= icon('kartu', 16) ?></span>
          <input type="number" id="uang_muka" name="uang_muka" min="0" step="1000"
                 value="<?= e($editMode ? $et['uang_muka'] : '0') ?>">
        </div>
        <?php if ((float)pengaturan('dp_minimal_persen', '0') > 0): ?>
          <span class="hint">Wajib minimal <?= round((float)pengaturan('dp_minimal_persen', '0')) ?>% dari total.</span>
        <?php else: ?>
          <span class="hint">Isi 0 bila pembayaran dilakukan setelah cucian selesai.</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="actions no-print">
    <a href="<?= url($editMode ? 'transaksi/detail/' . (int)$et['id'] : 'transaksi') ?>" class="btn btn-outline"><?= icon('kembali', 15) ?> Batal</a>
    <button class="btn btn-primary" type="submit" id="tombolSimpan"<?= (!$pelangganList || !$paketList) ? ' disabled' : '' ?>>
      <?= $editMode ? icon('simpan', 15) . ' Simpan Perubahan' : icon('simpan', 15) . ' Simpan Transaksi' ?>
    </button>
  </div>

</form>

<?php include __DIR__ . '/../partials/footer.php'; ?>