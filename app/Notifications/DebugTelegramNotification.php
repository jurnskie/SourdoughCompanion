<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class DebugTelegramNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $message = 'Debug test notification',
        public bool $useFallback = false
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        if ($this->useFallback) {
            return ['telegram-fallback'];
        }
        
        return [TelegramChannel::class];
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): TelegramMessage
    {
        Log::info('DebugTelegramNotification: Creating Telegram message', [
            'chat_id' => $notifiable->telegram_chat_id,
            'message' => $this->message
        ]);

        $message = "🔧 DEBUG: {$this->message}\n\nTime: " . now()->format('Y-m-d H:i:s');

        return TelegramMessage::create()
            ->to($notifiable->telegram_chat_id)
            ->content($message);
    }

    /**
     * Send notification via direct Telegram API call (fallback method)
     */
    public function toTelegramFallback(object $notifiable): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = $notifiable->telegram_chat_id;
        $message = "🔧 FALLBACK DEBUG: {$this->message}\n\nTime: " . now()->format('Y-m-d H:i:s');

        Log::info('DebugTelegramNotification: Using fallback API', [
            'chat_id' => $chatId,
            'message' => $message
        ]);

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
            ]);

            if ($response->successful()) {
                Log::info('DebugTelegramNotification: Fallback API success', [
                    'response' => $response->json()
                ]);
            } else {
                Log::error('DebugTelegramNotification: Fallback API failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('DebugTelegramNotification: Fallback API exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'debug_telegram',
            'message' => $this->message,
        ];
    }
}