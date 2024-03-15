<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObjectModel extends Model
{
    use HasFactory;
    protected $fillable = ['name','name_ar','en_description','ar_description'];
    protected $table = 'object_model';
}
