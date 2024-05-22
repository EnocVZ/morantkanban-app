<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackgroundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('backgrounds')->truncate();
        DB::table('backgrounds')->insert([ 'id' => 1, 'bg' => '#228cd5', 'image' => '/images/gradients/1.svg', 'top' => '#08479e', 'side' => '#0a52b7e6']);
        DB::table('backgrounds')->insert([ 'id' => 2, 'bg' => '#0b50af', 'image' => '/images/gradients/2.svg', 'top' => '#08479e', 'side' => '#0a52b7e6']);
        DB::table('backgrounds')->insert([ 'id' => 3, 'bg' => '#674284', 'image' => '/images/gradients/3.svg', 'top' => '#072754', 'side' => '#09326ce6']);
        DB::table('backgrounds')->insert([ 'id' => 4, 'bg' => '#a869c1', 'image' => '/images/gradients/4.svg', 'top' => '#473699', 'side' => '#4f3dace6']);
        DB::table('backgrounds')->insert([ 'id' => 5, 'bg' => '#ef763a', 'image' => '/images/gradients/5.svg', 'top' => '#872013', 'side' => '#971a67e6']);
        DB::table('backgrounds')->insert([ 'id' => 6, 'bg' => '#f488a6', 'image' => '/images/gradients/6.svg', 'top' => '#821659', 'side' => '#971a67e6']);
        DB::table('backgrounds')->insert([ 'id' => 7, 'bg' => '#3fa495', 'image' => '/images/gradients/7.svg', 'top' => '#14553a', 'side' => '#196a48e6']);
        DB::table('backgrounds')->insert([ 'id' => 8, 'bg' => '#374866', 'image' => '/images/gradients/8.svg', 'top' => '#46536a', 'side' => '#505f79e6']);
        DB::table('backgrounds')->insert([ 'id' => 9, 'bg' => '#762a14', 'image' => '/images/gradients/9.svg', 'top' => '#2e1c0a', 'side' => '#43290fe6']);
        DB::table('backgrounds')->insert([ 'id' => 10, 'bg' => '#624b66', 'image' => '/images/gradients/10.jpg', 'top' => '#624b66', 'side' => '#705675e6']);
    }
}
