<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'transaction_category_id', 'receipt_scan_id', 'note_id',
        'type', 'description', 'amount', 'currency', 'transaction_date',
        'payment_method', 'metadata',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'transaction_date' => 'date', 'metadata' => 'array'];
    }

    public function category(): BelongsTo { return $this->belongsTo(TransactionCategory::class, 'transaction_category_id'); }
}
