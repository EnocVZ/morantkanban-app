<?php

namespace App\Http\Controllers;

use App\Models\Assignee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

use App\Models\User;
use App\Models\Task;
use App\Models\TaskNotification;
use App\Helpers\MailerHelper;




class AssigneesController extends Controller
{
    public function assignUserToTask(Request $request){
        try {
            $requestData = $request->all();
            $user_id = Auth()->id();
            $userName = Auth()->user()->name;
            $assignee = Assignee::where($requestData)->first();
            if(!empty($assignee)){
                $assignee->delete();
                $assignee = ['success' => true ];
            }else{
                $task = Task::where('id', $requestData['task_id'])
                ->with('project')
                ->with('project.workspace')
                ->first();
                $user = User::where('id', $requestData['user_id'])
                ->first();
                $assignee = Assignee::create($requestData);
                $payload = [
                    'fromUser' => $user_id,
                    'task' => $requestData['task_id'],
                    'title' => "<p>{$userName}<strong> te asigno una nueva tarea</p></strong>",
                    'toUser' => $requestData['user_id'],
                    'wasRead' => false,
                    'notification_type'=>2
                ];
                
                
                $htmlTemplate = File::get(public_path('html/email_templates/assign_to_a_task.html'));
                $variables = [
                    '{title}' => "Nueva tarea asignada",
                    '{workspacename}' => $task->project->workspace->name,
                    '{proyect}' => $task->project->title,
                    '{task}' => $task->title,
                    '{url}' => env('APP_URL') . "p/board/{$task->project_id}?task={$task->id}",
                ];
                
                // Reemplazar las variables en el template
                $html = str_replace(array_keys($variables), array_values($variables), $htmlTemplate);

                if($user_id != $requestData['user_id']){
                    $notification = TaskNotification::create($payload);
                    $mailResponse =  MailerHelper::sendMail([$user->email], "{$userName} te asigno una nueva tarea", $html);
                }
               
               $assignee->load('user');
            }
            return response()->json($assignee);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }           
    }

    
}
