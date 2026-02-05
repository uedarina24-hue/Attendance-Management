<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Carbon;

class AttendanceListTest extends TestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected $users;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 12, 1));

        // 管理者ユーザー
        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // 一般ユーザー
        $this->users = User::factory()
            ->count(2)
            ->create(['role' => 'user']);

        // 昨日・今日・明日の勤怠データ作成
        foreach (['yesterday', 'today', 'tomorrow'] as $day) {
            foreach ($this->users as $user) {
                $date = Carbon::$day();

                $attendance = Attendance::factory()->create([
                    'user_id'      => $user->id,
                    'date'         => $date->toDateString(),
                    'clock_in_at'  => $date->copy()->setTime(9, 0),
                    'clock_out_at' => $date->copy()->setTime(18, 0),
                ]);

                // 休憩（1時間）を必ず入れる
                $attendance->breakTimes()->create([
                    'break_start_at' => $date->copy()->setTime(12, 0),
                    'break_end_at'   => $date->copy()->setTime(13, 0),
                ]);
            }
        }
    }

    protected function getAttendanceResponse(?Carbon $date = null)
    {
        $url = '/admin/attendance';

        if ($date) {
            $url .= '?date=' . $date->format('Y-m-d');
        }

        return $this->actingAs($this->admin, 'admin')->get($url);
    }

    protected function assertAttendanceRow($response, Attendance $attendance)
    {
        $attendance->load('user');

        $response
            ->assertSeeText($attendance->user->name)

            // 出勤・退勤
            ->assertSeeText($attendance->raw_clock_in)
            ->assertSeeText($attendance->raw_clock_out)

            // 休憩合計・勤怠合計
            ->assertSeeText($attendance->total_break_time)
            ->assertSeeText($attendance->total_working_time);
    }

    /** 当日の勤怠一覧が正しく表示される */
    public function test_today_attendance_list_is_displayed_correctly()
    {
        $response = $this->getAttendanceResponse(Carbon::today());
        $response->assertStatus(200);
        $response->assertSeeText(Carbon::today()->format('Y/m/d'));

        foreach ($this->users as $user) {
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', Carbon::today())
                ->first();

            $this->assertAttendanceRow($response, $attendance);
        }
    }

    /** 前日の勤怠一覧が正しく表示される */
    public function test_previous_day_attendance_list_is_displayed_correctly()
    {
        $response = $this->getAttendanceResponse(Carbon::yesterday());
        $response->assertStatus(200);
        $response->assertSeeText(Carbon::yesterday()->format('Y/m/d'));

        foreach ($this->users as $user) {
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', Carbon::yesterday())
                ->first();

            $this->assertAttendanceRow($response, $attendance);
        }
    }

    /** 翌日の勤怠一覧が正しく表示される */
    public function test_next_day_attendance_list_is_displayed_correctly()
    {
        $response = $this->getAttendanceResponse(Carbon::tomorrow());
        $response->assertStatus(200);
        $response->assertSeeText(Carbon::tomorrow()->format('Y/m/d'));

        foreach ($this->users as $user) {
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', Carbon::tomorrow())
                ->first();

            $this->assertAttendanceRow($response, $attendance);
        }
    }
}

