<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskTimeLife extends Model
{
    use HasFactory;
    protected $table = 'subcolumn_tasktimelife';

    public function task()
    {
        return $this->belongsTo(Task::class, 'subcolumn_id', 'sublist_id')->where('is_archive', 0);
    }
}