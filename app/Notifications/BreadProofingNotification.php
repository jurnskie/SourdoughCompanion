<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class BreadProofingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $stage,
        public int $minutesRemaining = 0
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
            'bulk_fermentation' => [
                'emoji' => '🍞',
                'title' => '*Bulk Fermentation Ready*',
                'body' => $this->minutesRemaining > 0 
                    ? "Bulk fermentation complete in *{$this->minutesRemaining} minutes*"
                    : "Time to shape your dough! 🙌"
            ],
            'final_proof' => [
                'emoji' => '🔥',
                'title' => '*Ready to Bake!*',
                'body' => $this->minutesRemaining > 0
                    ? "Final proof complete in *{$this->minutesRemaining} minutes*"
                    : "Your bread is ready for the oven! 🔥"
            ],
            'baking' => [
                'emoji' => '✨',
                'title' => '*Baking Complete*',
                'body' => "Your sourdough is ready! Let it cool before slicing. 🧊"
            ]
        ];

        $alert = $alerts[$this->stage] ?? [
            'emoji' => '🍞',
            'title' => '*Bread Update*',
            'body' => "Check on your bread!"
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
            'type' => 'bread_proofing',
            'stage' => $this->stage,
            'minutes_remaining' => $this->minutesRemaining,
        ];
    }
}
