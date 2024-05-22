<?php

namespace App\Listeners;

use App\Events\UserAddedToWorkspace;
use App\Mail\SendMailFromHtml;
use App\Models\EmailTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class WorkspaceUserAddNotification
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
    public function handle(UserAddedToWorkspace $event): void {
        $team_member = $event->team_member;
        $notifications = app('App\ProTask')->getSettingsEmailNotifications();
        if(!empty($team_member) && $notifications['adding_user_to_workspace']){
            $template = EmailTemplate::where('slug', 'adding_user_to_workspace')->first();
            if(!empty($template)){
                $template = $template->html;
                $variables = [
                    'added_by' => $team_member->addedBy->first_name.' '.$team_member->addedBy->last_name,
                    'workspace_name' => $team_member->workspace->name,
                    'workspace_link' => config('app.url').'/w/'.$team_member->workspace->slug??$team_member->workspace->id,
                ];

                $variables['user'] = $team_member->user->first_name;
                $variables['email'] = $team_member->user->email;
                $this->prepareMessage($template, $variables);
            }
        }
    }

    private function prepareMessage($template, $variables, $delay = 0){
        if (preg_match_all("/{(.*?)}/", $template, $m)) {
            foreach ($m[1] as $i => $varname) {
                $template = str_replace($m[0][$i], sprintf($variables[$m[1][$i]], $varname), $template);
            }
        }
        $messageData = ['html' => $template, 'subject' => 'You are added on the - '.$variables['workspace_name'].' Workspace'];
        if(config('queue.enable')){
            Mail::to($variables['email'])->queue(new SendMailFromHtml($messageData));
        }else{
            Mail::to($variables['email'])->send(new SendMailFromHtml($messageData));
        }
    }
}
