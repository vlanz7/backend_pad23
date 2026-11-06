<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilOlahan extends Model
{
    protected $table = 'hasil_olahan';
    protected $primaryKey = 'id_hasilolahan';
    public $timestamps = false;
    protected $fillable = ['id_olah_sampah','id_admin','berat_hasil_olahan','nilai_jual','status_publikasi','tanggal_direkap',];

    public function pengolahanSampah()
    {
        return $this->belongsTo(PengolahanSampah::class, 'id_olah_sampah', 'id_olah_sampah');
    }
}
