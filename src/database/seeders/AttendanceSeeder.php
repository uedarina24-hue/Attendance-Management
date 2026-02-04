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

        $start = Carbon::now()->subMonths(4)->startOfMonth();
        $end   = Carbon::yesterday();

        foreach ($users as $user) {

            $period = new \DatePeriod(
                $start,
                new \DateInterval('P1D'),
                $end->copy()->addDay()
            );

            foreach ($period as $date) {
                $carbonDate = Carbon::instance($date);

                // 土日スキップ
                if ($carbonDate->isWeekend()) {
                    continue;
                }

                $clockIn  = $carbonDate->copy()->setTime(9, 0);
                $clockOut = $carbonDate->copy()->setTime(18, 0);

                /** 勤怠作成 */
                $attendance = Attendance::factory()
                    ->for($user)
                    ->finished()
                    ->create([
                        'date'         => $carbonDate->toDateString(),
                        'clock_in_at'  => $clockIn,
                        'clock_out_at' => $clockOut,
                        'remarks'      => null,
                    ]);

                /** 休憩作成 */
                BreakTime::factory()
                    ->for($attendance)
                    ->create([
                        'break_start_at' => $carbonDate->copy()->setTime(12, 0),
                        'break_end_at'   => $carbonDate->copy()->setTime(13, 0),
                    ]);
            }
        }

    }
}

