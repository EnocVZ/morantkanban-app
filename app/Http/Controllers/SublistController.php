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
            $sublist = BoardSublist::create($requestData);
            
            return MethodHelper::successResponse($sublist);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }

    public function update($id, Request $request){
        try {
            $requestData = $request->all();
            $sublist = BoardSublist::where('id',$id)->first();
            $sublist->update($requestData);
            return MethodHelper::successResponse($sublist);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }

    public function itemListByListId($id){
        try {
            $sublist = BoardSublist::where('list_id',$id)->get();
            return MethodHelper::successResponse($sublist);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }
}