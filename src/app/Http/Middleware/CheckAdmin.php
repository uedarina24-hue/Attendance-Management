<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('admin')->check() || Auth::guard('admin')->user()->role !== 'admin') {
            abort(403, '管理者専用ページです');
        }

        return $next($request);
    }
}

