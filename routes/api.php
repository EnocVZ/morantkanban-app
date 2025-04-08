<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkSpacesController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ListsController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\LabelsController;
use App\Http\Controllers\UsersController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('workspaces/all', [WorkSpacesController::class, 'allWorkSpace'])->middleware('auth.apidata');
Route::get('project/byworkspace/{id}', [ProjectsController::class, 'getProjectByWorkspace'])->middleware('auth.apidata');
Route::get('list/byproject/{id}', [ListsController::class, 'getBoarListByProject'])->middleware('auth.apidata');
Route::post('task/new', [TasksController::class, 'newTaskWithDetail'])->middleware('auth.apidata');
Route::get('label/all', [LabelsController::class, 'listLabels'])->middleware('auth.apidata');
Route::get('workspaces/getusers/{id}', [WorkSpacesController::class, 'getUserByWorkSpace'])->middleware('auth.apidata');
Route::get('user/gettoken', [UsersController::class, 'token'])->middleware('auth.basic');
