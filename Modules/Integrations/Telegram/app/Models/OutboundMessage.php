<?php

namespace Modules\Integrations\Telegram\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundMessage extends Model
{
    protected $fillable = [
        'channel_account_id', 'inbound_message_id', 'provider', 'provider_message_id',
        'idempotency_key', 'message_type', 'content_encrypted', 'status', 'attempts',
        'last_error', 'queued_at', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['queued_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function channelAccount()
    {
        return $this->belongsTo(ChannelAccount::class);
    }
}
