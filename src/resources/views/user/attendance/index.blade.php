@extends('layouts.app')


@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css')}}">
@endsection

@section('cardClass', 'card--index')

@section('content')
<div class="container">

    <!-- ステータス表示 -->
    <p class="container__status {{ $attendance?->status_class ?? 'status-off' }}">
        <strong>{{ $attendance?->status_text ?? '勤務外' }}</strong>
    </p>

    <!-- 現在日時 -->
    <h2 class="container__datetime">{{ $dt->isoFormat('YYYY年M月D日(ddd)') }}</h2>
    <h3 class="container__time">{{ now()->format('H:i') }}</h3>


    <!-- 打刻ボタン -->
    <div class="form">
        <!-- 未出勤 -->
        @if(!$attendance)
            <form method="POST" action="{{ route('attendance.clock_in') }}">
                @csrf
                <button class="form__button--working" type="submit">出勤</button>
            </form>

        <!-- 出勤中 -->
        @elseif($attendance && $attendance->status === \App\Models\Attendance::STATUS_WORKING)
            <form method="POST" action="{{ route('attendance.clock_out') }}">
                @csrf
                <button class="form__button--finish" type="submit">退勤</button>
            </form>
            <form method="POST" action="{{ route('attendance.break_start') }}">
                @csrf
                <button class="form__button--break" type="submit">休憩入</button>
            </form>

        <!-- 休憩中 -->
        @elseif($attendance && $attendance->status === \App\Models\Attendance::STATUS_BREAK)
            <form method="POST" action="{{ route('attendance.break_end') }}">
                @csrf
                <button class="form__button--return" type="submit">休憩戻</button>
            </form>

        <!-- 退勤済 -->
        @elseif($attendance && $attendance->status === \App\Models\Attendance::STATUS_FINISHED)
            <p class="thanks-message">お疲れ様でした！</p>
        @endif
    </div>

</div>
@endsection