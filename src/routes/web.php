<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;


// メール認証完了
Route::get('/email/verify', function () {
    return view('auth.verify-email');
    })->middleware('auth')->name('verification.notice');

// メール再送
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

//勤怠画面へ
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();


    return redirect()->route('attendance.index');
})->middleware(['auth', 'signed'])->name('verification.verify');


// 打刻画面など、ログインユーザーのみアクセス
Route::middleware(['auth', 'verified'])->group(function () {
    // 打刻用ルート
    Route::get('/attendance', [UserController::class, 'attendanceIndex'])->name('attendance.index');
    Route::post('/attendance/clock-in', [UserController::class, 'clockIn'])->name('attendance.clock_in');
    Route::post('/attendance/break-start', [UserController::class, 'breakStart'])->name('attendance.break_start');
    Route::post('/attendance/break-end', [UserController::class, 'breakEnd'])->name('attendance.break_end');
    Route::post('/attendance/clock-out', [UserController::class, 'clockOut'])->name('attendance.clock_out');
    // 勤怠一覧
    Route::get('/attendance/list', [UserController::class, 'attendanceList'])->name('attendance.list');
    // 勤怠詳細
    Route::get('/attendance/detail/{attendance}', [UserController::class, 'attendanceShow'])
    ->name('attendance.detail');
    // 勤怠詳細修正
    Route::post('/attendance/detail/{attendance}', [UserController::class, 'attendanceUpdate'])
    ->name('attendance.update');
    // 勤怠申請一覧
    Route::get('/stamp_correction_request/list', [UserController::class, 'stampCorrectionRequestList'])
    ->name('stamp_correction_request.list');
});


// 管理者画面　 /admin
Route::prefix('admin')->name('admin.')->group(function () {

    // ゲスト管理者
    Route::middleware('guest')->group(function(){
        Route::get('/login',[AdminController::class,'loginForm'])->name('login');
        Route::post('/login',[AdminController::class,'login'])->name('login.store');
    });

    // 管理者のみ
    Route::middleware(['auth:admin', 'check.admin'])->group(function () {
        // 管理者ログアウト
        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
        // 勤怠
        Route::get('/attendance', [AdminController::class, 'attendanceIndex'])
            ->name('attendance.list');
        Route::get('/attendance/{attendance}', [AdminController::class, 'attendanceShow'])
            ->name('attendance.show');
        Route::post('/attendance/{attendance}', [AdminController::class, 'attendanceUpdate'])
            ->name('attendance.update');

        // スタッフ
        Route::get('/staff/list', [AdminController::class, 'staffList'])
            ->name('staff.list');
        Route::get('/attendance/staff/{staff}', [AdminController::class, 'attendanceStaff'])
            ->name('attendance.staff');
        Route::post('/attendance/staff/{staff}/export', [AdminController::class, 'attendanceExport'])
        ->name('attendance.export');

        // 修正申請一覧
        Route::get('/stamp_correction_request/list', [AdminController::class, 'stampCorrectionRequestList'])
            ->name('stamp_correction_request.list');
        // 修正申請承認画面
        Route::get('/stamp_correction_request/approve/{correction}', [AdminController::class, 'correctionShow'])
            ->name('stamp_correction_request.show');
        // 承認画面
        Route::post('/stamp_correction_request/approve/{correction}', [AdminController::class, 'correctionApprove'])
            ->name('stamp_correction_request.approve');

    });

});
