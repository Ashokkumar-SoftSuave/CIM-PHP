<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicReportsManagement extends Model
{
    use HasFactory;
    protected $fillable = ['categoryId', 'reportSetting', 'name','name_ar', 'report_from', 'report_to', 'isLatestDate', 'isUseSendDate'];
    protected $table = 'kic_reports_management';

    /**

     * Get the post that owns the comment.

     */

    public function category() {
        return $this->belongsTo(KicReportsCategory::class, 'categoryId');

    }

    public function reportsetting() {
        return $this->belongsTo(KicReportSetting::class, 'reportSetting');

    }

}
