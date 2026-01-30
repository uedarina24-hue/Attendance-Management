@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff-list.css')}}">
@endsection

@section('cardClass', 'card--request')

@section('content')
<div class="container">
    <h1 class="container-title">スタッフ一覧</h1>

    <!-- 勤怠テーブル -->
    <table class="staff-table">
        <thead class="staff-table__header-group">
            <tr class="staff-table__row">
                <th class="staff-table__head">名前</th>
                <th class="staff-table__head">メールアドレス</th>
                <th class="staff-table__head">月次勤怠</th>
            </tr>
        </thead>
        <tbody class="staff-table__body-group">
            @foreach($staffs as $staff)
            <tr class="staff-table__row">
                <td class="staff-table__data">{{ $staff->name }}</td>
                <td class="staff-table__data">{{ $staff->email }}</td>
                <td class="staff-table__data">
                    <a class="staff-table__link"
                    href="{{ route('admin.attendance.staff', $staff->id) }}">
                        詳細
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection