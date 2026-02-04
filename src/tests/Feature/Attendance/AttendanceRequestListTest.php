<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionDetail;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceRequestListTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Attendance $attendance;
    protected AttendanceCorrectionRequest $pendingRequest;
    protected AttendanceCorrectionRequest $approvedRequest;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト日時を固定
        Carbon::setTestNow(Carbon::create(2026, 2, 4, 10, 0));

        // メール認証済みユーザー作成
        $this->user = User::factory()->create([
            'name' => '田辺 裕太',
            'email_verified_at' => now(),
        ]);

        // 勤怠データ作成
        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-12-01',
            'clock_in_at' => '2025-12-01 09:00:00',
            'clock_out_at' => '2025-12-01 18:00:00',
        ]);

        // 承認待ち申請
        $this->pendingRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        AttendanceCorrectionDetail::create([
            'correction_request_id' => $this->pendingRequest->id,
            'field_name' => 'remarks',
            'before_value' => '通常勤務',
            'after_value' => '理由1',
        ]);

        // 承認済み申請
        $this->approvedRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $this->user->id, // 管理者代用
        ]);

        AttendanceCorrectionDetail::create([
            'correction_request_id' => $this->approvedRequest->id,
            'field_name' => 'remarks',
            'before_value' => '通常勤務',
            'after_value' => '理由2',
        ]);
    }

    /** 自分の承認待ち申請が一覧に表示される */
    public function test_pending_requests_are_displayed()
    {
        $response = $this->actingAs($this->user)
            ->get(route('stamp_correction_request.list', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('理由1');
        $response->assertDontSee('理由2'); // 承認済みは表示されない
    }

    /** 自分の承認済み申請が一覧に表示される */
    public function test_approved_requests_are_displayed()
    {
        $response = $this->actingAs($this->user)
            ->get(route('stamp_correction_request.list', ['status' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('理由2');
        $response->assertDontSee('理由1'); // 承認待ちは表示されない
    }

    /** 他人の申請は表示されない */
    public function test_other_users_requests_are_not_displayed()
    {
        // 他ユーザー作成
        $otherUser = User::factory()->create([
            'name' => '他ユーザー 太郎',
            'email_verified_at' => now(),
        ]);

        $otherAttendance = Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2025-12-01',
        ]);

        $otherRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $otherAttendance->id,
            'user_id' => $otherUser->id,
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        AttendanceCorrectionDetail::create([
            'correction_request_id' => $otherRequest->id,
            'field_name' => 'remarks',
            'before_value' => '通常勤務',
            'after_value' => '他ユーザー理由',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('stamp_correction_request.list', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertDontSee('他ユーザー理由');
    }

    /** 申請一覧から勤怠詳細画面にアクセスできる */
    public function test_can_access_attendance_detail_from_request_list()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.detail', $this->attendance));

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}
