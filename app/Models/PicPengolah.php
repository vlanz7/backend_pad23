<?php

namespace App\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

class PicPengolah extends Model
{
    protected $table = 'pic_pengolah';
    protected $primaryKey = 'id_pic';
    protected $fillable = ['updated_by','nama','nomor_wa', 'status',];

    public function pengolahanSampah()
    {
        return $this->hasMany(PengolahanSampah::class, 'id_pic', 'id_pic');
    }
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'updated_by', 'id_admin');
    }
}
