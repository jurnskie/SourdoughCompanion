<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class PhaseTransitionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $starterName,
        public string $fromPhase,
        public string $toPhase
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): TelegramMessage
    {
        $emoji = "🎉";
        $title = "*Starter Milestone!*";
        $body = "Your *{$this->starterName}* has transitioned from _{$this->fromPhase}_ to _{$this->toPhase}_ phase";

        $message = "{$emoji} {$title}\n\n{$body}";

        if ($this->toPhase === 'maintenance') {
            $message .= "\n\n🏆 Congratulations! Your starter is now mature and ready for consistent bread making!";
        }

        return TelegramMessage::create()
            ->to($notifiable->telegram_chat_id)
            ->content($message)
            ->options([
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'phase_transition',
            'starter_name' => $this->starterName,
            'from_phase' => $this->fromPhase,
            'to_phase' => $this->toPhase,
        ];
    }
}
