<?php

namespace App\Jobs;

use App\Models\Starter;
use App\Models\User;
use App\Notifications\PhaseTransitionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendPhaseTransitionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $starterId,
        private int $userId,
        private string $fromPhase,
        private string $toPhase
    ) {}

    public function handle(): void
    {
        $starter = Starter::find($this->starterId);
        $user = User::find($this->userId);
        
        if (!$starter || !$user) {
            Log::warning('SendPhaseTransitionJob: Starter or User not found', [
                'starter_id' => $this->starterId,
                'user_id' => $this->userId
            ]);
            return;
        }

        if (!$user->telegram_chat_id) {
            Log::info('SendPhaseTransitionJob: User has no Telegram chat ID', [
                'user_id' => $this->userId
            ]);
            return;
        }

        $user->notify(new PhaseTransitionNotification(
            $starter->name,
            $this->fromPhase,
            $this->toPhase
        ));
    }
}