<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from'],
        ]);
        $rows = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->whereBetween('transaction_date', [$data['from'], $data['to']])
            ->with('category:id,name')
            ->get()
            ->groupBy(fn (Transaction $transaction): string => $transaction->type.':'.($transaction->category?->name ?? 'Tanpa kategori'))
            ->map(fn ($group): array => [
                'type' => $group->first()->type,
                'category' => $group->first()->category?->name ?? 'Tanpa kategori',
                'total' => (float) $group->sum('amount'),
            ])->values();
        return response()->json([
            'from' => $data['from'], 'to' => $data['to'],
            'income' => (float) $rows->where('type', 'income')->sum('total'),
            'expense' => (float) $rows->where('type', 'expense')->sum('total'),
            'by_category' => $rows,
        ]);
    }
}
