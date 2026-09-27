<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model Transaksi: data nota, detail layanan, status, pembayaran,
 * dan seluruh perhitungan (total harga, statistik, laporan).
 */
class Transaksi extends Model
{
    public function all(string $q = '', string $status = ''): array
    {
        $sql = 'SELECT t.*, p.nama AS pelanggan
                FROM transaksi t
                JOIN pelanggan p ON p.id = t.pelanggan_id
                WHERE 1 = 1';
        $params = [];

        if ($q !== '') {
            /* Escape wildcard LIKE supaya "%" atau "_" yang diketik user tidak
               berubah jadi wildcard yang membuat seluruh tabel dipindai. */
            $cari = polaLike($q);
            $sql .= ' AND (t.no_nota LIKE ? OR p.nama LIKE ? OR p.telepon LIKE ?)';
            array_push($params, $cari, $cari, $cari);
        }

        if ($status !== '' && in_array($status, STATUS_LAYANAN, true)) {
            $sql .= ' AND t.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY t.id DESC LIMIT 200';

        $st = $this->db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db()->prepare(
            'SELECT t.*, p.nama AS pelanggan, p.telepon, p.alamat
             FROM transaksi t JOIN pelanggan p ON p.id = t.pelanggan_id
             WHERE t.id = ?'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function detailItems(int $id): array
    {
        $st = $this->db()->prepare(
            'SELECT d.*, k.nama AS paket
             FROM transaksi_detail d JOIN paket k ON k.id = d.paket_id
             WHERE d.transaksi_id = ? ORDER BY d.id'
        );
        $st->execute([$id]);
        return $st->fetchAll();
    }

    public function terbaru(int $limit): array
    {
        $st = $this->db()->prepare(
            'SELECT t.*, p.nama AS pelanggan
             FROM transaksi t JOIN pelanggan p ON p.id = t.pelanggan_id
             ORDER BY t.id DESC LIMIT ?'
        );
        $st->execute([$limit]);
        return $st->fetchAll();
    }

    public function siapDiambil(int $limit): array
    {
        $st = $this->db()->prepare(
            "SELECT t.*, p.nama AS pelanggan
             FROM transaksi t JOIN pelanggan p ON p.id = t.pelanggan_id
             WHERE t.status = 'siap_diambil'
             ORDER BY t.tanggal_masuk ASC LIMIT ?"
        );
        $st->execute([$limit]);
        return $st->fetchAll();
    }

    /** Jumlah cucian yang menunggu diambil (badge di menu Transaksi). */
    public function jumlahSiapDiambil(): int
    {
        return (int)$this->db()->query(
            "SELECT COUNT(*) FROM transaksi WHERE status = 'siap_diambil'"
        )->fetchColumn();
    }

    /** Generator no nota: PREFIX-YYYYMMDD-XXX (urutan per hari). Prefix dari Pengaturan. */
    public function buatNoNota(string $tanggalMasuk): string
    {
        $prefixSetting = strtoupper(trim((string)pengaturan('prefix_nota', 'LND')));
        $prefixSetting = preg_replace('/[^A-Z0-9]/', '', $prefixSetting) ?: 'LND';
        $prefixSetting = mb_substr($prefixSetting, 0, 8);
        $prefix = $prefixSetting . '-' . date('Ymd', strtotime($tanggalMasuk)) . '-';
        $st = $this->db()->prepare('SELECT no_nota FROM transaksi WHERE no_nota LIKE ? ORDER BY no_nota DESC LIMIT 1');
        $st->execute([$prefix . '%']);
        $last = $st->fetchColumn();
        $bagian = $last ? explode('-', (string)$last) : [];
        $seq    = $last ? ((int)end($bagian) + 1) : 1;
        return $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Simpan transaksi baru (id=0) atau perbarui transaksi lama (id>0).
     * Hitung total otomatis, no nota lama tetap dipakai saat edit.
     * Mengembalikan id transaksi. Melempar RuntimeException bila input tidak valid.
     */
    public function simpan(array $in, int $id = 0): int
    {
        $db = $this->db();

        $pelangganId = (int)($in['pelanggan_id'] ?? 0);
        $tanggal     = (string)($in['tanggal_masuk'] ?? date('Y-m-d'));
        $uangMuka    = max(0, (float)($in['uang_muka'] ?? 0));
        $keterangan  = trim((string)($in['keterangan'] ?? ''));
        $paketIds    = array_values((array)($in['paket_id'] ?? []));
        $berats      = array_values((array)($in['berat'] ?? []));

        if (!$pelangganId || empty($paketIds)) {
            throw new \RuntimeException('Pilih pelanggan dan minimal satu layanan.');
        }

        /* Lookup paket */
        $paketSemua = [];
        foreach ($db->query('SELECT * FROM paket') as $pk) {
            $paketSemua[$pk['id']] = $pk;
        }

        $detail   = [];
        $totBerat = 0.0;
        $totHarga = 0.0;
        $maxHari  = 0;

        foreach ($paketIds as $i => $pid) {
            $pid   = (int)$pid;
            $berat = (float)($berats[$i] ?? 0);
            if (!$pid || $berat <= 0 || !isset($paketSemua[$pid])) {
                continue;
            }
            /* Subtotal per baris dibulatkan ke rupiah penuh, sama persis dengan
               perhitungan di browser (app.js memakai Math.round). Bila server
               memakai 2 desimal sementara layar memakai rupiah penuh, jumlah
               baris detail tidak akan cocok dengan total nota saat direkonsiliasi. */
            $subtotal = (float)round((float)$paketSemua[$pid]['harga_per_kg'] * $berat);
            $detail[] = ['paket' => $paketSemua[$pid], 'berat' => $berat, 'subtotal' => $subtotal];
            $totBerat += $berat;
            $totHarga += $subtotal;
            if ((int)$paketSemua[$pid]['estimasi_hari'] > $maxHari) {
                $maxHari = (int)$paketSemua[$pid]['estimasi_hari'];
            }
        }

        if (!$detail) {
            throw new \RuntimeException('Isi paket dan berat dengan benar (minimal satu baris valid).');
        }

        $totBerat = round($totBerat, 2);
        $subtotal = round($totHarga);   // jumlah baris detail, sudah sama dengan pembulatan layar

        /* Pajak layanan & diskon mengikuti Pengaturan (menu Pajak & Diskon). */
        $pajakPersen = max(0, (float)pengaturan('pajak_persen', '0'));
        $diskonMaks  = max(0, (float)pengaturan('diskon_maks_persen', '0'));
        $dpMinimal   = max(0, (float)pengaturan('dp_minimal_persen', '0'));

        $pajak  = round($subtotal * $pajakPersen / 100);
        $diskon = max(0, (float)($in['diskon'] ?? 0));
        if ($diskonMaks > 0 && $diskon > $subtotal * $diskonMaks / 100) {
            throw new \RuntimeException('Diskon melebihi batas maksimal ' . round($diskonMaks)
                . '% dari subtotal (maks. ' . rupiah((float)round($subtotal * $diskonMaks / 100)) . ').');
        }
        if ($diskon > $subtotal) {
            $diskon = $subtotal;
        }
        $diskon = round($diskon);

        $totHarga = round($subtotal + $pajak - $diskon);
        if ($totHarga < 0) {
            $totHarga = 0;
        }
        if ($uangMuka > $totHarga) {
            $uangMuka = $totHarga;
        }
        /* Aturan DP minimal (bila > 0): uang muka wajib minimal X% dari total. */
        if ($dpMinimal > 0) {
            $minDp = (int)ceil($totHarga * $dpMinimal / 100);
            if ($uangMuka < $minDp) {
                throw new \RuntimeException('Uang muka minimal ' . rupiah($minDp) . ' ('
                    . round($dpMinimal) . '% dari total, sesuai pengaturan).');
            }
        }
        $statusBayar = $uangMuka >= $totHarga ? 'lunas' : 'belum';
        $estimasi = date('Y-m-d', strtotime($tanggal . " +{$maxHari} days"));

        try {
            $db->beginTransaction();

            if ($id > 0) {
                /* ---- Mode edit: perbarui nota yang sudah ada ---- */
                $cek = $db->prepare('SELECT id FROM transaksi WHERE id = ?');
                $cek->execute([$id]);
                if (!$cek->fetch()) {
                    $db->rollBack();
                    throw new \RuntimeException('Transaksi tidak ditemukan.');
                }
                $st = $db->prepare(
                    'UPDATE transaksi SET pelanggan_id = ?, tanggal_masuk = ?, estimasi_selesai = ?,
                     total_berat = ?, total_harga = ?, diskon = ?, pajak = ?, uang_muka = ?,
                     status_bayar = ?, keterangan = ?
                     WHERE id = ?'
                );
                $st->execute([$pelangganId, $tanggal, $estimasi, $totBerat, $totHarga, $diskon, $pajak, $uangMuka, $statusBayar, $keterangan, $id]);
                $db->prepare('DELETE FROM transaksi_detail WHERE transaksi_id = ?')->execute([$id]);
                $trxId = $id;
            } else {
                /* ---- Mode baru: buat nota baru ---- */
                $noNota   = $this->buatNoNota($tanggal);
                $st = $db->prepare(
                    'INSERT INTO transaksi
                       (no_nota, pelanggan_id, tanggal_masuk, estimasi_selesai, total_berat,
                        total_harga, diskon, pajak, uang_muka, status, status_bayar, keterangan)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $st->execute([$noNota, $pelangganId, $tanggal, $estimasi, $totBerat, $totHarga, $diskon, $pajak, $uangMuka, 'diterima', $statusBayar, $keterangan]);
                $trxId = (int)$db->lastInsertId();
            }

            $stDet = $db->prepare('INSERT INTO transaksi_detail (transaksi_id, paket_id, berat, subtotal) VALUES (?, ?, ?, ?)');
            foreach ($detail as $d) {
                $stDet->execute([$trxId, (int)$d['paket']['id'], $d['berat'], $d['subtotal']]);
            }

            $db->commit();
            return $trxId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Ubah status layanan.
     *
     * - Bila status='selesai', tanggal_selesai diisi dengan hari ini.
     * - Bila nota dibuka lagi (dari selesai ke status lain), tanggal_selesai
     *   dikosongkan supaya laporan waktu pengerjaan tidak memakai tanggal lama.
     *
     * Mengembalikan false bila transaksi tidak ada, supaya controller tidak
     * menampilkan pesan sukses untuk nota yang sebenarnya tidak ada.
     */
    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, STATUS_LAYANAN, true)) {
            return false;
        }
        $tgl = $status === 'selesai' ? 'CURDATE()' : 'NULL';
        $st  = $this->db()->prepare(
            'UPDATE transaksi SET status = ?, tanggal_selesai = ' . $tgl . ' WHERE id = ?'
        );
        $st->execute([$status, $id]);
        return $st->rowCount() > 0;
    }

    /**
     * Perbarui uang muka; otomatis lunas bila uang muka >= total.
     * Mengembalikan sisa bayar, atau null bila transaksi tidak ditemukan.
     */
    public function updatePembayaran(int $id, float $uangMuka): ?float
    {
        $db = $this->db();
        $st = $db->prepare('SELECT * FROM transaksi WHERE id = ?');
        $st->execute([$id]);
        $t = $st->fetch();

        if (!$t) {
            return null;
        }

        $totalHarga = (float)$t['total_harga'];
        $uangMuka   = max(0, $uangMuka);
        if ($uangMuka > $totalHarga) {
            $uangMuka = $totalHarga;
        }
        $statusBayar = $uangMuka >= $totalHarga ? 'lunas' : 'belum';

        $st = $db->prepare('UPDATE transaksi SET uang_muka = ?, status_bayar = ? WHERE id = ?');
        $st->execute([$uangMuka, $statusBayar, $id]);

        return $totalHarga - $uangMuka;
    }

    public function hapus(int $id): void
    {
        $st = $this->db()->prepare('DELETE FROM transaksi WHERE id = ?');
        $st->execute([$id]);
    }

    /** Statistik untuk dashboard. */
    public function statDashboard(): array
    {
        $db  = $this->db();
        $st  = [];
        $st['pelanggan']    = (int)$db->query('SELECT COUNT(*) FROM pelanggan')->fetchColumn();
        $st['proses']       = (int)$db->query("SELECT COUNT(*) FROM transaksi WHERE status IN ('diterima','dicuci','disetrika')")->fetchColumn();
        $st['siap_diambil'] = (int)$db->query("SELECT COUNT(*) FROM transaksi WHERE status = 'siap_diambil'")->fetchColumn();

        $hari = date('Y-m-d');
        $q = $db->prepare(
            "SELECT COUNT(*) AS jml,
                    COALESCE(SUM(total_harga), 0) AS nilai,
                    COALESCE(SUM(CASE WHEN status_bayar = 'lunas' THEN total_harga ELSE uang_muka END), 0) AS diterima
             FROM transaksi WHERE tanggal_masuk = ?"
        );
        $q->execute([$hari]);
        $r = $q->fetch();
        $st['nota_hari_ini']  = (int)$r['jml'];
        $st['nilai_hari_ini'] = (float)$r['nilai'];
        $st['diterima_today'] = (float)$r['diterima'];

        $bln = date('Y-m');
        $q = $db->prepare("SELECT COALESCE(SUM(total_harga), 0) FROM transaksi WHERE DATE_FORMAT(tanggal_masuk, '%Y-%m') = ?");
        $q->execute([$bln]);
        $st['nilai_bulan_ini'] = (float)$q->fetchColumn();

        return $st;
    }

    /** Data laporan lengkap + ringkasan untuk rentang tanggal. */
    public function laporan(string $from, string $to): array
    {
        $st = $this->db()->prepare(
            'SELECT t.*, p.nama AS pelanggan
             FROM transaksi t JOIN pelanggan p ON p.id = t.pelanggan_id
             WHERE t.tanggal_masuk BETWEEN ? AND ?
             ORDER BY t.tanggal_masuk DESC, t.id DESC'
        );
        $st->execute([$from, $to]);
        $list = $st->fetchAll();

        $totNilai   = 0.0;
        $totBerat   = 0.0;
        $totDibayar = 0.0;
        $belumSelesai = 0;

        foreach ($list as $t) {
            $totBerat += (float)$t['total_berat'];
            $totNilai += (float)$t['total_harga'];
            $totDibayar += $t['status_bayar'] === 'lunas' ? (float)$t['total_harga'] : (float)$t['uang_muka'];
            if ($t['status'] !== 'selesai') {
                $belumSelesai++;
            }
        }

        return [
            'list'          => $list,
            'totNilai'      => $totNilai,
            'totBerat'      => $totBerat,
            'totDibayar'    => $totDibayar,
            'belumSelesai'  => $belumSelesai,
        ];
    }
}