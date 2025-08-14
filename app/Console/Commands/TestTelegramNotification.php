<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\FeedingReminderNotification;
use Illuminate\Console\Command;

class TestTelegramNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test {chat_id : Telegram chat ID to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Telegram notifications by sending a test message to the specified chat ID';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chatId = $this->argument('chat_id');
        
        $this->info("Testing Telegram notification to chat ID: {$chatId}");
        
        // Create a temporary user object for testing
        $testUser = new User();
        $testUser->telegram_chat_id = $chatId;
        $testUser->name = 'Test User';
        
        try {
            // Send a test notification
            $testUser->notify(new FeedingReminderNotification('Test Starter', 0));
            
            $this->info('✅ Test notification sent successfully!');
            $this->info('Check your Telegram to see if you received the message.');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to send notification:');
            $this->error($e->getMessage());
            
            // Check common issues
            $this->line('');
            $this->info('Common issues:');
            $this->info('• Make sure TELEGRAM_BOT_TOKEN is set in your .env file');
            $this->info('• Verify you have started a conversation with your bot');
            $this->info('• Check if your chat ID is correct');
        }
    }
}
