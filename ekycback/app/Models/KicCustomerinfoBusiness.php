<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicCustomerinfoBusiness extends Model
{
    use HasFactory;
    protected $fillable = ['customerId','sectorId', 'departmentId', 'businessId'];
    protected $table = 'kic_customerinfo_business';
}
