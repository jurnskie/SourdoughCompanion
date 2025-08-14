<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\BreadProofingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendBreadProofingAlertJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $userId,
        private string $stage,
        private int $minutesRemaining = 0
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        
        if (!$user) {
            Log::warning('SendBreadProofingAlertJob: User not found', [
                'user_id' => $this->userId
            ]);
            return;
        }

        if (!$user->telegram_chat_id) {
            Log::info('SendBreadProofingAlertJob: User has no Telegram chat ID', [
                'user_id' => $this->userId
            ]);
            return;
        }

        $user->notify(new BreadProofingNotification(
            $this->stage,
            $this->minutesRemaining
        ));
    }
}