<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Aktivitas;

/**
 * Halaman Log Aktivitas: riwayat "siapa melakukan apa" untuk audit.
 *
 * HAK: root & manager (lihat HAK_AKSES['aktivitas']).
 * Route: index.php?r=aktivitas
 */
class AktivitasController extends Controller
{
    public function index(): void
    {
        $this->requireHak('aktivitas');

        $this->view('aktivitas/index', [
            'pageTitle' => 'Log Aktivitas',
            'active'    => 'aktivitas',
            'list'      => (new Aktivitas())->terbaru(200),
        ]);
    }
}