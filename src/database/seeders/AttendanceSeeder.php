<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{

    public function run()
    {
        $users = User::where('role', 'user')
                    ->whereNotNull('email_verified_at')
                    ->get();

        $start = Carbon::now()->subMonths(2)->startOfMonth();
        $end = Carbon::yesterday();

        foreach ($users as $user) {

            $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->copy()->addDay());

            foreach ($period as $date) {
                $carbonDate = Carbon::instance($date);

                if ($carbonDate->isWeekend()) {
                    continue;
                }

                $clockIn = Carbon::create($carbonDate->year, $carbonDate->month, $carbonDate->day, 9, 0);
                $clockOut = Carbon::create($carbonDate->year, $carbonDate->month, $carbonDate->day, 18, 0);

                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'date' => $carbonDate->format('Y-m-d'),
                    'clock_in_at' => $clockIn,
                    'clock_out_at' => $clockOut,
                    'status' => 'finished',
                    'remarks' => null,
                ]);

                BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => Carbon::create($carbonDate->year, $carbonDate->month, $carbonDate->day, 12, 0),
                    'break_end_at' => Carbon::create($carbonDate->year, $carbonDate->month, $carbonDate->day, 13, 0),
                ]);
            }
        }

    }
}

