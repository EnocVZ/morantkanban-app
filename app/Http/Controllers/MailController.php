<?php

namespace App\Http\Controllers;

use App\Events\AssignedUserToTask;
use App\Events\CommentAdded;
use App\Events\TaskUpdated;
use App\Events\UserAddedToWorkspace;
use App\Models\Assignee;
use App\Models\Comment;
use App\Models\Task;
use App\Models\TeamMember;
use App\Models\Workspace;
use Illuminate\Http\Request;

class MailController extends Controller {
    public function comment($id){
        $comment = Comment::whereId($id)->with('user')->first();
        $task = Task::where('id', $comment->task_id)->whereHas('assignees')->with('assignees.user')->whereHas('project')->with('project')->first();
        if(!empty($comment) and !empty($task)){
            event(new CommentAdded(['comment' => $comment, 'task' => $task]));
        }
        return response()->json(['success' => true, 'sent' => true]);
    }

    public function task_update($id){
        $task = Task::where('id', $id)->whereHas('assignees')->with('assignees.user')->whereHas('project')->with('project')->first();
        if(!empty($task)){
            event(new TaskUpdated($task));
        }
        return response()->json(['success' => true, 'sent' => true]);
    }

    public function addedUserToWorkspace($id, $user_id){
        $team_member = TeamMember::where('id', $id)->where('user_id', $user_id)->whereHas('workspace')->whereHas('user')->whereHas('addedBy')->with('workspace')->with('user')->with('addedBy')->first();
        if(!empty($team_member)){
            event(new UserAddedToWorkspace($team_member));
        }
        return response()->json(['success' => true, 'sent' => true]);
    }

    public function addedUserToTask($id, $user_id){
        $assignee = Assignee::where('id', $id)->where('user_id', $user_id)->whereHas('task')->whereHas('user')->with('task')->with('user')->first();
        if(!empty($assignee)){
            event(new AssignedUserToTask($assignee));
        }
        return response()->json(['success' => true, 'sent' => true]);
    }


}
