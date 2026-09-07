<?php

namespace App\Services;

use App\Models\ReceiptScan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use RuntimeException;

class ReceiptOcrService
{
    public function __construct(private readonly ReceiptParser $parser) {}

    public function process(ReceiptScan $scan): array
    {
        $disk = Storage::disk((string) config('ocr.private_disk', 'local'));
        $path = Crypt::decryptString($scan->original_path_encrypted);
        $encoded = Crypt::decryptString($disk->get($path));
        // $encrypted = $disk->get(Crypt::decryptString($scan->original_path_encrypted));
        $bytes = base64_decode($encoded, true);
        if ($bytes === false) throw new RuntimeException('Receipt image could not be decrypted.');

        $input = tempnam(sys_get_temp_dir(), 'receipt-').'.bin';
        file_put_contents($input, $bytes);
        try {
            $process = new Process([
                (string) env('OCR_PYTHON_BINARY', 'python'),
                base_path('ocr/receipt_ocr.py'),
                $input,
            ], base_path(), ['PYTHONIOENCODING' => 'utf-8'], 120);
            $process->mustRun();
            $payload = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            return $this->parser->parse((string) ($payload['text'] ?? ''), $payload['confidences'] ?? []);
        } finally {
            @unlink($input);
        }
    }
}
