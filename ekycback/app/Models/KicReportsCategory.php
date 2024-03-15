<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class KicReportsCategory extends Model  implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = ['name', 'name_ar'];
    protected $table = 'kic_reports_category';

    /**

     * Get the comments for the blog post.

     */

    public function reports() {
        return $this->hasMany(KicReportsManagement::class, 'id');
    }

    public function worksector() {
        return $this->hasMany(KicReportsManagement::class, 'id');
    }
}
