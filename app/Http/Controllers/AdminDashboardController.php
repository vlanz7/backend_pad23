<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'bulan' => 'sometimes|integer|between:1,12',
            'tahun' => 'sometimes|integer|between:2000,2100',
            'jumlah_bulan' => 'sometimes|integer|min:1|max:36',
        ]);
        $bulan = $validated['bulan'] ?? now()->month;
        $tahun = $validated['tahun'] ?? now()->year;
        $jumlahBulan = $validated['jumlah_bulan'] ?? 6;
        $periode = CarbonImmutable::createFromDate($tahun, $bulan, 1);
        $awalBulan = $periode->startOfMonth();
        $akhirBulan = $periode->endOfMonth();
        $awalGrafik = $awalBulan->subMonths($jumlahBulan - 1);
        $awal = $awalGrafik->toDateString();
        $akhir = $akhirBulan->toDateString();
        $operasional = [
            'batch_sedang_diolah' => DB::table('pengolahan_sampah')
                ->where('status', 'Sedang Diolah')
                ->count(),
            'antrean_pemilahan' => DB::table('pengumpulan_sampah')
                ->where('status', 'Menunggu Dipilah')
                ->count(),
            'pembayaran_menunggu' => DB::table('pembayaran')
                ->where('status', 'Menunggu Pembayaran')
                ->count(),
        ];
        $pengumpulan = $this->getDataPengumpulan($awal, $akhir);
        $pengolahan = $this->getDataPengolahan($awal, $akhir);
        $hasilOlahan = $this->getDataHasilOlahan($awal, $akhir);
        $keyPeriode = $periode->format('Y-m');
        $dataPengumpulan = $pengumpulan->get($keyPeriode);
        $dataPengolahan = $pengolahan->get($keyPeriode);
        $dataHasil = $hasilOlahan->get($keyPeriode);
        $grafik = [];
        for ($i = 0; $i < $jumlahBulan; $i++) {
            $tanggal = $awalGrafik->addMonths($i);
            $key = $tanggal->format('Y-m');
            $grafik[] = [
                'bulan' => $tanggal->month,
                'tahun' => $tanggal->year,
                'label' => $tanggal->translatedFormat('M'),
                'sampah_terkumpul' => (float) ($pengumpulan->get($key)->total_berat ?? 0),
                'sampah_terolah' => (float) ($pengolahan->get($key)->total_berat ?? 0),
                'hasil_olahan' => (float) ($hasilOlahan->get($key)->total_berat ?? 0),
            ];
        }
        return response()->json([
            'status' => true,
            'message' => 'Data admin dashboard berhasil diambil.',
            'data' => [
                'operasional' => $operasional,
                'ringkasan_periode' => [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'label_periode' => $periode->translatedFormat('F Y'),
                    'sampah_terkumpul' => [
                        'berat' => (float) ($dataPengumpulan->total_berat ?? 0),
                        'jumlah_setoran' => (int) ($dataPengumpulan->jumlah_setoran ?? 0),
                    ],
                    'sampah_terolah' => [
                        'berat' => (float) ($dataPengolahan->total_berat ?? 0),
                    ],
                    'hasil_olahan' => [
                        'berat' => (float) ($dataHasil->total_berat ?? 0),
                    ],
                    'nilai_sampah' => (float) ($dataPengumpulan->total_nilai ?? 0),
                    'warga_berpartisipasi' => (int) ($dataPengumpulan->jumlah_warga ?? 0),
                ],
                'grafik_perkembangan' => [
                    'jumlah_bulan' => $jumlahBulan,
                    'periode_awal' => $awalGrafik->format('Y-m'),
                    'periode_akhir' => $periode->format('Y-m'),
                    'data' => $grafik,
                ],
            ],
        ]);
    }

    private function getDataPengumpulan($awal, $akhir)
    {
        return DB::table('pengumpulan_sampah as ps')
            ->join(
                'detail_pengumpulan_sampah as dps',
                'ps.id_pengumpulan_sampah',
                '=',
                'dps.id_pengumpulan_sampah'
            )
            ->where('ps.status', 'Selesai Dipilah')
            ->whereBetween('ps.tanggal', [$awal, $akhir])
            ->selectRaw("
                DATE_FORMAT(ps.tanggal, '%Y-%m') as periode,
                SUM(dps.berat) as total_berat,
                SUM(dps.nilai) as total_nilai,
                COUNT(DISTINCT ps.id_pengumpulan_sampah) as jumlah_setoran,
                COUNT(DISTINCT ps.id_warga) as jumlah_warga
            ")
            ->groupByRaw("DATE_FORMAT(ps.tanggal, '%Y-%m')")
            ->get()
            ->keyBy('periode');
    }

    private function getDataPengolahan($awal, $akhir)
    {
        return DB::table('pengolahan_sampah as ps')
            ->join(
                'detail_pengolahan_sampah as dps',
                'ps.id_olah_sampah',
                '=',
                'dps.id_olah_sampah'
            )
            ->where('ps.status', 'Selesai Diolah')
            ->whereBetween(
                DB::raw('DATE(ps.tgl_selesai)'),
                [$awal, $akhir]
            )
            ->selectRaw("
                DATE_FORMAT(ps.tgl_selesai, '%Y-%m') as periode,
                SUM(dps.berat_digunakan) as total_berat
            ")
            ->groupByRaw("DATE_FORMAT(ps.tgl_selesai, '%Y-%m')")
            ->get()
            ->keyBy('periode');
    }

    private function getDataHasilOlahan($awal, $akhir)
    {
        return DB::table('hasil_olahan')
            ->whereBetween('tanggal_direkap', [$awal, $akhir])
            ->selectRaw("
                DATE_FORMAT(tanggal_direkap, '%Y-%m') as periode,
                SUM(berat_hasil_olahan) as total_berat
            ")
            ->groupByRaw("DATE_FORMAT(tanggal_direkap, '%Y-%m')")
            ->get()
            ->keyBy('periode');
    }
}