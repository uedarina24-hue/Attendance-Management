<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'break_start_at',
        'break_end_at',
    ];

    protected $casts = [
        'break_start_at' => 'datetime',
        'break_end_at' => 'datetime',
    ];

    // 親となる勤怠
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    // 休憩が進行中か
    public function isOngoing(): bool
    {
        return is_null($this->break_end_at);
    }

    // 休憩が終了済みか
    public function isFinished(): bool
    {
        return !is_null($this->break_end_at);
    }

    // 休憩時間（分）を返す
    public function durationInMinutes(): ?int
    {
        if ($this->isOngoing()) {
            return null;
        }
        return $this->break_end_at->diffInMinutes($this->break_start_at);
    }


}
