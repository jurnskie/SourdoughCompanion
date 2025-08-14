<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class FeedingReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $starterName,
        public int $hoursOverdue = 0
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
        $emoji = "🍞";
        $title = "*Feeding Time!*";
        
        if ($this->hoursOverdue > 0) {
            $message = "{$emoji} {$title}\n\nYour *{$this->starterName}* is ready for feeding\n⏰ *{$this->hoursOverdue}h overdue*";
        } else {
            $message = "{$emoji} {$title}\n\nTime to feed your *{$this->starterName}*";
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
            'type' => 'feeding_reminder',
            'starter_name' => $this->starterName,
            'hours_overdue' => $this->hoursOverdue,
        ];
    }
}
