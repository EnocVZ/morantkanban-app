<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){

        DB::table('email_templates')->truncate();
        $logo = URL::to('/images/logo.png');
        $html = File::get(public_path('html/email_templates/adding_user_to_workspace.html'));
        EmailTemplate::factory()->create([
            'name' => 'Adding user to Workspace',
            'slug' => 'adding_user_to_workspace',
            'details' => 'When customer add user to a workspace',
            'language' => 'en',
            'html' => str_replace('https://res.cloudinary.com/robinbd/image/upload/v1700499886/codecanyon/pro-task/vtezvly6a8z8yxlky2qj.png', $logo, $html)
        ]);

        $html = File::get(public_path('html/email_templates/assign_to_a_task.html'));
        EmailTemplate::factory()->create([
            'name' => 'Assign to a task',
            'slug' => 'assign_to_a_task',
            'details' => 'When an user got assigned on a task',
            'language' => 'en',
            'html' => str_replace('https://res.cloudinary.com/robinbd/image/upload/v1700499886/codecanyon/pro-task/vtezvly6a8z8yxlky2qj.png', $logo, $html)
        ]);

        $html = File::get(public_path('html/email_templates/task_update.html'));
        EmailTemplate::factory()->create([
            'name' => 'Task update',
            'slug' => 'task_update',
            'details' => 'When a task has been updated.',
            'language' => 'en',
            'html' => str_replace('https://res.cloudinary.com/robinbd/image/upload/v1700499886/codecanyon/pro-task/vtezvly6a8z8yxlky2qj.png', $logo, $html)
        ]);

//        $html = File::get(public_path('html/email_templates/project_update.html'));
//        EmailTemplate::factory()->create([
//            'name' => 'Project update',
//            'slug' => 'project_update',
//            'details' => 'When a project has been updated.',
//            'language' => 'en',
//            'html' => str_replace('https://res.cloudinary.com/robinbd/image/upload/v1700499886/codecanyon/pro-task/vtezvly6a8z8yxlky2qj.png', $logo, $html)
//        ]);

        $html = File::get(public_path('html/email_templates/custom_mail.html'));
        EmailTemplate::factory()->create([
            'name' => 'General Mail',
            'slug' => 'custom_mail',
            'details' => 'It will use to send any email general purpose.',
            'language' => 'en',
            'html' => str_replace('https://res.cloudinary.com/robinbd/image/upload/v1700499886/codecanyon/pro-task/vtezvly6a8z8yxlky2qj.png', $logo, $html)
        ]);

        $html = File::get(public_path('html/email_templates/new_comment.html'));
        EmailTemplate::factory()->create([
            'name' => 'New comment',
            'slug' => 'new_comment',
            'details' => 'When a comment has been added on a task.',
            'language' => 'en',
            'html' => str_replace('https://res.cloudinary.com/robinbd/image/upload/v1700499886/codecanyon/pro-task/vtezvly6a8z8yxlky2qj.png', $logo, $html)
        ]);

//        $html = File::get(public_path('html/email_templates/user_created.html'));
//        EmailTemplate::factory()->create([
//            'name' => 'User created',
//            'slug' => 'user_created',
//            'details' => 'When a new user created.',
//            'language' => 'en',
//            'html' => str_replace('https://res.cloudinary.com/robinbd/image/upload/v1700499886/codecanyon/pro-task/vtezvly6a8z8yxlky2qj.png', $logo, $html)
//        ]);

    }
}
