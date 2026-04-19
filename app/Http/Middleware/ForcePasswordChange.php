<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            // Avoid infinite redirect if already on profile page or performing logout
            if (!$request->is('profile*') && !$request->is('logout')) {
                return redirect()->route('profile.edit')->with('warning', 'Please update your institutional protocol password before proceeding.');
            }
        }

        return $next($request);
    }
}
