<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductivityController extends Controller
{
    public function index()
    {
        return Inertia::render('Productivity/Index', [
            'title' => 'Productividad',
        ]);
    }

    public function workspaces()
    {
        $rows = DB::table('workspaces')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    public function projects(Request $request)
    {
        $request->validate([
            'workspace_id' => ['required', 'integer'],
        ]);

        $workspaceId = (int) $request->workspace_id;

        $rows = DB::table('projects')
            ->where('workspace_id', $workspaceId)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    public function lanes(Request $request)
    {
        $request->validate([
            'project_id' => ['required', 'integer'],
        ]);

        $projectId = (int) $request->project_id;

        $rows = DB::table('board_lists')
            ->where('project_id', $projectId)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    public function cumulativeFlowByLane(Request $request)
    {
        $request->validate([
            'lane_id'    => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date'],
        ]);

        $laneId = (int) $request->lane_id;
        $start  = Carbon::parse($request->start_date)->startOfDay();
        $end    = Carbon::parse($request->end_date)->endOfDay();

        if ($start->gt($end)) {
            return response()->json([
                'error' => 'El rango de fechas no es válido (inicio > fin).'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | 1) Catálogo de sublistas del carril
        |--------------------------------------------------------------------------
        */
        $sublistCatalog = DB::table('board_sublist')
            ->where('list_id', $laneId)
            ->select('id', 'title')
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2) Conteo diario de movimientos por sublista
        |--------------------------------------------------------------------------
        | Se cuenta cuántas tareas entraron a cada sublista por día.
        */
        $raw = DB::table('log_task')
            ->join('tasks', 'log_task.task_id', '=', 'tasks.id')
            ->join('board_sublist', 'log_task.new_value', '=', 'board_sublist.id')
            ->join('board_lists', 'tasks.list_id', '=', 'board_lists.id')
            ->where('tasks.list_id', $laneId)
            ->whereBetween(DB::raw('DATE(log_task.created_at)'), [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->groupBy(
                'board_sublist.id',
                'board_sublist.title',
                DB::raw('DATE(log_task.created_at)')
            )
            ->selectRaw("
                board_sublist.id as sublist_id,
                board_sublist.title as sublist,
                DATE(log_task.created_at) as work_date,
                COUNT(log_task.task_id) as tareas
            ")
            ->orderBy('work_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3) Construir arreglo completo de fechas del rango
        |--------------------------------------------------------------------------
        */
        $dates = [];
        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
            $dates[] = $date->format('Y-MM-DD');
        }

        /*
        |--------------------------------------------------------------------------
        | 4) Mapear conteo diario: [sublist_id][date] = tareas
        |--------------------------------------------------------------------------
        */
        $dailyMap = [];

        foreach ($raw as $row) {
            $sublistId = (int) $row->sublist_id;
            $date      = (string) $row->work_date;
            $count     = (int) $row->tareas;

            if (!isset($dailyMap[$sublistId])) {
                $dailyMap[$sublistId] = [];
            }

            $dailyMap[$sublistId][$date] = $count;
        }

        /*
        |--------------------------------------------------------------------------
        | 5) Construir series acumulativas
        |--------------------------------------------------------------------------
        */
        $series = [];

        foreach ($sublistCatalog as $sublist) {
            $sublistId = (int) $sublist->id;
            $running = 0;
            $y = [];

            foreach ($dates as $date) {
                $dailyValue = (int) ($dailyMap[$sublistId][$date] ?? 0);
                $running += $dailyValue;
                $y[] = $running;
            }

            $series[] = [
                'id'   => $sublistId,
                'name' => (string) $sublist->title,
                'y'    => $y,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 6) Opcional: quitar series completamente vacías
        |--------------------------------------------------------------------------
        */
        $series = array_values(array_filter($series, function ($s) {
            return collect($s['y'])->sum() > 0;
        }));

        return response()->json([
            'lane_id'    => $laneId,
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'dates'      => $dates,
            'series'     => $series,
        ]);
    }

    public function completedTaskHoursBoxplot(Request $request)
    {
        $request->validate([
            'workspace_id' => ['required', 'integer'],
            'project_id'   => ['required', 'integer'],
            'lane_id'      => ['required', 'integer'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date'],
        ]);

        $workspaceId = (int) $request->workspace_id;
        $projectId   = (int) $request->project_id;
        $laneId      = (int) $request->lane_id;
        $start       = Carbon::parse($request->start_date)->startOfDay();
        $end         = Carbon::parse($request->end_date)->endOfDay();

        if ($start->gt($end)) {
            return response()->json([
                'error' => 'El rango de fechas no es válido (inicio > fin).'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | 1) Horas por tarea completada y usuario
        |--------------------------------------------------------------------------
        | Se toma la fecha de tasks.updated_at porque ahí estás considerando
        | cuándo la tarea quedó realizada/actualizada en ese rango.
        */
        $raw = DB::table('tasks')
            ->join('timers', 'tasks.id', '=', 'timers.task_id')
            ->join('users', 'timers.user_id', '=', 'users.id')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('tasks.is_done', true)
            // ->where('tasks.project_id', $projectId)
            // ->where('tasks.list_id', $laneId)
            // ->where('projects.workspace_id', $workspaceId)
            ->whereNotNull('timers.stopped_at')
            ->whereBetween(DB::raw('DATE(tasks.created_at)'), [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->groupBy(
                'tasks.id',
                'timers.user_id',
                'users.first_name',
                'users.last_name'
            )
            ->selectRaw("
                tasks.id as task_id,
                timers.user_id,
                CONCAT(users.first_name, ' ', users.last_name) as usuario,
                SUM(timers.duration)/3600 as horas
            ")
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2) Agrupar valores por usuario
        |--------------------------------------------------------------------------
        */
        $grouped = [];

        foreach ($raw as $row) {
            $user = trim((string) $row->usuario);
            $hours = (float) $row->horas;

            if (!isset($grouped[$user])) {
                $grouped[$user] = [];
            }

            $grouped[$user][] = round($hours, 2);
        }

        /*
        |--------------------------------------------------------------------------
        | 3) Formato de salida para boxplot
        |--------------------------------------------------------------------------
        */
        $rows = collect($grouped)
            ->map(function ($values, $user) {
                sort($values, SORT_NUMERIC);

                return [
                    'user'   => $user,
                    'values' => array_values($values),
                    'count'  => count($values),
                    'avg'    => count($values) > 0
                        ? round(array_sum($values) / count($values), 2)
                        : 0,
                ];
            })
            ->sortByDesc('avg')
            ->values()
            ->all();

        return response()->json([
            'workspace_id' => $workspaceId,
            'project_id'   => $projectId,
            'lane_id'      => $laneId,
            'start_date'   => $start->toDateString(),
            'end_date'     => $end->toDateString(),
            'rows'         => $rows,
        ]);
    }
}