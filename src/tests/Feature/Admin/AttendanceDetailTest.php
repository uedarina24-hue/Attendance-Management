<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\Attendance;

class AttendanceDetailTest extends TestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected User $user;
    protected Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザー
        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // 一般ユーザー
        $this->user = User::factory()->create([
            'role' => 'user',
        ]);

        // 勤怠データ
        $this->attendance = Attendance::factory()
            ->for($this->user)
            ->finished()
            ->create([
                'date'         => '2025-12-01',
                'clock_in_at'  => '2025-12-01 09:00:00',
                'clock_out_at' => '2025-12-01 18:00:00',
            ]);
    }

    //勤怠詳細画面を表示できる
    public function test_admin_can_view_attendance_detail()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.show', [
                'attendance' => '2025-12-01',
                'user' => $this->user->id,
            ]))
            ->assertStatus(200);
    }

    //表示内容が選択した勤怠と一致する
    public function test_selected_attendance_data_is_displayed()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.show', [
                'attendance' => '2025-12-01',
                'user' => $this->user->id,
            ]))
            ->assertSee($this->user->name)
            ->assertSee('2025年12月1日')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    //出勤時間 > 退勤時間はエラー
    public function test_clock_in_after_clock_out_is_invalid()
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.attendance.update', '2025-12-01'), [
                'user'         => $this->user->id,
                'clock_in_at'  => '19:00',
                'clock_out_at' => '18:00',
                'remarks'      => '修正理由',
            ])
            ->assertSessionHasErrors([
                'clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です',
            ]);
    }

    //休憩開始が退勤後ならエラー
    public function test_break_start_after_clock_out_is_invalid()
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.attendance.update', '2025-12-01'), [
                'user'         => $this->user->id,
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

    //休憩終了が退勤後ならエラー
    public function test_break_end_after_clock_out_is_invalid()
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.attendance.update', '2025-12-01'), [
                'user'         => $this->user->id,
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

    //備考未入力はエラー
    public function test_remarks_is_required()
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.attendance.update', '2025-12-01'), [
                'user'         => $this->user->id,
                'clock_in_at'  => '09:00',
                'clock_out_at' => '18:00',
                'remarks'      => '',
            ])
            ->assertSessionHasErrors([
                'remarks' => '備考を記入してください',
            ]);
    }
}