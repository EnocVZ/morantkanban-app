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
    public function handle()
    {
        TaskTimeLife::with('task')
            ->chunk(100, function ($taskTimeLifes) {
                foreach ($taskTimeLifes as $taskTimeLife) {
                    $task = $taskTimeLife->task;
                    
                    if ($task && !is_null($task->updatesublist_at)) {
                        $days = Carbon::parse($task->updatesublist_at)->diffInDays(now());
                        
                        if ($days > $taskTimeLife->expire_at) {
                            $this->archiveTaskExpiredSubcolumn($task);
                        }
                    }
                }
            });
    }

    private function archiveTaskExpiredSubcolumn(Task $task)
    {
        $task->is_archive = 1;
        $task->updated_at = now();
        $task->save();
    }
}
