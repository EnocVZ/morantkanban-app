<?php

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Mail\SendMailFromHtml;
use App\Models\Comment;
use App\Models\EmailTemplate;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class CommentAddedNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\CommentAdded  $event
     * @return void
     */
    public function handle(CommentAdded $event) {
        $comment = $event->comment;
        $task = $event->task;
        $notifications = app('App\ProTask')->getSettingsEmailNotifications();
        if(!empty($task) && !empty($comment) && $notifications['new_comment']){
            $template = EmailTemplate::where('slug', 'new_comment')->first();
            if(!empty($template)){
                $template = $template->html;
                $variables = [
                    'task_name' => $task->title,
                    'project_name' => $task->project->title,
                    'comment' => $comment->details,
                    'task_url' => config('app.url').'/p/board/'.$task->project->id.'/?task='.$task->id,
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
        $messageData = ['html' => $template, 'subject' => '[Task - '.$variables['task_name'].'] - A new comment'];
        if(config('queue.enable')){
            Mail::to($variables['email'])->queue(new SendMailFromHtml($messageData));
        }else{
            Mail::to($variables['email'])->send(new SendMailFromHtml($messageData));
        }
    }
}
