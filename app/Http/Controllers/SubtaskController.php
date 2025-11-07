<?php
namespace App\Http\Controllers;

use Carbon\Traits\Creator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use App\Models\User;
use App\Models\Task;
use App\Models\BoardSublist;
use App\Models\BoardList;
use App\Models\SubTask;
use App\Models\Project;

use App\Helpers\MethodHelper;

class SubtaskController extends Controller
{

    public function create(Request $request){
        try {
            $body = $request->all();
            $project = Project::where('id', $body['project_id'])->first();
            $boardList = BoardList::where('project_id', $body['project_id'])
            ->where('is_archive', 0)
            ->orderBy('order')
            ->first();
            
            $sublist = BoardSublist::where('list_id', $boardList->id)
            ->orderBy('order')
            ->first();
            $requestTask = [
                'title' => $body['title'],
                'list_id' => $boardList->id,
                'project_id' => $body['project_id'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            if($project->project_type == 2){
                $requestTask['sublist_id'] = $sublist->id;
            }
            
            $task = Task::create($requestTask);
            //return response()->json($task);
            $newSubtask = [
                'maintask_id' => $body['parent_id'],
                'subtask_id' => $task->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            $subTask = SubTask::create($newSubtask);
            $getTask = Task::with(['list', 'sublist', 'assignees'])->where('id', $task->id)->first();
            $subTask['task'] = $getTask;
            return MethodHelper::successResponse($subTask);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }
}