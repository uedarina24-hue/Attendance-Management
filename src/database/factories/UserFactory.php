<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        $faker = \Faker\Factory::create('ja_JP');

        return [
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'role' => 'user',
            'remember_token' => Str::random(10),
        ];
    }

    public function admin()
    {
        return $this->state([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }
}
