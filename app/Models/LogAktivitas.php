<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';
    protected $primaryKey = 'id_log_aktivitas';
    protected $fillable = [
        'id_admin',	
        'aksi',	
        'entitas',	
        'id_entitas',
        'data_lama',	
        'data_baru',	
        'keterangan',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }
}
