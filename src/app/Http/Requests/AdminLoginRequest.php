<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;

class AdminLoginRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'email' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
            ],
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'メールアドレスを入力して下さい',
            'email.email'  => 'メールアドレスはメール形式で入力してください',
            'password.required'  => 'パスワードを入力してください',
        ];
    }

    /**
     * バリデーション後に認証チェックを行う
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // すでに必須エラーがある場合は認証しない
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $credentials = [
                'email'    => $this->input('email'),
                'password' => $this->input('password'),
                'role'     => 'admin',
            ];

            if (!Auth::guard('admin')->attempt($credentials)) {
                $validator->errors()->add(
                    'email',
                    'ログイン情報が登録されていません'
                );
            }
        });
    }


    /**
     * 短時間連続ログイン防止
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 10)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
                'minutes' => ceil(RateLimiter::availableIn($this->throttleKey()) / 60),
            ]),
        ]);
    }

    /**
     * レートリミット用のキー
     */
    protected function throttleKey(): string
    {
        return strtolower($this->input('email')) . '|' . $this->ip();
    }
}
