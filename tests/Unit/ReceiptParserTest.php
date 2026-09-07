<?php

use App\Services\ReceiptParser;

it('parses varied Indonesian receipt formats', function (string $text): void {
    $result = (new ReceiptParser)->parse($text, array_fill(0, 8, 90));
    expect($result['merchant_name'])->not->toBeNull()
        ->and($result['total'])->not->toBeNull()
        ->and($result['transaction_date'])->not->toBeNull();
})->with([
    "INDOMARET\n01/09/2026\nAQUA 2 x 3500\nTOTAL 7.000\nQRIS",
    "ALFAMART\n02-09-2026\nROTI 12.500\nSUBTOTAL 12.500\nPPN 1.375\nTOTAL BAYAR 13.875\nTUNAI",
    "TOKO MAKMUR\n03/09/26\nBERAS 5KG 75.000\nJUMLAH 75.000\nCASH",
    "WARUNG BU SITI\n04.09.2026\nNASI GORENG 2 x 15000\nTOTAL 30.000\nTUNAI",
    "NOTA M-12\n05/09/2026\nKOPI 8,000\nTOTAL 8,000\nDANA",
    "SUPERMARKET ABC\n06/09/2026\nSUSU 2 x 10.000\nPISANG 12.000\nPPN 3.200\nGRAND TOTAL 35.200\nDEBIT",
    "KASIR 2\n07-09-2026\nAYAM 45.000\nTOTAL BAYAR: Rp45.000\nQRIS",
    "TOKO KELONTONG\n08/09/2026\nGULA 18.500\nTOTAL 18.500\nTRANSFER",
    "MINIMARKET 24\n09/09/2026\nAIR MINERAL 3 X 4.000\nTOTAL 12.000\nOVO",
    "WARUNG MAKAN SEDERHANA\n10/09/2026\nMIE AYAM 15000\nPAJAK 1500\nTOTAL 16500\nTUNAI",
]);
