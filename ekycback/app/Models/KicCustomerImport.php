<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OwenIt\Auditing\Contracts\Auditable;

class KicCustomerImport extends Model
{
    //use \OwenIt\Auditing\Auditable;
    //protected $fillable = ['FullNameEn' , 'FullNameAr', 'Email', 'Mobile', 'AddInfo_SMS_LangId', 'CivilId', 'CivilIdExpiry', 'UpdatedOn', 'CRS_FirstName', 'CRS_GivenName'] ;
    protected $table = "kic_customerinfo_import" ;

    protected $auditInclude = [
        'FullNameEn' , 'FullNameAr', 'Email', 'Mobile', 'AddInfo_SMS_LangId', 'CivilId', 'CivilIdExpiry', 'UpdatedOn', 'CRS_FirstName', 'CRS_GivenName'
    ];

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    public function todos() {
        return $this->hasMany(TaskTodos::class, 'task_id', 'CustomerId');
    }
}
