<form method="post" action="{{ route('receipts.store') }}" enctype="multipart/form-data">
    @csrf
    <label for="receipt">Unggah foto struk</label>
    <input id="receipt" type="file" name="receipt" accept="image/*" required>
    <button type="submit">Proses OCR</button>
</form>
