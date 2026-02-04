<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use DatabaseMigrations;

    protected $admin;
    protected $users;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザー
        $this->admin = User::factory()->create(['role' => 'admin']);

        // 一般ユーザー
        $this->users = User::factory()->count(2)->create(['role' => 'user']);

        // 勤怠データ作成
        foreach (['yesterday', 'today', 'tomorrow'] as $day) {
            foreach ($this->users as $user) {
                $date = Carbon::$day();
                $clockIn = match($day) {
                    'yesterday' => Carbon::$day()->setTime(8, 30),
                    'today'     => Carbon::$day()->setTime(9, 0),
                    'tomorrow'  => Carbon::$day()->setTime(10, 0),
                };
                $clockOut = match($day) {
                    'yesterday' => Carbon::$day()->setTime(17, 30),
                    'today'     => Carbon::$day()->setTime(18, 0),
                    'tomorrow'  => Carbon::$day()->setTime(19, 0),
                };

                Attendance::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'clock_in_at' => $clockIn,
                    'clock_out_at' => $clockOut,
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
        // 休憩・合計時間はモデルのアクセサを参照する
        $totalBreak = $attendance->totalBreakTime ?? '';
        $totalWork  = $attendance->total_working_time ?? '';

        $response->assertSeeText($attendance->user->name)
            ->assertSeeText($attendance->clock_in_at->format('H:i'))
            ->assertSeeText($attendance->clock_out_at->format('H:i'))
            ->assertSeeText($totalBreak)
            ->assertSeeText($totalWork);
    }

    // 当日の勤怠一覧表示テスト
    public function test_today_attendance_list_shows_correct_data()
    {
        $response = $this->getAttendanceResponse(Carbon::today());
        $response->assertStatus(200);

        foreach ($this->users as $user) {
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', Carbon::today())
                ->first();
            $attendance->load('user');
            $this->assertAttendanceRow($response, $attendance);
        }

        $response->assertSeeText(Carbon::today()->format('Y/m/d'));
    }

    // 前日の勤怠一覧表示テスト
    public function test_previous_day_attendance_list_shows_correct_data()
    {
        $response = $this->getAttendanceResponse(Carbon::yesterday());
        $response->assertStatus(200);

        foreach ($this->users as $user) {
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', Carbon::yesterday())
                ->first();
            $attendance->load('user');
            $this->assertAttendanceRow($response, $attendance);
        }

        $response->assertSeeText(Carbon::yesterday()->format('Y/m/d'));
    }

    //翌日の勤怠一覧表示テスト
    public function test_next_day_attendance_list_shows_correct_data()
    {
        $response = $this->getAttendanceResponse(Carbon::tomorrow());
        $response->assertStatus(200);

        foreach ($this->users as $user) {
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', Carbon::tomorrow())
                ->first();
            $attendance->load('user');
            $this->assertAttendanceRow($response, $attendance);
        }

        $response->assertSeeText(Carbon::tomorrow()->format('Y/m/d'));
    }
}


