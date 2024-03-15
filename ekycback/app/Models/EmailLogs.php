<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OwenIt\Auditing\Contracts\Auditable;

class EmailLogs extends Model
{
    protected $table = "email_log";

    protected $fillable = [
        'fromemail', 'toemail', 'CustomerId', 'FullNameEn', 'KICSectorId', 'KICSectorName', 'KICDeptId', 'KICDeptName', 'subject', 'body', 'channel', 'attachments', 'reports', 'is_send'
    ];
}
