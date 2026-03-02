<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    public function exportHoursByUserProject(Request $request)
    {
        $request->validate([
            'workspace_id' => ['required', 'integer'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date'],
            'projects'     => ['nullable', 'array'],
            'projects.*'   => ['string'],
        ]);

        $workspaceId = (int) $request->workspace_id;
        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        if ($start->gt($end)) {
            return response()->json(['error' => 'El rango de fechas no es válido (inicio > fin).'], 422);
        }

        // 1) Data base (usuario, proyecto, horas)
        $rows = DB::table('tasks')
            ->join('timers', 'tasks.id', '=', 'timers.task_id')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->join('users', 'timers.user_id', '=', 'users.id')
            ->whereNotNull('timers.stopped_at')
            ->whereBetween(DB::raw('DATE(timers.started_at)'), [$start->toDateString(), $end->toDateString()])
            // ->where('projects.workspace_id', $workspaceId)
            ->groupBy('timers.user_id', 'tasks.project_id', 'projects.title', 'users.first_name', 'users.last_name')
            ->selectRaw('CONCAT(users.first_name, " ", users.last_name) as usuario')
            ->selectRaw('projects.title as proyecto')
            ->selectRaw('SUM(timers.duration)/3600 as horas')
            ->get();

        // 2) Columnas a exportar
        $allProjects = $rows->pluck('proyecto')->unique()->values()->all();
        $selectedProjects = $request->projects ?: $allProjects;

        // evita columnas fantasma
        $selectedProjects = array_values(array_filter(
            $selectedProjects,
            fn($p) => in_array($p, $allProjects, true)
        ));

        // 3) Pivot usuario -> proyecto -> horas
        $users = $rows->pluck('usuario')->unique()->values()->all();
        $map = []; // $map[usuario][proyecto] = horas

        foreach ($rows as $r) {
            $u = $r->usuario;
            $p = $r->proyecto;
            $h = (float) $r->horas;

            if (!isset($map[$u])) $map[$u] = [];
            $map[$u][$p] = ($map[$u][$p] ?? 0) + $h;
        }

        // 4) Crear Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Horas');

        // Header en fila 1
        $col = 1;
        $sheet->setCellValue($this->excelCol($col++) . '1', 'Usuario');

        foreach ($selectedProjects as $p) {
            $sheet->setCellValue($this->excelCol($col++) . '1', $p);
        }

        $sheet->setCellValue($this->excelCol($col++) . '1', 'Total');

        // Body desde fila 2
        $rowIndex = 2;
        foreach ($users as $u) {
            $col = 1;
            $sheet->setCellValue($this->excelCol($col++) . $rowIndex, $u);

            $total = 0.0;

            foreach ($selectedProjects as $p) {
                $val = (float) ($map[$u][$p] ?? 0);
                $total += $val;

                $sheet->setCellValue(
                    $this->excelCol($col++) . $rowIndex,
                    round($val, 2)
                );
            }

            $sheet->setCellValue(
                $this->excelCol($col++) . $rowIndex,
                round($total, 2)
            );

            $rowIndex++;
        }

        // Estética básica
        $lastCol = $this->excelCol(2 + count($selectedProjects)); // Usuario + proyectos + Total
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);

        // AutoSize (solo hasta Z funciona perfecto, pero esto te ayuda mucho)
        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // 5) Descargar response
        $filename = 'horas_por_usuario_y_proyecto_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        // Stream (mejor que ob_start en memoria cuando crezca)
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function excelCol(int $n): string
    {
        // 1->A, 2->B, ... 26->Z, 27->AA...
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = intdiv($n - 1, 26);
        }
        return $s;
    }

    public function tableTaskTimers(Request $request)
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

        $rows = DB::table('tasks as ta')
            ->join('timers as ti', 'ta.id', '=', 'ti.task_id')
            ->join('projects as p', 'ta.project_id', '=', 'p.id')
            ->join('workspaces as w', 'p.workspace_id', '=', 'w.id')
            ->join('board_lists as bl', 'ta.list_id', '=', 'bl.id')
            ->leftJoin('board_sublist as bs', 'ta.sublist_id', '=', 'bs.id')
            ->join('users as u', 'ti.user_id', '=', 'u.id')
            ->whereNotNull('ti.stopped_at')
            ->whereBetween(DB::raw('DATE(ti.started_at)'), [$start->toDateString(), $end->toDateString()])
            ->where('p.workspace_id', $workspaceId)
            ->orderBy('w.name')
            ->orderBy('p.title')
            ->orderBy('ti.started_at')
            ->selectRaw('
                ta.title as tarea,
                ta.is_done as terminada,
                ta.created_at as creacion,
                w.name as espacio,
                p.title as proyecto,
                CONCAT(u.first_name, " ", u.last_name) as realizada_por,
                ti.started_at as inicio,
                ti.stopped_at as fin,
                (ti.duration / 3600) as horas,
                bl.title as carril,
                bs.title as estatus
            ')
            ->get();

        return response()->json([
            'workspace_id' => $workspaceId,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'rows' => $rows,
        ]);
    }

    public function exportTaskTimersDetail(Request $request)
    {
        $request->validate([
            'workspace_id' => ['required', 'integer'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date'],
            'projects'     => ['nullable', 'array'],   // opcional: filtrar por nombres de proyectos seleccionados
            'projects.*'   => ['string'],
        ]);

        $workspaceId = (int) $request->workspace_id;
        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        if ($start->gt($end)) {
            return response()->json(['error' => 'El rango de fechas no es válido (inicio > fin).'], 422);
        }

        // Query detalle (similar a tu SQL)
        $q = DB::table('tasks')
            ->join('timers', 'tasks.id', '=', 'timers.task_id')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->join('board_lists', 'tasks.list_id', '=', 'board_lists.id')
            ->join('board_sublist', 'tasks.sublist_id', '=', 'board_sublist.id')
            ->join('users', 'timers.user_id', '=', 'users.id')
            ->join('workspaces', 'projects.workspace_id', '=', 'workspaces.id')
            // ->where('projects.workspace_id', $workspaceId)
            ->whereNotNull('timers.stopped_at')
            ->whereBetween(DB::raw('DATE(timers.started_at)'), [$start->toDateString(), $end->toDateString()])
            ->selectRaw('tasks.title as tarea')
            ->selectRaw('tasks.is_done as terminada')
            ->selectRaw('tasks.created_at as creacion')
            ->selectRaw('workspaces.name as espacio')
            ->selectRaw('projects.title as proyecto')
            ->selectRaw('CONCAT(users.first_name, " ", users.last_name) as realizada_por')
            ->selectRaw('timers.started_at as inicio')
            ->selectRaw('timers.stopped_at as fin')
            ->selectRaw('(timers.duration / 3600) as horas')
            ->selectRaw('board_lists.title as carril')
            ->selectRaw('board_sublist.title as estatus')
            ->orderBy('espacio')
            ->orderBy('proyecto')
            ->orderBy('inicio');

        // Opcional: si quieres que exporte SOLO proyectos seleccionados (mismo filtro del front)
        $selectedProjects = $request->input('projects', null);
        if (is_array($selectedProjects) && count($selectedProjects)) {
            $q->whereIn('projects.title', $selectedProjects);
        }

        $rows = $q->get();

        // Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detalle');

        $headers = [
            'Tarea',
            'Terminada',
            'Creación',
            'Espacio',
            'Proyecto',
            'Realizada por',
            'Inicio',
            'Fin',
            'Horas',
            'Carril',
            'Estatus',
        ];

        // Header row
        $col = 1;
        foreach ($headers as $h) {
            // ✅ método compatible (evita el error que tuviste)
            $sheet->setCellValue([$col, 1], $h);
            $col++;
        }
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        // Data rows
        $r = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue([1, $r], (string) $row->tarea);
            $sheet->setCellValue([2, $r], ((int)$row->terminada) ? 'Sí' : 'No');
            $sheet->setCellValue([3, $r], (string) $row->creacion);
            $sheet->setCellValue([4, $r], (string) $row->espacio);
            $sheet->setCellValue([5, $r], (string) $row->proyecto);
            $sheet->setCellValue([6, $r], (string) $row->realizada_por);
            $sheet->setCellValue([7, $r], (string) $row->inicio);
            $sheet->setCellValue([8, $r], (string) $row->fin);
            $sheet->setCellValue([9, $r], round((float) $row->horas, 2));
            $sheet->setCellValue([10, $r], (string) $row->carril);
            $sheet->setCellValue([11, $r], (string) $row->estatus);
            $r++;
        }

        // Auto-size columns A..K
        foreach (range('A', 'K') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = 'detalle_tareas_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $excelOutput = ob_get_clean();

        return response($excelOutput, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}