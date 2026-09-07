<?php

namespace Modules\Integrations\Telegram\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramApiClient
{
    private function client(): PendingRequest
    {
        $token = (string) config('services.telegram.bot_token');

        if ($token === '') {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        return Http::baseUrl("https://api.telegram.org/bot{$token}")
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 200);
    }

    public function sendMessage(string $chatId, string $text): array
    {
        return (array) $this->client()->post('/sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ])->throw()->json();
    }

    public function setWebhook(string $url, string $secret): array
    {
        return (array) $this->client()->post('/setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message'],
        ])->throw()->json();
    }

    public function setMyCommands(): array
    {
        return (array) $this->client()->post('/setMyCommands', [
            'commands' => [
                ['command' => 'help', 'description' => 'Show available commands'],
                ['command' => 'task', 'description' => 'Create a task'],
                ['command' => 'list', 'description' => 'List open tasks'],
                ['command' => 'done', 'description' => 'Complete a task'],
            ],
        ])->throw()->json();
    }
}
