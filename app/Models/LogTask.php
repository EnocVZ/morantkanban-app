<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogTask extends Model
{
    use HasFactory;
    protected $table = 'log_task';
    public $timestamps = false;


}
