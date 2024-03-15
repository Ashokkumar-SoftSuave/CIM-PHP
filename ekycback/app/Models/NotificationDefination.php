<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OwenIt\Auditing\Contracts\Auditable;

class NotificationDefination extends Model
{
    //use \OwenIt\Auditing\Auditable;
    //implements Auditable
    protected $fillable = ['description', 'channel', 'event_id', 'entity', 'kicmanagement', 'content', 'status_active', 'start_dt', 'end_dt', 'isOfflineOrCron', 'notif_time', 'is_recur', 'recur_period', 'recur_dow', 'recur_dom', 'recur_m_condition', 'recur_q_condition', 'recur_qe_diff_days'];
    protected $table = "notif_def";

    protected $auditInclude = ['description', 'channel', 'event_id', 'entity', 'content', 'status_active', 'start_dt', 'end_dt', 'notif_time', 'is_recur', 'recur_period', 'recur_dow', 'recur_dom', 'recur_m_condition', 'recur_q_condition', 'recur_qe_diff_days'];

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];
}
