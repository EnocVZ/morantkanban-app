<?php
namespace App\Http\Controllers;

use Carbon\Traits\Creator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use App\Models\User;
use App\Models\Task;
use App\Models\BoardSublist;


use App\Helpers\MethodHelper;
use Mavinoo\Batch\Batch;

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
            $task = Task::where('sublist_id', $id)->update([
                'list_id' => $requestData['list_id'],
            ]);
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

    public function updateRow($id, Request $request){
        try {
            $requestData = $request->all();
            $sublist = BoardSublist::where('id', $id)->first();
            foreach ($requestData as $itemKey => $itemValue){
                $sublist->{$itemKey} = $itemValue;
            }
            $sublist->save();
            return MethodHelper::successResponse($sublist);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }

    public function updateorder(Request $request){
        try{
            $requestData = $request->all();
            $result = \Batch::update(new BoardSublist, $requestData, 'id');
            return MethodHelper::successResponse($result);
        }catch(\Exception $e){
            return MethodHelper::errorResponse($e->getMessage());
        }
    }
}