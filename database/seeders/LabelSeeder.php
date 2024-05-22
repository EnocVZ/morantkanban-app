<?php

namespace Database\Seeders;

use App\Models\Label;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('labels')->truncate();
        Label::factory()->createMany([
            ['name' => 'Copy Request', 'color' => '#7366FF'],
            ['name' => 'One More Step', 'color' => '#F97316'],
            ['name' => 'Priority', 'color' => '#EF4444'],
            ['name' => 'Design Team', 'color' => '#8B5CF6'],
            ['name' => 'Product Marketing', 'color' => '#0099ff'],
            ['name' => 'Help', 'color' => '#17e885'],
            ['name' => 'Production', 'color' => '#3B82F6'],
        ]);
    }
}
