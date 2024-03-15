<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicCustomerSignature extends Model
{
    use HasFactory;
    protected $fillable = ['customerId', 'image'];
    protected $table = 'kic_customerinfo_signature';

}
