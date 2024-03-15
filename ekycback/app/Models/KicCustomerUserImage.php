<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicCustomerUserImage extends Model
{
    use HasFactory;
    protected $fillable = ['customerId', 'image'];
    protected $table = 'kic_customerinfo_userimage';
}
