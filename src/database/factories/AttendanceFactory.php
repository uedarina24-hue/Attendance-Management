<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        return [
            'user_id' => 1,
            'date' => now()->format('Y-m-d'),
            'clock_in_at' => now(),
            'clock_out_at' => now()->addHours(8),
            'status' => 'finished',
            'remarks' => null,
        ];
    }
}
