<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRequest extends Model
{
    use HasFactory;

    protected $table = 'user_request';

    protected $fillable = [
        'email',
        'title',
        'description',
        'workspace_id',
        'project_id',
        'task_id',
        'request_type_id',
        'path'
    ];
}
