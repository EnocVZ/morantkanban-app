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
                $notification = TaskNotification::create($payload);
                $notification->save();
                $assignee->load('user');
                $htmlTemplate = File::get(public_path('html/email_templates/assign_to_a_task.html'));
                $variables = [
                    '{title}' => "Nueva tarea asignada",
                    '{workspacename}' => $task->project->workspace->name,
                    '{proyect}' => $task->project->title,
                    '{task}' => $task->title,
                ];
                
                // Reemplazar las variables en el template
                $html = str_replace(array_keys($variables), array_values($variables), $htmlTemplate);
            
               $mailResponse =  MailerHelper::sendMail([$user->email], "{$userName} te asigno una nueva tarea", $html);
               return response()->json($mailResponse);
            }
            return response()->json($assignee);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }           
    }

    
}
