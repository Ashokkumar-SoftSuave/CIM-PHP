<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OwenIt\Auditing\Contracts\Auditable;

class NotificationMessage extends Model
{
    //use \OwenIt\Auditing\Auditable;
    protected $fillable = ['user' , 'userImage', 'message', 'status'] ;
    protected $table = "notification_messages" ;

    protected $auditInclude = [
        'user' , 'userImage', 'tenant_id' , 'message', 'status'
    ];

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];
}
