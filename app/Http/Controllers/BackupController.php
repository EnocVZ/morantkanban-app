<?php

namespace App\Http\Controllers;

use App\Events\CommentAdded;
use App\Models\CheckList;
use App\Models\Comment;
use App\Models\Faq;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskLabel;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller {
    public function restore(){
//        if(!Schema::hasColumn('users', 'role_id')){
//            $this->role();
//        }

//        Artisan::call('optimize');
//        Artisan::call('cache:clear');
//        Artisan::call('route:cache');
//        Artisan::call('view:clear');
//        Artisan::call('config:cache');
//        Artisan::call('clear-compiled');


//        $role = Role::whereSlug('admin')->first();
//        $user = User::whereEmail('sapradeep123@gmail.com')->first();
//        if(!empty($user) && !empty($role)){
//            $user->role_id = $role->id;
//            $user->save();
//        }
        $tickets = Ticket::limit(100)->get();
        dd($tickets);
    }

    private function role(){
        $users = User::pluck('role', 'id')->all();
        Artisan::call('migrate');
        $this->import_sql('role');
        $userRoles = Role::pluck('id', 'slug')->all();
        foreach ($users as $k => $v){
            User::where('id', $k)->update(['role_id' => $userRoles[$v]]);
        }
    }

    private function import_sql($file) {
        $sql_path = base_path('database/backup/'.$file.'.sql');
        DB::unprepared(file_get_contents($sql_path));
    }

    public function test(){
        dd('ok');
    }
}
