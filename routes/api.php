<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkSpacesController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ListsController;
use App\Http\Controllers\TasksController;
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


Route::get('workspaces/all', [WorkSpacesController::class, 'allWorkSpace']);
Route::get('project/byworkspace/{id}', [ProjectsController::class, 'getProjectByWorkspace']);
Route::get('list/byproject/{id}', [ListsController::class, 'getBoarListByProject']);
Route::post('task/new', [TasksController::class, 'newTaskWithDetail']);