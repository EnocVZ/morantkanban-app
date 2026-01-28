<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Task;
use App\Models\TaskTimeLife;
use Carbon\Carbon;

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
    //refactoriza la logica 
    public function handle()
    {
        TaskTimeLife::with('task')
            ->get()
            ->each(function ($taskTimeLife) {
                $taskTimeLife->task->each(function ($task) use ($taskTimeLife) {
                    if (!is_null($task->updatesublist_at)) {
                        $date = Carbon::parse($task->updatesublist_at);
                        $days = $date->diffInDays(Carbon::now());
                        if ($days > $taskTimeLife->expire_at) {
                            $this->archiveTaskExpiredSubcolumn($task);
                        }
                    }
                    
                });
            });
    }

    private function archiveTaskExpiredSubcolumn(Task $task)
    {
        $task->is_archive = 1;
        $task->updated_at = now();
        $task->save();
    }
}
