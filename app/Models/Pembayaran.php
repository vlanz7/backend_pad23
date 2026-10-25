<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';
    protected $fillable = [
        'id_pengumpulan_sampah',
        'id_admin',
        'jumlah_dibayar',
        'tanggal_bayar',
        'status',
    ];
    protected $casts = ['jumlah_dibayar' => 'decimal:2', 'tanggal_bayar' => 'date',];

    public function pengumpulanSampah()
    {
        return $this->belongsTo(PengumpulanSampah::class, 'id_pengumpulan_sampah', 'id_pengumpulan_sampah');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }
}
