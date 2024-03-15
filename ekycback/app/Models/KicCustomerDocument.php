<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicCustomerDocument extends Model
{
    use HasFactory;
    protected $fillable = ['customerId', 'file', 'filename'];
    protected $table = 'kic_customerinfo_document';

}
