<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Integrations\Telegram\Services\TelegramApiClient;

class SetTelegramCommandsCommand extends Command
{
    protected $signature = 'telegram:set-commands';

    protected $description = 'Configure the Telegram command menu without changing the webhook.';

    public function handle(TelegramApiClient $telegram): int
    {
        $telegram->setMyCommands();
        $this->info('Telegram command menu configured.');

        return self::SUCCESS;
    }
}
