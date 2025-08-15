<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        // Use direct API method since package has delivery issues
        return ['telegram-direct'];
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
     * Send notification via direct Telegram API (reliable method)
     */
    public function toTelegramDirect(object $notifiable): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = $notifiable->telegram_chat_id;

        // Create message content
        $emoji = "🍞";
        $title = "*Feeding Time!*";
        
        if ($this->hoursOverdue > 0) {
            $message = "{$emoji} {$title}\n\nYour *{$this->starterName}* is ready for feeding\n⏰ *{$this->hoursOverdue}h overdue*";
        } else {
            $message = "{$emoji} {$title}\n\nTime to feed your *{$this->starterName}*";
        }

        Log::info('FeedingReminderNotification: Sending via direct API', [
            'chat_id' => $chatId,
            'starter_name' => $this->starterName,
            'hours_overdue' => $this->hoursOverdue
        ]);

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful()) {
                Log::info('FeedingReminderNotification: Sent successfully via direct API', [
                    'chat_id' => $chatId,
                    'message_id' => $response->json()['result']['message_id'] ?? null,
                    'starter_name' => $this->starterName,
                    'hours_overdue' => $this->hoursOverdue
                ]);
            } else {
                Log::error('FeedingReminderNotification: Failed to send via direct API', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'starter_name' => $this->starterName,
                    'hours_overdue' => $this->hoursOverdue
                ]);
                
                // Throw exception to trigger job retry
                throw new \Exception("Telegram API returned status: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('FeedingReminderNotification: Exception during direct API call', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'starter_name' => $this->starterName,
                'hours_overdue' => $this->hoursOverdue
            ]);
            
            throw $e;
        }
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
