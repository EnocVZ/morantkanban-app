<?php

namespace App\Http\Controllers;

use App\Models\Timer;
use Carbon\Traits\Creator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
//models
use App\Models\RequestType;
use App\Helpers\MethodHelper;

class RequestTypeController extends Controller
{
   public function categoriesbyWorkSpace($workspace_id){
    try {
        $categories = RequestType::where('workspace_id', $workspace_id)->get();
        return MethodHelper::successResponse($categories);
    } catch (\Exception $e) {
        return MethodHelper::errorResponse($e->getMessage());
    }
   }

}