<?php

namespace App\Services;

use App\Mail\IncomeReceiptMail;
use App\Models\ReceiptEmailLog;
use App\Models\Transaction;
use App\Models\User;
use App\Support\ReceiptRecipients;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ReceiptEmailSender
{
    public function send(Transaction $transaction, User $sender, ?string $to, ?string $cc): ?ReceiptEmailLog
    {
        $recipients = ReceiptRecipients::normalize($to, $cc);

        if ($recipients['to'] === [] && $recipients['cc'] === []) {
            return null;
        }

        try {
            Mail::to($recipients['to'])
                ->cc($recipients['cc'])
                ->send(new IncomeReceiptMail($transaction));

            return $transaction->receiptEmailLogs()->create([
                'sent_by' => $sender->id,
                'to_recipients' => $recipients['to'],
                'cc_recipients' => $recipients['cc'],
                'status' => ReceiptEmailLog::STATUS_SENT,
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar el recibo de ingreso.', [
                'transaction_id' => $transaction->id,
                'to_recipients' => $recipients['to'],
                'cc_recipients' => $recipients['cc'],
                'error' => $exception->getMessage(),
            ]);

            return $transaction->receiptEmailLogs()->create([
                'sent_by' => $sender->id,
                'to_recipients' => $recipients['to'],
                'cc_recipients' => $recipients['cc'],
                'status' => ReceiptEmailLog::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
