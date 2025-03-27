<?php

namespace App\Http\Controllers;

use App\Models\TaskNotification;
use Carbon\Traits\Creator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\File;

use App\Models\User;
use App\Models\Task;
use App\Helpers\MailerHelper;

class TaskNotificationController extends Controller
{
    //

    public function saveNew(Request $request){
        $requests = $request->all();
        $users = $request['users'];
        unset($request['users']);
        $userName = Auth()->user()->name;
        foreach ($users as $key => $value) {
            $payload = [
                'fromUser' => $request['fromUser'],
                'task' => $request['task'],
                'title' => "{$userName}<strong> comento: </strong>{$request['title']}",
                'toUser' => $value,
                'wasRead' => false
            ];
           $notification = TaskNotification::create($payload);
           $notification->save();
        }
        $task = Task::where('id', $request['task'])
        ->with('project')
        ->with('project.workspace')
        ->first();
        $usuarios = $this->getUserMail($users);
        
        $htmlTemplate = File::get(public_path('html/email_templates/new_comment.html'));
        $variables = [
            '{title}' => "Te mencionaron en un comentario",
            '{workspacename}' => $task->project->workspace->name,
            '{proyect}' => $task->project->title,
            '{task}' => $task->title,
            '{comment}' => $request['title']
        ];
        
        // Reemplazar las variables en el template
        $html = str_replace(array_keys($variables), array_values($variables), $htmlTemplate);
    
        $mailResponse =  MailerHelper::sendMail($usuarios, "{$userName} te mencionó en un comentario", $html);
       $response = ["succces" => true];
        return response()->json($response);
    }

    public function wasReadNotification($id){
        $response = ["succces" => false, "data" => []];
        $notification = TaskNotification::where('idtask_notification', $id)->update(['wasRead' => true]);//->first();
        if($notification){
           $response = ["succces" => true,"data" => $notification];
        }
        return response()->json($response);
    }

    public function getUserMail($idList){
        $emails = User::whereIn('id', $idList)->pluck('email')->toArray();
        
        return $emails;
    }

    public function getNotificationByUser($auth_id){
        
        $notification = TaskNotification::where('toUser', $auth_id)
        ->with('task')
        ->with('task.project')
        ->with('task.project.workspace')
        ->orderBy('idtask_notification', 'desc')
        ->get()
        ->toArray();
        
        return response()->json($notification);
    }

    public function deleteNotification($id){
        $notification = TaskNotification::where('idtask_notification', $id);
        $result = null;
        if($notification){
            $result = $notification->delete();
        }
        return response()->json($result);
    }

}