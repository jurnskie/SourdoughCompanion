<?php

namespace App\Jobs;

use App\Models\Starter;
use App\Models\User;
use App\Notifications\FeedingReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendFeedingReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $starterId,
        private int $userId,
        private int $hoursOverdue = 0
    ) {}

    public function handle(): void
    {
        $starter = Starter::find($this->starterId);
        $user = User::find($this->userId);
        
        if (!$starter || !$user) {
            Log::warning('SendFeedingReminderJob: Starter or User not found', [
                'starter_id' => $this->starterId,
                'user_id' => $this->userId
            ]);
            return;
        }

        if (!$user->telegram_chat_id) {
            Log::info('SendFeedingReminderJob: User has no Telegram chat ID', [
                'user_id' => $this->userId
            ]);
            return;
        }

        // Check if starter still needs feeding
        $canFeed = $starter->canFeedNow();
        if (!$canFeed['can_feed']) {
            Log::info('SendFeedingReminderJob: Starter no longer needs feeding', [
                'starter_id' => $this->starterId,
                'reason' => $canFeed['reason']
            ]);
            return;
        }

        $user->notify(new FeedingReminderNotification(
            $starter->name,
            $this->hoursOverdue
        ));
    }
}