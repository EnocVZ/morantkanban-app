<?php

namespace App\Http\Controllers;

use App\Models\Assignee;
use Illuminate\Http\Request;

class AssigneesController extends Controller
{
    public function assignUserToTask(Request $request){
        $requestData = $request->all();
        $assignee = Assignee::where($requestData)->first();
        if(!empty($assignee)){
            $assignee->delete();
            $assignee = ['success' => true ];
        }else{
            $assignee = Assignee::create($requestData);
            $assignee->load('user');
        }
        return response()->json($assignee);
    }
}
