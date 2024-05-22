<?php

namespace App\Listeners;

use App\Events\TaskUpdated;
use App\Mail\SendMailFromHtml;
use App\Models\EmailTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class TaskUpdateNotification
{
    /**
     * Create the event listener.
     */
    public function __construct() {

    }

    /**
     * Handle the event.
     */
    public function handle(TaskUpdated $event): void {
        $task = $event->task;
        $notifications = app('App\ProTask')->getSettingsEmailNotifications();
        if(!empty($task) && $notifications['task_update']){
            $template = EmailTemplate::where('slug', 'task_update')->first();
            if(!empty($template)){
                $template = $template->html;
                $variables = [
                    'task_name' => $task->title,
                    'project_name' => $task->project->title,
                    'task_link' => config('app.url').'/p/board/'.$task->project->id.'/?task='.$task->id,
                ];
                if(!empty($task->assignees)){
                    $count = 0;
                    foreach ($task->assignees as $assignee){
                        $variables['user'] = $assignee->user->first_name;
                        $variables['email'] = $assignee->user->email;
                        $delay = now()->addSeconds($count * 10);
                        $this->prepareMessage($template, $variables, $delay);
                        $count+= 1;
                    }

                }
            }
        }
    }

    private function prepareMessage($template, $variables, $delay){
        if (preg_match_all("/{(.*?)}/", $template, $m)) {
            foreach ($m[1] as $i => $varname) {
                $template = str_replace($m[0][$i], sprintf($variables[$m[1][$i]], $varname), $template);
            }
        }
        $messageData = ['html' => $template, 'subject' => '[Task - '.$variables['task_name'].'] - Updated!'];
        if(config('queue.enable')){
            Mail::to($variables['email'])->queue(new SendMailFromHtml($messageData));
        }else{
            Mail::to($variables['email'])->send(new SendMailFromHtml($messageData));
        }
    }
}
