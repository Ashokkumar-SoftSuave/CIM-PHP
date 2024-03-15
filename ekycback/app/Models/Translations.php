<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Translations extends Model
{

   // use SoftDeletes;


    protected $table = "kic_trans_table";
    protected $primaryKey = "id";
    protected $fillable = ['tenant_id', 'key_type', 'key_pos', 'key_name', 'value_ar', 'value_en'];
    // ...

    protected $auditInclude = [
        'tenant_id', 'key_type', 'key_pos', 'key_name', 'value_ar', 'value_en', 'svalue_ar', 'svalue_en', 'key_type', 'key_pos', 'key_name', 'value_ar', 'value_en', 'svalue_ar', 'svalue_en'
    ];

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];
}
