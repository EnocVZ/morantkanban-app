<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubTask extends Model {
    use HasFactory;
    protected $table = 'subtasks';

   public function task(){
        return $this->hasOne(Task::class, 'id', 'subtask_id');
    }

    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'maintask_id')->with('list');
    }
}
