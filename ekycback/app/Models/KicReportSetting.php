<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicReportSetting extends Model
{
    use HasFactory;
    protected $fillable = ['name','name_ar'];
    protected $table = 'kic_report_settings';

    /**

     * Get the post that owns the comment.

     */

    public function settings() {
        return $this->hasMany(KicReportsManagement::class, 'id');
    }
}
