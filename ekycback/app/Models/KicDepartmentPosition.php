<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicDepartmentPosition extends Model
{
    use HasFactory;
    protected $fillable = ['position','position_ar'];
    protected $table = 'kic_department_positions';
}
