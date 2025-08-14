<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpAllowlistMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = explode(',', env('ALLOWED_IPS', '127.0.0.1,::1'));
        $allowedIps = array_map('trim', $allowedIps);
        
        $clientIp = $request->ip();
        
        // Allow access if IP is in the allowlist or if no restriction is set
        if (empty($allowedIps) || in_array($clientIp, $allowedIps)) {
            return $next($request);
        }
        
        // For development, always allow localhost IPs
        $localhostIps = ['127.0.0.1', '::1', 'localhost'];
        if (app()->environment('local') && in_array($clientIp, $localhostIps)) {
            return $next($request);
        }
        
        abort(403, 'Access denied. Your IP address is not authorized.');
    }
}
