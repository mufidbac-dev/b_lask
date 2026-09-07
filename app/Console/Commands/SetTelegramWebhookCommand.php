<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Integrations\Telegram\Services\TelegramApiClient;

class SetTelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook {url : Public HTTPS webhook URL}';

    protected $description = 'Register the Telegram webhook without exposing the bot token.';

    public function handle(TelegramApiClient $telegram): int
    {
        $secret = (string) config('services.telegram.webhook_secret');
        if ($secret === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET is not configured.');

            return self::FAILURE;
        }

        $telegram->setWebhook((string) $this->argument('url'), $secret);
        $telegram->setMyCommands();
        $this->info('Telegram webhook registered.');
        $this->info('Telegram command menu configured.');

        return self::SUCCESS;
    }
}
