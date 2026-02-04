<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use DatabaseMigrations;

    protected Attendance $attendance;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->attendance = Attendance::factory()
            ->for($this->user)
            ->finished()
            ->create([
                'date'         => '2025-12-01',
                'clock_in_at'  => '2025-12-01 09:00:00',
                'clock_out_at' => '2025-12-01 18:00:00',
            ]);
    }

    /** 他人の勤怠詳細にはアクセスできない */
    public function test_cannot_view_other_users_attendance_detail()
    {
        $otherUser = User::factory()->create();

        $attendance = Attendance::factory()
            ->for($otherUser)
            ->create();

        $this->actingAs($this->user)
            ->get(route('attendance.detail', $attendance))
            ->assertStatus(403);
    }

    /** 勤怠詳細画面に遷移できる */
    public function test_can_view_attendance_detail_page()
    {
        $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance))
            ->assertStatus(200);
    }

    /** 名前がログインユーザーの氏名になっている */
    public function test_user_name_is_displayed_on_detail_page()
    {
        $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance))
            ->assertSee($this->user->name);
    }

    /** 日付が選択した日付になっている */
    public function test_date_is_displayed_correctly()
    {
        $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance))
            ->assertSee('2025年12月1日');
    }

    /** 出勤・退勤時間が表示される */
    public function test_clock_in_and_out_time_are_displayed()
    {
        $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance))
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    /** 出勤時間が退勤時間より後ならエラー */
    public function test_clock_in_after_clock_out_is_invalid()
    {
        $response = $this->actingAs($this->user)
            ->post(route('attendance.update', $this->attendance), [
                'clock_in_at'  => '19:00',
                'clock_out_at' => '18:00',
                'remarks'      => '修正理由',
            ]);

        $response->assertSessionHasErrors(['clock_in_at']);

        $response->assertSessionHasErrors(['clock_in_at' => '出勤時間が不適切な値です',]);
    }

    /** 休憩開始が退勤時間より後ならエラー */
    public function test_break_start_after_clock_out_is_invalid()
    {
        $this->actingAs($this->user)
            ->post(route('attendance.update', $this->attendance), [
                'clock_in_at'  => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    ['start' => '18:30', 'end' => '18:45'],
                ],
                'remarks' => '修正理由',
            ])
            ->assertSessionHasErrors([
                'breaks.0.start' => '休憩時間が不適切な値です',
            ]);
    }

    /** 休憩終了が退勤時間より後ならエラー */
    public function test_break_end_after_clock_out_is_invalid()
    {
        $this->actingAs($this->user)
            ->post(route('attendance.update', $this->attendance), [
                'clock_in_at'  => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    ['start' => '17:30', 'end' => '18:30'],
                ],
                'remarks' => '修正理由',
            ])
            ->assertSessionHasErrors([
                'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です',
            ]);
    }

    /** 備考未入力はエラー */
    public function test_remarks_is_required()
    {
        $this->actingAs($this->user)
            ->post(route('attendance.update', $this->attendance), [
                'clock_in_at'  => '09:00',
                'clock_out_at' => '18:00',
                'remarks'      => '',
            ])
            ->assertSessionHasErrors([
                'remarks' => '備考を記入してください',
            ]);
    }

    /** 修正申請が正常に作成される */
    public function test_correction_request_is_created()
    {
        $this->actingAs($this->user)
            ->post(route('attendance.update', $this->attendance), [
                'clock_in_at'  => '10:00',
                'clock_out_at' => '19:00',
                'remarks'      => '電車遅延のため',
            ])
            ->assertRedirect(route('attendance.detail', $this->attendance));

        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_id' => $this->attendance->id,
            'user_id'       => $this->user->id,
            'status'        => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);
    }
}

