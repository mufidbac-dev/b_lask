<?php

namespace Modules\Integrations\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundMessage extends Model
{
    protected $fillable = [
        'channel_account_id', 'provider', 'provider_event_id', 'provider_message_id',
        'sender_id', 'payload_hash', 'payload_encrypted', 'normalized_text',
        'status', 'received_at', 'processed_at',
    ];

    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'processed_at' => 'datetime'];
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }
}
