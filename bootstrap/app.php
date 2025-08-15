<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function ($schedule) {
        // Check for feeding reminders every hour
        $schedule->command('starter:check-feeding-reminders')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Check active baking timers every 15 minutes
        $schedule->command('baking:check-timers')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Clean up old completed/cancelled timers daily
        $schedule->call(function () {
            $bakingTimerService = app(\App\Services\BakingTimerService::class);
            $bakingTimerService->cleanupOldTimers(30); // 30 days
        })->daily();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web([
            \App\Http\Middleware\IpAllowlistMiddleware::class,
        ]);
        
        $middleware->alias([
            'ip.allowlist' => \App\Http\Middleware\IpAllowlistMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
