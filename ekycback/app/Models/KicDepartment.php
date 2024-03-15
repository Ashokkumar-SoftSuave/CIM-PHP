<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicDepartment extends Model
{
    use HasFactory;
    protected $fillable = ['department','department_ar'];
    protected $table = 'kic_departments';
}
