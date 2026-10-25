<?php 
 
namespace App\Http\Controllers; 
 
use App\Models\JenisOlahan; 
use App\Models\LogAktivitas;
use App\Models\KomposisiOlahan; 
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Validator; 
 
class JenisOlahanController extends Controller 
{ 
    public function index(Request $request) 
    { 
        $limit = min((int) $request->query('limit', 10), 10); 
        $jenisOlahan = JenisOlahan::orderBy('id_jenis_olahan', 'asc')->paginate($limit, ['*'], 'page', $request->query('page', 1)); 
        $jenisOlahan->setCollection($jenisOlahan->getCollection()->map(fn ($item) => $item->except(['updated_by']))); 
        return response()->json([ 
            'status' => true, 
            'message' => 'Daftar jenis olahan berhasil diambil.', 
            'data' => $jenisOlahan 
        ], 200); 
    } 
 
    public function store(Request $request) 
    { 
        $validator = Validator::make($request->all(), [ 
            'nama_olahan' => 'required|string|max:255', 
            'tarif_nilai_olahan' => 'required|numeric|min:0', 
            'deskripsi' => 'nullable|string', 
            'satuan_massa' => 'required|string|max:255', 
        ]); 
        if ($validator->fails()) { 
            return response()->json([ 
                'status' => false, 
                'message' => 'Data yang diberikan tidak valid.', 
                'errors' => $validator->errors() 
            ], 422); 
        } 
        $jenisOlahan = DB::transaction(function () use ($request) { 
            $jenisOlahan = JenisOlahan::create([ 
                'updated_by' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT 
                'nama_olahan' => $request->nama_olahan, 
                'tarif_nilai_olahan' => $request->tarif_nilai_olahan, 
                'deskripsi' => $request->deskripsi, 
                'satuan_massa' => $request->satuan_massa, 
            ]); 
            LogAktivitas::create([ 
                'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT 
                'aksi' => 'create', 
                'entitas' => 'jenis_olahan', 
                'id_entitas' => $jenisOlahan->id_jenis_olahan, 
                'data_lama' => null, 
                'data_baru' => json_encode([ 
                    'nama_olahan' => $jenisOlahan->nama_olahan, 
                    'tarif_nilai_olahan' => $jenisOlahan->tarif_nilai_olahan, 
                    'deskripsi' => $jenisOlahan->deskripsi, 
                    'satuan_massa' => $jenisOlahan->satuan_massa 
                ]), 
                'keterangan' => 'Menambahkan jenis olahan baru.' 
            ]); 
            return $jenisOlahan; 
        }); 
        return response()->json([ 
            'status' => true, 
            'message' => 'Jenis olahan berhasil ditambahkan.', 
            'data' => $jenisOlahan->except(['updated_by']) 
        ], 201); 
    } 
 
    public function update(Request $request, $id) 
    { 
        $jenisOlahan = JenisOlahan::find($id); 
        if (!$jenisOlahan) { 
            return response()->json([ 
                'status' => false, 
                'message' => 'Jenis olahan tidak ditemukan.', 
                'data' => null 
            ], 404); 
        } 
        $validator = Validator::make($request->all(), [ 
            'nama_olahan' => 'required|string|max:255', 
            'tarif_nilai_olahan' => 'required|numeric|min:0', 
            'deskripsi' => 'nullable|string', 
            'satuan_massa' => 'required|string|max:255', 
        ]); 
        if ($validator->fails()) { 
            return response()->json([ 
                'status' => false, 
                'message' => 'Data yang diberikan tidak valid.', 
                'errors' => $validator->errors() 
            ], 422); 
        } 
 
        $dataLama = [ 
            'nama_olahan' => $jenisOlahan->nama_olahan, 
            'tarif_nilai_olahan' => $jenisOlahan->tarif_nilai_olahan, 
            'deskripsi' => $jenisOlahan->deskripsi, 
            'satuan_massa' => $jenisOlahan->satuan_massa 
        ]; 
        $dataBaru = [ 
            'nama_olahan' => $request->nama_olahan, 
            'tarif_nilai_olahan' => $request->tarif_nilai_olahan, 
            'deskripsi' => $request->deskripsi, 
            'satuan_massa' => $request->satuan_massa 
        ]; 
 
        DB::transaction(function () use ($jenisOlahan, $dataLama, $dataBaru) { 
            $jenisOlahan->update([ 
                'updated_by' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT 
                'nama_olahan' => $dataBaru['nama_olahan'], 
                'tarif_nilai_olahan' => $dataBaru['tarif_nilai_olahan'], 
                'deskripsi' => $dataBaru['deskripsi'], 
                'satuan_massa' => $dataBaru['satuan_massa'], 
            ]); 
            if ($dataLama != $dataBaru) { 
                LogAktivitas::create([ 
                    'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT 
                    'aksi' => 'update', 
                    'entitas' => 'jenis_olahan', 
                    'id_entitas' => $jenisOlahan->id_jenis_olahan, 
                    'data_lama' => json_encode($dataLama), 
                    'data_baru' => json_encode($dataBaru), 
                    'keterangan' => 'Memperbarui data jenis olahan.' 
                ]); 
            } 
        }); 
        $jenisOlahan->refresh(); 
        return response()->json([ 
            'status' => true, 
            'message' => 'Jenis olahan berhasil diperbarui.', 
            'data' => $jenisOlahan->except(['updated_by']) 
        ], 200); 
    } 
 
