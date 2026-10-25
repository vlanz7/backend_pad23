<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilOlahan extends Model
{
    protected $table = 'hasil_olahan';
    protected $primaryKey = 'id_hasilolahan';

    public function pengolahanSampah()
    {
        return $this->belongsTo(PengolahanSampah::class, 'id_olah_sampah', 'id_olah_sampah');
    }
}
