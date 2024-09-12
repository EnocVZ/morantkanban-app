<?php

namespace App\Http\Controllers;

use App\Models\TaskNotification;
use Carbon\Traits\Creator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TaskNotificationController extends Controller
{
    //

    public function saveNew(Request $request){
        $requests = $request->all();
        $users = $request['users'];
        unset($request['users']);
        foreach ($users as $key => $value) {
            $payload = [
                'fromUser' => $request['fromUser'],
                'task' => $request['task'],
                'title' => $request['title'],
                'toUser' => $value,
                'wasRead' => false
            ];
           $notification = TaskNotification::create($payload);
           $notification->save();
        }
        $response = ["succces" => true];
        return response()->json($response);
    }

    public function getNotificationByUser($auth_id){
        $notification = TaskNotification::where('toUser', $auth_id)->get()->toArray();
        return response()->json($notification);
    }
}
