<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Events\CommentAdded;
use App\Models\Comment;
use App\Models\TaskNotification;

class CommentsController extends Controller
{
    public function saveNew(Request $request){
        $requestData = $request->all();
        $comment = Comment::create($requestData);
        $comment->load('user');
        return response()->json($comment);
    }

    public function update($id, Request $request){
        $comment = Comment::whereId($id)->first();
        $requestData = $request->all();
        foreach ($requestData as $itemKey => $itemValue){
            $comment->{$itemKey} = $itemValue;
        }
        $comment->save();
        return response()->json($comment);
    }

    public function deleteItem($id){
        $comment = Comment::whereId($id)->first();
        $comment->delete();
        return response()->json(['success' => true]);
    }

    public function readComment($id, Request $request){
        $user = Auth()->user();
        $user_id = Auth()->id();
        $requestData = $request->all();
        $payload = [
            'fromUser' => $user_id,
            'task' => $requestData['task_id'],
            'title' => "<p>{$user->name}<strong> ha leído tu comentario.</p></strong>",
            'toUser' => $requestData['toUser'],
            'wasRead' => false,
            'notification_type'=>3
        ];
        $notification = TaskNotification::create($payload);
        $notification->save();
           
        $comment = Comment::whereId($id)->first();
        
        $comment->was_read = $requestData['was_read'];
        $comment->save();
        return response()->json($user);
    }
}
