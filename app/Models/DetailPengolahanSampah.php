<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPengolahanSampah extends Model
{
    protected $table = 'detail_pengolahan_sampah';
    protected $primaryKey = 'id_detail_pengolahan_sampah';
    protected $fillable = ['id_komposisi', 'id_olah_sampah', 'berat_digunakan',];
    public $timestamps = false;

    public function pengolahanSampah()
    {
        return $this->belongsTo(PengolahanSampah::class, 'id_olah_sampah', 'id_olah_sampah');
    }

    public function komposisiOlahan()
    {
        return $this->belongsTo(KomposisiOlahan::class, 'id_komposisi', 'id_komposisi');
    }
}
