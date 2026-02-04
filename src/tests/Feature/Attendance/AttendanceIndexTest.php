<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceIndexTest extends TestCase
{
    use DatabaseMigrations;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 時刻を固定（日時表示テストのため）
        Carbon::setTestNow(Carbon::create(2025, 1, 15, 9, 0, 0));

        $this->user = User::factory()->create();
    }

    //日時取得機能のテスト
    public function test_current_datetime_is_displayed()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);

        $response->assertSee('2025年1月15日');
        $response->assertSee('09:00');
    }


    //ステータス表示のテスト（勤務外）
    public function test_status_is_off_duty()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertSee('勤務外');
    }


    //ステータス表示のテスト（勤務中）
    public function test_status_is_working()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => today(),
            'clock_in_at' => now(),
            'status' => Attendance::STATUS_WORKING,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertSee('出勤中');
    }

    //ステータス表示のテスト（休憩中）
    public function test_status_is_break()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => today(),
            'clock_in_at' => now(),
            'status' => Attendance::STATUS_BREAK,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertSee('休憩中');
    }

    //ステータス表示のテスト（退勤済）
    public function test_status_is_finished()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => today(),
            'clock_in_at' => now(),
            'clock_out_at' => now(),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertSee('退勤済');
    }

    //出勤ボタンが表示される（勤務外）
    public function test_clock_in_button_is_visible_when_off_duty()
    {
        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertSee('出勤');
    }

    //出勤は一日一回のみ
    public function test_cannot_clock_in_twice_in_a_day()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => today(),
            'clock_in_at' => now(),
            'status' => Attendance::STATUS_WORKING,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('attendance.clock_in'));

        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHas('error');
    }


    //出勤からステータスが勤務中になる
    public function test_clock_in_changes_status_to_working()
    {
        $this->actingAs($this->user)
            ->post(route('attendance.clock_in'));

        $response = $this->get(route('attendance.index'));

        $response->assertSee('出勤中');
    }

    //休憩入ボタンが表示される（出勤中）
    public function test_break_start_button_is_visible_when_working()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => today(),
            'clock_in_at' => now(),
            'status' => Attendance::STATUS_WORKING,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertSee('休憩入');
    }
    //休憩入からステータスが「休憩中」になる
    public function test_break_start_changes_status_to_break()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => today(),
            'clock_in_at' => now(),
            'status' => Attendance::STATUS_WORKING,
        ]);

        $this->actingAs($this->user)
            ->post(route('attendance.break_start'));

        $response = $this->get(route('attendance.index'));

        $response->assertSee('休憩中');
    }

    //休憩戻ボタンが表示される（休憩中）
    public function test_break_end_button_is_visible_when_breaking()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => today(),
            'clock_in_at' => now(),
            'status' => Attendance::STATUS_BREAK,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertSee('休憩戻');
    }

    //休憩戻からステータスが「出勤中」になる
    public function test_break_end_changes_status_to_working()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => today(),
            'clock_in_at' => now(),
            'status' => Attendance::STATUS_BREAK,
        ]);

        // 休憩中データを作る（break_end_at が null）
        $attendance->breakTimes()->create([
            'break_start_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->post(route('attendance.break_end'));

        $response = $this->get(route('attendance.index'));

        $response->assertSee('出勤中');
    }

    //休憩は一日に何回でもできる
    public function test_break_can_be_taken_multiple_times()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => today(),
            'clock_in_at' => now(),
            'status' => Attendance::STATUS_WORKING,
        ]);

        // 1回目
        $this->actingAs($this->user)->post(route('attendance.break_start'));
        $this->actingAs($this->user)->post(route('attendance.break_end'));

        // 2回目
        $this->actingAs($this->user)->post(route('attendance.break_start'));
        $response = $this->get(route('attendance.index'));

        $response->assertSee('休憩中');
    }

     //退勤済は出勤ボタンが表示されない
    public function test_clock_in_button_is_not_visible_when_finished()
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => today(),
            'status' => Attendance::STATUS_FINISHED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('attendance.index'));

        $response->assertDontSeeText('出勤');
    }

}

