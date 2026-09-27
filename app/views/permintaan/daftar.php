<?php
include __DIR__ . '/../partials/header.php';
?>

<div class="card card-info">
  <span class="ci-ico"><?= icon('toko', 17) ?></span>
  <div>
    <strong>Permintaan Akun Toko.</strong>
    Dibuat lewat halaman publik <em>Minta Akun Toko</em> (di layar login). Setujui
    untuk membuat akun <strong>manager (kepala toko)</strong> dengan kredensial yang
    didaftarkan pemohon — langsung aktif. Tolak bila data tidak valid, sertakan alasan
    agar pemohon bisa melihatnya saat cek status.
  </div>
</div>

<div class="card">
  <div class="card-head"><h2>Daftar Permintaan</h2></div>
  <?php if (!$permintaan): ?>
    <p class="empty"><?= icon('toko', 30) ?>Belum ada permintaan akun toko.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>#</th><th>Toko / Pemohon</th><th>Username</th><th>Kontak</th><th>Diajukan</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          <?php foreach ($permintaan as $p): ?>
            <tr>
              <td class="hint">#<?= (int)$p['id'] ?></td>
              <td>
                <div class="cell-user">
                  <span class="cell-ava"><?= e(mb_substr($p['nama_toko'], 0, 1, 'UTF-8')) ?></span>
                  <div>
                    <strong><?= e($p['nama_toko']) ?></strong>
                    <div class="hint"><?= e($p['nama_pemohon']) ?></div>
                  </div>
                </div>
              </td>
              <td class="hint">@<?= e($p['username']) ?></td>
              <td class="hint">
                <?= e($p['telepon'] ?: '-') ?><br><?= e($p['alamat'] ?: '-') ?>
              </td>
              <td class="hint"><?= tglIndo($p['created_at'], true) ?></td>
              <td>
                <?php if ($p['status'] === 'menunggu'): ?>
                  <span class="badge badge-dicuci">Menunggu</span>
                <?php elseif ($p['status'] === 'disetujui'): ?>
                  <span class="badge badge-lunas">Disetujui</span>
                <?php else: ?>
                  <span class="badge badge-belum">Ditolak</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($p['status'] === 'menunggu'): ?>
                  <div class="inline" style="flex-wrap:wrap;gap:6px">
                    <form method="post" action="<?= url('akun/setujui-permintaan/' . (int)$p['id']) ?>" class="inline">
                      <button class="btn btn-small btn-success" type="submit"
                              onclick="return confirm('Setujui permintaan ini? Akun manager akan langsung dibuat.')">
                        <?= icon('check', 13) ?> Setujui
                      </button>
                    </form>
                    <details class="tolak-details">
                      <summary class="btn btn-small btn-danger"><?= icon('hapus', 13) ?> Tolak</summary>
                      <form method="post" action="<?= url('akun/tolak-permintaan/' . (int)$p['id']) ?>"
                            class="tolak-form js-hapus"
                            data-pesan="Tolak permintaan akun toko ini?">
                        <input type="text" name="catatan" maxlength="255" placeholder="Alasan (contoh: username sudah terpakai)">
                        <button class="btn btn-small btn-danger" type="submit">Tolak</button>
                      </form>
                    </details>
                  </div>
                <?php else: ?>
                  <span class="hint">
                    <?= $p['diproses_at'] ? tglIndo($p['diproses_at'], true) : '' ?>
                    <?= $p['status'] === 'ditolak' && $p['catatan'] ? '<br>' . e($p['catatan']) : '' ?>
                  </span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>