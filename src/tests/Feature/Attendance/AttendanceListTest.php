<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 12, 1));
        $this->user = User::factory()->create();
    }

    /** 自分が行った勤怠情報が全て表示されている */
    public function test_only_own_attendances_are_displayed_with_time_columns()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::now()->toDateString(),
            'clock_in_at' => '2025-12-01 09:00:00',
            'clock_out_at' => '2025-12-01 18:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.list'));

        $response->assertStatus(200);

        // 日付
        $response->assertSeeText($attendance->date->isoFormat('MM/DD(ddd)'));

        // 出社・退社
        $response->assertSeeText($attendance->raw_clock_in);
        $response->assertSeeText($attendance->raw_clock_out);

        // 休憩合計・勤怠合計
        $response->assertSeeText($attendance->total_break_time ?? '');
        $response->assertSeeText($attendance->total_working_time ?? '');
    }

    /** 勤怠一覧画面に遷移した際に現在の月が表示される */
    public function test_current_month_is_displayed()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSeeText(Carbon::now()->format('Y/m'));
    }

    /** 前月ボタンで前月が表示される */
    public function test_previous_month_is_displayed()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-11-10',
            'clock_in_at' => '2025-11-10 09:00:00',
            'clock_out_at' => '2025-11-10 18:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/attendance/list?month=2025-11');

        $response->assertStatus(200);
        $response->assertSeeText('2025/11');
        $response->assertSeeText('11/10');
    }

    /** 翌月ボタンで翌月が表示される */
    public function test_next_month_is_displayed()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-01-10',
            'clock_in_at' => '2026-01-10 09:00:00',
            'clock_out_at' => '2026-01-10 18:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/attendance/list?month=2026-01');

        $response->assertStatus(200);
        $response->assertSeeText('2026/01');
        $response->assertSeeText('01/10');
    }

    /** 詳細ボタンで勤怠詳細画面に遷移する */
    public function test_can_navigate_to_attendance_detail()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => today(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.list'));

        $response->assertSee(
            route('attendance.detail', $attendance)
        );

        $detailResponse = $this->actingAs($this->user)
            ->get(route('attendance.detail', $attendance));

        $detailResponse->assertStatus(200);
    }
}