    public function destroy($id) 
    { 
        $jenisOlahan = JenisOlahan::find($id); 
        if (!$jenisOlahan) { 
            return response()->json([ 
                'status' => false, 
                'message' => 'Jenis olahan tidak ditemukan.', 
                'data' => null 
            ], 404); 
        } 
        $jenisOlahan->delete(); 
        return response()->json([ 
            'status' => true, 
            'message' => 'Jenis olahan berhasil dihapus.', 
            'data' => new \stdClass() 
        ], 200); 
    } 

    public function indexKomposisi($id)
    {
        $jenisOlahan = JenisOlahan::find($id);
        if (!$jenisOlahan) {
            return response()->json([
                'status' => false,
                'message' => 'Jenis olahan tidak ditemukan.',
                'data' => null
            ], 404);
        }
        $komposisi = KomposisiOlahan::where('id_jenis_olahan', $id)
            ->with('jenisSampah')
            ->get();
        return response()->json([
            'status' => true,
            'message' => 'Komposisi jenis olahan berhasil diambil.',
            'data' => $komposisi
        ], 200);
    }

    public function storeKomposisi(Request $request, $id)
    {
        $jenisOlahan = JenisOlahan::find($id);
        if (!$jenisOlahan) {
            return response()->json([
                'status' => false,
                'message' => 'Jenis olahan tidak ditemukan.',
                'data' => null
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'id_jenis_sampah' => 'required|exists:jenis_sampah,id_jenis_sampah',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $komposisiSudahAda = KomposisiOlahan::where('id_jenis_olahan', $id)
            ->where('id_jenis_sampah', $request->id_jenis_sampah)
            ->exists();
        if ($komposisiSudahAda) {
            return response()->json([
                'status' => false,
                'message' => 'Jenis sampah tersebut sudah menjadi komposisi pada jenis olahan ini.',
                'data' => null
            ], 422);
        }
        $komposisi = DB::transaction(function () use ($request, $jenisOlahan) {
            $komposisi = KomposisiOlahan::create([
                'id_jenis_sampah' => $request->id_jenis_sampah,
                'id_jenis_olahan' => $jenisOlahan->id_jenis_olahan,
            ]);
            LogAktivitas::create([
                'id_admin' => 1, // SEMENTARA KARENA AUTH BELUM DIBUAT
                'aksi' => 'create',
                'entitas' => 'komposisi_olahan',
                'id_entitas' => $komposisi->id_komposisi,
                'data_lama' => null,
                'data_baru' => json_encode([
                    'id_jenis_olahan' => $komposisi->id_jenis_olahan,
                    'id_jenis_sampah' => $komposisi->id_jenis_sampah
                ]),
                'keterangan' => 'Menambahkan jenis sampah sebagai komposisi jenis olahan.'
            ]);
            return $komposisi;
        });
        $komposisi->load('jenisSampah');
        return response()->json([
            'status' => true,
            'message' => 'Komposisi jenis olahan berhasil ditambahkan.',
            'data' => $komposisi
        ], 201);
    }

    public function destroyKomposisi($id, $id_komposisi) 
    { 
        $jenisOlahan = JenisOlahan::find($id); 
        if (!$jenisOlahan) { 
            return response()->json([ 
                'status' => false, 
                'message' => 'Jenis olahan tidak ditemukan.', 
                'data' => null 
            ], 404); 
        } 
        $komposisi = KomposisiOlahan::where('id_komposisi', $id_komposisi)
        ->where('id_jenis_olahan', $id)
        ->first();
        if (!$komposisi) {
            return response()->json([
                'status' => false,
                'message' => 'Komposisi tidak ditemukan pada jenis olahan tersebut.',
                'data' => null
            ], 404);
        }
        $komposisi->delete(); 
        return response()->json([ 
            'status' => true, 
            'message' => 'Komposisi jenis olahan berhasil dihapus.', 
            'data' => new \stdClass() 
        ], 200); 
    }
}