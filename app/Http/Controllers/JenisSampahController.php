<?php 
 
namespace App\Http\Controllers; 
 
use App\Models\JenisSampah; 
use App\Models\LogAktivitas; 
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Validator; 
 
class JenisSampahController extends Controller 
{ 
    public function index(Request $request) 
    {
        $limit = min((int) $request->query('limit', 10), 10);
        $jenisSampah = JenisSampah::orderBy('id_jenis_sampah', 'asc')->paginate($limit, ['*'], 'page', $request->query('page', 1));
        $jenisSampah->setCollection($jenisSampah->getCollection()->map(fn ($item) => $item->except(['updated_by'])));
        return response()->json([ 
            'status' => true, 
            'message' => 'Daftar jenis sampah berhasil diambil.', 
            'data' => $jenisSampah 
        ], 200); 
    } 
 
    public function store(Request $request) 
    { 
        $validator = Validator::make($request->all(), [ 
            'nama_sampah' => 'required|string|max:255', 
            'satuan_massa' => 'required|string|max:255', 
            'tarif_jasa_pengelolaan' => 'required|numeric|min:0', 
        ]); 
        if ($validator->fails()) { 
            return response()->json([ 
                'status' => false, 
                'message' => 'Data yang diberikan tidak valid.', 
                'errors' => $validator->errors() 
            ], 422); 
        } 
        $jenisSampah = DB::transaction(function () use ($request) { 
            $jenisSampah = JenisSampah::create([ 
                'updated_by' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT 
                'nama_sampah' => $request->nama_sampah, 
                'satuan_massa' => $request->satuan_massa, 
                'tarif_jasa_pengelolaan' => $request->tarif_jasa_pengelolaan, 
            ]); 
            LogAktivitas::create([ 
                'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT 
                'aksi' => 'create', 
                'entitas' => 'jenis_sampah', 
                'id_entitas' => $jenisSampah->id_jenis_sampah, 
                'data_lama' => null, 
                'data_baru' => json_encode([ 
                    'nama_sampah' => $jenisSampah->nama_sampah, 
                    'satuan_massa' => $jenisSampah->satuan_massa, 
                    'tarif_jasa_pengelolaan' => $jenisSampah->tarif_jasa_pengelolaan 
                ]), 
                'keterangan' => 'Menambahkan jenis sampah baru.' 
            ]); 
            return $jenisSampah; 
        }); 
        return response()->json([ 
            'status' => true, 
            'message' => 'Jenis sampah berhasil ditambahkan.', 
            'data' => $jenisSampah->except(['updated_by']) 
        ], 201); 
    } 
 
    public function update(Request $request, $id) 
    { 
        $jenisSampah = JenisSampah::find($id); 
        if (!$jenisSampah) { 
            return response()->json([ 
                'status' => false, 
                'message' => 'Jenis sampah tidak ditemukan.', 
                'data' => null 
            ], 404); 
        } 
        $validator = Validator::make($request->all(), [ 
            'nama_sampah' => 'required|string|max:255', 
            'satuan_massa' => 'required|string|max:255', 
            'tarif_jasa_pengelolaan' => 'required|numeric|min:0', 
        ]); 
        if ($validator->fails()) { 
            return response()->json([ 
                'status' => false, 
                'message' => 'Data yang diberikan tidak valid.', 
                'errors' => $validator->errors() 
            ], 422); 
        } 
 
        $dataLama = [ 
            'nama_sampah' => $jenisSampah->nama_sampah, 
            'satuan_massa' => $jenisSampah->satuan_massa, 
            'tarif_jasa_pengelolaan' => $jenisSampah->tarif_jasa_pengelolaan 
        ]; 
        $dataBaru = [ 
            'nama_sampah' => $request->nama_sampah, 
            'satuan_massa' => $request->satuan_massa, 
            'tarif_jasa_pengelolaan' => $request->tarif_jasa_pengelolaan 
        ]; 
 
        DB::transaction(function () use ($jenisSampah, $dataLama, $dataBaru) { 
            $jenisSampah->update([ 
                'updated_by' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT 
                'nama_sampah' => $dataBaru['nama_sampah'], 
                'satuan_massa' => $dataBaru['satuan_massa'], 
                'tarif_jasa_pengelolaan' => $dataBaru['tarif_jasa_pengelolaan'], 
            ]); 
 
            if ($dataLama != $dataBaru) { 
                LogAktivitas::create([ 
                    'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT 
                    'aksi' => 'update', 
                    'entitas' => 'jenis_sampah', 
                    'id_entitas' => $jenisSampah->id_jenis_sampah, 
                    'data_lama' => json_encode($dataLama), 
                    'data_baru' => json_encode($dataBaru), 
                    'keterangan' => 'Memperbarui data jenis sampah.' 
                ]); 
            } 
        }); 
        $jenisSampah->refresh(); 
        return response()->json([ 
            'status' => true, 
            'message' => 'Jenis sampah berhasil diperbarui.', 
            'data' => $jenisSampah->except(['updated_by']) 
        ], 200); 
    } 
 
    public function destroy($id) 
    { 
        $jenisSampah = JenisSampah::find($id); 
        if (!$jenisSampah) { 
            return response()->json([ 
                'status' => false, 
                'message' => 'Jenis sampah tidak ditemukan.', 
                'data' => null 
            ], 404); 
        } 
 
        $jenisSampah->delete(); 
        return response()->json([ 
            'status' => true, 
            'message' => 'Jenis sampah berhasil dihapus.', 
            'data' => new \stdClass() 
        ], 200); 
    } 
 
    public function riwayatTarif($id) 
    { 
        $jenisSampah = JenisSampah::find($id); 
        if (!$jenisSampah) { 
            return response()->json([ 
                'status' => false, 
                'message' => 'Jenis sampah tidak ditemukan.', 
                'data' => null 
            ], 404); 
        } 
 
        $riwayat = LogAktivitas::where('entitas', 'jenis_sampah') 
            ->where('id_entitas', $id) 
            ->where('aksi', 'update') 
            ->with('admin') 
            ->orderBy('created_at', 'desc') 
            ->get() 
            ->map(function ($log) { 
                $dataLama = json_decode($log->data_lama, true); 
                $dataBaru = json_decode($log->data_baru, true); 
                return [ 
                    'id_log' => $log->id_log_aktivitas, 
                    'id_admin' => $log->id_admin, 
                    'nama_admin' => $log->admin ? $log->admin->nama : null, 
                    'tarif_lama' => $dataLama['tarif_jasa_pengelolaan'] ?? null, 
                    'tarif_baru' => $dataBaru['tarif_jasa_pengelolaan'] ?? null, 
                    'created_at' => $log->created_at, 
                ]; 
            }); 
        return response()->json([ 
            'status' => true, 
            'message' => 'Riwayat perubahan tarif berhasil diambil.', 
            'data' => $riwayat 
        ], 200); 
    } 
}