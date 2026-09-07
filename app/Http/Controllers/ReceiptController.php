<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessReceiptScan;
use App\Models\ReceiptScan;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function create(): View { return view('receipts.create'); }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['receipt' => ['required', 'file', 'image', 'max:10240']]);
        $bytes = file_get_contents($request->file('receipt')->getRealPath());
        $hash = hash('sha256', $bytes);
        $existing = ReceiptScan::where('user_id', $request->user()->id)->where('original_sha256', $hash)->first();
        if ($existing) return redirect()->route('receipts.show', $existing);

        $path = 'receipts/'.$request->user()->id.'/'.$hash.'.enc';
        Storage::disk(config('ocr.private_disk'))->put($path, Crypt::encryptString(base64_encode($bytes)));
        $scan = ReceiptScan::create([
            'user_id' => $request->user()->id, 'source' => 'web',
            'idempotency_key' => hash('sha256', $request->user()->id.':'.$hash),
            'original_sha256' => $hash, 'original_path_encrypted' => Crypt::encryptString($path),
        ]);
        ProcessReceiptScan::dispatch($scan->id);
        return redirect()->route('receipts.show', $scan);
    }

    public function show(Request $request, ReceiptScan $receipt): View
    {
        abort_unless($receipt->user_id === $request->user()->id, 403);
        return view('receipts.show', ['scan' => $receipt->fresh()]);
    }

    public function confirm(Request $request, ReceiptScan $receipt): RedirectResponse
    {
        abort_unless($receipt->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'type' => ['required', 'in:income,expense'], 'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'], 'transaction_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'], 'category' => ['nullable', 'string', 'max:100'],
        ]);
        DB::transaction(function () use ($receipt, $data, $request): void {
            $category = $data['category'] ? TransactionCategory::firstOrCreate(
                ['user_id' => $request->user()->id, 'name' => $data['category']],
                ['type' => $data['type']]
            ) : null;
            Transaction::create([
                'user_id' => $request->user()->id, 'receipt_scan_id' => $receipt->id,
                'transaction_category_id' => $category?->id, 'type' => $data['type'],
                'description' => $data['description'], 'amount' => $data['amount'],
                'transaction_date' => $data['transaction_date'], 'payment_method' => $data['payment_method'],
            ]);
            $receipt->update(['status' => 'confirmed']);
        });
        return redirect()->route('receipts.show', $receipt)->with('status', 'Transaksi tersimpan.');
    }
}
