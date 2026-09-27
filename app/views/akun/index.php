<?php
include __DIR__ . '/../partials/header.php';
$sayaId = (int)($_SESSION['user']['id'] ?? 0);
$roleSaya = userRole();
?>

<div class="card card-info">
  <span class="ci-ico"><?= icon('info', 17) ?></span>
  <div>
    <strong>Menu khusus Pemilik (root).</strong>
    Di sini Anda membuat akun manager &amp; staff, mengatur peran, menonaktifkan akun,
    mereset password, dan menghapus akun. Setiap perubahan tercatat di Log Aktivitas.
    Permintaan akun toko dari halaman publik ditinjau di menu
    <a href="<?= url('akun/permintaan') ?>" class="link-inline">Permintaan Toko</a>.
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head">
      <h2><?= $dataEdit ? icon('edit', 17) . ' Edit Akun' : icon('plus', 17) . ' Tambah Akun' ?></h2>
      <?php if ($dataEdit): ?>
        <a class="btn btn-small" href="<?= url('akun') ?>"><?= icon('kembali', 13) ?> Batal edit</a>
      <?php endif; ?>
    </div>
    <form method="post"
          action="<?= $dataEdit ? url('akun/ubah/' . (int)$dataEdit['id']) : url('akun/tambah') ?>">
      <div class="form-group">
        <label for="nama">Nama Lengkap <span class="required-mark">*</span></label>
        <div class="input-icon">
          <span><?= icon('user', 16) ?></span>
          <input type="text" id="nama" name="nama" required
                 value="<?= e($dataEdit['nama'] ?? '') ?>" placeholder="cth: Dewi Lestari">
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label for="username">Username <span class="required-mark">*</span></label>
          <div class="input-icon">
            <span><?= icon('pengguna', 16) ?></span>
            <input type="text" id="username" name="username" required
                   value="<?= e($dataEdit['username'] ?? '') ?>" placeholder="cth: dewi">
          </div>
        </div>
        <div class="form-group">
          <label for="role">Peran <span class="required-mark">*</span></label>
          <select id="role" name="role" required>
            <?php foreach (DAFTAR_ROLE as $r): ?>
              <option value="<?= e($r) ?>"
                <?= (($dataEdit['role'] ?? '') === $r || (!$dataEdit && $r === 'staff')) ? 'selected' : '' ?>
                <?= $dataEdit && (int)$dataEdit['id'] === $sayaId && $r !== $dataEdit['role'] ? 'disabled' : '' ?>>
                <?= e(roleLabel($r)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label for="password"><?= $dataEdit ? 'Password Baru' : 'Password' ?> <span class="required-mark"><?= $dataEdit ? '(opsional)' : '*' ?></span></label>
          <div class="input-icon">
            <span><?= icon('kunci', 16) ?></span>
            <input type="password" id="password" name="password" autocomplete="new-password"
                   minlength="<?= $dataEdit ? '0' : minLengthPassword() ?>" placeholder="<?= $dataEdit ? 'Kosongkan bila tetap' : 'Min. ' . minLengthPassword() . ' karakter' ?>">
          </div>
          <?php if ($dataEdit): ?>
            <div class="hint">Isi hanya bila ingin mereset password akun ini.</div>
          <?php endif; ?>
        </div>
        <?php if ($dataEdit): ?>
          <div class="form-group">
            <label>Status Akun</label>
            <div class="check-line">
              <input type="checkbox" id="aktif" name="aktif" value="1"
                     <?= (int)($dataEdit['aktif'] ?? 1) === 1 ? 'checked' : '' ?>
                     <?= (int)$dataEdit['id'] === $sayaId ? 'disabled' : '' ?>>
              <label for="aktif">Akun aktif</label>
              <span class="hint">(nonaktif = tidak bisa login)</span>
            </div>
            <?php if ((int)$dataEdit['id'] === $sayaId): ?>
              <div class="hint">Status akun sendiri tidak bisa diubah.</div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <button class="btn btn-primary" type="submit">
        <?= $dataEdit ? icon('simpan', 15) . ' Simpan Perubahan' : icon('plus', 15) . ' Buat Akun' ?>
      </button>
    </form>
  </div>

  <div class="card">
    <div class="card-head"><h2>Daftar Akun</h2></div>
    <?php if (!$list): ?>
      <p class="empty"><?= icon('pengguna', 30) ?>Belum ada akun. Buat akun pertama lewat form di samping.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr><th>Pengguna</th><th>Peran</th><th>Status</th><th>Login Terakhir</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($list as $u): ?>
              <?php $adalahSaya = (int)$u['id'] === $sayaId; ?>
              <tr>
                <td>
                  <div class="cell-user">
                    <span class="cell-ava"><?= e(mb_substr($u['nama'], 0, 1, 'UTF-8')) ?></span>
                    <div>
                      <strong><?= e($u['nama']) ?><?= $adalahSaya ? ' <span class="hint">(Anda)</span>' : '' ?></strong>
                      <div class="hint">@<?= e($u['username']) ?></div>
                    </div>
                  </div>
                </td>
                <td><?= badgeRole((string)$u['role']) ?></td>
                <td>
                  <?php if ((int)$u['aktif'] === 1): ?>
                    <span class="badge badge-lunas">Aktif</span>
                  <?php else: ?>
                    <span class="badge badge-belum">Nonaktif</span>
                  <?php endif; ?>
                </td>
                <td class="hint"><?= $u['last_login'] ? tglIndo($u['last_login'], true) : 'Belum pernah' ?></td>
                <td>
                  <div class="inline">
                    <a class="btn btn-small btn-outline" href="<?= url('akun', ['edit' => (int)$u['id']]) ?>"><?= icon('edit', 13) ?> Edit</a>
                    <?php if (!$adalahSaya): ?>
                      <form method="post" action="<?= url('akun/hapus/' . (int)$u['id']) ?>" class="inline js-hapus">
                        <button class="btn btn-small btn-danger" type="submit"><?= icon('hapus', 13) ?> Hapus</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>