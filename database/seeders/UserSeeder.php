<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->truncate();
        User::factory()->createMany([
            ['email' => 'john.due.helo@mail.com', 'password' => 'secret', 'role_id' => 1],
            ['email' => 'sabbir@example.com', 'password' => 'secret', 'role_id' => 2]
        ]);
        User::factory(100)->create();
    }
}
