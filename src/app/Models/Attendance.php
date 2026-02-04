<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use DB;

class Attendance extends Model
{
    use HasFactory;

    public const STATUS_OUTSIDE  = 'outside';
    public const STATUS_WORKING  = 'working';
    public const STATUS_BREAK    = 'break';
    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'user_id',
        'date',
        'clock_in_at',
        'clock_out_at',
        'status',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    /* =====================
    リレーション
    ===================== */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    /* =====================
    ステータス表示用
    ===================== */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            self::STATUS_WORKING => '出勤中',
            self::STATUS_BREAK   => '休憩中',
            self::STATUS_FINISHED=> '退勤済',
            default              => '勤務外',
        };
    }

    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_WORKING => 'status-working',
            self::STATUS_BREAK   => 'status-resting',
            self::STATUS_FINISHED=> 'status-finished',
            default              => 'status-off',
        };
    }

    /* =====================
　　　　　　　今日の勤怠取得
　　　　　　===================== */
    public static function todayForUser(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->whereDate('date', today())
            ->first();
    }

    /* =====================
    勤怠時間
    ===================== */
    public function getRawClockInAttribute(): ?string
    {
        return $this->clock_in_at?->format('H:i');
    }

    public function getRawClockOutAttribute(): ?string
    {
        return $this->clock_out_at?->format('H:i');
    }

    private function diffInMinutesByMinute(Carbon $start, Carbon $end): int
    {
        return $end->copy()->startOfMinute()->diffInMinutes($start->copy()->startOfMinute());
    }

    public function totalBreakMinutes(): int
    {
        return $this->breakTimes
            ->filter(fn($b) => $b->break_start_at && $b->break_end_at)
            ->sum(fn($b) => $this->diffInMinutesByMinute($b->break_start_at, $b->break_end_at));
    }

    public function getTotalBreakTimeAttribute(): ?string
    {
        $minutes = $this->totalBreakMinutes();
        if ($minutes === 0) return null;
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function totalWorkingMinutes(): int
    {
        if (!$this->clock_in_at || !$this->clock_out_at) return 0;

        $workMinutes = $this->diffInMinutesByMinute($this->clock_in_at, $this->clock_out_at);
        return max(0, $workMinutes - $this->totalBreakMinutes());
    }

    public function getTotalWorkingTimeAttribute(): ?string
    {
        $minutes = $this->totalWorkingMinutes();
        if ($minutes === 0) return null;
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /* =====================
    修正申請関連
    ===================== */
    public function latestPendingCorrection()
    {
        return $this->correctionRequests()
            ->where('status', AttendanceCorrectionRequest::STATUS_PENDING)
            ->latest()
            ->with('details')
            ->first();
    }

    public function latestApprovedCorrection()
    {
        return $this->correctionRequests()
            ->where('status', AttendanceCorrectionRequest::STATUS_APPROVED)
            ->latest()
            ->with('details')
            ->first();
    }

    public function latestCorrectionForDetail()
    {
        return $this->latestPendingCorrection() ?? $this->latestApprovedCorrection();
    }

    public function latestCorrectionForList()
    {
        return $this->latestApprovedCorrection();
    }

    public function getDisplayClockInAttribute(): ?string
    {
        return $this->latestCorrectionForDetail()?->detailAfter('clock_in_at')
            ?? $this->clock_in_at?->format('H:i') ?? '';
    }

    public function getDisplayClockOutAttribute(): ?string
    {
        return $this->latestCorrectionForDetail()?->detailAfter('clock_out_at')
            ?? $this->clock_out_at?->format('H:i') ?? '';
    }

    public function getDisplayRemarksAttribute(): ?string
    {
        return $this->latestCorrectionForDetail()?->detailAfter('remarks')
            ?? ($this->remarks ?? '');
    }

    public function getDisplayBreaks(): array
    {
        if ($correction = $this->latestCorrectionForDetail()) {
            $breaks = $correction->details
                ->where('field_name', 'breaks')
                ->pluck('after_value')
                ->filter(fn($v) => !empty($v) && $v !== '〜')
                ->values()
                ->all();
            return !empty($breaks) ? $breaks : ['〜'];
        }

        $breaks = $this->breakTimes
            ->filter(fn($b) => $b->break_start_at && $b->break_end_at)
            ->map(fn($b) => $b->break_start_at->format('H:i') . '〜' . $b->break_end_at->format('H:i'))
            ->toArray();

        return !empty($breaks) ? $breaks : ['〜'];
    }

    /* =====================
    編集可否
    ===================== */
    public function isLocked(): bool
    {
        return $this->latestCorrectionForDetail() !== null;
    }

    public function canEdit(): bool
    {
        // 承認済み・承認待ちは編集不可
        if ($this->isLocked()) {
            return false;
        }

        // 未来日は編集不可
        if ($this->date->isFuture()) {
            return false;
        }

        // 過去の打刻漏れ・今日の勤怠は編集可能
        return true;
    }

    public function lockReasonMessage(): ?string
    {
        if ($this->latestPendingCorrection()) return '承認待ちのため修正できません。';
        if ($this->latestApprovedCorrection()) return '承認済みのため修正できません。';
        return null;
    }
    /* =====================
    修正申請作成
    ===================== */
    public function submitCorrection(array $data, int $userId)
    {
        if (!$this->exists) {
            throw new \LogicException('Attendance must exist before submitting correction.');
        }

        return DB::transaction(function() use ($data, $userId) {
            $correction = AttendanceCorrectionRequest::create([
                'attendance_id' => $this->id,
                'user_id'       => $userId,
                'status'        => AttendanceCorrectionRequest::STATUS_PENDING,
            ]);

            $fields = ['clock_in_at', 'clock_out_at', 'remarks'];
            foreach ($fields as $field) {
                if (!array_key_exists($field, $data)) continue;

                $before = $this->$field;
                if (in_array($field, ['clock_in_at','clock_out_at']) && $before) {
                    $before = $before->format('H:i');
                }

                $after = $data[$field];
                if (in_array($field, ['clock_in_at','clock_out_at']) && $after) {
                    $after = Carbon::parse($after)->format('H:i');
                }

                AttendanceCorrectionDetail::create([
                    'correction_request_id' => $correction->id,
                    'field_name'   => $field,
                    'before_value' => $before,
                    'after_value'  => $after,
                ]);
            }

            collect($data['breaks'] ?? [])
                ->filter(fn($b) => !empty($b['start']) && !empty($b['end']))
                ->each(function($b) use ($correction) {
                    AttendanceCorrectionDetail::create([
                        'correction_request_id' => $correction->id,
                        'field_name'   => 'breaks',
                        'before_value' => null,
                        'after_value'  => "{$b['start']}〜{$b['end']}",
                    ]);
                });

            return $correction;
        });
    }

    /* =====================
    所有者判定
    ===================== */
    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}

