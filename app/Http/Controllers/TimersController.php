<?php

namespace App\Http\Controllers;

use App\Models\Timer;
use Carbon\Traits\Creator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
date_default_timezone_set('America/Mexico_City');

class TimersController extends Controller
{
    //

    public function stopTimer(Request $request){
        $requests = $request->all();
        $timer = Timer::whereId($requests['id'])->first();
        if ($timer) {
            $start = Carbon::parse($timer->started_at, 'America/Mexico_City');
            $now = Carbon::now('America/Mexico_City');
            $seconds = $now->diffInSeconds($start);

            $timer->duration = $seconds;
            $timer->stopped_at = new Carbon();
            $timer->save();
        }
        $duration = Timer::where('task_id', $timer->task_id)->sum('duration');
        return response()->json($duration);
    }

    public function startTimer(Request $request){
        $requests = $request->all();
        $existingTimer = Timer::mine()->running()->first();
        if ($existingTimer) {
            $start = Carbon::now('America/Mexico_City');
            $stopped = new Carbon();
            $existingTimer->duration = $stopped->diffInSeconds($start);
            $existingTimer->stopped_at = $stopped;
            $existingTimer->save();
        }
        $timer = Timer::create(['user_id' => auth()->id(), 'task_id' => $requests['task_id'], 'started_at' => new Carbon(), 'stopped_at' => null, 'duration' => 0 ]);
        $timer->load('task');
        return response()->json($timer);
    }

    public function getDuration($task_id){
        $duration = Timer::where('task_id', $task_id)->sum('duration');
        return response()->json($duration);
    }
}