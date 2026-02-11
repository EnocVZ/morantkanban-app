<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Models\Task;
use App\Models\TaskTimeLife;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;



class ArchiveTaskExpiredSubcolumn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:archive-task-expired-subcolumn';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
       
        try {
                $dateNow = now();
                $updatedRows = Task::whereExists(function ($query) use ($dateNow) {
                    $query->select(DB::raw(1))
                        ->from('subcolumn_tasktimelife')
                        // Unimos por la columna que relaciona ambas tablas
                        ->whereColumn('subcolumn_tasktimelife.subcolumn_id', 'tasks.sublist_id')
                        ->whereNotNull('subcolumn_tasktimelife.expire_at')
                        // Filtramos: Si la diferencia de días entre HOY y la actualización es mayor al límite
                        ->whereRaw('DATEDIFF(?, tasks.updatesublist_at) > subcolumn_tasktimelife.expire_at', [$dateNow])
                        ->where('tasks.is_archive', 0); // Solo consideramos tareas no archivadas
                })
                ->update([
                    'is_archive' => 1,
                    'updated_at' => $dateNow
                ]);
                //Log::info("Tareas expiradas archivadas: " . $updatedRows);
        } catch (\Exception $e) {
                Log::error('Error al archivar tareas expiradas: ' . $e->getMessage());
        }
           
    }
}
