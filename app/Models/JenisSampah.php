<?php

namespace App\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

class JenisSampah extends Model
{
    protected $table = 'jenis_sampah';
    protected $primaryKey = 'id_jenis_sampah';
    protected $fillable = ['updated_by', 'nama_sampah', 'satuan_massa', 'tarif_jasa_pengelolaan',];
    protected $casts = ['tarif_jasa_pengelolaan' => 'decimal:2',];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'updated_by', 'id_admin');
    }

    public function detailPengumpulanSampah()
    {
        return $this->hasMany(DetailPengumpulanSampah::class, 'id_jenis_sampah', 'id_jenis_sampah');
    }

    public function komposisiOlahan()
    {
        return $this->hasMany(KomposisiOlahan::class, 'id_jenis_sampah', 'id_jenis_sampah');
    }
}
