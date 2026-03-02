<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkspaceStatisticsController extends Controller
{
    public function hoursByUserProject(Request $request)
    {
        $request->validate([
            'workspace_id' => ['required', 'integer'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date'],
        ]);

        $workspaceId = (int) $request->workspace_id;
        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        if ($start->gt($end)) {
            return response()->json(['error' => 'El rango de fechas no es válido (inicio > fin).'], 422);
        }

        $raw = DB::table('tasks as ta')
            ->join('timers as t', 'ta.id', '=', 't.task_id')
            ->join('projects as p', 'ta.project_id', '=', 'p.id')
            ->join('users as u', 't.user_id', '=', 'u.id')
            // ->where('p.workspace_id', $workspaceId)
            ->whereNotNull('t.stopped_at')
            ->whereBetween(DB::raw('DATE(t.started_at)'), [$start->toDateString(), $end->toDateString()])
            ->groupBy('t.user_id', 'ta.project_id', 'u.first_name', 'u.last_name', 'p.title')
            ->selectRaw("
                t.user_id,
                ta.project_id,
                CONCAT(u.first_name,' ',u.last_name) as usuario,
                p.title as proyecto,
                SUM(t.duration)/3600 as horas
            ")
            ->get();

        $projects = $raw->pluck('proyecto')->unique()->values()->all();
        $users = $raw->pluck('usuario')->unique()->values()->all();

        $map = [];
        foreach ($raw as $r) {
            $user = (string) $r->usuario;
            $proj = (string) $r->proyecto;
            $hrs  = (float) $r->horas;

            if (!isset($map[$user])) $map[$user] = [];
            $map[$user][$proj] = ($map[$user][$proj] ?? 0) + $hrs;
        }

        $rows = [];
        foreach ($users as $user) {
            $row = [
                'usuario' => $user,
                'total' => 0.0,
                'projects' => [],
            ];

            foreach ($projects as $proj) {
                $v = (float) ($map[$user][$proj] ?? 0.0);
                $row['projects'][$proj] = $v;
                $row['total'] += $v;
            }

            $rows[] = $row;
        }

        usort($rows, fn($a, $b) => ($b['total'] <=> $a['total']));

        $chart = [
            'users' => array_map(fn($r) => $r['usuario'], $rows),
            'series' => [],
        ];

        foreach ($projects as $proj) {
            $chart['series'][] = [
                'name' => $proj,
                'values' => array_map(fn($r) => (float) $r['projects'][$proj], $rows),
            ];
        }

        return response()->json([
            'workspace_id' => $workspaceId,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'projects' => $projects,
            'rows' => $rows,
            'chart' => $chart,
        ]);
    }
}