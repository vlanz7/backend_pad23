<?php

namespace App\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

class StatusOperasional extends Model
{
    protected $table = 'status_operasional';
    protected $primaryKey = 'id_status_operasional';
    protected $fillable = ['status', 'updated_by',];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'updated_by', 'id_admin');
    }
}
