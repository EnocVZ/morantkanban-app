<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Models\Task;
use App\Models\BoardSublist;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;



class MarkTaskDone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-task-done';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark tasks as done if they are enabled in the column';

    /**
     * Execute the console command.
     */
    public function handle()
    {
       
        try {
                $dateNow = now();
                $boardSublist = BoardSublist::where('mark_completed_task', 1)->pluck('id')->toArray();
                $updatedRows = Task::whereIn('sublist_id', $boardSublist)
                ->where('is_done', 0)
                ->update(['is_done' => 1, 'updated_at' => $dateNow]);
                //Log::info("Tareas expiradas archivadas: " . $updatedRows);
        } catch (\Exception $e) {
                Log::error('Error al archivar tareas expiradas: ' . $e->getMessage());
        }
           
    }
}
