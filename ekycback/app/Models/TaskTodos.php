<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskTodos extends Model
{
    protected $fillable = ['task_id' , "todo_text", "created_by", "approved_by", "created_by_name", "approved_by_name"];
    protected $table = "kic_task_todos" ;
    protected $primaryKey = "id" ;

    public function customer() {
        return $this->belongsTo(KicCustomerImport::class, 'task_id');

    }

    public function user() {
        return $this->belongsTo(Users::class, 'created_by');

    }
}
