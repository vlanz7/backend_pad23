<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPengumpulanSampah extends Model
{
    protected $table = 'detail_pengumpulan_sampah';
    protected $primaryKey = 'id_detail_pengumpulan_sampah';
    protected $fillable = [
        'id_pengumpulan_sampah',
        'id_jenis_sampah',
        'berat',
        'nilai',
    ];
    protected $casts = ['berat' => 'decimal:2', 'nilai' => 'decimal:2',];

    public function pengumpulanSampah()
    {
        return $this->belongsTo(PengumpulanSampah::class, 'id_pengumpulan_sampah', 'id_pengumpulan_sampah');
    }

    public function jenisSampah()
    {
        return $this->belongsTo(JenisSampah::class, 'id_jenis_sampah', 'id_jenis_sampah');
    }
}
