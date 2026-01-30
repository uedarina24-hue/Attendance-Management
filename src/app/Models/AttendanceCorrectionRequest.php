<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    /* =====================
     * 定数（statusの正規値）
     * ===================== */
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';

    /* =====================
     * 属性
     * ===================== */
    protected $fillable = [
        'attendance_id',
        'user_id',
        'status',
        'applied_at',
        'reason',
        'approved_at',
    ];

    protected $casts = [
        'applied_at'  => 'datetime',
        'approved_at' => 'datetime',
    ];

    /* =====================
     * リレーション
     * ===================== */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionDetail::class, 'correction_request_id');
    }

    /* =====================
     * クエリスコープ
     * ===================== */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->whereHas('attendance', fn ($q) =>
            $q->where('user_id', $userId)
        );
    }

    /* =====================
     * 表示用アクセサ
     * ===================== */

    // ステータス表示用テキスト
    public function getStatusTextAttribute(): string
    {
        return $this->isPending() ? '承認待ち' : '承認済み';
    }

    // ステータス表示用CSSクラス
    public function getStatusClassAttribute(): string
    {
        return $this->isPending() ? 'request__status--pending' :    'request__status--approved';
    }

    /* =====================
     * 状態判定メソッド
     * ===================== */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /* =====================
     * 勤怠ロジック
     * ===================== */
    // 指定項目の修正後値を取得
    public function detailAfter(string $field): ?string
    {
        return $this->details
            ->firstWhere('field_name', $field)
            ?->after_value;
    }

    // 休憩詳細を取得
    public function breaksAfter(): array
    {
        return $this->details
            ->where('field_name', 'breaks')
            ->map(function ($d) {
                [$start, $end] = array_pad(
                    explode('〜', $d->after_value),
                    2,
                    null
                );
                return compact('start', 'end');
        })
            ->values()
            ->toArray();
    }

    // 修正申請の詳細を追加する
    public function addDetail(
        string $field,
        ?string $before,
        ?string $after
    ): void {
        $this->details()->create([
            'field_name'   => $field,
            'before_value' => $before,
            'after_value'  => $after,
        ]);
    }

    // Attendance 更新用データを生成
    public function toAttendanceUpdateData(): array
    {
        return [
            'clock_in_at'  => $this->detailAfter('clock_in_at'),
            'clock_out_at' => $this->detailAfter('clock_out_at'),
            'remarks'      => $this->detailAfter('remarks'),
            'breaks'       => $this->breaksAfter(),
        ];
    }

    // 自身を承認状態に更新
    public function approve(): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    // 管理者承認
    public function approveByAdmin(int $adminId): void
    {
        DB::transaction(function () use ($adminId) {

            $attendance = $this->attendance;

            foreach ($this->details as $detail) {
                match ($detail->field_name) {

                    'clock_in_at' => $attendance->clock_in_at =
                        $detail->after_value
                            ? Carbon::createFromFormat(
                                'Y-m-d H:i',
                                $attendance->date->format('Y-m-d') . ' ' . $detail->after_value
                            )
                            : null,

                    'clock_out_at' => $attendance->clock_out_at =
                        $detail->after_value
                            ? Carbon::createFromFormat(
                                'Y-m-d H:i',
                                $attendance->date->format('Y-m-d') . ' ' . $detail->after_value
                            )
                            : null,

                    'remarks' => $attendance->remarks = $detail->after_value,

                    default => null,
                };
            }

            $attendance->breakTimes()->delete();

            $this->details
                ->where('field_name', 'breaks')
                ->each(function ($detail) use ($attendance) {

                    if (empty($detail->after_value) || !str_contains($detail->after_value, '〜')) {
                        return;
                    }

                    [$start, $end] = explode('〜', $detail->after_value);

                    if (!$start || !$end) {
                        return;
                    }

                    $attendance->breakTimes()->create([
                        'break_start_at' => Carbon::createFromFormat(
                            'Y-m-d H:i',
                            $attendance->date->format('Y-m-d') . ' ' . $start
                        ),
                        'break_end_at' => Carbon::createFromFormat(
                            'Y-m-d H:i',
                            $attendance->date->format('Y-m-d') . ' ' . $end
                        ),
                    ]);
                });

            $attendance->save();

            $this->update([
                'status'       => self::STATUS_APPROVED,
                'approved_by'  => $adminId,
                'approved_at'  => now(),
            ]);
        });
    }
}
