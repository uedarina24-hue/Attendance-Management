<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 12, 1));

        $this->user = User::factory()->create();

        $this->actingAs($this->user);
    }

    /** 自分の勤怠のみ表示され、他人の勤怠は表示されない */
    public function test_only_own_attendances_are_displayed()
    {

        $myAttendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-12-01',
            'clock_in_at' => '2025-12-01 09:00:00',
            'clock_out_at' => '2025-12-01 18:00:00',
        ]);


        $otherUser = User::factory()->create();
        $otherAttendance = Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2025-12-01',
        ]);

        $response = $this->get(route('attendance.list'));

        $response->assertStatus(200);


        $response->assertSeeText('12/01');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');


        $response->assertDontSeeText($otherUser->name ?? '');
    }

    /** 初期表示で現在月が表示される */
    public function test_current_month_is_displayed()
    {
        $response = $this->get(route('attendance.list'));

        $response->assertStatus(200);
        $response->assertSeeText('2025/12');
    }

    /** 前月表示で前月のデータのみ表示される */
    public function test_previous_month_is_displayed()
    {

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-11-10',
            'clock_in_at' => '2025-11-10 09:00:00',
            'clock_out_at' => '2025-11-10 18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-12-10',
        ]);

        $response = $this->get(route('attendance.list', [
            'month' => '2025-11'
        ]));

        $response->assertStatus(200);
        $response->assertSeeText('2025/11');
        $response->assertSeeText('11/10');

        $response->assertDontSeeText('12/10');
    }

    /** 翌月表示で翌月のデータのみ表示される */
    public function test_next_month_is_displayed()
    {

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2026-01-10',
            'clock_in_at' => '2026-01-10 09:00:00',
            'clock_out_at' => '2026-01-10 18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-12-10',
        ]);

        $response = $this->get(route('attendance.list', [
            'month' => '2026-01'
        ]));

        $response->assertStatus(200);
        $response->assertSeeText('2026/01');
        $response->assertSeeText('01/10');

        $response->assertDontSeeText('12/10');
    }

    /** 一覧から詳細画面へのリンクが存在する */
    public function test_detail_link_exists_on_list_page()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => '2025-12-01',
        ]);

        $response = $this->get(route('attendance.list'));

        $response->assertSee(
            route('attendance.detail', $attendance),
            false
        );
    }
}