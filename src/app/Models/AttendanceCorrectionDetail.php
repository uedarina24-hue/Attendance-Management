<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'field_name',
        'before_value',
        'after_value',
    ];

    // 親の修正申請
    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceCorrectionRequest::class,
            'correction_request_id'
        );
    }
}
