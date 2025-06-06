<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\BoardList;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Mavinoo\Batch\Batch;
use App\Helpers\MethodHelper;

class ListsController extends Controller
{
    public function update($id, Request $request){
        $board = BoardList::whereId($id)->first();
        $requestData = $request->all();
        foreach ($requestData as $itemKey => $itemValue){
            $board->{$itemKey} = $itemValue;
        }
        $board->save();
        return response()->json($board);
    }

    public function projects(){
        $projects = Project::limit(50)->get();
        return response()->json($projects);
    }

    public function orderList(Request $request){
        $requestData = $request->all();
        $result = \Batch::update(new BoardList, $requestData, 'id');
        return response()->json($result);
    }

    public function jsonArchiveBoardLists($project_id){
        $boards = BoardList::where('is_archive', 1)->where('project_id',$project_id)->get();
        return response()->json($boards);
    }

    public function jsonRemoveArchive($id){
        try {
            BoardList::where('id', $id)->update(['is_archive' => 0]);
            Task::where('list_id', $id)->update(['is_archive' => 0]);
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message'=>$e->getMessage()]);
        }
    }

    public function all(){
        $lists = BoardList::limit(50)->get();
        return response()->json($lists);
    }

    public function makeArchive($list_id){
        $list = BoardList::where('id', $list_id)->first();
        Task::where('list_id', $list->id)->update(['is_archive' => 1]);
        $list->is_archive = 1;
        $list->save();
        return response()->json($list);
    }

    public function addNew(Request $request){
        $requests = $request->all();
        $requests['user_id'] = auth()->id();
        $list = BoardList::create($requests);
        return response()->json($list);
    }

    public function getBoarListByProject($project_id){
        try {
            $lists = BoardList::where('project_id', $project_id)
            ->where('is_archive', 0)->get();
            return MethodHelper::successResponse($lists);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }
}

