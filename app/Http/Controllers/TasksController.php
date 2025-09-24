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
use App\Models\User;
use App\Models\UserRequest;
use App\Models\SubTask;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mavinoo\Batch\Batch;
use App\Http\Controllers\GoogleController;
use Carbon\Carbon;
use App\Models\TaskNotification;
use App\Helpers\MailerHelper;
use App\Models\LogTask;
use App\Helpers\MethodHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


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
        $action = "full-update";
        foreach ($requestData as $itemKey => $itemValue){
            
            if($itemKey == 'title'){
                $slug = $this->clean($itemValue);
                $existingItem = Task::where('slug', $slug)->first();
                if(!empty($existingItem)){
                    $slug = $slug . '-' . $task->id;
                }
                $task->slug = $slug;
               // $action = "update-title";

            }else if($itemKey == 'list_id'){
                $action = "update-list";
                $this->LogTask($taskId, $action, $task->list_id, $itemValue);
                if($this->validateListComplete($itemValue)){
                    $task->is_done = 1;
                }
            }else if($itemKey == 'due_date'){
               // $action = "update-duedate";
            }else if($itemKey == 'description'){
               // $action = "update-description";
            }
            $task->{$itemKey} = $itemValue;
        }
        //$this->LogTask($taskId, $action);
        $task->save();
        $this->changeRequestUserToList($taskId, $requestData, $task->is_done);
        $task->load('list')->load('taskLabels.label')->load('assignees');
        return response()->json($task);
    }
    public function validateListComplete($list_id){
        $list = Boardlist::where('id', $list_id)->first();
        if($list->title == 'Hecho' && $list->is_basic == 1){
            return true;
        }
        return false;
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
        $this->LogTask($task->id, "new-taskinlist", 0, $task->list_id);
        
        $task->load('lastAssignee')->load('taskLabels.label')->loadCount('checklistDone')->loadCount('comments')->loadCount('checklists')->loadCount('attachments')->loadCount('assignees');
        return response()->json($task);
    }

    public function deleteTask($id){
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
            UserRequest::where('task_id', $task->id)->delete();
            $result = $task->delete();
        }
        return response()->json($result);
    }

    public function getJsonTask($taskUid){
        $task = Task::where('id', $taskUid)
        ->orWhere('slug', $taskUid)
        ->with([
            'project',
            'timer',
            'timerList.user',
            'cover',
            'list',
            'checklists',
            'comments.user',
            'attachments',
            'assignees',
            'createdby',
            'userUpdateList',
            'taskLabels.label',
            'sublist',
            'subtaskList.task' => function ($q) {
                $q->with(['list', 'sublist']);
            },
            'subtask'
        ])
        ->withCount('checklistDone')
        ->first();


        if ($task && $task->timerList) {
            $userDurations = $task->timerList
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->map(function ($group) {
                    $user = $group->first()->user;
                    return [
                        'user_name' => $user ? $user->name : null,
                        'total_duration' => $group->sum(function($item) {
                            return (int) $item->duration;
                        }),
                    ];
                });
    
            $task->user_durations = $userDurations;
        }
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
            unset($requestData['attachment']);
            unset($requestData['user']);
            unset($requestData['label']);
            unset($requestData['usercreation']);
            $task = Task::create($requestData);

            $slug = $this->clean($task->title);
            
            $task->slug = $slug;
            $task->user_id = $request['usercreation'];
            $task->save();
            foreach ($checkList as $key => $value) {
                $checkList = CheckList::create(['task_id' => $task->id, 'title' => $value]);
                $checkList->save();
            }
            $files = [];
            if($request["attachment"] == true){
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

            $findLabel = Label::where('name', $request["label"])->first();
            if($findLabel != null){
                $taskLabel = TaskLabel::create(['task_id' => $task->id, 'label_id' => $findLabel->id]);
                $taskLabel->save();
            }
            
            $teamMember = TeamMember::whereHas('user', function ($query) use ($request) {
                $query->whereRaw("CONCAT(first_name, ' ', last_name) = ?", [$request["user"]]);
            })->first();

            if($teamMember != null){
               
                $usercreation = User::where('id', $request['usercreation'])
                ->first();

                $assignee = Assignee::create(['task_id' => $task->id, 'user_id' => $teamMember->user_id]);
                $assignee->save();
                $userasigned = $assignee->user;
                $payload = [
                    'fromUser' => $request['usercreation'],
                    'task' =>  $task->id,
                    'title' => "<p>{$usercreation->name}<strong> te asigno una nueva tarea</p></strong>",
                    'toUser' =>$teamMember->user_id,
                    'wasRead' => false,
                    'notification_type'=>2
                ];
                $notification = TaskNotification::create($payload);
                $notification->save();
                $htmlTemplate = File::get(public_path('html/email_templates/assign_to_a_task.html'));
                $variables = [
                    '{title}' => "Nueva tarea asignada",
                    '{workspacename}' => $task->project->workspace->name,
                    '{proyect}' => $task->project->title,
                    '{task}' => $task->title,
                ];
                
                $html = str_replace(array_keys($variables), array_values($variables), $htmlTemplate);
                $mailResponse =  MailerHelper::sendMail([$teamMember->user->email], "{$usercreation->name} te asigno una nueva tarea", $html);
           
            }
            $data = [
                'task' => $task,
                'checkList' => $checkList,
                'attachment' => $files,
                'userasigned' => $teamMember->user,
            ];

           return response()->json(['error' => false, 'message' => "success", 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    private function LogTask($task_id, $action, $prev_value, $new_value){
       try {
        $user_id = auth()->id();
        $log = new LogTask();
        $log->task_id = $task_id;
        $log->action = $action;
        $log->user_id = $user_id;
        $log->prev_value = $prev_value;
        $log->new_value = $new_value;
        $log->created_at = now();
        $log->save();
       } catch (\Throwable $th) {
        //throw $th;
       }
    }

       public function changeList($taskId, Request $request){
            try {
                $task = Task::whereId($taskId)->first();
                $prev_value = $task->list_id;
                $requestData = $request->all();
                $task->list_id = $requestData['list_id'];
                $task->project_id = $requestData['project_id'];
                $task->sublist_id = $requestData['sublist_id'];
                $task->userupdate_list = auth()->id();
                $task->updatedlist_at = now();
                $this->LogTask($taskId,"update-list", $prev_value, $task->list_id);
                $task->save();
                return response()->json(['error' => false, 'message' => "success", 'data' => $task]);
            } catch (\Exception $e) {
                return response()->json(['error' => true, 'message' => 'Error: ' . $e->getMessage()]);
            }
        }

        public function taskFromLink(Request $request){


            try {

                $driveFileUrl = null;
                $validated = $request->validate([
                    'workspace_id' => 'required|integer|exists:workspaces,id',
                    'title' => 'required|string|max:200',
                    'description' => 'nullable|string',
                    'email' => 'nullable|email|max:50',
                    'tipo_solicitud' => 'required|exists:request_type,id',
                    'project_id' => 'nullable|integer|exists:projects,id',
                    'file' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,docx'
                ]);

                DB::beginTransaction();

                //Buscar la lista backlog en ese proyecto
                $board_list = BoardList::where('project_id', $validated['project_id'])
                    ->where('title', 'backlog')
                    ->where('order', 0)
                    ->where('is_basic', 1)
                    ->first();

                 if (!$board_list) {
                    throw new \Exception("No se encontró una lista backlog válida para este proyecto.");
                }

                
                $slug = implode('-', explode(' ', $validated['title']));
                // crear la tarea
                $task = Task::create([
                    'title'       => $validated['title'],
                    'slug'        => $slug,
                    'is_done'     => 0,
                    'is_archive'  => 0,
                    'is_request'  => 1,
                    'description' => $validated['description'] ?? null,
                    'cover'       => null,
                    'list_id'     => $board_list->id,
                    'sublist_id'  => null,
                    'order'       => 0,
                    'user_id'     => auth()->id() ?? 0,
                    'project_id'  => $validated['project_id'],
                    'updatedlist_at' => now(),
                ]);

                if($request->hasFile('file')){
                    $file = $request->file('file');
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $google = new GoogleController();
                    // obtener carpeta del proyecto
                    $project = Project::findOrFail($validated['project_id']);
                    // Si no hay folderkey, lo creamos en Drive
                    // if (!$project->folderkey) {
                        
                    //     $driveService = $google->getDriveService(); 
                    //     $parentFolderId = $google->createFolder($driveService, 'morantkanban');
                    //     $newFolderId = $google->createFolder($driveService, $project->title, $parentFolderId);

                    //     $project->folderkey = $newFolderId;
                    //     $project->save();
                    // }
                     $folderKey = $project->folderkey; 

                    $driveFileId = $google->uploadFile($project, $request, $folderKey);
                    if ($driveFileId['error']) {
                        throw new \Exception($driveFileId['message']);
                    }
                    $driveFileUrl = $driveFileId['fileId'];

                    // guardar en attachments
                    Attachment::create([
                        'task_id' => $task->id,
                        'name' => $fileName,
                        'user_id' => auth()->id() ?? null,
                        'size' => $file->getSize(),
                        'path' => $driveFileUrl,
                        'width' => null,
                        'height' => null,
                    ]);
                }

                
                UserRequest::create([
                    'workspace_id' => $validated['workspace_id'],
                    'email' => $validated['email'] ?? auth()->user()?->email,
                    'request_type_id' => $validated['tipo_solicitud'],
                    'project_id' => $validated['project_id'] ?? null,
                    'task_id' => $task->id,
                    'user_id' => auth()->id() ?? null,
                ]);

                $this->LogTask($task->id, "new-taskinlist", 0,$board_list->id); 

                DB::commit();

                return response()->json(['success' => true]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                return MethodHelper::errorResponse($e->getMessage());
            }
        }

        public function updateTaskBacklog($taskId, Request $request){
            try {
                $requestData = $request->all();

                $taskData = collect($requestData)->except(['workspace_id', 'request_type_id']);
                $userRequestData = collect($requestData)->only(['workspace_id', 'request_type_id','project_id']);
                
                $task = Task::whereId($taskId)->first();
                foreach ($taskData as $itemKey => $itemValue){
                    if($itemKey == 'title'){
                        $slug = $this->clean($itemValue);
                        $existingItem = Task::where('slug', $slug)->first();
                        if(!empty($existingItem)){
                            $slug = $slug . '-' . $task->id;
                        }
                        $task->slug = $slug;
                    }
                    $task->{$itemKey} = $itemValue;
                }

                $task->save();

                if ($userRequestData->isNotEmpty()) {
                    $userRequest = UserRequest::where('task_id', $task->id)->first();
                    if ($userRequest) {
                        $userRequest->update($userRequestData->toArray());
                    }
                }

                $updatedTask = Task::query()
                    ->select(
                        'tasks.*',
                        'request_type.title as requestTitle',
                        'user_request.workspace_id','user_request.request_type_id',
                        'projects.title as projectTitle','workspaces.name as workspaceName','board_lists.title as listName')
                    ->join('user_request', 'tasks.id', '=', 'user_request.task_id')
                    ->join('request_type', 'user_request.request_type_id', '=', 'request_type.id')
                    ->join('workspaces', 'user_request.workspace_id', '=', 'workspaces.id')
                    ->join('projects', 'tasks.project_id', '=', 'projects.id')
                    ->join('board_lists', 'tasks.list_id', '=', 'board_lists.id')
                    ->where('tasks.id', $task->id)
                    ->first();
                
                return MethodHelper::successResponse($updatedTask);
            } catch (\Exception $e) {
                return MethodHelper::errorResponse($e->getMessage());
            }
        }

        public function changeRequestUserToList($taskId, $requestData, $isDoneList){
            try {
                $isDoneAllTask = false;
                $findParent = SubTask::where('subtask_id', $taskId)->first();
                
                if($findParent){
                    $findSubTask = SubTask::where('maintask_id', $findParent->maintask_id)
                    ->with('task')
                    ->get();
                    $totalsubtask = $findSubTask->count();
                    $doneTasks = $findSubTask->where('task.is_done', 1)->count();
                    if($totalsubtask == $doneTasks){
                        $isDoneAllTask = true;
                    }
                    $taskParent = Task::where('id', $findParent->maintask_id)->first();
                    if($taskParent->is_request == 1){
                        $taskParent->list_id = $requestData['list_id'];
                        $taskParent->updatedlist_at = now();
                        $taskParent->userupdate_list = auth()->id();
                        
                        if($isDoneList == 1 && $isDoneAllTask){
                            $taskParent->is_done = 1;
                            $taskParent->save();
                        }else if($isDoneList == 0){
                            $taskParent->save();
                        }
                    }
                }
            }catch (\Exception $e) {
                return MethodHelper::errorResponse($e->getMessage());
            }
        }
}