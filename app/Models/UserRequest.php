<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserRequest extends Model
{
    use HasFactory;
    protected $table = 'user_request';
    
    protected $fillable = [
        'email',
        'workspace_id',
        'project_id',
        'task_id',
        'request_type_id',
    ];
    protected $appends = ['time_elapsed'];

    public function task(){
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function getTimeElapsedAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}
