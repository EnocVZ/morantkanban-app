<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardSublist extends Model
{
    use HasFactory;

    protected $table = 'board_sublist';

    public function tasklist(){
        return $this->hasMany(Task::class, 'sublist_id');
    }
}
