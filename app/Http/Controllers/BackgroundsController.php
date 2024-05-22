<?php

namespace App\Http\Controllers;

use App\Models\Background;
use Illuminate\Http\Request;

class BackgroundsController extends Controller
{
    //

    public function jsonAll(){
        $backgrounds = Background::limit(50)->get();
        return response()->json($backgrounds);
    }
}
