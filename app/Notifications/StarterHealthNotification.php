<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class StarterHealthNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $starterName,
        public string $healthStatus
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
        $alerts = [
            'excellent' => [
                'emoji' => '✨', 
                'title' => '*Excellent Health!*', 
                'body' => "Your *{$this->starterName}* is thriving! Keep up the good work! 🌟"
            ],
            'good' => [
                'emoji' => '👍', 
                'title' => '*Good Health*', 
                'body' => "Your *{$this->starterName}* is doing well! 😊"
            ],
            'fair' => [
                'emoji' => '⚠️', 
                'title' => '*Needs Attention*', 
                'body' => "Your *{$this->starterName}* needs more regular feeding. Consider adjusting your schedule. 📅"
            ],
            'poor' => [
                'emoji' => '🚨', 
                'title' => '*Health Warning*', 
                'body' => "Your *{$this->starterName}* has been neglected - feed soon! Your starter needs immediate attention. 🆘"
            ],
        ];

        $alert = $alerts[$this->healthStatus] ?? [
            'emoji' => '🍞',
            'title' => '*Starter Update*',
            'body' => "Check on your *{$this->starterName}*"
        ];

        $message = "{$alert['emoji']} {$alert['title']}\n\n{$alert['body']}";

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
            'type' => 'health_alert',
            'starter_name' => $this->starterName,
            'health_status' => $this->healthStatus,
        ];
    }
}
