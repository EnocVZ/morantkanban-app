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
        $task = Task::where('id', $taskUid)->orWhere('slug', $taskUid)->with('project')->with('timer')->with('cover')->with('list')->with('checklists')->with('comments.user')->with('attachments')->with('assignees')->with('taskLabels.label')->withCount('checklistDone')->first();
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
        $teamMembers = TeamMember::with('user')->groupBy('user_id')->where('workspace_id', $project->workspace_id)->get();
        $timer = Timer::running()->mine()->where('task_id', '!=', $task_id)->first() ?? null;
        $duration = Timer::where('task_id', $task_id)->sum('duration');
        return response()->json(['labels' => $labels, 'lists' => $lists, 'timer' => $timer, 'duration' => $duration, 'projects' => $projects, 'team_members' => $teamMembers]);
    }

    public function addAttachment($id, Request $request){
        $attachment = [];
        if($request->file('file')){
            $file = $request->file('file');
            $allowedMimeTypes = [
                'image/jpeg','image/gif','image/png','image/bmp','image/svg+xml', 'image/tiff',
                'video/x-flv', 'video/mp4', 'application/x-mpegURL', 'video/3gpp', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv',
                'text/plain', 'audio/wav', 'audio/aac', 'audio/mpeg', 'video/mpeg',
                'application/pdf', 'application/vnd.ms-powerpoint', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/csv'
            ];
            $contentType = $file->getClientmimeType();

            if(! in_array($contentType, $allowedMimeTypes) ){
                return response()->json(['error' => true]);
            }
            list($width, $height) = getimagesize($file);
            $file_name_origin = $file->getClientOriginalName();
            $file_name = uniqid().'-'.$this->clean(pathinfo($file_name_origin, PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
            $size = $file->getSize();
            $file_path = '/files/'.$file->storeAs('tasks', $file_name, ['disk' => 'file_uploads']);
            $attachment = Attachment::create(['task_id' => $id, 'name' => $file_name_origin, 'user_id' => auth()->id(), 'size' => $size, 'path' => $file_path, 'width' => $width, 'height' => $height]);
        }
        return response()->json($attachment);
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
}
