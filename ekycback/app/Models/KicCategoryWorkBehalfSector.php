<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KicCategoryWorkBehalfSector extends Model
{
    use HasFactory;
    protected $table = 'kic_category_work_on_behalf_sectors';

    /**

     * Get the post that owns the comment.

     */

    public function category() {
        return $this->belongsTo(KicReportsCategory::class, 'categoryId');

    }
}
