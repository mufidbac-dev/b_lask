<?php

namespace Modules\Integrations\Telegram\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Integrations\Telegram\Models\ChannelLinkToken;

class TelegramLinkController
{
    public function store(Request $request): JsonResponse
    {
        $plainToken = Str::random(48);
        $expiresAt = now()->addMinutes(10);

        ChannelLinkToken::where('user_id', $request->user()->id)
            ->where('provider', 'telegram')
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        ChannelLinkToken::create([
            'user_id' => $request->user()->id,
            'provider' => 'telegram',
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'provider' => 'telegram',
            'token' => $plainToken,
            'expires_at' => $expiresAt->toISOString(),
        ], 201);
    }
}
