<?php

namespace App\Http\Controllers;

use App\Models\Assignee;
use App\Models\Attachment;
use App\Models\BoardList;
use App\Models\CheckList;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Project;
use App\Models\RecentProject;
use App\Models\StarredProject;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\TeamMember;
use App\Models\Timer;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ProjectsController extends Controller {
    public function index(){
        return Inertia::render('Projects/Index', [
            'title' => 'Projects',
        ]);
    }

    public function test(){
        return Inertia::render('Projects/Test', [
            'title' => 'Projects',
        ]);
    }

    public function jsonCreate(Request $request){
        $requests = $request->all();
        $requests['user_id'] = auth()->id();
        $project = Project::create($requests);

        $slug = $this->clean($project->title);
        $existingItem = Project::where('slug', $slug)->first();
        if(!empty($existingItem)){
            $slug = $slug . '-' . $project->id;
        }
        $project->slug = $slug;
        $project->save();

        return response()->json($project);
    }

    public function jsonMembers($project_id){
        $assignees = Assignee::whereHas('task', function ($q) use ($project_id) {
            $q->where('project_id', $project_id);
        })->where('user_id', '!=', auth()->id())->groupBy('user_id')->with('user:id,first_name,last_name,photo_path')->get();
        return response()->json($assignees);
    }

    public function jsonFilterData($project_id){
        $assignees = Assignee::whereHas('task', function ($q) use ($project_id) {
            $q->where('project_id', $project_id);
        })->where('user_id', '!=', auth()->id())->groupBy('user_id')->with('user:id,first_name,last_name,photo_path')->get();
        $labels = Label::orderBy('name')->get();
        return response()->json(['assignees' => $assignees, 'labels' => $labels]);
    }

    public function all(){
        $projects = Project::get();
        return response()->json($projects);
    }

    public function jsonAll($workspace_id){
        $projects = Project::where('workspace_id', $workspace_id)->with('background')->with('star')->get();
        return response()->json($projects);
    }

    public function jsonRecent(){
        $user_id = auth()->id();
        $workspaceIds = Workspace::where('user_id', $user_id)->orWhereHas('member')->pluck('id');
        $projects = RecentProject::where('user_id', $user_id)->with('project')->has('project.workspace')->whereHas('project', function ($q) use ($workspaceIds) {
            $q->whereIn('workspace_id', $workspaceIds);
        })->orderBy('opened', 'desc')->paginate(10)
            ->through(function ($project) {
                return [
                    'id' => $project->project->id,
                    'title' => $project->project->title,
                    'slug' => $project->project->slug,
                    'star' => (bool)$project->project->star,
                    'workspace' => $project->project->workspace->name,
                    'background' => $project->project->background?$project->project->background->image:null,
                ];
            });
        return response()->json($projects);
    }

    public function jsonStar(){
        $user_id = auth()->id();
        $workspaceIds = Workspace::where('user_id', $user_id)->orWhereHas('member')->pluck('id');
        $projects = StarredProject::where('user_id', $user_id)->with('project')->has('project.workspace')->whereHas('project', function ($q) use ($workspaceIds) {
            $q->whereIn('workspace_id', $workspaceIds);
        })->orderBy('updated_at', 'desc')->paginate(100)
            ->through(function ($project) {
                return [
                    'id' => $project->project->id,
                    'title' => $project->project->title,
                    'slug' => $project->project->slug,
                    'star' => (bool)$project->project->star,
                    'workspace' => $project->project->workspace->name,
                    'background' => $project->project->background?$project->project->background->image:null,
                ];
            });
        return response()->json($projects);
    }

    public function update($id, Request $request){
        $project = Project::whereId($id)->first();
        $requestData = $request->all();
        foreach ($requestData as $itemKey => $itemValue){
            $project->{$itemKey} = $itemValue;
        }
        $project->save();
        return response()->json($project);
    }

    public function noProject(){
        return Inertia::render('Projects/Na', [
            'title' => 'No Workspace',
            'notice' => 'You did not assigned any workspace yet. Please contact with admin'
        ]);
    }

    public function view($uid, Request $request){
        $auth_id = auth()->id();
        $workspaceIds = Workspace::where('user_id', $auth_id)->orWhereHas('member')->pluck('id');
        $requests = $request->all();
        $project = Project::bySlugOrId($uid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        RecentProject::updateOrCreate(['user_id' => $auth_id, 'project_id' => $project->id], ['opened' => Carbon::now()]);
        $list_index = [];
        $board_lists = BoardList::where('project_id', $project->id)->isOpen()->orderByOrder()->get()->toArray();
        $loopIndex = 0;
        foreach ($board_lists as &$listItem){
            $list_index[$listItem['id']] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex+= 1;
        }
        if($project->is_private && (auth()->user()['role_id'] != 1)){
            $requests['private_task'] = $auth_id;
        }
        $tasks = Task::filter($requests)
            ->isOpen()
            ->byProject($project->id)
            ->with('taskLabels.label')
            ->with('timer')
            ->whereHas('list')
            ->with('cover')
            ->withCount('checklistDone')
            ->withCount('comments')
            ->withCount('checklists')
            ->withCount('attachments')->with('assignees')
            ->orderByOrder()->get()->toArray();
        foreach ($tasks as $task){
            if(isset($list_index[$task['list_id']])){
                $board_lists[$list_index[$task['list_id']]]['tasks'][] = $task;
            }
        }
//        dd($board_lists);
        return Inertia::render('Projects/View', [
            'title' => 'Board | '.$project->title,
            'board_lists' => $board_lists,
            'lists' => $board_lists,
            'list_index' => $list_index,
            'filters' => $requests,
            'project' => $project,
            'tasks' => $tasks,
        ]);
    }

    public function viewWithTask($projectUid, $taskUid, Request $request){
        $requests = $request->all();
        $auth_id = auth()->id();
        $workspaceIds = Workspace::where('user_id', $auth_id)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($projectUid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $list_index = [];
        $board_lists = BoardList::where('project_id', $project->id)->isOpen()->orderByOrder()->get()->toArray();
        $loopIndex = 0;
        foreach ($board_lists as &$listItem){
            $list_index[$listItem['id']] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex+= 1;
        }
        $tasks = Task::filter($requests)
            ->isOpen()
            ->byProject($project->id)
            ->with('taskLabels.label')
            ->whereHas('list')
            ->withCount('checklistDone')
            ->withCount('comments')
            ->withCount('checklists')
            ->withCount('attachments')
            ->with('assignees')
            ->orderByOrder()
            ->get()->toArray();
        foreach ($tasks as $task){
            $board_lists[$list_index[$task['list_id']]]['tasks'][] = $task;
        }
        return Inertia::render('Projects/View', [
            'title' => 'Projects',
            'filters' => $requests,
            'board_lists' => $board_lists,
            'lists' => $board_lists,
            'list_index' => $list_index,
            'project' => $project,
            'task' => Task::where('id', $taskUid)->orWhere('slug', $taskUid)->first(),
            'tasks' => $tasks,
        ]);
    }

    public function viewTable($uid, Request $request){
        $requests = $request->all();
        $auth_id = auth()->id();
        $workspaceIds = Workspace::where('user_id', $auth_id)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($uid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $list_index = [];
        $board_lists = BoardList::where('project_id', $project->id)->isOpen()->orderByOrder()->get()->toArray();
        $loopIndex = 0;
        foreach ($board_lists as &$listItem){
            $list_index[$listItem['id']] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex+= 1;
        }
        $tasks = Task::filter($requests)
            ->isOpen()
            ->byProject($project->id)
            ->with('taskLabels.label')
            ->with('timer')
            ->whereHas('list')
            ->with('assignees')
            ->with('list')
            ->orderByOrder()
            ->get()->toArray();
        foreach ($tasks as $task){
            $board_lists[$list_index[$task['list_id']]]['tasks'][] = $task;
        }
        return Inertia::render('Projects/Table', [
            'title' => 'Table | '.$project->title,
            'board_lists' => $board_lists,
            'lists' => $board_lists,
            'list_index' => $list_index,
            'project' => $project,
            'filters' => $requests,
            'tasks' => $tasks
        ]);
    }

    public function viewTableWithTask($uid, $taskUid, Request $request){
        $requests = $request->all();
        $auth_id = auth()->id();
        $workspaceIds = Workspace::where('user_id', $auth_id)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($uid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $list_index = [];
        $board_lists = BoardList::where('project_id', $project->id)->isOpen()->orderByOrder()->get()->toArray();
        $loopIndex = 0;
        foreach ($board_lists as &$listItem){
            $list_index[$listItem['id']] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex+= 1;
        }
        $tasks = Task::filter($requests)
            ->isOpen()
            ->byProject($project->id)
            ->with('taskLabels.label')
            ->whereHas('list')
            ->with('assignees')
            ->with('list')
            ->orderByOrder()
            ->get()->toArray();
        foreach ($tasks as $task){
            $board_lists[$list_index[$task['list_id']]]['tasks'][] = $task;
        }
        return Inertia::render('Projects/Table', [
            'title' => 'Projects',
            'board_lists' => $board_lists,
            'lists' => $board_lists,
            'list_index' => $list_index,
            'filters' => $requests,
            'project' => $project,
            'task' => Task::where('id', $taskUid)->orWhere('slug', $taskUid)->first(),
            'timer' => Timer::with('task')->mine()->running()->first() ?? null,
            'tasks' => $tasks
        ]);
    }

    public function viewDashboard($uid){
        $auth_id = auth()->id();
        $workspaceIds = Workspace::where('user_id', $auth_id)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($uid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $taskIds = Task::where('project_id', $project->id)->pluck('id')->toArray();
        $per_list = Task::select('list_id', DB::raw('count(*) as total'))->where('project_id', $project->id)->groupBy('list_id')->whereHas('list')->with('list')->get()->toArray();
        $per_assignee = Assignee::select('user_id', DB::raw('count(*) as total'))->whereIn('task_id', $taskIds)->groupBy('user_id')->with('user')->get()->toArray();
        $per_label = TaskLabel::select('label_id', DB::raw('count(*) as total'))->whereIn('task_id', $taskIds)->groupBy('label_id')->with('label')->get()->toArray();
        $due_done = Task::where('project_id', $project->id)->where('is_done', 1)->count();
        $no_due = Task::where('project_id', $project->id)->whereNull('due_date')->count();
        $due_over = Task::where('project_id', $project->id)->where('due_date', '<', Carbon::now())->count();
        $due_later = Task::where('project_id', $project->id)->where('due_date', '>', Carbon::now()->addDay())->count();
        $due_soon = Task::where('project_id', $project->id)->whereBetween('due_date', [Carbon::now(), Carbon::now()->addDay()])->count();
        return Inertia::render('Projects/Dashboard', [
            'title' => 'Dashboard | '.$project->title,
            'per_list' => $per_list,
            'project' => $project,
            'per_assignee' => $per_assignee,
            'per_label' => $per_label,
            'due_data' => [
                ['due' => ['name' => 'Complete', 'color' => '#22A06B' ], 'total' => $due_done],
                ['due' => ['name' => 'Due soon', 'color' => '#B38600' ], 'total' => $due_soon],
                ['due' => ['name' => 'Due later', 'color' => '#E56910' ], 'total' => $due_later],
                ['due' => ['name' => 'Overdue', 'color' => '#C9372C' ], 'total' => $due_over],
                ['due' => ['name' => 'No due date', 'color' => '#607d8b' ], 'total' => $no_due],
            ]
        ]);
    }

    public function viewCalendar($uid, Request $request)
    {
        $requests = $request->all();
        $auth_id = auth()->id();
        $workspaceIds = Workspace::where('user_id', $auth_id)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($uid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $list_index = [];
        $board_lists = BoardList::where('project_id', $project->id)->isOpen()->orderByOrder()->get()->toArray();
        $loopIndex = 0;
        foreach ($board_lists as &$listItem){
            $list_index[$listItem['id']] = $loopIndex;
            $listItem['tasks'] = [];
            $loopIndex+= 1;
        }
        $tasks = Task::filter($requests)
            ->isOpen()
            ->byProject($project->id)
            ->with('taskLabels.label')
            ->with('timer')
            ->whereHas('list')
            ->with('assignees')
            ->with('list')
            ->orderByOrder()
            ->get()->toArray();
        foreach ($tasks as $task){
            $board_lists[$list_index[$task['list_id']]]['tasks'][] = $task;
        }
        return Inertia::render('Projects/Calendar', [
            'title' => 'Calendar | '.$project->title,
            'board_lists' => $board_lists,
            'lists' => $board_lists,
            'list_index' => $list_index,
            'project' => $project,
            'filters' => $requests,
            'tasks' => $tasks
        ]);
    }

    public function viewTimeLogs($projectUid, Request $request){
        $requests = $request->all();
        $auth_id = auth()->id();
        $workspaceIds = Workspace::where('user_id', $auth_id)->orWhereHas('member')->pluck('id');
        $project = Project::bySlugOrId($projectUid)->whereIn('workspace_id', $workspaceIds)->with('workspace.member')->with('star')->with('background')->first();
        $timerQuery = Timer::whereHas('task', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })->filter($requests);
        return Inertia::render('Projects/Timer.vue', [
            'title' => 'Time Logs | '.$project->title,
            'project' => $project,
            'filters' => $requests,
            'total_duration' => $timerQuery->sum('duration'),
            'time_logs' => $timerQuery->with('task')
                ->with('user')
                ->orderBy('created_at', 'DESC')
                ->paginate(9)
                ->withQueryString()
                ->through(function ($log) {
                    return [
                        'id' => $log->id,
                        'title' => $log->title,
                        'user' => $log->user,
                        'task' => $log->task,
                        'task_id' => $log->task_id,
                        'duration' => $log->duration,
                        'started_at' => $log->started_at,
                        'stopped_at' => $log->stopped_at,
                        'created_at' => $log->created_at,
                    ];
                } ),
        ]);
    }

    public function projectOtherData($project_id){
        $project = Project::where('id', $project_id)->first();
        $labels = Label::get();
        $lists = BoardList::withCount('tasks')->get();
        $teamMembers = TeamMember::with('user')->where('workspace_id', $project->workspace_id)->get();
        return response()->json(['labels' => $labels, 'lists' => $lists, 'team_members' => $teamMembers]);
    }

    public function workspaceOtherData($workspace_id){
        $labels = Label::get();
        $teamMembers = TeamMember::with('user')->where('workspace_id', $workspace_id)->get();
        return response()->json(['labels' => $labels, 'team_members' => $teamMembers]);
    }

    private function clean($string) {
        $string = str_replace(' ', '-', $string);
        $string = preg_match("/[a-z]/i", $string)?$string:'untitled';
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        return preg_replace('/-+/', '-', $string);
    }

    public function destroy($id){
        $project = Project::where('id', $id)->first();
        $workspace_id = $project->workspace_id;
        if(!empty($project)){
            BoardList::where('project_id', $project->id)->delete();
            RecentProject::where('project_id', $project->id)->delete();
            StarredProject::where('project_id', $project->id)->delete();
            $tasks = Task::where('project_id', $project->id)->get();
            foreach ($tasks as $task){
                $attachments = Attachment::where('task_id', $task->id)->get();
                foreach ($attachments as $attachment){
                    if(!empty($attachment->path) && File::exists(public_path($attachment->path))){
                        File::delete(public_path($attachment->path));
                    }
                    $attachment->delete();
                }
                CheckList::where('task_id', $task->id)->delete();
                Timer::where('task_id', $task->id)->delete();
                Comment::where('task_id', $task->id)->delete();
                Assignee::where('task_id', $task->id)->delete();
                TaskLabel::where('task_id', $task->id)->delete();
                $task->delete();
            }
            $project->delete();
        }
        return Redirect::route('workspace.view', $workspace_id);
    }
}
