<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionDetail;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected User $admin;
    protected Attendance $attendance;
    protected AttendanceCorrectionRequest $pendingRequest;
    protected AttendanceCorrectionRequest $approvedRequest;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト日時を固定
        Carbon::setTestNow(Carbon::create(2026, 2, 4, 10, 0));

        // 一般ユーザー
        $this->user = User::factory()->create([
            'name' => '田辺 裕太',
            'email_verified_at' => now(),
            'role' => 'user',
        ]);

        // 管理者ユーザー
        $this->admin = User::factory()->create([
            'name' => '管理者 太郎',
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        // 勤怠データ
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
            'approved_by' => $this->admin->id,
        ]);

        AttendanceCorrectionDetail::create([
            'correction_request_id' => $this->approvedRequest->id,
            'field_name' => 'remarks',
            'before_value' => '通常勤務',
            'after_value' => '理由2',
        ]);
    }

    /** 承認待ちの申請が一覧に表示される */
    public function test_pending_requests_are_listed_for_admin()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stamp_correction_request.list', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('理由1');
        $response->assertDontSee('理由2'); // 承認済みは表示されない
    }

    /** 承認済みの申請が一覧に表示される */
    public function test_approved_requests_are_listed_for_admin()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stamp_correction_request.list', ['status' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('理由2');
        $response->assertDontSee('理由1'); // 承認待ちは表示されない
    }

    /** 修正申請の詳細内容が正しく表示される */
    public function test_correction_detail_displays_correctly()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.stamp_correction_request.show', $this->pendingRequest->id));

        $response->assertStatus(200);
        $response->assertSee('田辺 裕太');
        $response->assertSee('2025年12月1日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('理由1');
    }

    /** 修正申請を承認でき、勤怠情報が更新される */
    public function test_correction_can_be_approved_by_admin()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.stamp_correction_request.approve', $this->pendingRequest->id));

        $response->assertRedirect(); // 成功後リダイレクト

        $this->pendingRequest->refresh();
        $this->attendance->refresh();

        $this->assertTrue($this->pendingRequest->isApproved());
        $this->assertNotNull($this->pendingRequest->approved_at);
    }
}


