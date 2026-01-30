@extends('layouts.admin')

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
                <a href="{{ route('admin.stamp_correction_request.list', ['status' => 'pending']) }}"class="{{ $status === 'pending' ? 'is-active' : '' }}">
                    承認待ち
                </a>
            </li>

            <li class="request-tab__item">
                <a href="{{ route('admin.stamp_correction_request.list', ['status' => 'approved']) }}"class="{{ $status === 'approved' ? 'is-active' : '' }}">
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
        @foreach($corrections as $correction)
        <tr class="request__data-row">
            <td class="request__data">
                <span class="request__status {{ $correction->status_class }}">
                    {{ $correction->status_text }}
                </span>
            </td>
            <td class="request__data">{{ $correction->attendance?->user->name ?? '' }}</td>
            <td class="request__data">{{ $correction->attendance?->date?->format('Y/m/d') ?? '' }}</td>
            <td class="request__data request__data request__data--remarks">{{ $correction->attendance?->display_remarks ?? '' }}</td>
            <td class="request__data">{{ $correction->created_at->format('Y/m/d') }}</td>
            <td class="request__data">
                <a class="request__data-link" href="{{ route('admin.stamp_correction_request.show', $correction->id) }}">詳細</a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection