<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RedirectIfNotAdmin;
use App\Models\Assignee;
use App\Models\Attachment;
use App\Models\BoardList;
use App\Models\CheckList;
use App\Models\Comment;
use App\Models\Project;
use App\Models\RecentProject;
use App\Models\StarredProject;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\TeamMember;
use App\Models\Timer;
use App\Models\User;
use App\Models\Workspace;
use App\Models\TaskNotification;
use App\Models\RequestType;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use App\Helpers\MethodHelper;
use Exception;
use Illuminate\Support\Facades\Crypt;

class WorkSpacesController extends Controller 
{
    //
    public function index(){
        $user_id = auth()->id();
        $workspaceIds = Workspace::where('user_id', $user_id)->orWhereHas('member')->pluck('id');
        $project = RecentProject::where('user_id', $user_id)->with('project')->has('project.workspace')->whereHas('project', function ($q) use ($workspaceIds) {
            $q->whereIn('workspace_id', $workspaceIds);
        })->orderBy('opened', 'desc')->first();
        if(!empty($project)){
            return Redirect::route('projects.view.board', $project->project->slug?:$project->project->id);
        }
        $project = Project::whereIn('workspace_id', $workspaceIds)->orderBy('updated_at', 'desc')->first();
        if(!empty($project)){
            return Redirect::route('projects.view.board', $project->slug?:$project->id);
        }
        $assignee = Assignee::where('user_id', $user_id)->whereHas('task')->with('task')->first();
        if(!empty($assignee)){
            return Redirect::route('projects.view.board', ['uid' => $assignee->task->project_id, 'task' => $assignee->task->id]);
        }
        return Redirect::route('projects.view.na');
    }
    public function jsonAll(){
        $user_id = auth()->id();
        $workSpaces = Workspace::where('user_id', $user_id)
        ->orWhereHas('member')
        ->with('member')
        ->orderBy('name')
        ->get()
        ->toArray();
        return response()->json($workSpaces);
    }

    public function jsonMineAll(){
        $myWorkspaces = Workspace::where('user_id', auth()->id())->limit(50)->get()->toArray();
        return response()->json($myWorkspaces);
    }


    public function jsonCreate(Request $request){
        $requests = $request->all();
        $requests['user_id'] = auth()->id();
        $workspace = Workspace::create($requests);

        $slug = $this->clean($workspace->name);
        $existingItem = Workspace::where('slug', $slug)->first();
        if(!empty($existingItem)){
            $slug = $slug . '-' . $workspace->id;
        }
        $workspace->slug = $slug;
        $workspace->save();

        TeamMember::create(['workspace_id' => $workspace->id, 'user_id' => $requests['user_id'], 'role' => 'admin', 'added_by' => $requests['user_id']]);

        return response()->json($workspace);
    }

    public function jsonChangeWorkspace(Request $request){
        $requestData = $request->all();
        $project = Project::where('id', $requestData['project_id'])->first();
        $project->workspace_id = $requestData['workspace_id'];
        $project->save();
        return response()->json($project);
    }

    public function jsonUpdateWorkspace($id, Request $request){
        $requestData = $request->all();
        $workspace = Workspace::where('id', $id)->first();
        foreach ($requestData as $itemKey => $itemValue){
            $workspace->{$itemKey} = $itemValue;
        }
        $workspace->save();
        return response()->json($workspace);
    }

    public function jsonAddMember(Request $request){
        $requestData = $request->all();
        $teamMember = TeamMember::where(['workspace_id' => $requestData['workspace_id'], 'user_id' => $requestData['user_id']])->first();
        if(!empty($teamMember)){
            $teamMember->delete();
            $teamMember = ['success' => true ];
        }else{
            $requestData['added_by'] = auth()->id();
            $teamMember = TeamMember::create($requestData);
            $teamMember->load('user');
        }
        return response()->json($teamMember);
    }

    public function workspaceView($uid){
        $workspace = Workspace::whereId($uid)->orWhere('slug', $uid)->whereHas('member')->with('member')->first();
        $projects = Project::where('workspace_id', $workspace->id)->with('star')->with('background')->get();
        $types_requests = RequestType::where('workspace_id', $workspace->id)->select('id', 'title', 'workspace_id')->get();
        return Inertia::render('Workspaces/View', [
            'title' => 'Proyectos | '.$workspace->name,
            'workspace' => $workspace,
            'projects' => $projects,
            'requests' => $types_requests
        ]);
    }

    public function workspaceMembers($uid, Request $request){
        $workspace = Workspace::whereId($uid)->orWhere('slug', $uid)->whereHas('member')->with('member')->first();
        /*if($workspace->member->role != 'admin'){
                return Redirect::route('workspace.view', $workspace->id);
        }*/
        $projects = Project::where('workspace_id', $workspace->id)->with('star')->with('background')->get();
        return Inertia::render('Workspaces/Members', [
            'title' => 'Participantes | '.$workspace->name,
            'workspace' => $workspace,
            'projects' => $projects,
            'team_members' => TeamMember::where('workspace_id', $workspace->id)
                ->filter($request->only('search'))
                ->orderBy('created_at', 'DESC')
                ->paginate(10)
                ->withQueryString()
                ->through(function ($member) {
                    $name = $member->user->first_name.' '.$member->user->last_name;
                    $photo_path = $member->user->photo_path;
                    return [
                        'id' => $member->id,
                        'name' => $name,
                        'photo' => $photo_path,
                        'role' => $member->role,
                        'workspace_id' => $member->workspace_id,
                        'user_id' => $member->user_id,
                        'created_at' => $member->created_at,
                    ];
                } ),
        ]);
    }

    public function workspaceTables($uid, Request $request){
        $user = auth()->user()->load('role');
        $requests = $request->all();
        if(!empty($user->role)){
            if($user->role->slug != 'admin' && empty($requests['user'])){
                return Redirect::route('workspace.tables', ['uid' => $uid, 'user' => $user->id]);
            }
        }else{
            return abort(404);
        }

        $list_index = [];
        $board_lists = BoardList::orderByOrder()->get();
        $workspace = Workspace::where('id', $uid)->orWhere('slug', $uid)->whereHas('member')->with('member')->first();
        $loopIndex = 0;
        foreach ($board_lists as &$listItem){
            $list_index[$listItem->id] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex+= 1;
        }
        $taksList = Task::filter($requests)->whereHas('project', function ($q) use ($workspace) {
                $q->where('workspace_id', $workspace->id);
            })->with(['list','taskLabels.label', 'project.background', 'assignees','timer',
            'subtaskList.task' => function ($q) {
                $q->with(['list', 'sublist']);
            }
             ])
            ->isOpen()->orderByOrder()->get();
        return Inertia::render('Workspaces/Table', [
            'title' => 'Tareas | '.$workspace->name,
            'board_lists' => $board_lists,
            'filters' => $requests,
            'list_index' => $list_index,
            'workspace' => $workspace,
            'tasks' => $taksList
        ]);
    }

    public function getOtherUsers($workspace_id){
        $workspaceUsers = TeamMember::where('workspace_id', $workspace_id)->groupBy('user_id')->pluck('user_id');
        $users = User::select('id', 'first_name', 'last_name', 'photo_path')->where('id', '!=', auth()->id())->get();
        return response()->json(['users' => $users, 'workspace_users' => $workspaceUsers]);
    }

    private function clean($string) {
        $string = str_replace(' ', '-', $string);
        $string = preg_match("/[a-z]/i", $string)?$string:'untitled';
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        return preg_replace('/-+/', '-', $string);
    }

    public function destroy($id){
        $workspace = Workspace::where('id', $id)->first();
        $workspace->delete();
        TeamMember::where('workspace_id', $id)->delete();
        $projects = Project::where('workspace_id', $id)->get();
        foreach ($projects as $project){
            BoardList::where('project_id', $project->id)->delete();
            RecentProject::where('project_id', $project->id)->delete();
            StarredProject::where('project_id', $project->id)->delete();
            $tasks = Task::where('project_id', $project->id)->get();
            foreach ($tasks as $task){
                $attachments = Attachment::where('task_id', $task->id)->get();
                foreach ($attachments as $attachment){
                    $attachment->delete();
                }
                CheckList::where('task_id', $task->id)->delete();
                Timer::where('task_id', $task->id)->delete();
                Comment::where('task_id', $task->id)->delete();
                Assignee::where('task_id', $task->id)->delete();
                TaskLabel::where('task_id', $task->id)->delete();
                TaskNotification::where('task', $task->id)->delete();
                $task->delete();
            }
            $project->delete();
        }
        return Redirect::route('dashboard');
    }

