<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicCustomerinfoCif extends Model
{
    use HasFactory;
    protected $fillable = ['custometId', 'sector_id', 'industryId', 'targetId', 'customerstatusId'];
    protected $table = 'kic_customerinfo_cif';
}
