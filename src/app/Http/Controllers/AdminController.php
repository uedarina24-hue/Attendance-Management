<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequest as ACR;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\AdminCorrectionRequestForm;
use Carbon\Carbon;
use Carbon\CarbonPeriod;



class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:admin'])->except(['loginForm','login']);
    }

    // 管理者ログイン
    public function loginForm()
    {
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request)
    {
        $request->session()->regenerate();
        return redirect()->route('admin.attendance.list');
    }

    // 管理者ログアウト
    public function logout()
    {
        Auth::guard('admin')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    // 管理者：勤怠一覧
    public function attendanceIndex(Request $request)
    {
        $currentDate = Carbon::parse($request->get('date', now()));
        $users = User::where('role', 'user')->get();

        $attendances = Attendance::with('breakTimes')
            ->whereDate('date', $currentDate)
            ->get()
            ->keyBy('user_id');

        $rows = $users->map(fn($user) => [
            'user' => $user,
            'attendance' => $attendances->get($user->id),
        ]);

        return view('admin.attendance.list', [
            'rows' => $rows,
            'currentDate' => $currentDate,
            'prevDate' => $currentDate->copy()->subDay(),
            'nextDate' => $currentDate->copy()->addDay(),
        ]);
    }

    // 詳細画面
    public function attendanceShow(Request $request, $attendance)
    {
        $request->validate([
            'user' => ['required', 'exists:users,id'],
        ]);

        $date = Carbon::parse($attendance)->startOfDay();

        // Attendance は存在しなくても表示用に生成
        $attendance = Attendance::firstOrNew([
            'user_id' => $request->user,
            'date'    => $date->toDateString(),
        ]);

        $attendance->load(['user', 'breakTimes', 'correctionRequests.details']);

        // 未来日は入力不可
        $attendance->setAttribute('can_edit', !$date->isFuture());

        return view('admin.attendance.detail', compact('attendance'));
    }

    // 修正
    public function attendanceUpdate(
        AdminCorrectionRequestForm $request,
        $attendance)
    {

        $userId = $request->user;

        // もし $attendance がモデルでなければ生成（未来日など）
        if (!($attendance instanceof Attendance)) {
            $date = Carbon::parse($attendance)->startOfDay();

            $attendance = Attendance::firstOrNew([
                'user_id' => $userId,
                'date' => $date->toDateString(),
            ]);
        }

        // 未来日は修正不可
        if ($attendance->date->isFuture()) {
            return back()->withErrors([
                'attendance' => '未来日の勤怠は修正できません。',
            ]);
        }

        if (!$attendance->exists) {
            $attendance->save();
        }

        // 修正申請
        $attendance->submitCorrection($request->validated(), auth('admin')->id());

        return redirect()->route('admin.attendance.show', [
            'attendance' => $attendance->date->format('Y-m-d'),
            'user' => $userId]);
    }

    // スタッフ一覧
    public function staffList()
    {
        $staffs = User::where('role', 'user')->get();
        return view('admin.staff.list', compact('staffs'));
    }
    // スタッフ月次勤怠
    public function attendanceStaff(User $staff, Request $request)
    {
        if ($request->filled('month')) {
        $currentDate = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        } elseif ($request->filled('date')) {
        $currentDate = Carbon::parse($request->date)->startOfMonth();
        } else {
        $currentDate = Carbon::now()->startOfMonth();
        }

        $currentMonth = $currentDate->format('Y/m');
        $prevMonth    = $currentDate->copy()->subMonth()->format('Y-m');
        $nextMonth    = $currentDate->copy()->addMonth()->format('Y-m');

        $attendanceData = Attendance::where('user_id', $staff->id)
            ->whereBetween('date', [
                $currentDate->copy()->startOfMonth(),
                $currentDate->copy()->endOfMonth(),
            ])
            ->with('breakTimes')
            ->get()
            ->keyBy(fn ($a) => $a->date->format('Y-m-d'));


        $period = CarbonPeriod::create(
            $currentDate->copy()->startOfMonth(),
            $currentDate->copy()->endOfMonth()
        );

        $attendances = collect();

        foreach ($period as $day) {
            $attendances->push(
                $attendanceData[$day->format('Y-m-d')]
                ?? new Attendance([
                    'user_id' => $staff->id,
                    'date'    => $day->copy(),
                ])
            );
        }

        return view('admin.attendance.staff', compact(
            'staff',
            'attendances',
            'currentDate',
            'currentMonth',
            'prevMonth',
            'nextMonth'
        ));
    }
    // CSV処理
    public function attendanceExport(Request $request, User $staff)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $currentDate = Carbon::createFromFormat('Y-m', $month);
        $monthForDisplay = $currentDate->format('Y/m');

        $attendanceData = Attendance::where('user_id', $staff->id)
            ->whereBetween('date', [$currentDate->copy()->startOfMonth(), $currentDate->copy()->endOfMonth()])
            ->with('breakTimes')
            ->get()
            ->keyBy(fn($a) => $a->date->format('Y-m-d'));

        $csvData = "日付,出勤,退勤,休憩,合計時間\n";

        $day = $currentDate->copy()->startOfMonth();
        while ($day->month === $currentDate->month) {
            $attendance = $attendanceData[$day->format('Y-m-d')]
                ?? new Attendance(['date' => $day->copy()]);
            $totalBreak = $attendance->totalBreakTime ?? '';
            $csvData .= "{$attendance->date->isoFormat('MM/DD(ddd)')},"
                . "{$attendance->clock_in_at?->format('H:i')},"
                . "{$attendance->clock_out_at?->format('H:i')},"
                . "{$attendance->totalBreakTime},"
                . "{$attendance->total_working_time}\n";

            $day->addDay();
        }

        $fileName = "{$staff->name}_勤怠_{$month}.csv";

        return Response::make($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ]);
    }


    // 申請一覧
    public function stampCorrectionRequestList(Request $request)
    {
        $status = $request->input('status', ACR::STATUS_PENDING);
        $corrections = ACR::with('attendance.user')
            ->where('status', $status)
            ->latest()
            ->get();

        return view('admin.stamp_correction_request.list', compact('corrections', 'status'));
    }

    // 承認画面
    public function correctionShow(AttendanceCorrectionRequest $correction)
    {
        $correction->load('attendance.user', 'attendance.breakTimes', 'details');

        $attendance = $correction->attendance;

        return view('admin.stamp_correction_request.approve', [
            'correction' => $correction,
            'attendance' => $attendance,
            'isPending' => $correction->isPending(),
            'isApproved' => $correction->isApproved(),
        ]);
    }

    // 承認処理
    public function correctionApprove(AttendanceCorrectionRequest $correction)
    {
        $correction->approveByAdmin(auth()->id());
        return redirect()->route('admin.stamp_correction_request.show', $correction->id);
    }
}