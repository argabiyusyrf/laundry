<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Transaksi;

/**
 * Laporan + export CSV.
 */
class LaporanController extends Controller
{
    /**
     * Ambil rentang tanggal dari input, dengan default bulan berjalan.
     *
     * Divalidasi agar:
     * - tanggal awal tidak melewati tanggal akhir (kalau terbalik, laporan kosong
     *   tanpa penjelasan sehingga dikira aplikasi error);
     * - tanggal tidak di masa depan (data laundry tidak bisa masa depan);
     * - rentang dibatasi maksimal 3 tahun, supaya satu request tidak memindai
     *   seluruh tabel transaksi.
     */
    private function rentang(): array
    {
        $dariBulan = date('Y-m-01');
        $hariIni   = date('Y-m-d');

        $from = trim((string)($_GET['from'] ?? $dariBulan));
        $to   = trim((string)($_GET['to'] ?? $hariIni));

        $tFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? strtotime($from) : false;
        $tTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? strtotime($to) : false;

        if ($tFrom === false) {
            $tFrom = strtotime($dariBulan);
        }
        if ($tTo === false) {
            $tTo = strtotime($hariIni);
        }

        if ($tTo > strtotime($hariIni)) {
            $tTo = strtotime($hariIni);
        }
        if ($tFrom > $tTo) {
            $tFrom = $tTo;
        }
        if ($tTo - $tFrom > 1095 * 86400) { // maksimal 3 tahun
            $tFrom = $tTo - 1095 * 86400;
        }

        return [
            'from' => date('Y-m-d', (int)$tFrom),
            'to'   => date('Y-m-d', (int)$tTo),
        ];
    }

    /**
     * Amankan isi sel CSV terhadap formula injection.
     *
     * Excel/LibreOffice menganggap sel yang diawali "=", "+", "-", "@", TAB,
     * atau CR sebagai formula. Nama pelanggan bebas diisi pengguna, sehingga
     * tanpa awalan kutip nilai seperti "=1+1" bisa dieksekusi spreadsheet.
     */
    private function selCsv($nilai): string
    {
        $teks = (string)$nilai;
        if ($teks !== '' && strpos("=+-@\t\r", $teks[0]) !== false) {
            return "'" . $teks;
        }
        return $teks;
    }

    public function index(): void
    {
        $this->requireHak('laporan');

        ['from' => $from, 'to' => $to] = $this->rentang();
        $data = (new Transaksi())->laporan($from, $to);

        $this->view('laporan/index', [
            'pageTitle' => 'Laporan',
            'active'    => 'laporan',
            'from'      => $from,
            'to'        => $to,
            'list'      => $data['list'],
            'totNilai'  => $data['totNilai'],
            'totBerat'  => $data['totBerat'],
            'totDibayar'=> $data['totDibayar'],
            'belumSelesai' => $data['belumSelesai'],
        ]);
    }

    public function export(): void
    {
        $this->requireHak('laporan');

        ['from' => $from, 'to' => $to] = $this->rentang();
        $list = (new Transaksi())->laporan($from, $to)['list'];

        logAktivitas('laporan_export', 'Export CSV laporan ' . $from . ' s/d ' . $to);

        $namaBerkas = 'laporan-laundry-' . $from . '_' . $to . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $namaBerkas . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar terbaca di Excel
        fputcsv($out, ['No Nota', 'Tanggal Masuk', 'Pelanggan', 'Berat (kg)', 'Total Harga', 'Dibayar', 'Status', 'Pembayaran']);

        foreach ($list as $t) {
            $dibayar = $t['status_bayar'] === 'lunas' ? $t['total_harga'] : $t['uang_muka'];
            fputcsv($out, [
                $this->selCsv($t['no_nota']),
                $t['tanggal_masuk'],
                $this->selCsv($t['pelanggan']),
                $t['total_berat'],
                $t['total_harga'],
                $dibayar,
                STATUS_LABEL[$t['status']] ?? $t['status'],
                $t['status_bayar'] === 'lunas' ? 'Lunas' : 'Belum Lunas',
            ]);
        }

        fclose($out);
        exit;
    }
}