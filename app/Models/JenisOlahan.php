<?php

namespace App\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

class JenisOlahan extends Model
{
    protected $table = 'jenis_olahan';
    protected $primaryKey = 'id_jenis_olahan';
    protected $fillable = ['updated_by', 'nama_olahan', 'tarif_nilai_olahan', 'deskripsi','satuan_massa',];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'updated_by', 'id_admin');
    }

    public function komposisiOlahan()
    {
        return $this->hasMany(KomposisiOlahan::class, 'id_jenis_olahan', 'id_jenis_olahan');
    }

    public function pengolahanSampah()
    {
        return $this->hasMany(PengolahanSampah::class, 'id_jenis_olahan', 'id_jenis_olahan');
    }
}
