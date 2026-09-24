<?php

namespace App\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureAbsoluteSessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && Auth::check()) {
            $created = DB::table('sessions')->where('id', $request->session()->getId())
                ->where('user_id', Auth::id())->value('created_at');

            if ($created !== null && CarbonImmutable::parse($created)->addMinutes((int) config('session.absolute_minutes'))->isPast()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => 'Your session expired. Please sign in again.']);
            }
        }

        return $next($request);
    }
}