    public function allWorkSpace(){
        try {
            $workspaces = Workspace::all();
            return MethodHelper::successResponse($workspaces);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }

    public function getUserByWorkSpace($workspace_id)
    {
        try {
            
            $workspaceUsers = TeamMember::where('workspace_id', $workspace_id)
                ->with('user', function ($query) {
                    $query->select('id', 'first_name', 'last_name');
                })
                ->get();
    
           
            $sortedUsers = $workspaceUsers->sortBy(function ($item) {
                return $item->user->name;
            });
    
            return MethodHelper::successResponse($sortedUsers->values()->all());
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }
    
    public function viewBacklog($uid, Request $request){

        $user = auth()->user()->load('role');
        $requests = $request->all();
        $search = $request->input('search');
        // if(!empty($user->role)){
        //     if($user->role->slug != 'admin' && empty($requests['user'])){
        //         return Redirect::route('workspace.tables', ['uid' => $uid, 'user' => $user->id]);
        //     }
        // }else{
        //     return abort(404);
        // }
        $list_index = [];
        $board_lists = BoardList::orderByOrder()->get();
        $workspace = Workspace::where('id', $uid)->orWhere('slug', $uid)->whereHas('member')->with('member')->first();
        $allWorkSpace = Workspace::orderBy('name')->get();
        $loopIndex = 0;
        foreach ($board_lists as &$listItem){
            $list_index[$listItem->id] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex+= 1;
        }

        $taksList = Task::query()
            ->select('tasks.*', 'request_type.title as requestTitle')
            ->join('user_request', 'tasks.id', '=', 'user_request.task_id')
            ->join('request_type', 'user_request.request_type_id', '=', 'request_type.id')
            ->where('is_request', 1)
            ->when($search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('tasks.title', 'like', "%$search%")
                    ->orWhere('tasks.description', 'like', "%$search%")
                    ->orWhere('request_type.title', 'like', "%$search%");
                });
            })
            ->orderBy('created_at', 'DESC')
            ->paginate(20)
            ->withQueryString();

                

        return Inertia::render('Workspaces/Backlog', [
            'title' => 'Backlog | '.$workspace->name,
            'board_lists' => $board_lists,
            'filters' => $requests,
            'list_index' => $list_index,
            'workspace' => $workspace,
            'tasks' => $taksList,
            'workspace_id' => Crypt::encryptString($workspace->id),
            'allWorkSpace' => $allWorkSpace,
        ]);

    }

    public function viewFormLink($workspace_id, Request $request){

        try {
            $workspace_id_decrypt = Crypt::decryptString($workspace_id);
        } catch (\Exception $e) {
            abort(404, 'ID inválido');
        }

        $categories = RequestType::where('workspace_id', $workspace_id_decrypt)->get();
        $projects = Project::where('workspace_id',$workspace_id_decrypt)->get();

        return Inertia::render('Link/Index', [
            'title' => 'Solicitud',
            'workspace_id' => $workspace_id_decrypt,
            'categories' => $categories,
            'projects' => $projects
        ]);

    }


    public function jsonAddUpdTypesRequests(Request $request) {
        try {
            $data = $request->validate([
                '*.id' => 'nullable|integer|exists:request_type,id',
                '*.title' => 'required|string|max:255',
                '*.workspace_id' => 'required|integer|exists:workspaces,id',
            ]);

        $res = RequestType::upsert($data, ['id'], ['title', 'workspace_id']);

        if($res > 0) $list_requests = RequestType::select('id', 'title', 'workspace_id')->get();
        else $list_requests = $request->all();

        return response()->json([
            'success' => true,
            'data' => $list_requests
        ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),

            ], 500);
        }
    }
    
    public function deleteRequest($id) {
        try {
            $requestType = RequestType::findOrFail($id);

            if (!$requestType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request type not found.'
                ], 404);
            }

            $requestType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Request type deleted successfully.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
