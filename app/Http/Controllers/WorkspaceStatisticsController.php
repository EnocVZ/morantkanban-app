<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

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
                p.title as dimension,
                SUM(t.duration)/3600 as horas
            ")
            ->get();

        return response()->json(
            $this->buildHoursMatrixResponse($workspaceId, $start, $end, $raw, 'project')
        );
    }

    public function hoursByUserLabel(Request $request)
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
            ->join('task_labels as tl', 'ta.id', '=', 'tl.task_id')
            ->join('labels as l', 'tl.label_id', '=', 'l.id')
            ->join('users as u', 't.user_id', '=', 'u.id')
            // ->where('ta.workspace_id', $workspaceId)
            ->whereNotNull('t.stopped_at')
            ->whereBetween(DB::raw('DATE(t.started_at)'), [$start->toDateString(), $end->toDateString()])
            ->groupBy('t.user_id', 'l.id', 'u.first_name', 'u.last_name', 'l.name')
            ->selectRaw("
                t.user_id,
                l.id as dimension_id,
                CONCAT(u.first_name,' ',u.last_name) as usuario,
                l.name as dimension,
                SUM(t.duration)/3600 as horas
            ")
            ->get();

        return response()->json(
            $this->buildHoursMatrixResponse($workspaceId, $start, $end, $raw, 'label')
        );
    }

    public function hoursByUserLane(Request $request)
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
            ->join('board_lists as bl', 'ta.list_id', '=', 'bl.id')
            ->join('users as u', 't.user_id', '=', 'u.id')
            // ->where('ta.workspace_id', $workspaceId)
            ->whereNotNull('t.stopped_at')
            ->whereBetween(DB::raw('DATE(t.started_at)'), [$start->toDateString(), $end->toDateString()])
            ->groupBy('t.user_id', 'bl.id', 'u.first_name', 'u.last_name', 'bl.title')
            ->selectRaw("
                t.user_id,
                bl.id as dimension_id,
                CONCAT(u.first_name,' ',u.last_name) as usuario,
                bl.title as dimension,
                SUM(t.duration)/3600 as horas
            ")
            ->get();

        return response()->json(
            $this->buildHoursMatrixResponse($workspaceId, $start, $end, $raw, 'lane')
        );
    }

    public function taskTimersByDimension(Request $request, $workspace)
    {
        $request->validate([
            'workspace_id'   => ['required', 'integer'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date'],
            'dimension_type' => ['required', 'in:project,label,lane'],
            'selected_items' => ['nullable', 'array'],
            'selected_items.*' => ['string'],
        ]);

        $workspaceId = (int) $request->workspace_id;
        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();
        $dimensionType = $request->dimension_type;
        $selectedItems = $request->selected_items ?? [];

        if ($start->gt($end)) {
            return response()->json(['error' => 'El rango de fechas no es válido (inicio > fin).'], 422);
        }

        $query = DB::table('tasks as ta')
            ->join('timers as t', 'ta.id', '=', 't.task_id')
            ->leftJoin('projects as p', 'ta.project_id', '=', 'p.id')
            ->leftJoin('board_lists as bl', 'ta.list_id', '=', 'bl.id')
            ->leftJoin('board_sublist as bs', 'ta.sublist_id', '=', 'bs.id')
            ->leftJoin('workspaces as w', 'p.workspace_id', '=', 'w.id')
            ->leftJoin('users as u', 't.user_id', '=', 'u.id')
            // ->where('ta.workspace_id', $workspaceId)
            ->whereNotNull('t.stopped_at')
            ->whereBetween(DB::raw('DATE(t.started_at)'), [$start->toDateString(), $end->toDateString()]);

        if ($dimensionType === 'label') {
            $query->join('task_labels as tl_filter', 'ta.id', '=', 'tl_filter.task_id')
                ->join('labels as l_filter', 'tl_filter.label_id', '=', 'l_filter.id');

            if (!empty($selectedItems)) {
                $query->whereIn('l_filter.name', $selectedItems);
            }
        }

        if ($dimensionType === 'project' && !empty($selectedItems)) {
            $query->whereIn('p.title', $selectedItems);
        }

        if ($dimensionType === 'lane' && !empty($selectedItems)) {
            $query->whereIn('bl.title', $selectedItems);
        }

        $query->leftJoin('task_labels as tl', 'ta.id', '=', 'tl.task_id')
            ->leftJoin('labels as l', 'tl.label_id', '=', 'l.id');

        $rows = $query
            ->groupBy(
                'w.name',
                'p.title',
                'ta.title',
                'ta.is_done',
                'ta.created_at',
                'u.first_name',
                'u.last_name',
                't.started_at',
                't.stopped_at',
                'bl.title',
                'bs.title'
            )
            ->selectRaw("
                w.name as espacio,
                p.title as proyecto,
                ta.title as tarea,
                ta.is_done as terminada,
                ta.created_at as creacion,
                CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as realizada_por,
                t.started_at as inicio,
                t.stopped_at as fin,
                SUM(t.duration)/3600 as horas,
                bl.title as carril,
                bs.title as estatus,
                GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR ', ') as etiquetas
            ")
            ->orderByDesc('t.started_at')
            ->get();

        return response()->json([
            'workspace_id'   => $workspaceId,
            'start_date'     => $start->toDateString(),
            'end_date'       => $end->toDateString(),
            'dimension_type' => $dimensionType,
            'rows'           => $rows,
        ]);
    }

    private function buildHoursMatrixResponse(int $workspaceId, Carbon $start, Carbon $end, $raw, string $dimensionType): array
    {
        $dimensions = $raw->pluck('dimension')->unique()->values()->all();
        $users = $raw->pluck('usuario')->unique()->values()->all();

        $map = [];
        foreach ($raw as $r) {
            $user = (string) $r->usuario;
            $dim  = (string) $r->dimension;
            $hrs  = (float) $r->horas;

            if (!isset($map[$user])) {
                $map[$user] = [];
            }

            $map[$user][$dim] = ($map[$user][$dim] ?? 0) + $hrs;
        }

        $rows = [];
        foreach ($users as $user) {
            $row = [
                'usuario' => $user,
                'total' => 0.0,
                'items' => [],
            ];

            foreach ($dimensions as $dim) {
                $v = (float) ($map[$user][$dim] ?? 0.0);
                $row['items'][$dim] = $v;
                $row['total'] += $v;
            }

            $rows[] = $row;
        }

        usort($rows, fn($a, $b) => ($b['total'] <=> $a['total']));

        $chart = [
            'users' => array_map(fn($r) => $r['usuario'], $rows),
            'series' => [],
        ];

        foreach ($dimensions as $dim) {
            $chart['series'][] = [
                'name' => $dim,
                'values' => array_map(fn($r) => (float) $r['items'][$dim], $rows),
            ];
        }

        return [
            'workspace_id'   => $workspaceId,
            'start_date'     => $start->toDateString(),
            'end_date'       => $end->toDateString(),
            'dimension_type' => $dimensionType,
            'dimensions'     => $dimensions,
            'rows'           => $rows,
            'chart'          => $chart,
        ];
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
            // ->where('p.workspace_id', $workspaceId)
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

    public function chartWorkspacesTimePct(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date'],
        ]);

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        if ($start->gt($end)) {
            return response()->json([
                'error' => 'El rango de fechas no es válido (inicio > fin).'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | 1) Traer horas trabajadas por workspace
        |--------------------------------------------------------------------------
        */
        $rows = DB::table('tasks as ta')
            ->join('timers as t', 'ta.id', '=', 't.task_id')
            ->join('projects as p', 'ta.project_id', '=', 'p.id')
            ->join('workspaces as w', 'p.workspace_id', '=', 'w.id')
            ->whereNotNull('t.stopped_at')
            ->whereBetween(DB::raw('DATE(t.started_at)'), [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->groupBy('w.id', 'w.name')
            ->selectRaw('w.id as workspace_id, w.name as workspace, SUM(t.duration)/3600 as hours')
            ->orderByDesc(DB::raw('SUM(t.duration)'))
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2) Total de horas trabajadas
        |--------------------------------------------------------------------------
        */
        $workedHours = (float) $rows->sum(fn($r) => (float) $r->hours);

        /*
        |--------------------------------------------------------------------------
        | 3) Calcular días hábiles (L-V)
        |--------------------------------------------------------------------------
        */
        $businessDays = 0;
        $cursor = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();

        while ($cursor->lte($lastDay)) {
            if ($cursor->isWeekday()) {
                $businessDays++;
            }
            $cursor->addDay();
        }

        /*
        |--------------------------------------------------------------------------
        | 4) Horas teóricas y tiempo muerto
        |--------------------------------------------------------------------------
        */
        $theoreticalHours = $businessDays * 8;
        $idleHours = max(0, $theoreticalHours - $workedHours);

        /*
        |--------------------------------------------------------------------------
        | 5) Base total para porcentajes
        |--------------------------------------------------------------------------
        */
        $baseTotalHours = $workedHours + $idleHours;

        /*
        |--------------------------------------------------------------------------
        | 6) Mapear workspaces con porcentaje recalculado
        |--------------------------------------------------------------------------
        */
        $mapped = $rows->map(function ($r) use ($baseTotalHours) {
            $hours = (float) $r->hours;
            $pct = $baseTotalHours > 0 ? ($hours / $baseTotalHours) * 100 : 0;

            return [
                'workspace_id' => (int) $r->workspace_id,
                'workspace'    => (string) $r->workspace,
                'hours'        => round($hours, 2),
                'pct'          => round($pct, 2),
            ];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | 7) Agregar tiempo muerto si aplica
        |--------------------------------------------------------------------------
        */
        if ($idleHours > 0) {
            $idlePct = $baseTotalHours > 0 ? ($idleHours / $baseTotalHours) * 100 : 0;

            $mapped->push([
                'workspace_id' => 0,
                'workspace'    => 'Tiempo muerto',
                'hours'        => round($idleHours, 2),
                'pct'          => round($idlePct, 2),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 8) Responder
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'start_date'         => $start->toDateString(),
            'end_date'           => $end->toDateString(),
            'business_days'      => $businessDays,
            'theoretical_hours'  => round((float) $theoreticalHours, 2),
            'worked_hours'       => round((float) $workedHours, 2),
            'idle_hours'         => round((float) $idleHours, 2),
            'total_hours'        => round((float) $baseTotalHours, 2),
            'rows'               => $mapped->values(),
        ]);
    }

    public function chartUserDailyHoursHeatmap(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date'],
            // opcional si luego quieres filtrar:
            // 'workspace_id' => ['nullable','integer'],
        ]);

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        if ($start->gt($end)) {
            return response()->json([
                'error' => 'El rango de fechas no es válido (inicio > fin).'
            ], 422);
        }

        // (Opcional) si después quieres filtrar por workspace:
        // $workspaceId = $request->integer('workspace_id');

        // 1) Query base: horas por usuario por día
        $rows = DB::table('timers as t')
            ->join('tasks as ta', 'ta.id', '=', 't.task_id')
            ->join('projects as p', 'ta.project_id', '=', 'p.id')
            ->join('users as u', 't.user_id', '=', 'u.id')
            ->whereNotNull('t.stopped_at')
            ->whereBetween(DB::raw('DATE(t.started_at)'), [
                $start->toDateString(),
                $end->toDateString(),
            ])
            // ->when($workspaceId, fn($q) => $q->where('p.workspace_id', $workspaceId))
            ->groupBy('t.user_id', DB::raw('DATE(t.started_at)'), 'u.first_name', 'u.last_name')
            ->selectRaw('t.user_id as user_id')
            ->selectRaw('CONCAT(u.first_name, " ", u.last_name) as nombre')
            ->selectRaw('DATE(t.started_at) as fecha')
            ->selectRaw('SUM(t.duration)/3600 as horas')
            ->get();

        // 2) Fechas del rango (lista completa)
        $dates = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        // 3) Usuarios únicos
        $users = $rows->pluck('nombre')->unique()->values()->all();

        // 4) Mapear horas: map[nombre][fecha] = horas
        $map = [];
        $totals = []; // total por usuario para ordenar

        foreach ($rows as $r) {
            $name = (string)$r->nombre;
            $date = (string)$r->fecha;
            $hrs  = round((float)$r->horas, 2);

            if (!isset($map[$name])) $map[$name] = [];
            $map[$name][$date] = ($map[$name][$date] ?? 0) + $hrs;

            $totals[$name] = ($totals[$name] ?? 0) + $hrs;
        }

        // 5) Ordenar usuarios por total desc (para que se parezca a tu ejemplo)
        usort($users, function($a, $b) use ($totals) {
            return ($totals[$b] ?? 0) <=> ($totals[$a] ?? 0);
        });

        // 6) Construir matriz Z (usuarios x fechas)
        $z = [];
        foreach ($users as $name) {
            $rowZ = [];
            foreach ($dates as $d) {
                $rowZ[] = round((float)($map[$name][$d] ?? 0), 2);
            }
            $z[] = $rowZ;
        }

        return response()->json([
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'dates'      => $dates,
            'users'      => $users,
            'z'          => $z,
            'totals'     => array_map(fn($u) => round((float)($totals[$u] ?? 0), 2), $users),
        ]);
    }

    public function chartProjectTasksByDay(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date'],
        ]);

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        if ($start->gt($end)) {
            return response()->json([
                'error' => 'El rango de fechas no es válido (inicio > fin).'
            ], 422);
        }

        // 1) Query base: tareas por proyecto por día (solo si hay timer cerrado)
        $rows = DB::table('tasks as ta')
            ->join('timers as t', 'ta.id', '=', 't.task_id')
            ->join('projects as p', 'ta.project_id', '=', 'p.id')
            ->whereNotNull('t.stopped_at')
            ->whereBetween(DB::raw('DATE(t.started_at)'), [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->groupBy('ta.project_id', 'p.title', DB::raw('DATE(t.started_at)'))
            ->selectRaw('p.title as project, DATE(t.started_at) as date, COUNT(DISTINCT ta.id) as tasks')
            ->orderBy('date')
            ->orderBy('project')
            ->get();

        // 2) Armar lista de fechas (completa, incluyendo días sin registros)
        $dates = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        // 3) Lista de proyectos presentes
        $projects = $rows->pluck('project')->unique()->values()->all();

        // 4) Map: map[project][date] = tasks
        $map = [];
        foreach ($projects as $p) $map[$p] = [];

        foreach ($rows as $r) {
            $p = (string) $r->project;
            $d = (string) $r->date;
            $v = (int) $r->tasks;
            $map[$p][$d] = $v;
        }

        // 5) Serie: para cada proyecto, un array alineado con dates
        $series = [];
        foreach ($projects as $p) {
            $series[] = [
                'project' => $p,
                'y'       => array_map(fn($d) => (int)($map[$p][$d] ?? 0), $dates),
            ];
        }

        return response()->json([
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'dates'      => $dates,
            'projects'   => $projects,
            'series'     => $series, // <- listo para Plotly grouped bar
        ]);
    }

    public function exportReportByDimension(Request $request, $workspace)
    {
        $request->validate([
            'workspace_id'      => ['required', 'integer'],
            'start_date'        => ['required', 'date'],
            'end_date'          => ['required', 'date'],
            'dimension_type'    => ['required', 'in:project,label,lane'],
            'selected_items'    => ['nullable', 'array'],
            'selected_items.*'  => ['string'],
        ]);

        $workspaceId   = (int) $request->workspace_id;
        $start         = Carbon::parse($request->start_date)->startOfDay();
        $end           = Carbon::parse($request->end_date)->endOfDay();
        $dimensionType = $request->dimension_type;
        $selectedItems = $request->input('selected_items', []);

        if ($start->gt($end)) {
            return response()->json([
                'error' => 'El rango de fechas no es válido (inicio > fin).'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | 1) DATASET MATRIZ
        |--------------------------------------------------------------------------
        */
        if ($dimensionType === 'project') {
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
                    ta.project_id as dimension_id,
                    CONCAT(u.first_name,' ',u.last_name) as usuario,
                    p.title as dimension,
                    SUM(t.duration)/3600 as horas
                ")
                ->get();
        } elseif ($dimensionType === 'label') {
            $raw = DB::table('tasks as ta')
                ->join('timers as t', 'ta.id', '=', 't.task_id')
                ->join('task_labels as tl', 'ta.id', '=', 'tl.task_id')
                ->join('labels as l', 'tl.label_id', '=', 'l.id')
                ->join('users as u', 't.user_id', '=', 'u.id')
                // ->where('ta.workspace_id', $workspaceId)
                ->whereNotNull('t.stopped_at')
                ->whereBetween(DB::raw('DATE(t.started_at)'), [$start->toDateString(), $end->toDateString()])
                ->groupBy('t.user_id', 'l.id', 'u.first_name', 'u.last_name', 'l.name')
                ->selectRaw("
                    t.user_id,
                    l.id as dimension_id,
                    CONCAT(u.first_name,' ',u.last_name) as usuario,
                    l.name as dimension,
                    SUM(t.duration)/3600 as horas
                ")
                ->get();
        } else {
            $raw = DB::table('tasks as ta')
                ->join('timers as t', 'ta.id', '=', 't.task_id')
                ->join('board_lists as bl', 'ta.list_id', '=', 'bl.id')
                ->join('users as u', 't.user_id', '=', 'u.id')
                // ->where('ta.workspace_id', $workspaceId)
                ->whereNotNull('t.stopped_at')
                ->whereBetween(DB::raw('DATE(t.started_at)'), [$start->toDateString(), $end->toDateString()])
                ->groupBy('t.user_id', 'bl.id', 'u.first_name', 'u.last_name', 'bl.title')
                ->selectRaw("
                    t.user_id,
                    bl.id as dimension_id,
                    CONCAT(u.first_name,' ',u.last_name) as usuario,
                    bl.title as dimension,
                    SUM(t.duration)/3600 as horas
                ")
                ->get();
        }

        $allDimensions = $raw->pluck('dimension')->filter()->unique()->values()->all();

        if (!empty($selectedItems)) {
            $dimensions = array_values(array_filter($allDimensions, function ($item) use ($selectedItems) {
                return in_array($item, $selectedItems, true);
            }));
        } else {
            $dimensions = $allDimensions;
        }

        $users = $raw->pluck('usuario')->filter()->unique()->values()->all();

        $map = [];
        foreach ($raw as $r) {
            $user = (string) $r->usuario;
            $dim  = (string) $r->dimension;
            $hrs  = (float) $r->horas;

            if (!empty($dimensions) && !in_array($dim, $dimensions, true)) {
                continue;
            }

            if (!isset($map[$user])) {
                $map[$user] = [];
            }

            $map[$user][$dim] = ($map[$user][$dim] ?? 0) + $hrs;
        }

        $matrixRows = [];
        foreach ($users as $user) {
            $row = [
                'usuario' => $user,
                'total'   => 0.0,
                'items'   => [],
            ];

            foreach ($dimensions as $dim) {
                $value = (float) ($map[$user][$dim] ?? 0.0);
                $row['items'][$dim] = $value;
                $row['total'] += $value;
            }

            if ($row['total'] > 0 || empty($selectedItems)) {
                $matrixRows[] = $row;
            }
        }

        usort($matrixRows, fn($a, $b) => ($b['total'] <=> $a['total']));

        /*
        |--------------------------------------------------------------------------
        | 2) DATASET DETALLE
        |--------------------------------------------------------------------------
        */
        $detailQuery = DB::table('tasks as ta')
            ->join('timers as t', 'ta.id', '=', 't.task_id')
            ->leftJoin('projects as p', 'ta.project_id', '=', 'p.id')
            ->leftJoin('board_lists as bl', 'ta.list_id', '=', 'bl.id')
            ->leftJoin('board_sublist as bs', 'ta.sublist_id', '=', 'bs.id')
            ->leftJoin('workspaces as w', 'p.workspace_id', '=', 'w.id')
            ->leftJoin('users as u', 't.user_id', '=', 'u.id')
            // ->where('ta.workspace_id', $workspaceId)
            ->whereNotNull('t.stopped_at')
            ->whereBetween(DB::raw('DATE(t.started_at)'), [$start->toDateString(), $end->toDateString()]);

        if ($dimensionType === 'label') {
            $detailQuery
                ->join('task_labels as tl_filter', 'ta.id', '=', 'tl_filter.task_id')
                ->join('labels as l_filter', 'tl_filter.label_id', '=', 'l_filter.id');

            if (!empty($selectedItems)) {
                $detailQuery->whereIn('l_filter.name', $selectedItems);
            }
        }

        if ($dimensionType === 'project' && !empty($selectedItems)) {
            $detailQuery->whereIn('p.title', $selectedItems);
        }

        if ($dimensionType === 'lane' && !empty($selectedItems)) {
            $detailQuery->whereIn('bl.title', $selectedItems);
        }

        $detailQuery
            ->leftJoin('task_labels as tl', 'ta.id', '=', 'tl.task_id')
            ->leftJoin('labels as l', 'tl.label_id', '=', 'l.id');

        $detailRows = $detailQuery
            ->groupBy(
                'ta.id',
                'w.name',
                'p.title',
                'ta.title',
                'ta.is_done',
                'ta.created_at',
                'u.first_name',
                'u.last_name',
                't.started_at',
                't.stopped_at',
                'bl.title',
                'bs.title'
            )
            ->selectRaw("
                ta.id as task_id,
                w.name as espacio,
                p.title as proyecto,
                ta.title as tarea,
                ta.is_done as terminada,
                ta.created_at as creacion,
                CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as realizada_por,
                t.started_at as inicio,
                t.stopped_at as fin,
                SUM(t.duration)/3600 as horas,
                bl.title as carril,
                bs.title as estatus,
                GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR '||') as etiquetas
            ")
            ->orderByDesc('t.started_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2.1) CONSTRUIR COLUMNAS DINÁMICAS DE ETIQUETAS
        |--------------------------------------------------------------------------
        */
        $allLabels = [];

        foreach ($detailRows as $row) {
            $labels = [];

            if (!empty($row->etiquetas)) {
                $labels = array_filter(array_map('trim', explode('||', $row->etiquetas)));
            }

            foreach ($labels as $label) {
                $allLabels[$label] = true;
            }
        }

        $labelColumns = array_keys($allLabels);
        sort($labelColumns, SORT_NATURAL | SORT_FLAG_CASE);

        /*
        |--------------------------------------------------------------------------
        | 3) GENERAR EXCEL
        |--------------------------------------------------------------------------
        */
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Resumen');

        $headers1 = array_merge(['Usuario'], $dimensions, ['Total']);

        foreach ($headers1 as $index => $header) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet1->setCellValue($col . '1', $header);
        }

        $rowNumber = 2;
        foreach ($matrixRows as $row) {
            $sheet1->setCellValue('A' . $rowNumber, $row['usuario']);

            $colIndex = 2;
            foreach ($dimensions as $dimension) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet1->setCellValue($col . $rowNumber, (float) ($row['items'][$dimension] ?? 0));
                $colIndex++;
            }

            $totalCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet1->setCellValue($totalCol . $rowNumber, (float) ($row['total'] ?? 0));

            $rowNumber++;
        }

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Detalle');

        $headers2Base = [
            'Espacio',
            'Proyecto',
            'Tarea',
            'Terminada',
            'Creación',
            'Realizada por',
            'Inicio',
            'Fin',
            'Horas',
            'Carril',
            'Estatus',
        ];

        $headers2 = array_merge($headers2Base, $labelColumns);

        foreach ($headers2 as $index => $header) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet2->setCellValue($col . '1', $header);
        }

        $rowNumber = 2;
        foreach ($detailRows as $row) {
            $taskLabels = [];

            if (!empty($row->etiquetas)) {
                $taskLabels = array_filter(array_map('trim', explode('||', $row->etiquetas)));
            }

            $taskLabelsMap = array_fill_keys($taskLabels, true);

            $sheet2->setCellValue('A' . $rowNumber, $row->espacio);
            $sheet2->setCellValue('B' . $rowNumber, $row->proyecto);
            $sheet2->setCellValue('C' . $rowNumber, $row->tarea);
            $sheet2->setCellValue('D' . $rowNumber, ((int) $row->terminada) ? 'Sí' : 'No');
            $sheet2->setCellValue('E' . $rowNumber, $row->creacion);
            $sheet2->setCellValue('F' . $rowNumber, $row->realizada_por);
            $sheet2->setCellValue('G' . $rowNumber, $row->inicio);
            $sheet2->setCellValue('H' . $rowNumber, $row->fin);
            $sheet2->setCellValue('I' . $rowNumber, (float) $row->horas);
            $sheet2->setCellValue('J' . $rowNumber, $row->carril);
            $sheet2->setCellValue('K' . $rowNumber, $row->estatus);

            $colIndex = count($headers2Base) + 1;
            foreach ($labelColumns as $labelName) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet2->setCellValue($col . $rowNumber, isset($taskLabelsMap[$labelName]) ? 'X' : '');
                $colIndex++;
            }

            $rowNumber++;
        }

        /*
        |--------------------------------------------------------------------------
        | 4) FORMATO BÁSICO
        |--------------------------------------------------------------------------
        */
        $lastCol1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers1));
        $lastCol2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers2));

        $sheet1->getStyle('A1:' . $lastCol1 . '1')->getFont()->setBold(true);
        $sheet2->getStyle('A1:' . $lastCol2 . '1')->getFont()->setBold(true);

        $sheet1->freezePane('A2');
        $sheet2->freezePane('A2');

        $sheet1->setAutoFilter('A1:' . $lastCol1 . '1');
        $sheet2->setAutoFilter('A1:' . $lastCol2 . '1');

        for ($i = 1; $i <= count($headers1); $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        for ($i = 1; $i <= count($headers2); $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $fileName = 'reporte_' . $dimensionType . '_' . $start->format('Ymd') . '_' . $end->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }    
}