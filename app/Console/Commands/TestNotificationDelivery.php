<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DebugTelegramNotification;
use App\Notifications\FeedingReminderNotification;
use Illuminate\Console\Command;

class TestNotificationDelivery extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notification:test-delivery {--fallback : Use direct API fallback}';

    /**
     * The console command description.
     */
    protected $description = 'Test notification delivery to debug Telegram issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing notification delivery...');

        $user = User::where('telegram_chat_id', '!=', null)->first();
        
        if (!$user) {
            $this->error('No user found with telegram_chat_id');
            return self::FAILURE;
        }

        $this->info("Testing with user: {$user->name} (chat_id: {$user->telegram_chat_id})");

        // Test 1: Debug notification with current package
        $this->info('Test 1: Debug notification via TelegramChannel');
        try {
            $user->notify(new DebugTelegramNotification('Package delivery test'));
            $this->info('✅ Debug notification sent via package');
        } catch (\Exception $e) {
            $this->error('❌ Debug notification failed: ' . $e->getMessage());
        }

        // Test 2: Original feeding reminder
        $this->info('Test 2: Original feeding reminder notification');
        try {
            $user->notify(new FeedingReminderNotification('Test Starter', 5));
            $this->info('✅ Feeding reminder sent via package');
        } catch (\Exception $e) {
            $this->error('❌ Feeding reminder failed: ' . $e->getMessage());
        }

        // Test 3: Fallback direct API (if flag provided)
        if ($this->option('fallback')) {
            $this->info('Test 3: Direct API fallback');
            try {
                $debug = new DebugTelegramNotification('Fallback API test', true);
                $debug->toTelegramFallback($user);
                $this->info('✅ Fallback API test completed (check logs)');
            } catch (\Exception $e) {
                $this->error('❌ Fallback API failed: ' . $e->getMessage());
            }
        }

        $this->info('');
        $this->info('Check your Telegram and logs for delivery results');
        $this->info('Run: tail -f storage/logs/laravel.log | grep Debug');

        return self::SUCCESS;
    }
}