<?php

namespace App\Services;

use Carbon\Carbon;

class ReceiptParser
{
    public function parse(string $text, array $wordConfidences = []): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: [])));
        $merchant = $this->merchant($lines);
        $date = $this->date($text);
        $items = $this->items($lines);
        $subtotal = $this->moneyAfter($text, ['subtotal', 'sub total', 'jumlah']);
        $tax = $this->moneyAfter($text, ['ppn', 'pajak', 'tax']);
        $total = $this->moneyAfter($text, ['grand total', 'total bayar', 'total', 'jumlah']) ?? $subtotal;
        $payment = $this->payment($text);
        $fields = [$merchant, $date, $items !== [], $subtotal !== null, $tax !== null, $total !== null, $payment !== null];
        $completeness = count(array_filter($fields)) / count($fields);
        $ocrConfidence = $wordConfidences === [] ? 0.0 : array_sum($wordConfidences) / count($wordConfidences);

        return [
            'merchant_name' => $merchant,
            'transaction_date' => $date,
            'items' => $items,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'payment_method' => $payment,
            'confidence_score' => round(($ocrConfidence * 0.6) + ($completeness * 100 * 0.4), 2),
            'raw_text' => $text,
        ];
    }

    private function merchant(array $lines): ?string
    {
        foreach (array_slice($lines, 0, 5) as $line) {
            if (! preg_match('/^(struk|nota|receipt|telp|phone|kasir|tanggal|date)\b/i', $line) && ! preg_match('/\d{3,}/', $line)) {
                return mb_substr($line, 0, 160);
            }
        }
        return $lines[0] ?? null;
    }

    private function date(string $text): ?string
    {
        if (preg_match('/\b(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})\b/', $text, $m)) {
            $year = strlen($m[3]) === 2 ? '20'.$m[3] : $m[3];
            try {
                return Carbon::createSafe((int) $year, (int) $m[2], (int) $m[1])->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }
        return null;
    }

    private function items(array $lines): array
    {
        $items = [];
        foreach ($lines as $line) {
            if (preg_match('/^(.+?)\s+(?:(\d+)\s*[xX]\s*)?(?:Rp\.?\s*)?([\d.,]+)$/i', $line, $m)) {
                $name = trim($m[1]);
                if (preg_match('/(total|subtotal|jumlah|ppn|pajak|bayar|kembali)/i', $name)) continue;
                $items[] = ['name' => $name, 'quantity' => (int) ($m[2] ?: 1), 'price' => $this->number($m[3])];
            }
        }
        return $items;
    }

    private function moneyAfter(string $text, array $labels): ?float
    {
        $label = implode('|', array_map(fn ($v) => preg_quote($v, '/'), $labels));
        return preg_match("/(?:{$label})\s*[:\-]?\s*(?:rp\.?\s*)?([\d.,]+)/iu", $text, $m) ? $this->number($m[1]) : null;
    }

    private function payment(string $text): ?string
    {
        if (preg_match('/\b(qris|gopay|go-pay|ovo|dana|shopeepay|debit|kartu kredit|credit card|transfer|tunai|cash)\b/i', $text, $m)) return strtoupper($m[1]);
        return null;
    }

    private function number(string $value): float
    {
        $value = preg_replace('/[^\d]/', '', $value) ?? '0';
        return (float) $value;
    }
}
