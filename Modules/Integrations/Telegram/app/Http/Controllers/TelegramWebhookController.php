<?php

namespace Modules\Integrations\Telegram\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Integrations\Telegram\Jobs\SendTelegramMessage;
use Modules\Integrations\Telegram\Models\ChannelAccount;
use Modules\Integrations\Telegram\Models\ChannelLinkToken;
use Modules\Integrations\Telegram\Models\InboundMessage;
use Modules\Integrations\Telegram\Models\OutboundMessage;
use Modules\Integrations\Telegram\Services\TelegramApiClient;
use Modules\Integrations\Telegram\Services\TelegramCommandService;

class TelegramWebhookController
{
    public function __construct(
        private readonly TelegramApiClient $telegram,
        private readonly TelegramCommandService $commands,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $configuredSecret = (string) config('services.telegram.webhook_secret');
        if ($configuredSecret === '' || ! hash_equals($configuredSecret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(401, 'Invalid Telegram webhook secret.');
        }

        $update = $request->validate(['update_id' => ['required', 'integer']]);
        $message = $request->input('message');
        if (! is_array($message) || ! isset($message['chat']['id'], $message['from']['id'])) {
            return response()->json(['accepted' => true]);
        }

        $eventId = (string) $update['update_id'];
        $inbound = InboundMessage::firstOrCreate(
            ['provider' => 'telegram', 'provider_event_id' => $eventId],
            [
                'sender_id' => (string) $message['from']['id'],
                'provider_message_id' => isset($message['message_id']) ? (string) $message['message_id'] : null,
                'payload_hash' => hash('sha256', $request->getContent()),
                'payload_encrypted' => Crypt::encryptString($request->getContent()),
                'normalized_text' => $message['text'] ?? null,
                'status' => 'received',
                'received_at' => now(),
            ],
        );

        if (! $inbound->wasRecentlyCreated && $inbound->status !== 'received') {
            return response()->json(['accepted' => true, 'duplicate' => true]);
        }

        $account = ChannelAccount::where('provider', 'telegram')
            ->where('provider_chat_id', (string) $message['chat']['id'])
            ->where('status', 'active')
            ->first();

        $text = trim((string) ($message['text'] ?? ''));
        if (! $account && Str::startsWith($text, '/start ')) {
            $this->linkAccount($message, Str::after($text, '/start '));
            $inbound->update(['status' => 'processed', 'processed_at' => now()]);

            return response()->json(['accepted' => true]);
        }

        if (! $account) {
            $this->telegram->sendMessage(
                (string) $message['chat']['id'],
                'Account belum terhubung. Gunakan link token dari aplikasi.',
            );
            $inbound->update(['status' => 'rejected', 'processed_at' => now()]);

            return response()->json(['accepted' => true]);
        }

        $reply = $this->commands->execute((int) $account->user_id, $text);
        $outbound = OutboundMessage::firstOrCreate(
            ['idempotency_key' => "telegram:inbound:{$inbound->id}"],
            [
                'channel_account_id' => $account->id,
                'inbound_message_id' => $inbound->id,
                'provider' => 'telegram',
                'message_type' => 'text',
                'content_encrypted' => Crypt::encryptString($reply),
                'status' => 'pending',
                'queued_at' => now(),
            ],
        );
        SendTelegramMessage::dispatch($outbound->id);
        $account->update(['last_seen_at' => now()]);
        $inbound->update(['channel_account_id' => $account->id, 'status' => 'processed', 'processed_at' => now()]);

        return response()->json(['accepted' => true]);
    }

    private function linkAccount(array $message, string $plainToken): void
    {
        $token = ChannelLinkToken::where('provider', 'telegram')
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        DB::transaction(function () use ($token, $message): void {
            ChannelAccount::updateOrCreate(
                ['provider' => 'telegram', 'provider_chat_id' => (string) $message['chat']['id']],
                [
                    'user_id' => $token->user_id,
                    'provider_account_id' => (string) $message['from']['id'],
                    'display_name' => trim(($message['from']['first_name'] ?? '').' '.($message['from']['last_name'] ?? '')),
                    'status' => 'active',
                    'linked_at' => now(),
                    'last_seen_at' => now(),
                ],
            );
            $token->update(['status' => 'used', 'used_at' => now()]);
        });
    }
}
