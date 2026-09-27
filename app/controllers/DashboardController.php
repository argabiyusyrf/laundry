<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Transaksi;

/**
 * Halaman dashboard.
 */
class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireHak('dashboard');

        $trx = new Transaksi();

        $this->view('dashboard/index', [
            'pageTitle'   => 'Dashboard',
            'active'      => 'dashboard',
            'stat'        => $trx->statDashboard(),
            'siapDiambil' => $trx->siapDiambil(8),
            'terbaru'     => $trx->terbaru(6),
            'bolehLaporan'=> cekHak('laporan'),
        ]);
    }
}