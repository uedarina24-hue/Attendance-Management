@extends('layouts.admin')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('cardClass', 'card--detail')

@section('content')
    <div class="container">
        <h1 class="container-title">勤怠詳細</h1>

        <!-- 名前 -->
        <div class="detail__section">
            <label class="detail__label">名前</label>
            <p class="detail__value">{{ $attendance->user?->name ?? '' }}</p>
        </div>

        <!-- 日付 -->
        <div class="detail__section">
            <label class="detail__label">日付</label>
            <p class="detail__value">{{ $attendance->date?->format('Y年n月j日') ?? '' }}</p>
        </div>

        <!-- 出勤・退勤 -->
        <div class="detail__section">
            <label class="detail__label">出勤・退勤</label>
            <div class="detail__value">
                <div class="detail__inputs">
                    <span class="detail__readonly detail__readonly--locked">
                        {{ $correction->detailAfter('clock_in_at')?: $attendance->clock_in_at?->format('H:i') }}
                    </span>
                    <span class="detail__tilde">〜</span>
                    <span class="detail__readonly detail__readonly--locked">
                        {{ $correction->detailAfter('clock_out_at')?: $attendance->clock_out_at?->format('H:i') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 休憩 -->
        @foreach ($correction->breaksAfter() as $index => $break)
            <div class="detail__section">
                <label class="detail__label">
                    {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
                </label>

                <div class="detail__value">
                    <div class="detail__inputs">
                        <span class="detail__readonly detail__readonly--locked">
                            {{ $break['start'] }}
                        </span>
                        <span class="detail__tilde">〜</span>
                        <span class="detail__readonly detail__readonly--locked">
                            {{ $break['end'] }}
                        </span>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- 備考 -->
        <div class="detail__section detail__section--remarks">
            <label class="detail__label">備考</label>
            <div class="detail__value">
                {{ $correction->detailAfter('remarks') }}
            </div>
        </div>

        <!-- 承認ボタン -->
        @if($correction?->isPending())
            <form method="POST" action="{{ route('admin.stamp_correction_request.approve', $correction->id) }}">
                @csrf
                <div class="detail__button-area">
                    <button type="submit" class="detail__button--approve">承認</button>
                </div>
            </form>
        @elseif($correction?->isApproved())
            <div class="detail__button-area">
                <button class="detail__button--approved">承認済み</button>
            </div>
        @endif
    </div>
@endsection
