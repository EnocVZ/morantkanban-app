<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskNotification extends Model
{
    use HasFactory;
    protected $table = 'task_notification';
    protected $fillable = ['wasRead', 'idtask_notification'];
    
    public function scopeByUser($query, $id) {
        if(!empty($id)){
            $query->where('toUser', $id);
        }
    }

    public function task(){
        return $this->belongsTo(Task::class, 'task');
    }
}
