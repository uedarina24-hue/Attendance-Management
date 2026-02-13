@extends('layouts.admin')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/list.css')}}">
@endsection

@section('link')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('cardClass', 'card--list')

@section('content')
    <div class="container">
        <h1 class="container-title">{{ $staff->name }}さんの勤怠</h1>

        <!-- 月切り替え -->
        <nav class="nav">
            <ul class="nav__list">
                <!-- 前月（左） -->
                <li class="nav__item nav__item--prev">
                    <a class="nav__link"
                        href="{{ route('admin.attendance.staff', ['staff' => $staff->id,'month' => $prevMonth]) }}">
                        ◀︎前月
                    </a>
                </li>

                <!-- カレンダー＋現在月（中央） -->
                <li class="nav__item nav__item--center">
                    <form class="nav-calendar"
                        method="GET"
                        action="{{ route('admin.attendance.staff', ['staff' => $staff->id]) }}">
                        <label class="calendar-label" for="calendar">
                            <input
                                id="calendar"
                                class="calendar-input"
                                type="date"
                                name="date"
                                value="{{ $currentDate->format('Y-m-d') }}"
                                onchange="this.form.submit()">
                            <i class="fa fa-calendar calendar-img"></i>
                        </label>
                    </form>

                    <span class="nav__current">
                        {{ $currentMonth }}
                    </span>
                </li>

                <!-- 翌月（右） -->
                <li class="nav__item nav__item--next">
                    <a class="nav__link"
                        href="{{ route('admin.attendance.staff', ['staff' => $staff->id,'month' => $nextMonth]) }}">
                        翌月▶︎
                    </a>
                </li>
            </ul>
        </nav>

        <!-- 勤怠テーブル -->
        <table class="attendance-table">
            <thead class="attendance-table__header-group">
                <tr class="attendance-table__row">
                    <th class="attendance-table__head">日付</th>
                    <th class="attendance-table__head">出勤</th>
                    <th class="attendance-table__head">退勤</th>
                    <th class="attendance-table__head">休憩</th>
                    <th class="attendance-table__head">合計</th>
                    <th class="attendance-table__head">詳細</th>
                </tr>
            </thead>
            <tbody class="attendance-table__body-group">
                @foreach($attendances as $attendance)
                    <tr class="attendance-table__row">
                        <td class="attendance-table__content">
                            {{ $attendance->date->format('m/d') }}({{ $attendance->date->isoFormat('ddd') }})
                        </td>
                        <td class="attendance-table__content">{{ $attendance->raw_clock_in }}</td>
                        <td class="attendance-table__content">{{ $attendance->raw_clock_out }}</td>
                        <td class="attendance-table__content">{{ $attendance->total_break_time }}</td>
                        <td class="attendance-table__content">{{ $attendance->total_working_time }}</td>
                        <td class="attendance-table__content">
                            <a class="attendance-table__link"
                                href="{{ route('admin.attendance.show', ['attendance' => $attendance->date->format('Y-m-d'),'user' => $staff->id]) }}">
                                詳細
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <form action="{{ route('admin.attendance.export', ['staff' => $staff->id, 'month' => request()->get('month')]) }}" method="post">
            @csrf
            <div class="export-form__button">
                <button class="export-form__button-submit" type="submit">CSV出力</button>
            </div>
        </form>
    </div>
@endsection