@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request.css')}}">
@endsection

@section('cardClass', 'card--request')

@section('content')
<div class="container">
    <h1 class="container-title">申請一覧</h1>

    <!-- ナビ -->
    <nav class="request-tab">
        <ul class="request-tab__list">
            <li class="request-tab__item">
                <a href="{{ route('stamp_correction_request.list', ['status' => $pendingStatus]) }}"class="{{ $status === $pendingStatus ? 'is-active' : '' }}">
                    承認待ち
                </a>
            </li>
            <li class="request-tab__item">
                <a href="{{ route('stamp_correction_request.list', ['status' => $approvedStatus]) }}"class="{{ $status === $approvedStatus ? 'is-active' : '' }}">
                    承認済み
                </a>
            </li>
        </ul>
    </nav>

    <!-- テーブル -->
    <table class="request__table">
        <thead class="request__header-group">
        <tr class="request__header-row">
            <th class="request__header">状態</th>
            <th class="request__header">名前</th>
            <th class="request__header">対象日時</th>
            <th class="request__header">申請理由</th>
            <th class="request__header">申請日時</th>
            <th class="request__header">詳細</th>
        </tr>
        </thead>

        <tbody class="request__body-group">
        @foreach($requests as $req)
        <tr class="request__data-row">
            <td class="request__data">
                <span class="request__status {{ $req->status_class }}">
                    {{ $req->status_text }}
                </span>
            </td>
            <td class="request__data">{{ $req->attendance->user->name }}</td>
            <td class="request__data">{{ $req->attendance->date->format('Y/m/d') }}</td>
            <td class="request__data request__data request__data--remarks">
                {{ $req->detailAfter('remarks') }}
            </td>
            <td class="request__data">{{ $req->created_at->format('Y/m/d') }}</td>
            <td class="request__data">
                <a class="request__data-link" href="{{ route('attendance.detail', $req->attendance->id) }}">詳細</a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection