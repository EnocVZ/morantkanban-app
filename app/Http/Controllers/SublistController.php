<?php
namespace App\Http\Controllers;

use Carbon\Traits\Creator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use App\Models\User;
use App\Models\BoardSublist;

use App\Helpers\MethodHelper;

class SublistController extends Controller
{

    public function create(Request $request){
        try {
            $requestData = $request->all();
            $task = BoardSublist::create($requestData);
            
            return MethodHelper::successResponse($task);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }
}