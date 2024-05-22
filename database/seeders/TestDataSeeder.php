<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class
        ]);

        $this->copyDemoFiles();

        $file_path = database_path('sql/pro_task.sql');
        \DB::unprepared(
            file_get_contents($file_path)
        );
    }

    private function copyDemoFiles()
    {
        array_filter(File::allFiles(public_path('files/tasks_demo')), function ($item) {
            $newPath = public_path('files/tasks/'.$item->getFilename());
            if(!File::exists($newPath)){
                File::copy(public_path('files/tasks_demo/'.$item->getFilename()), $newPath);
            }
        });
    }
}
