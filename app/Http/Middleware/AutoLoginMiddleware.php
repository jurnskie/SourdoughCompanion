<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoLoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            $defaultUser = \App\Models\User::where('email', 'sourdough@localhost')->first();
            
            if ($defaultUser) {
                auth()->login($defaultUser);
            }
        }
        
        return $next($request);
    }
}
