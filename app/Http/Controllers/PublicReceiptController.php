<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Support\SpanishMoney;

class PublicReceiptController extends Controller
{
    public function show(string $token)
    {
        $transaction = Transaction::with(['client', 'event', 'quotation'])
            ->where('receipt_token', $token)
            ->firstOrFail();

        return view('receipts.public-show', [
            'transaction' => $transaction,
            'receiptTitle' => $transaction->type === Transaction::TYPE_INCOME ? 'RECIBO DE ANTICIPO' : 'RECIBO PAGO TRABAJOS',
            'amountInWords' => SpanishMoney::toWords((float) $transaction->amount),
            'brandGreen' => '#243834',
        ]);
    }
}
