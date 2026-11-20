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
            'mode_grafik' => 'sometimes|in:bulan,tahun',
        ]);

        $bulan = $validated['bulan'] ?? now()->month;
        $tahun = $validated['tahun'] ?? now()->year;
        $modeGrafik = $validated['mode_grafik'] ?? 'bulan';

        $periode = CarbonImmutable::createFromDate($tahun, $bulan, 1);
        $awalBulan = $periode->startOfMonth();
        $akhirBulan = $periode->endOfMonth();

        if ($modeGrafik == 'bulan') {
            $awalGrafik = CarbonImmutable::createFromDate($tahun, 1, 1)->startOfYear();
            $akhirGrafik = $awalGrafik->endOfYear();
        } else {
            $tahunAwal = $tahun - 9;
            $awalGrafik = CarbonImmutable::createFromDate($tahunAwal, 1, 1)->startOfYear();
            $akhirGrafik = CarbonImmutable::createFromDate($tahun, 12, 31)->endOfYear();
        }

        $tanggalAwal = $awalGrafik->toDateString();
        $tanggalAkhir = $akhirGrafik->toDateString();

        $dataStatistikPengumpulan = $this->getDataPengumpulan(
            $awalBulan->toDateString(),
            $akhirBulan->toDateString()
        )->get($periode->format('Y-m'));

        $dataStatistikPengolahan = $this->getDataPengolahan(
            $awalBulan->toDateString(),
            $akhirBulan->toDateString()
        )->get($periode->format('Y-m'));

        $dataStatistikHasil = $this->getDataHasilOlahan(
            $awalBulan->toDateString(),
            $akhirBulan->toDateString()
        )->get($periode->format('Y-m'));

        $beratTerkumpul = (float) ($dataStatistikPengumpulan->total_berat ?? 0);
        $beratTerolah = (float) ($dataStatistikPengolahan->total_berat ?? 0);
        $beratHasil = (float) ($dataStatistikHasil->total_berat ?? 0);
        $nilaiSampah = (float) ($dataStatistikPengumpulan->total_nilai ?? 0);

        $persentase = $beratTerkumpul > 0
            ? round(($beratTerolah / $beratTerkumpul) * 100, 2)
            : 0;

        $pengumpulan = $this->getDataPengumpulan($tanggalAwal, $tanggalAkhir, $modeGrafik);
        $pengolahan = $this->getDataPengolahan($tanggalAwal, $tanggalAkhir, $modeGrafik);
        $hasilOlahan = $this->getDataHasilOlahan($tanggalAwal, $tanggalAkhir, $modeGrafik);

        $grafik = [];

        if ($modeGrafik == 'bulan') {
            for ($i = 0; $i < 12; $i++) {
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
        } else {
            for ($i = 0; $i < 10; $i++) {
                $tanggal = $awalGrafik->addYears($i);
                $key = $tanggal->format('Y');

                $grafik[] = [
                    'tahun' => $tanggal->year,
                    'label' => $tanggal->year,
                    'sampah_terkumpul' => (float) ($pengumpulan->get($key)->total_berat ?? 0),
                    'sampah_terolah' => (float) ($pengolahan->get($key)->total_berat ?? 0),
                    'hasil_olahan' => (float) ($hasilOlahan->get($key)->total_berat ?? 0),
                ];
            }
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
                        'jumlah_setoran' => (int) ($dataStatistikPengumpulan->jumlah_setoran ?? 0),
                    ],
                    'sampah_terolah' => [
                        'berat' => $beratTerolah,
                    ],
                    'hasil_olahan' => [
                        'berat' => $beratHasil,
                    ],
                    'nilai_sampah' => $nilaiSampah,
                ],
                'grafik_perkembangan' => [
                    'mode' => $modeGrafik,
                    'tahun_awal' => $awalGrafik->year,
                    'tahun_akhir' => $tahun,
                    'data' => $grafik,
                ],
            ],
        ]);
    }

    private function getDataPengumpulan($awal, $akhir, $mode = 'bulan')
    {
        $format = $mode == 'bulan' ? '%Y-%m' : '%Y';

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
                DATE_FORMAT(ps.tanggal, '{$format}') as periode,
                SUM(dps.berat) as total_berat,
                SUM(dps.nilai) as total_nilai,
                COUNT(DISTINCT ps.id_pengumpulan_sampah) as jumlah_setoran,
                COUNT(DISTINCT ps.id_warga) as jumlah_warga
            ")
            ->groupByRaw("DATE_FORMAT(ps.tanggal, '{$format}')")
            ->get()
            ->keyBy('periode');
    }

    private function getDataPengolahan($awal, $akhir, $mode = 'bulan')
    {
        $format = $mode == 'bulan' ? '%Y-%m' : '%Y';

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
                DATE_FORMAT(ps.tgl_selesai, '{$format}') as periode,
                SUM(dps.berat_digunakan) as total_berat
            ")
            ->groupByRaw("DATE_FORMAT(ps.tgl_selesai, '{$format}')")
            ->get()
            ->keyBy('periode');
    }

    private function getDataHasilOlahan($awal, $akhir, $mode = 'bulan')
    {
        $format = $mode == 'bulan' ? '%Y-%m' : '%Y';

        return DB::table('hasil_olahan')
            ->where('status_publikasi', 'Sudah Dipublikasi')
            ->whereBetween('tanggal_direkap', [$awal, $akhir])
            ->selectRaw("
                DATE_FORMAT(tanggal_direkap, '{$format}') as periode,
                SUM(berat_hasil_olahan) as total_berat,
                SUM(nilai_jual) as total_nilai
            ")
            ->groupByRaw("DATE_FORMAT(tanggal_direkap, '{$format}')")
            ->get()
            ->keyBy('periode');
    }
}
