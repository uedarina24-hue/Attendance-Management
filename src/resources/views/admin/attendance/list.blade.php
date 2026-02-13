@extends('layouts.admin')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/list.css') }}">
@endsection

@section('link')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('cardClass', 'card--list')

@section('content')
    <div class="container">

        <h1 class="container-title">
            {{ $currentDate->format('Y年n月j日') }}の勤怠
        </h1>

        <nav class="nav">
            <ul class="nav__list">
                <!-- 前日（左） -->
                <li class="nav__item nav__item--prev">
                    <a class="nav__link"
                        href="{{ route('admin.attendance.list', ['date' => $prevDate->format('Y-m-d')]) }}">
                        ◀︎前日
                    </a>
                </li>

                <!-- 当日カレンダー＋日付（中央） -->
                <li class="nav__item nav__item--center">
                    <form class="nav-calendar" method="GET" action="{{ route('admin.attendance.list') }}">
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
                        {{ $currentDate->format('Y/m/d') }}
                    </span>
                </li>

                <!-- 翌日（右） -->
                <li class="nav__item nav__item--next">
                    <a class="nav__link"
                        href="{{ route('admin.attendance.list', ['date' => $nextDate->format('Y-m-d')]) }}">
                        翌日▶︎
                    </a>
                </li>
            </ul>
        </nav>

        <table class="attendance-table">
            <thead class="attendance-table__header-group">
                <tr class="attendance-table__row">
                    <th class="attendance-table__head">名前</th>
                    <th class="attendance-table__head">出勤</th>
                    <th class="attendance-table__head">退勤</th>
                    <th class="attendance-table__head">休憩</th>
                    <th class="attendance-table__head">合計</th>
                    <th class="attendance-table__head">詳細</th>
                </tr>
            </thead>

            <tbody class="attendance-table__body-group">
                @foreach ($rows as $row)
                    <tr class="attendance-table__row">
                        <td class="attendance-table__content">{{ $row['user']->name }}</td>
                        <td class="attendance-table__content">{{ $row['attendance']?->clock_in_at?->format('H:i') ?? '' }}</td>
                        <td class="attendance-table__content">{{ $row['attendance']?->clock_out_at?->format('H:i') ?? '' }}</td>
                        <td class="attendance-table__content">{{ $row['attendance']?->total_break_time ?? '' }}</td>
                        <td class="attendance-table__content">{{ $row['attendance']?->total_working_time ?? '' }}</td>
                        <td class="attendance-table__content">
                            <a class="attendance-table__link"
                                href="{{ route('admin.attendance.show', ['attendance' => $currentDate->format('Y-m-d'),'user' => $row['user']->id]) }}">
                                詳細
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection