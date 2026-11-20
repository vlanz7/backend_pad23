<?php 
 
namespace App\Http\Controllers; 
 
use App\Models\JenisSampah; 
use App\Models\LogAktivitas; 
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Validator; 
 
class JenisSampahController extends Controller 
{ 
    private function catatLog(string $aksi, string $entitas, int $idEntitas, $dataLama = null,
    $dataBaru = null, ?string $keterangan = null): void
    {
        LogAktivitas::create([
            'id_admin' => Auth::user()->id_admin,
            'aksi' => $aksi,
            'entitas' => $entitas,
            'id_entitas' => $idEntitas,
            'data_lama' => $dataLama === null ? null : json_encode($dataLama, JSON_UNESCAPED_UNICODE),
            'data_baru' => $dataBaru === null ? null : json_encode($dataBaru, JSON_UNESCAPED_UNICODE),
            'keterangan' => $keterangan,
        ]);
    }

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
                'updated_by' => $request->user()->id_admin,
                'nama_sampah' => $request->nama_sampah, 
                'satuan_massa' => $request->satuan_massa, 
                'tarif_jasa_pengelolaan' => $request->tarif_jasa_pengelolaan, 
            ]); 
            $dataBaru = [
                'nama_sampah' => $jenisSampah->nama_sampah,
                'satuan_massa' => $jenisSampah->satuan_massa,
                'tarif_jasa_pengelolaan' => $jenisSampah->tarif_jasa_pengelolaan
            ];
            $this->catatLog(
                'create',
                'jenis_sampah',
                $jenisSampah->id_jenis_sampah,
                null,
                $dataBaru,
                'Menambahkan jenis sampah baru.'
            );
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
 
        DB::transaction(function () use ($jenisSampah, $dataLama, $dataBaru, $request) { 
            $jenisSampah->update([ 
                'updated_by' => $request->user()->id_admin,
                'nama_sampah' => $dataBaru['nama_sampah'], 
                'satuan_massa' => $dataBaru['satuan_massa'], 
                'tarif_jasa_pengelolaan' => $dataBaru['tarif_jasa_pengelolaan'], 
            ]); 
 
            if ($dataLama != $dataBaru) { 
                $this->catatLog(
                    'update',
                    'jenis_sampah',
                    $jenisSampah->id_jenis_sampah,
                    $dataLama,
                    $dataBaru,
                    'Memperbarui data jenis sampah.'
                ); 
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
        $dataLama = [
            'nama_sampah' => $jenisSampah->nama_sampah,
            'satuan_massa' => $jenisSampah->satuan_massa,
            'tarif_jasa_pengelolaan' => $jenisSampah->tarif_jasa_pengelolaan
        ];
        DB::transaction(function () use ($jenisSampah, $dataLama) {
            $idJenisSampah = $jenisSampah->id_jenis_sampah;
            $jenisSampah->delete();
            $this->catatLog(
                'delete',
                'jenis_sampah',
                $idJenisSampah,
                $dataLama,
                null,
                'Menghapus jenis sampah.'
            );
        });
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