<?php

namespace App\Http\Controllers;

use App\Models\Assignee;
use App\Models\Attachment;
use App\Models\BoardList;
use App\Models\CheckList;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\TeamMember;
use App\Models\Timer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mavinoo\Batch\Batch;
use App\Http\Controllers\GoogleController;
use Carbon\Carbon;

class TasksController extends Controller
{
    public function updateTaskOrder(Request $request){
        $requestData = $request->all();
//        $taskIds = array_keys($requestData);
//        $orderValues = array_values($requestData);
//        $tasks = Task::whereIn('id', $taskIds);

        // New Code
        $result = \Batch::update(new Task, $requestData, 'id');
        // New Code


//        dd($requestData);
//        foreach ($requestData as $itemKey => $itemValue){
//            $task->{$itemKey} = $itemValue;
//        }
//        $task->save();
        return response()->json($result);
    }

    public function updateTask($taskId, Request $request){
        $task = Task::whereId($taskId)->first();
        $requestData = $request->all();
        foreach ($requestData as $itemKey => $itemValue){
            $task->{$itemKey} = $itemValue;
            if($itemKey == 'title'){
                $slug = $this->clean($itemValue);
                $existingItem = Task::where('slug', $slug)->first();
                if(!empty($existingItem)){
                    $slug = $slug . '-' . $task->id;
                }
                $task->slug = $slug;
            }
        }
        $task->save();
        $task->load('list')->load('taskLabels.label')->load('assignees');
        return response()->json($task);
    }

    public function jsonArchiveTasks($project_id){
        $archiveTasks = Task::where('is_archive', 1)
            ->byProject($project_id)
            ->withCount('checklistDone')
            ->withCount('comments')
            ->withCount('checklists')
            ->withCount('attachments')
            ->with('assignees')
            ->with('list')
            ->has('list')
            ->get();
        return response()->json($archiveTasks);
    }

    public function updateTaskListByProjectId($projectId, Request $request){
        $data = $request->all();
        $from_lists = [];
        $new_list = [];
        if (!empty($data['is_move'])){
            $from_lists = Task::where('list_id', $data['previous_list'])->orderBy('order')->select(['id', 'order'])->get()->toArray();
            $to_lists = Task::where('list_id', $data['new_list'])->orderBy('order')->pluck('id')->toArray();
            $previous_order = array_search($data['task_id'], $to_lists);
            $out = array_splice($to_lists, $previous_order, 1);
            array_splice($to_lists, $data['to'] - 1, 0, $out);
        }else{
            $to_lists = Task::where('list_id', $data['new_list'])->orderBy('order')->pluck('id')->toArray();
            $out = array_splice($to_lists, $data['from'] - 1, 1);
            array_splice($to_lists, $data['to'] - 1, 0, $out);
        }
        foreach ($to_lists as $item_k => $item_v){
            $new_list[$item_k] = ['id' => $item_v, 'order' => $item_k + 1];
        }
        $result = \Batch::update(new Task, $from_lists + $new_list, 'id');
        return response()->json($result);
    }

    private function moveElement(&$array, $a, $b) {
        $out = array_splice($array, $a, 1);
        array_splice($array, $b, 0, $out);
        return $array;
    }

    public function newTask(Request $request){
        $user_id = auth()->id();
        $requestData = $request->all();
        $requestData['user_id'] = $user_id;
        $task = Task::create($requestData);

        $slug = $this->clean($task->title);
        $existingItem = Task::where('slug', $slug)->first();
        if(!empty($existingItem)){
            $slug = $slug . '-' . $task->id;
        }
        $task->slug = $slug;
        $task->save();

        $task->load('lastAssignee')->load('taskLabels.label')->loadCount('checklistDone')->loadCount('comments')->loadCount('checklists')->loadCount('attachments')->loadCount('assignees');
        return response()->json($task);
    }

    public function deleteDask($id){
        $result = null;
        $task = Task::where('id', $id)->first();
        if(!empty($task)){
            $attachments = Attachment::where('task_id', $task->id)->get();
            foreach ($attachments as $attachment){
                if(!empty($attachment->path) && File::exists(public_path($attachment->path))){
                    File::delete(public_path($attachment->path));
                }
                $attachment->delete();
            }
            CheckList::where('task_id', $task->id)->delete();
            Timer::where('task_id', $task->id)->delete();
            Comment::where('task_id', $task->id)->delete();
            Assignee::where('task_id', $task->id)->delete();
            TaskLabel::where('task_id', $task->id)->delete();
            $result = $task->delete();
        }
        return response()->json($result);
    }

