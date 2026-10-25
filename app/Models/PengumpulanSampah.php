<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengumpulanSampah extends Model
{
    protected $table = 'pengumpulan_sampah';
    protected $primaryKey = 'id_pengumpulan_sampah';
    protected $fillable = [
        'id_warga',
        'id_admin',
        'status',
        'tanggal',
    ];
    protected $casts = ['tanggal' => 'date',];

    public function warga()
    {
        return $this->belongsTo(Warga::class, 'id_warga', 'id_warga');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }

    public function detailPengumpulanSampah()
    {
        return $this->hasMany(DetailPengumpulanSampah::class, 'id_pengumpulan_sampah', 'id_pengumpulan_sampah');
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'id_pengumpulan_sampah', 'id_pengumpulan_sampah');
    }
}
