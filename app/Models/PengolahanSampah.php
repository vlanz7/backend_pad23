<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengolahanSampah extends Model
{
    protected $table = 'pengolahan_sampah';
    protected $primaryKey = 'id_olah_sampah';
    protected $fillable = ['id_jenis_olahan', 'id_admin', 'id_pic', 'status', 'tgl_mulai','tgl_selesai',];
    public $timestamps = false;

    public function jenisOlahan()
    {
        return $this->belongsTo(JenisOlahan::class, 'id_jenis_olahan', 'id_jenis_olahan');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }

    public function picPengolah()
    {
        return $this->belongsTo(PicPengolah::class, 'id_pic', 'id_pic');
    }

     public function detailPengolahanSampah()
    {
        return $this->hasMany(DetailPengolahanSampah::class, 'id_olah_sampah', 'id_olah_sampah');
    }

    public function hasilOlahan()
    {
        return $this->hasOne(HasilOlahan::class, 'id_olah_sampah', 'id_olah_sampah');
    }
}
