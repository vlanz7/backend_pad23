<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

class PublicDashboardController extends Controller
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
        $tanggalAwal = $awalGrafik->toDateString();
        $tanggalAkhir = $akhirBulan->toDateString();
        $pengumpulan = $this->getDataPengumpulan($tanggalAwal, $tanggalAkhir);
        $pengolahan = $this->getDataPengolahan($tanggalAwal, $tanggalAkhir);
        $hasilOlahan = $this->getDataHasilOlahan($tanggalAwal, $tanggalAkhir);
        $keyPeriode = $periode->format('Y-m');
        $dataPengumpulan = $pengumpulan->get($keyPeriode);
        $dataPengolahan = $pengolahan->get($keyPeriode);
        $dataHasil = $hasilOlahan->get($keyPeriode);
        $beratTerkumpul = (float) ($dataPengumpulan->total_berat ?? 0);
        $beratTerolah = (float) ($dataPengolahan->total_berat ?? 0);
        $beratHasil = (float) ($dataHasil->total_berat ?? 0);
        $nilaiSampah = (float) ($dataPengumpulan->total_nilai ?? 0);
        $persentase = $beratTerkumpul > 0 ? round(($beratTerolah / $beratTerkumpul) * 100, 2): 0;
        $grafik = [];
        for ($i = 0; $i < $jumlahBulan; $i++) {
            $tanggal = $awalGrafik->addMonths($i);
            $key = $tanggal->format('Y-m');
            $grafik[] = [
                'bulan' => $tanggal->month,
                'tahun' => $tanggal->year,
                'label' => $tanggal->translatedFormat('M'),
                'sampah_terkumpul' => (float) (
                    $pengumpulan->get($key)->total_berat ?? 0
                ),
                'sampah_terolah' => (float) (
                    $pengolahan->get($key)->total_berat ?? 0
                ),
                'hasil_olahan' => (float) (
                    $hasilOlahan->get($key)->total_berat ?? 0
                ),
            ];
        }
        return response()->json([
            'status' => true,
            'message' => 'Data public dashboard berhasil diambil.',
            'data' => [
                'hero' => [
                    'persentase_sampah_diolah' => $persentase,
                    'berat_terkumpul' => $beratTerkumpul,
                    'berat_terolah' => $beratTerolah,
                ],
                'ringkasan_periode' => [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'label_periode' => $periode->translatedFormat('F Y'),
                    'sampah_terkumpul' => [
                        'berat' => $beratTerkumpul,
                        'jumlah_setoran' => (int) ($dataPengumpulan->jumlah_setoran ?? 0),
                    ],
                    'sampah_terolah' => ['berat' => $beratTerolah,],
                    'hasil_olahan' => ['berat' => $beratHasil,],
                    'nilai_sampah' => $nilaiSampah,
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
            ->where('status_publikasi', 'Sudah Dipublikasi')
            ->whereBetween('tanggal_direkap', [$awal, $akhir])
            ->selectRaw("
                DATE_FORMAT(tanggal_direkap, '%Y-%m') as periode,
                SUM(berat_hasil_olahan) as total_berat,
                SUM(nilai_jual) as total_nilai
            ")
            ->groupByRaw("DATE_FORMAT(tanggal_direkap, '%Y-%m')")
            ->get()
            ->keyBy('periode');
    }
}
