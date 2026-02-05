<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;

class StaffListTest extends TestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 12, 15));

        // 管理者
        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // 一般ユーザー
        $this->user1 = User::factory()->create([
            'name'  => '山田太郎',
            'email' => 'taro@example.com',
            'role'  => 'user',
        ]);

        $this->user2 = User::factory()->create([
            'name'  => '佐藤花子',
            'email' => 'hanako@example.com',
            'role'  => 'user',
        ]);
    }

    //管理者は全一般ユーザーの氏名・メールアドレスを確認できる
    public function test_admin_can_view_all_staff_names_and_emails()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.staff.list'));

        $response->assertStatus(200);

        $response->assertSee('山田太郎');
        $response->assertSee('taro@example.com');

        $response->assertSee('佐藤花子');
        $response->assertSee('hanako@example.com');
    }

    //選択したユーザーの勤怠一覧が表示される
    public function test_selected_staff_attendance_is_displayed()
    {
        Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'date' => '2025-12-10',
            'clock_in_at' => '2025-12-10 09:00:00',
            'clock_out_at' => '2025-12-10 18:00:00',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.staff', $this->user1));

        $response->assertStatus(200);

        // 日付と打刻が表示される
        $response->assertSeeText('12/10');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
    }

    //前月ボタンで前月の勤怠が表示される
    public function test_previous_month_is_displayed()
    {
        Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'date' => '2025-11-10',
            'clock_in_at' => '2025-11-10 09:00:00',
            'clock_out_at' => '2025-11-10 18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'date' => '2025-12-10',
            'clock_in_at' => '2025-12-10 09:00:00',
            'clock_out_at' => '2025-12-10 18:00:00',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.staff', [
                'staff' => $this->user1->id,
                'month' => '2025-11',
            ]));

        $response->assertStatus(200);

        $response->assertSeeText('2025/11');
        $response->assertSeeText('11/10');
        $response->assertDontSeeText('12/10');
    }

    //翌月ボタンで翌月の勤怠が表示される
    public function test_next_month_is_displayed()
    {
        Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'date' => '2026-01-10',
            'clock_in_at' => '2026-01-10 09:00:00',
            'clock_out_at' => '2026-01-10 18:00:00',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.staff', [
                'staff' => $this->user1->id,
                'month' => '2026-01',
            ]));

        $response->assertStatus(200);

        $response->assertSeeText('2026/01');
        $response->assertSeeText('01/10');
    }

    //詳細ボタンから勤怠詳細画面に遷移できる
    public function test_can_navigate_to_attendance_detail()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user1->id,
            'date' => '2025-12-10',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.staff', $this->user1));

        $response->assertSee(
            route('admin.attendance.show', [
                'attendance' => '2025-12-10',
                'user' => $this->user1->id,
            ])
        );

        $detailResponse = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.attendance.show', [
                'attendance' => '2025-12-10',
                'user' => $this->user1->id,
            ]));

        $detailResponse->assertStatus(200);
    }
}
