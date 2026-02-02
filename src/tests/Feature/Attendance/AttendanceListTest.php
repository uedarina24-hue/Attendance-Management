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

    /** 自分の勤怠情報が全て表示される */
    public function test_only_own_attendances_are_displayed()
    {
        $otherUser = User::factory()->create();

        $myAttendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::now()->toDateString(),
            'clock_in_at' => now(),
        ]);

        $otherAttendance = Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => Carbon::now()->toDateString(),
            'clock_in_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.list'));

        $response->assertStatus(200);

        // 自分の勤怠は表示される
        $response->assertSee(
            $myAttendance->date->format('Y-m-d')
        );

        // 他人の勤怠は表示されない
        $response->assertDontSee(
            $otherAttendance->date->format('Y-m-d')
        );

    }

    /** 勤怠一覧画面に遷移した際に現在の月が表示される */
    public function test_current_month_is_displayed()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.list'));

        $response->assertStatus(200);

        // 表示月（YYYY/MM）
        $response->assertSee(
            Carbon::now()->format('Y/m')
        );
    }

    /** 前月ボタンで前月が表示される */
    public function test_previous_month_is_displayed()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::create(2025, 11, 10),
            'clock_in_at' => now(),
        ]);

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::create(2025, 12, 10),
            'clock_in_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.list', [
                'month' => '2025-11'
            ]));

        $response->assertStatus(200);

        // 表示月：2025年11月
        $response->assertSee('2025/11');

        // 11月は表示
        $response->assertSee('2025-11-10');

        // 12月は表示されない
        $response->assertDontSee('2025-12-10');

    }

    /** 翌月ボタンで翌月が表示される */
    public function test_next_month_is_displayed()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::create(2025, 12, 10),
            'clock_in_at' => now(),
        ]);

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::create(2026, 1, 10),
            'clock_in_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.list', [
                'month' => '2026-01'
            ]));

        $response->assertStatus(200);

        // 表示月：2026年1月
        $response->assertSee('2026/01');

        // 1月は表示
        $response->assertSee('2026-01-10');

        // 12月は表示されない
        $response->assertDontSee('2025-12-10');

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

        $detailResponse = $this->get(
            route('attendance.detail', $attendance)
        );

        $detailResponse->assertStatus(200);
    }


}
