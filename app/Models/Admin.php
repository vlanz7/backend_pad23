<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class admin extends Model
{
    protected $table = 'admin';
    protected $primaryKey = 'id_admin';
    protected $fillable = [
        'nama',
        'username',
        'password',
        'role',
        'status',
    ];
    public function statusOperasional()
    {
        return $this->hasMany(StatusOperasional::class, 'updated_by', 'id_admin');
    }

    public function logAktivitas()
    {
        return $this->hasMany(LogAktivitas::class, 'id_admin', 'id_admin');
    }

    public function warga()
    {
        return $this->hasMany(Warga::class, 'updated_by', 'id_admin');
    }

    public function jenisSampah()
    {
        return $this->hasMany(JenisSampah::class, 'updated_by', 'id_admin');
    }

    public function jenisOlahan()
    {
        return $this->hasMany(JenisOlahan::class, 'updated_by', 'id_admin');
    }

    public function pengumpulanSampah()
    {
        return $this->hasMany(PengumpulanSampah::class, 'id_admin', 'id_admin');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'id_admin', 'id_admin');
    }

    public function pengolahanSampah()
    {
        return $this->hasMany(PengolahanSampah::class, 'id_admin', 'id_admin');
    }

    public function picPengolah()
    {
        return $this->hasMany(PicPengolah::class, 'updated_by', 'id_admin');
    }
}
