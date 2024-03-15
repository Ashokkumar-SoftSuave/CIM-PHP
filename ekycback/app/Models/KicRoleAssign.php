<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicRoleAssign extends Model
{
    use HasFactory;
    protected $fillable = ['department', 'departmentId', 'position', 'positionId', 'roleId'];
    protected $table = 'kic_role_assign';
}
