<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KomposisiOlahan extends Model
{
    protected $table = 'komposisi_olahan';
    protected $primaryKey = 'id_komposisi';
    protected $fillable = ['id_jenis_sampah', 'id_jenis_olahan',];
    public $timestamps = false;
    
    public function jenisSampah()
    {
        return $this->belongsTo(JenisSampah::class, 'id_jenis_sampah', 'id_jenis_sampah');
    }

    public function jenisOlahan()
    {
        return $this->belongsTo(JenisOlahan::class, 'id_jenis_olahan', 'id_jenis_olahan');
    }

    public function detailPengolahanSampah()
    {
        return $this->hasMany(DetailPengolahanSampah::class, 'id_komposisi', 'id_komposisi');
    }
}
