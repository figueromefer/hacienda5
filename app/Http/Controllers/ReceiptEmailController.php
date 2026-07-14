<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendReceiptEmailRequest;
use App\Models\ReceiptEmailLog;
use App\Models\Transaction;
use App\Services\ReceiptEmailSender;

class ReceiptEmailController extends Controller
{
    public function create(Transaction $transaction)
    {
        $this->ensureEmailable($transaction);
        $transaction->load(['client.user', 'event.client.user', 'receiptEmailLogs.sender']);
        $lastLog = $transaction->receiptEmailLogs->first();

        return view('transactions.email', [
            'transaction' => $transaction,
            'toRecipients' => old('receipt_to', $lastLog
                ? implode(', ', $lastLog->to_recipients)
                : implode(', ', $this->suggestedRecipients($transaction))),
            'ccRecipients' => old('receipt_cc', $lastLog ? implode(', ', $lastLog->cc_recipients) : ''),
        ]);
    }

    public function store(
        SendReceiptEmailRequest $request,
        Transaction $transaction,
        ReceiptEmailSender $sender,
    ) {
        $this->ensureEmailable($transaction);

        $log = $sender->send(
            $transaction->load(['client', 'event', 'quotation']),
            $request->user(),
            $request->string('receipt_to')->toString(),
            $request->string('receipt_cc')->toString(),
        );

        if ($log === null) {
            return redirect()->route('transactions.show', $transaction)
                ->with('info', 'No se seleccionaron destinatarios; el recibo no se envió.');
        }

        $message = $log->status === ReceiptEmailLog::STATUS_SENT
            ? 'Recibo enviado correctamente.'
            : 'El movimiento se conserva, pero el correo no pudo enviarse. Puedes reintentarlo desde este recibo.';

        return redirect()->route('transactions.show', $transaction)
            ->with($log->status === ReceiptEmailLog::STATUS_SENT ? 'success' : 'warning', $message);
    }

    private function ensureEmailable(Transaction $transaction): void
    {
        abort_unless(
            $transaction->type === Transaction::TYPE_INCOME && $transaction->status === 'paid',
            422,
            'Sólo se pueden enviar recibos de ingresos pagados.',
        );
    }

    private function suggestedRecipients(Transaction $transaction): array
    {
        return collect([
            $transaction->client?->email,
            $transaction->client?->user?->email,
            $transaction->event?->client?->email,
            $transaction->event?->client?->user?->email,
        ])->filter()->unique(fn (string $email) => strtolower($email))->values()->all();
    }
}
