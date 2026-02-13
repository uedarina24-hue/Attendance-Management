<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequestForm;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;



class UserController extends Controller
{

    // 打刻画面
    public function attendanceIndex(): View
    {
        $attendance = Attendance::todayForUser(auth()->id());
        $dt = now();

        return view('user.attendance.index', compact('attendance', 'dt'));
    }


    // 出勤打刻
    public function clockIn(): RedirectResponse
    {
        $today = today()->toDateString();
        $attendance = Attendance::firstOrNew([
            'user_id' => auth()->id(),
            'date' => $today,
        ]);

        if ($attendance->clock_in_at) {
            return redirect()
                ->route('attendance.index')
                ->with('error', 'すでに出勤しています');
        }

        $attendance->clock_in_at = now();
        $attendance->status = Attendance::STATUS_WORKING;
        $attendance->save();

        return redirect()->route('attendance.index');
    }

    // 休憩開始
    public function breakStart(): RedirectResponse
    {
        $attendance = Attendance::todayForUser(auth()->id());

        if (!$attendance || $attendance->status !== Attendance::STATUS_WORKING) {
            return redirect()
                ->route('attendance.index')
                ->with('error', '勤務中のみ休憩できます');
        }

        $latestBreak = $attendance->breakTimes()->latest()->first();
        if ($latestBreak && !$latestBreak->break_end_at) {
            return redirect()
                ->route('attendance.index')
                ->with('error', 'すでに休憩中です');
        }

        $attendance->breakTimes()->create([
            'break_start_at' => now(),
        ]);

        $attendance->status = Attendance::STATUS_BREAK;
        $attendance->save();

        return redirect()->route('attendance.index');
    }

    // 休憩終了
    public function breakEnd(): RedirectResponse
    {
        $attendance = Attendance::todayForUser(auth()->id());

        if (!$attendance || $attendance->status !== Attendance::STATUS_BREAK) {
            return redirect()
                ->route('attendance.index')
                ->with('error', '休憩中ではありません');
        }

        $break = $attendance->breakTimes()
            ->whereNull('break_end_at')
            ->latest()
            ->first();

        if (!$break) {
            return redirect()
                ->route('attendance.index')
                ->with('error', '休憩データが見つかりません');
        }

        $break->update([
            'break_end_at' => now(),
        ]);

        $attendance->status = Attendance::STATUS_WORKING;
        $attendance->save();

        return redirect()->route('attendance.index');
    }

    // 退勤打刻
    public function clockOut(): RedirectResponse
    {
        $attendance = Attendance::todayForUser(auth()->id());

        if (!$attendance || $attendance->status !== Attendance::STATUS_WORKING) {
            return redirect()
                ->route('attendance.index')
                ->with('error', '勤務中のみ退勤できます');
        }

        $break = $attendance->breakTimes()
            ->whereNull('break_end_at')
            ->latest()
            ->first();

        if ($break) {
            $break->update([
                'break_end_at' => now(),
            ]);
        }

        $attendance->clock_out_at = now();
        $attendance->status = Attendance::STATUS_FINISHED;
        $attendance->save();

        return redirect()->route('attendance.index');
    }

     // 勤怠一覧
    public function attendanceList(Request $request): View
    {
        // 表示月
        $currentDate = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : ($request->filled('date')
                ? Carbon::parse($request->date)->startOfMonth()
                : now()->startOfMonth());

        // 月情報
        $currentMonth = $currentDate->format('Y/m');
        $prevMonth    = $currentDate->copy()->subMonth()->format('Y-m');
        $nextMonth    = $currentDate->copy()->addMonth()->format('Y-m');

        // 勤怠取得
        $attendanceData = Attendance::where('user_id', auth()->id())
            ->whereBetween('date', [
            $currentDate->copy()->startOfMonth(),
            $currentDate->copy()->endOfMonth(),
            ])
            ->with('breakTimes')
            ->get()
            ->keyBy(fn ($a) => $a->date->format('Y-m-d'));

        // 月の日付をすべて生成
        $period = CarbonPeriod::create(
            $currentDate->copy()->startOfMonth(),
            $currentDate->copy()->endOfMonth()
        );

        $attendances = collect();

        foreach ($period as $day) {
            $attendances->push(
                $attendanceData[$day->format('Y-m-d')]
                ?? new Attendance([
                    'user_id' => auth()->id(),
                    'date'    => $day->copy(),
                ])
            );
        }

        return view('user.attendance.list', compact(
        'attendances',
        'currentDate',
        'currentMonth',
        'prevMonth',
        'nextMonth'
        ));
    }
    //詳細画面
    public function attendanceShow(Attendance $attendance): View
    {
        abort_unless($attendance->isOwnedBy(auth()->id()), 403);

        $attendance->load(['user', 'breakTimes', 'correctionRequests.details']);

        return view('user.attendance.detail', compact('attendance'));
    }

    // 勤怠詳細（修正申請）
    public function attendanceUpdate(
    AttendanceCorrectionRequestForm $request,
    Attendance $attendance): RedirectResponse
    {

        abort_unless($attendance->isOwnedBy(auth()->id()), 403);


        if (!$attendance->canEdit()) {
            return back()->withErrors([
                'attendance' => $attendance->lockReasonMessage(),
            ]);
        }

        $attendance->submitCorrection($request->all(), auth()->id());

        return redirect()->route('attendance.detail', $attendance);
    }

    // 申請一覧
    public function stampCorrectionRequestList(Request $request): View
    {
        $status = $request->input('status', AttendanceCorrectionRequest::STATUS_PENDING);

        $requests = AttendanceCorrectionRequest::ownedBy(auth()->id())
            ->where('status', $status)
            ->latest()
            ->get();

        $pendingStatus  = AttendanceCorrectionRequest::STATUS_PENDING;
        $approvedStatus = AttendanceCorrectionRequest::STATUS_APPROVED;

        return view(
            'user.stamp_correction_request.list',
            compact('requests', 'status', 'pendingStatus', 'approvedStatus')
        );
    }

}

