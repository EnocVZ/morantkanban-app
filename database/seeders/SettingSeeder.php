<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->truncate();
        DB::table('settings')->insert(['name' => 'Pro Task', 'slug' => 'app_name', 'type' => 'text', 'value' => 'Pro Task']);
        DB::table('settings')->insert(['name' => 'Enable Registration', 'slug' => 'enable_registration', 'type' => 'text', 'value' => '1']);
        DB::table('settings')->insert(['name' => 'Default Language', 'slug' => 'default_language', 'type' => 'text', 'value' => 'en']);
        DB::table('settings')->insert(['name' => 'Email Notifications', 'slug' => 'email_notifications', 'type' => 'json',
                'value' => json_encode([
                    ['name' => 'Adding user to Workspace', 'slug' => 'adding_user_to_workspace', 'value' => false],
                    ['name' => 'Assign to a task', 'slug' => 'assign_to_a_task', 'value' => false],
                    ['name' => 'Task update', 'slug' => 'task_update', 'value' => false],
                    ['name' => 'Project update', 'slug' => 'project_update', 'value' => false],
                    ['name' => 'New comment', 'slug' => 'new_comment', 'value' => false],
                ])
            ]);
    }
}
