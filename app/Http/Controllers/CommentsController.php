<?php

namespace App\Http\Controllers;

use App\Events\CommentAdded;
use App\Models\Comment;
use Illuminate\Http\Request;

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
}
