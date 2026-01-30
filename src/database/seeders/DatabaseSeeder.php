<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{

    public function run()
    {

        User::factory()
            ->admin()
            ->create([
                'email' => 'admin@example.com',
                'name' => '管理者',
                'password' => bcrypt('password'),
            ]);

        \App\Models\User::factory()
            ->count(5)
            ->state(['role' => 'user'])
            ->create();


        $this->call(AttendanceSeeder::class);


        $userUnverified = User::factory()->create([
            'name' => '山田 太郎',
            'email' => 'yamada.tarou@example.com',
            'email_verified_at' => null,
            'password' => bcrypt('password'),
        ]);
    }



}
