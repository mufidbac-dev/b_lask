<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiptScan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'inbound_message_id', 'source', 'idempotency_key',
        'original_sha256', 'original_path_encrypted', 'status',
        'confidence_score', 'ocr_result', 'error_message', 'processed_at',
    ];

    protected function casts(): array
    {
        return ['ocr_result' => 'array', 'confidence_score' => 'decimal:2', 'processed_at' => 'datetime'];
    }

    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
}
