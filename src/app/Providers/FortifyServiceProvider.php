<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        //
    }


    public function boot(): void
    {

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::registerView(function () {return view('auth.register');});
        Fortify::loginView(function () {return view('auth.login');});

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });

        Fortify::authenticateUsing(function (Request $request) {

            $user = Auth::getProvider()->retrieveByCredentials(
            $request->only('email')
            );
            if (! $user || ! Auth::validate($request->only('email', 'password'))) {
                throw ValidationException::withMessages([
                'email' => 'ログイン情報が正しくありません',
                ]);
            }

        return $user;
        });

        $this->app->bind(\Laravel\Fortify\Http\Requests\LoginRequest::class,
        \App\Http\Requests\LoginRequest::class);

    }
}
