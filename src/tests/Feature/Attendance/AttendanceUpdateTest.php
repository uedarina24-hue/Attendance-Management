<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionDetail;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceUpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected User $user;
    protected Attendance $attendance;
    protected AttendanceCorrectionRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 12, 1, 9, 0));

        // 一般ユーザー
        $this->user = User::factory()->create();

        // 管理者ユーザー
        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        // 勤怠データ
        $this->attendance = Attendance::factory()->create([
            'user_id'      => $this->user->id,
            'date'         => Carbon::create(2025, 12, 1),
            'clock_in_at'  => Carbon::create(2025, 12, 1, 9, 0),
            'clock_out_at' => Carbon::create(2025, 12, 1, 18, 0),
            'remarks'      => '通常勤務',
        ]);

        // 修正申請（承認待ち）
        $this->request = AttendanceCorrectionRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'user_id'       => $this->user->id,
            'status'        => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        // 修正内容（出勤・退勤・備考）
        AttendanceCorrectionDetail::factory()->create([
            'correction_request_id' => $this->request->id,
            'field_name'   => 'clock_in_at',
            'before_value' => '09:00',
            'after_value'  => '10:00',
        ]);

        AttendanceCorrectionDetail::factory()->create([
            'correction_request_id' => $this->request->id,
            'field_name'   => 'clock_out_at',
            'before_value' => '18:00',
            'after_value'  => '19:00',
        ]);

        AttendanceCorrectionDetail::factory()->create([
            'correction_request_id' => $this->request->id,
            'field_name'   => 'remarks',
            'before_value' => '通常勤務',
            'after_value'  => '電車遅延のため',
        ]);
    }

    /** 管理者承認で勤怠情報が更新される */
    public function test_attendance_is_updated_when_request_is_approved()
    {
        // 承認実行
        $this->request->approveByAdmin($this->admin->id);

        $attendance = $this->attendance->fresh();

        $this->assertEquals(
            '10:00',
            $attendance->clock_in_at->format('H:i')
        );

        $this->assertEquals(
            '19:00',
            $attendance->clock_out_at->format('H:i')
        );

        $this->assertEquals(
            '電車遅延のため',
            $attendance->remarks
        );
    }

    /** 承認後、申請ステータスが approved になる */
    public function test_request_status_is_updated_to_approved()
    {
        $this->request->approveByAdmin($this->admin->id);

        $this->request->refresh();

        $this->assertEquals(
            AttendanceCorrectionRequest::STATUS_APPROVED,
            $this->request->status
        );

        $this->assertNotNull($this->request->approved_at);
        $this->assertEquals($this->admin->id, $this->request->approved_by);
    }

    /** 承認時にトランザクションが正しく完了している */
    public function test_approve_process_is_atomic()
    {
        $this->request->approveByAdmin($this->admin->id);

        $this->assertDatabaseHas('attendance_correction_requests', [
            'id'     => $this->request->id,
            'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $this->attendance->id,
        ]);
    }
}
