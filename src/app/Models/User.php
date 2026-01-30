<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;


    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // ユーザーの勤怠一覧
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // ユーザーの修正申請一覧
    public function attendanceCorrectionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    // 管理者判定
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

}
