<?php

namespace App\Jobs;

use App\Models\ReceiptScan;
use App\Services\ReceiptOcrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Integrations\Telegram\Jobs\SendTelegramMessage;
use Modules\Integrations\Telegram\Models\InboundMessage;
use Modules\Integrations\Telegram\Models\OutboundMessage;
use Throwable;

class ProcessReceiptScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly int $receiptScanId) {}

    public function handle(ReceiptOcrService $ocr): void
    {
        $scan = ReceiptScan::findOrFail($this->receiptScanId);
        if (in_array($scan->status, ['confirmed', 'cancelled'], true)) return;
        $scan->update(['status' => 'processing', 'error_message' => null]);
        try {
            $result = $ocr->process($scan);
            $scan->update([
                'ocr_result' => $result,
                'confidence_score' => $result['confidence_score'],
                'status' => $result['confidence_score'] >= (float) config('ocr.review_threshold', 70) ? 'awaiting_confirmation' : 'pending_review',
                'processed_at' => now(),
            ]);
            if ($scan->inbound_message_id) {
                $inbound = InboundMessage::with('channelAccount')->find($scan->inbound_message_id);
                $account = $inbound?->channelAccount;
                if ($inbound && $account) {
                    $summary = "Struk {$result['merchant_name']}\nTotal: Rp ".number_format((float) ($result['total'] ?? 0), 0, ',', '.').
                        "\nTanggal: ".($result['transaction_date'] ?? '-').
                        "\nBalas: Simpan / Edit / Batal";
                    $outbound = OutboundMessage::firstOrCreate(
                        ['idempotency_key' => "telegram:receipt:result:{$scan->id}"],
                        [
                            'channel_account_id' => $account->id, 'inbound_message_id' => $inbound->id,
                            'provider' => 'telegram', 'message_type' => 'receipt_result',
                            'content_encrypted' => Crypt::encryptString($summary),
                            'status' => 'pending', 'queued_at' => now(),
                        ],
                    );
                    SendTelegramMessage::dispatch($outbound->id);
                }
            }
        } catch (Throwable $exception) {
            $scan->update(['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 1000)]);
            throw $exception;
        }
    }
}
