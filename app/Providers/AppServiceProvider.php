<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom notification channel for direct Telegram API
        Notification::extend('telegram-direct', function ($app) {
            return new class {
                public function send($notifiable, $notification)
                {
                    if (method_exists($notification, 'toTelegramDirect')) {
                        $notification->toTelegramDirect($notifiable);
                    }
                }
            };
        });
    }
}
