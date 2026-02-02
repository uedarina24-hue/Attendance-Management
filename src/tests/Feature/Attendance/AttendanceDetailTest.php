<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use DatabaseMigrations;
    protected User $user;
    protected Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 12, 1, 9, 0));

        $this->user = User::factory()->create([
            'name' => 'テスト太郎',
        ]);

        $this->attendance = Attendance::factory()->create([
            'user_id'      => $this->user->id,
            'date'         => Carbon::create(2025, 12, 1),
            'clock_in_at'  => Carbon::create(2025, 12, 1, 9, 0),
            'clock_out_at' => Carbon::create(2025, 12, 1, 18, 0),
            'remarks'      => '通常勤務',
        ]);
    }

    /** 名前がログインユーザーの氏名になっている */
    public function test_user_name_is_displayed_on_detail_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance));

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
    }

    /** 日付が選択した日付になっている */
    public function test_date_is_displayed_correctly()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance));

        $response->assertSee('2025-12-01');
    }

    /** 出勤・退勤時間が正しく表示される */
    public function test_clock_in_and_out_time_are_displayed()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance));

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** 出勤時間が退勤時間より後の場合はエラー */
    public function test_clock_in_after_clock_out_is_invalid()
    {
        $response = $this->actingAs($this->user)
            ->post(route('attendance.correction.store', $this->attendance), [
                'clock_in_at'  => '19:00',
                'clock_out_at' => '18:00',
                'remarks'      => '修正理由',
            ]);

        $response->assertSessionHasErrors([
            'clock_in_at' => '出勤時間が不適切な値です',
        ]);
    }

    /** 休憩開始が退勤時間より後の場合はエラー */
    public function test_break_start_after_clock_out_is_invalid()
    {
        $response = $this->actingAs($this->user)
            ->post(route('attendance.correction.store', $this->attendance), [
                'clock_in_at'  => '09:00',
                'clock_out_at' => '18:00',
                'break_start'  => '19:00',
                'break_end'    => '19:30',
                'remarks'      => '修正理由',
            ]);

        $response->assertSessionHasErrors([
            'break' => '休憩時間が不適切な値です',
        ]);
    }

    /** 備考未入力はエラー */
    public function test_remarks_is_required()
    {
        $response = $this->actingAs($this->user)
            ->post(route('attendance.correction.store', $this->attendance), [
                'clock_in_at'  => '09:00',
                'clock_out_at' => '18:00',
                'remarks'      => '',
            ]);

        $response->assertSessionHasErrors([
            'remarks' => '備考を記入してください',
        ]);
    }

    /** 修正申請が正常に作成される */
    public function test_correction_request_is_created()
    {
        $response = $this->actingAs($this->user)
            ->post(route('attendance.correction.store', $this->attendance), [
                'clock_in_at'  => '10:00',
                'clock_out_at' => '19:00',
                'remarks'      => '電車遅延のため',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_id' => $this->attendance->id,
            'user_id'       => $this->user->id,
            'status'        => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);
    }

    /** 申請一覧（承認待ち）に自分の申請が表示される */
    public function test_pending_requests_are_displayed()
    {
        AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'user_id'       => $this->user->id,
            'status'        => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.corrections.index'));

        $response->assertSee('承認待ち');
    }

    /** 申請詳細から勤怠詳細画面へ遷移できる */
    public function test_can_navigate_from_request_detail_to_attendance_detail()
    {
        $request = AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'user_id'       => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.corrections.show', $request));

        $response->assertSee(
            route('attendance.detail', $this->attendance)
        );
    }
}
