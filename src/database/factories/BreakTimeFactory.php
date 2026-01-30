<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BreakTime;

class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition()
    {
        return [
            'attendance_id' => 1,
            'break_start_at' => now(),
            'break_end_at' => now()->addHour(),
        ];
    }
}