    public function getJsonTask($taskUid){
        $task = Task::where('id', $taskUid)->orWhere('slug', $taskUid)
        ->with('project')
        ->with('timer')
        ->with('cover')
        ->with('list')
        ->with('checklists')
        ->with('comments.user')
        ->with('attachments')
        ->with('assignees')
        ->with('createdby')
        ->with('userUpdateList')
        ->with('taskLabels.label')
        ->withCount('checklistDone')->first();
        return response()->json($task);
    }

    public function countListItemsById($id){
        $taskCount = Task::where('list_id', $id)->count();
        return response()->json($taskCount);
    }

    public function taskOtherData($task_id, $project_id){
        $project = Project::where('id', $project_id)->first();
        $labels = Label::get();
        $lists = BoardList::withCount('tasks')->get();
        $projects = Project::get();
        $teamMembers = TeamMember::withOrderedUsers($project->workspace_id)->with('user')->get();
        $timer = Timer::running()->mine()->where('task_id', '!=', $task_id)->first() ?? null;
        $duration = Timer::where('task_id', $task_id)->sum('duration');
        return response()->json(['labels' => $labels, 'lists' => $lists, 'timer' => $timer, 'duration' => $duration, 'projects' => $projects, 'team_members' => $teamMembers]);
    }

    public function addAttachment($id, Request $request){
        try {
            $requests = $request->all();
            $attachment = [];
            $objGoogle = new GoogleController;
            $task = Task::where('id', $id)
            ->with('project')
            ->with('project.workspace')
            ->first();

            $file_path = $requests['url'];
            $file_name = $requests['name'];
            $attachment = Attachment::create(['task_id' => $id, 'name' => $file_name, 'user_id' => auth()->id(), 'size' => 0, 'path' => $file_path, 'width' => 0, 'height' => 0]);
       
            return response()->json($attachment);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'Error: ' . $e->getMessage()]);
        }
        
    }

    public function removeAttachment($id){
        $attachment = Attachment::find($id);
        if(!empty($attachment) && !empty($attachment->path) && File::exists(public_path($attachment->path))){
            File::delete(public_path($attachment->path));
        }
        $result = $attachment->delete();
        return response()->json($result);
    }

    private function clean($string) {
        $string = str_replace(' ', '-', $string);
        $string = filter_var($string, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        return preg_replace('/-+/', '-', $string);
    }

    public function addAttachmentFromLink($id, Request $request){
        $requests = $request->all();
        $attachment = Attachment::create(['task_id' => $id, 'name' => $requests['name'], 'user_id' => auth()->id(), 'size' => null, 'path' => $requests['link'], 'width' => null, 'height' => null]);
        return response()->json($attachment);
    }

    public function getTaskToExpire($user_id){
        $timezone = 'America/Mexico_City';
        $tasks = Task::whereDate('due_date', Carbon::tomorrow($timezone)) // Filtrar por fecha actual
                ->whereHas('assignees', function ($query) use ($user_id) {
                    $query->where('user_id', $user_id); // Filtrar por usuario asignado
                })
                ->with('project')
                ->with('project.workspace')
                ->get()
                ->toArray(); // Convertir a un array si es necesario
        return response()->json($tasks);
    }

    public function newTaskWithDetail(Request $request){
        try {
            $requestData = $request->all();
            $checkList = $requestData['checkList'];
            unset($requestData['checkList']);
            unset($requestData['adjunto']);
            $task = Task::create($requestData);

            $slug = $this->clean($task->title);
            
            $task->slug = $slug;
            $task->save();
            foreach ($checkList as $key => $value) {
                $checkList = CheckList::create(['task_id' => $task->id, 'title' => $value]);
                $checkList->save();
            }
            $files = [];
            if($request["adjunto"] == true){
                $googleController = new GoogleController;
                $project = Project::where('id', $requestData["project_id"])->first();
                $filesUpload = $googleController->uploadMultipleFilesToGoogle($request, $project->folderKey);
                if($filesUpload['error'] == false){
                    $files = $filesUpload["data"];
                    foreach ($filesUpload["data"] as $key => $value) {
                        $attachment = Attachment::create(['task_id' => $task->id, 'name' => $value['name'], 'user_id' => 1, 'size' => 0, 'path' => $value['url'], 'width' => 0, 'height' => 0]);
                        $attachment->save();
                    }
                }
            }
            $data = [
                'task' => $task,
                'checkList' => $checkList,
                'attachment' => $files
            ];
           return response()->json(['error' => false, 'message' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}