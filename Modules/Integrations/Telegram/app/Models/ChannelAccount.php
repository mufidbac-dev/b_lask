<?php

namespace Modules\Integrations\Telegram\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChannelAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'provider', 'provider_account_id', 'provider_chat_id',
        'display_name', 'status', 'metadata', 'linked_at', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'linked_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
