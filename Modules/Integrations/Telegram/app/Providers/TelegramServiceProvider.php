<?php

namespace Modules\Integrations\Telegram\Providers;

use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('Modules/Integrations/Telegram/routes/api.php'));
        $this->loadMigrationsFrom(base_path('Modules/Integrations/Telegram/database/migrations'));
    }
}
