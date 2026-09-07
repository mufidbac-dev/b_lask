<?php

use Illuminate\Support\Facades\Route;
use Modules\Integrations\Telegram\Http\Controllers\TelegramLinkController;
use Modules\Integrations\Telegram\Http\Controllers\TelegramWebhookController;

Route::post('/api/v1/integrations/telegram/webhook', TelegramWebhookController::class)
    ->middleware('throttle:30,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/api/v1/integrations/telegram/link-token', [TelegramLinkController::class, 'store'])
        ->middleware('abilities:profile:read');
});
