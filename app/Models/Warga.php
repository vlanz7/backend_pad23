<?php

namespace App\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

class Warga extends Model
{
    protected $table = 'warga';
    protected $primaryKey = 'id_warga';
    protected $fillable = [ 'nama', 'nomor_wa', 'status', 'updated_by', ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'updated_by', 'id_admin');
    }

    public function pengumpulanSampah()
    {
        return $this->hasMany(PengumpulanSampah::class, 'id_warga', 'id_warga');
    }
}
