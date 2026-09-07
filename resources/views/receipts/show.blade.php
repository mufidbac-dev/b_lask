<h1>Hasil OCR struk</h1>
<p>Status: {{ $scan->status }} | Confidence: {{ $scan->confidence_score ?? 'menunggu' }}</p>
@if($scan->ocr_result)
<form method="post" action="{{ route('receipts.confirm', $scan) }}">
    @csrf
    <input name="description" value="{{ $scan->ocr_result['merchant_name'] ?? '' }}" required>
    <input name="amount" type="number" step="0.01" value="{{ $scan->ocr_result['total'] ?? '' }}" required>
    <input name="transaction_date" type="date" value="{{ $scan->ocr_result['transaction_date'] ?? now()->toDateString() }}" required>
    <input name="payment_method" value="{{ $scan->ocr_result['payment_method'] ?? '' }}">
    <input name="category" placeholder="Kategori">
    <select name="type"><option value="expense">Pengeluaran</option><option value="income">Pemasukan</option></select>
    <button type="submit">Simpan</button>
</form>
@else
<p>OCR sedang diproses. Muat ulang halaman beberapa saat lagi.</p>
@endif
