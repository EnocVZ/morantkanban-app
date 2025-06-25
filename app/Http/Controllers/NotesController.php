<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Note;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use App\Helpers\MethodHelper;

class NotesController extends Controller {

    public function index(){
        $user_id = Auth()->id();
        return Inertia::render('Notes/Index', [
            'title' => 'Notes',
            'filters' => Request::all(['search']),
            'notes' => Note::byUser($user_id)
                ->orderBy('name')
                ->filter(Request::only('search'))
                ->paginate(15)
                ->withQueryString()
                ->through(function ($note) {
                    return [
                        'id' => $note->id,
                        'name' => $note->name,
                        'color' => $note->color,
                        'details' => $note->details,
                        'created_at' => $note->created_at,
                    ];
                } ),
        ]);
    }

    public function saveNote(){
        try {
            $noteFields = Request::validate([
                'project_id' => ['required'],
                'details' => ['required'],
                'color' => ['nullable']
            ]);
            $noteFields['user_id'] = Auth()->id();
            $note = Note::create($noteFields);
            return MethodHelper::successResponse($note);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }

    public function updateNote($id){
       try {
         $noteFields = Request::validate([
            'details' => ['required'],
            'color' => ['nullable']
        ]);
           $note = Note::where('id', $id)->update($noteFields);
            return MethodHelper::successResponse($note);
        } catch (\Exception $e) {
            return MethodHelper::errorResponse($e->getMessage());
        }
    }

    public function deleteNote($id){
        try {
            $note = Note::where('id', $id)->delete();
             return MethodHelper::successResponse();
         } catch (\Exception $e) {
             return MethodHelper::errorResponse($e->getMessage());
         }
     }

}