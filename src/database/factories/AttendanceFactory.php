<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use App\Models\User;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        $date = today();

        return [
            'user_id'      => User::factory(),
            'date'         => $date,
            'clock_in_at'  => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
            'status'       => Attendance::STATUS_FINISHED,
            'remarks'      => null,
        ];
    }

    public function working()
    {
        return $this->state(fn () => [
            'clock_out_at' => null,
            'status' => Attendance::STATUS_WORKING,
        ]);
    }

    public function onBreak()
    {
        return $this->state(fn () => [
            'clock_out_at' => null,
            'status' => Attendance::STATUS_BREAK,
        ]);
    }

    public function finished()
    {
        return $this->state(fn () => [
            'status' => Attendance::STATUS_FINISHED,
        ]);
    }

    public function outside()
    {
        return $this->state(fn () => [
            'clock_in_at' => null,
            'clock_out_at' => null,
            'status' => Attendance::STATUS_OUTSIDE,
        ]);
    }
}
