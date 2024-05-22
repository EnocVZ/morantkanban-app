<?php

namespace App\Listeners;

use App\Events\AssignedUserToTask;
use App\Mail\SendMailFromHtml;
use App\Models\EmailTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class TaskAssignUserNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AssignedUserToTask $event): void {
        $assignee = $event->assignee;
        $notifications = app('App\ProTask')->getSettingsEmailNotifications();
        if(!empty($assignee) && $notifications['assign_to_a_task']){
            $template = EmailTemplate::where('slug', 'assign_to_a_task')->first();
            if(!empty($template)){
                $template = $template->html;
                $variables = [
                    'user' => $assignee->user->first_name,
                    'email' => $assignee->user->email,
                    'task_name' => $assignee->task->title,
                    'task_link' => config('app.url').'/p/board/'.$assignee->task->project_id.'/?task='.$assignee->task->id,
                ];
                $this->prepareMessage($template, $variables);
            }
        }
        //
    }

    private function prepareMessage($template, $variables){
        if (preg_match_all("/{(.*?)}/", $template, $m)) {
            foreach ($m[1] as $i => $varname) {
                $template = str_replace($m[0][$i], sprintf($variables[$m[1][$i]], $varname), $template);
            }
        }
        $messageData = ['html' => $template, 'subject' => 'You got assigned on a task'];
        if(config('queue.enable')){
            Mail::to($variables['email'])->queue(new SendMailFromHtml($messageData));
        }else{
            Mail::to($variables['email'])->send(new SendMailFromHtml($messageData));
        }
    }
}
