<?php

namespace App\Http\Controllers;

use App\Models\Assignee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\Task;
use App\Models\TaskNotification;
use App\Helpers\MailerHelper;
use App\Models\UserRequest;
use App\Helpers\MethodHelper;
use App\Events\UserRequestCreated;


class UserRequestController extends Controller
{
    public function countRequestNoRead($projectId){
        try{
            $countNoRead = UserRequest::where('read', 0)->where('project_id', $projectId)->count();
            return MethodHelper::successResponse($countNoRead);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }

    public function makeRead($idRequest){
        try{
            $item = UserRequest::where('id', $idRequest)->update(['read' => 1]);
            $row = UserRequest::where('id', $idRequest)->first();
            broadcast(new UserRequestCreated($row))->toOthers();
            return MethodHelper::successResponse($item);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }

    public function getRequestsByProject($projectId){
        try{
            $items = Task::where('project_id', $projectId)
              ->where('is_request', 1)
              ->where('sublist_id', 0)
              ->with([
                    'taskLabels.label',
                    'timer',
                    'cover',
                    'assignees',
                ])
                ->withCount([
                    'checklistDone',
                    'comments',
                    'checklists',
                    'attachments',
                ])
                ->orderBy('created_at', 'desc')
                ->get();
            return MethodHelper::successResponse($items);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }

    
}
