@extends('layouts.app')

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
        <p class="detail__value">{{ $attendance->user->name }}</p>
    </div>

    <!-- 日付 -->
    <div class="detail__section">
        <label class="detail__label">日付</label>
        <p class="detail__value">{{ $attendance->date->format('Y年n月j日') }}</p>
    </div>

    <form method="POST" action="{{ route('attendance.update', $attendance) }}">
        @csrf

        <!-- 出勤・退勤 -->
        <div class="detail__section">
            <label class="detail__label">出勤・退勤</label>
            <div class="detail__value">
                @if(!$attendance->canEdit())
                    <div class="detail__inputs">
                        <span class="detail__readonly detail__readonly--locked">
                            {{ $attendance->display_clock_in }}
                        </span>
                        <span class="detail__tilde">〜</span>
                        <span class="detail__readonly detail__readonly--locked">
                            {{ $attendance->display_clock_out }}
                        </span>
                    </div>
                @else
                    <div class="detail__inputs">
                        <input class="detail__input" type="text"
                            name="clock_in_at"
                            value="{{ old('clock_in_at', $attendance->display_clock_in) }}">
                        <span>〜</span>
                        <input class="detail__input" type="text"
                            name="clock_out_at"
                            value="{{ old('clock_out_at', $attendance->display_clock_out) }}">
                    </div>
                    @error('clock_in_at') <div class="user__error">{{ $message }}</div> @enderror
                    @error('clock_out_at') <div class="user__error">{{ $message }}</div> @enderror
                @endif
            </div>
        </div>

        <!-- 休憩 -->
        @if(!$attendance->canEdit())
            @forelse($attendance->getDisplayBreaks() as $index => $break)
                @php [$start, $end] = array_pad(explode('〜', $break), 2, '') @endphp
                <div class="detail__section">
                    <label class="detail__label">
                        {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
                    </label>
                    <div class="detail__value">
                        <div class="detail__inputs">
                            <span class="detail__readonly detail__readonly--locked">{{ $start }}</span>
                            <span class="detail__tilde">〜</span>
                            <span class="detail__readonly detail__readonly--locked">{{ $end }}</span>
                        </div>
                    </div>
                </div>
            @empty
            @endforelse
        @else
            @php
                // old() があれば優先、なければDBの休憩を取得
                $sourceBreaks = old('breaks')
                ? collect(old('breaks'))
                : $attendance->breakTimes->map(fn ($b) => [
                'start' => optional($b->break_start_at)->format('H:i'),
                'end'   => optional($b->break_end_at)->format('H:i'),
                ]);

                // 有効休憩（どちらかが入力されている）を抽出
                $validBreaks = $sourceBreaks->filter(fn($b) =>
                !empty($b['start']) || !empty($b['end'])
                )->values();

                // 常に有効休憩 + 1 行表示
                $displayBreaks = collect($validBreaks);
                $displayBreaks->push(['start' => '', 'end' => '']);
            @endphp

            @foreach($displayBreaks as $i => $break)
                <div class="detail__section">
                    <label class="detail__label">{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</label>
                    <div class="detail__value">
                        <div class="detail__inputs">
                            <input class="detail__input" type="text"
                                name="breaks[{{ $i }}][start]"
                                value="{{ $break['start'] }}">
                            <span>〜</span>
                            <input class="detail__input" type="text"
                                name="breaks[{{ $i }}][end]"
                                value="{{ $break['end'] }}">
                        </div>
                        @error("breaks.$i.start")
                            <div class="user__error">{{ $message }}</div>
                        @enderror
                        @error("breaks.$i.end")
                            <div class="user__error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach
        @endif

        <!-- 備考 -->
        <div class="detail__section detail__section--remarks">
            <label class="detail__label" for="remarks">備考</label>
            <div class="detail__value">
                @if(!$attendance->canEdit())
                    <div class="detail__remarks-readonly">{{ $attendance->display_remarks }}</div>
                @else
                    <textarea id="remarks" class="detail__textarea"
                            name="remarks"
                            rows="3">{{ old('remarks', $attendance->display_remarks) }}</textarea>
                @endif
                @error('remarks') <div class="user__error">{{ $message }}</div> @enderror
            </div>
        </div>

        <!-- ボタン -->
        @if($attendance->canEdit())
            <div class="detail__button-area">
                <button type="submit" class="detail__button">修正申請</button>
            </div>
        @else
            <p class="detail__error">{{ $attendance->lockReasonMessage() }}</p>
        @endif
    </form>
</div>

@endsection

