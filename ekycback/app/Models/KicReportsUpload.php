<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicReportsUpload extends Model
{
    use HasFactory;
    protected $fillable = ['categoryId', 'reportSetting', 'filename','reportSetting', 'send_date', 'name', 'date', 'civilid'];
    protected $table = 'kic_reports_upload';

    public function category() {
        return $this->belongsTo(KicReportsCategory::class, 'categoryId');

    }

    public function reportsetting() {
        return $this->belongsTo(KicReportSetting::class, 'reportSetting');

    }

    public function reports()
    {
        return $this->belongsTo(KicReportsManagement::class, 'reportId');
    }
}
