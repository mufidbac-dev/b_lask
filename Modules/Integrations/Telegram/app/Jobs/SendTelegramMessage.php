<?php

namespace Modules\Integrations\Telegram\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Modules\Integrations\Telegram\Models\OutboundMessage;
use Modules\Integrations\Telegram\Services\TelegramApiClient;
use Throwable;

class SendTelegramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $outboundMessageId) {}

    public function handle(TelegramApiClient $telegram): void
    {
        $message = OutboundMessage::findOrFail($this->outboundMessageId);
        $message->increment('attempts');
        $message->update(['status' => 'sending']);

        $telegram->sendMessage(
            (string) $message->channelAccount->provider_chat_id,
            Crypt::decryptString($message->content_encrypted),
        );

        $message->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        OutboundMessage::whereKey($this->outboundMessageId)->update([
            'status' => 'failed',
            'last_error' => mb_substr($exception->getMessage(), 0, 1000),
        ]);
    }
}
