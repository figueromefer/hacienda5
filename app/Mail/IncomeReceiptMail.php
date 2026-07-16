<?php

namespace App\Mail;

use App\Models\Transaction;
use App\Support\SpanishMoney;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class IncomeReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Transaction $transaction)
    {
        $this->transaction->loadMissing(['client', 'event', 'quotation']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Confirmación de abono - Recibo #'.$this->transaction->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.income-receipt',
            with: [
                'transaction' => $this->transaction,
                'client' => $this->transaction->client,
                'event' => $this->transaction->event,
                'amountInWords' => SpanishMoney::toWords((float) $this->transaction->amount),
                'publicUrl' => $this->transaction->receipt_token ? route('receipts.public.show', $this->transaction->receipt_token) : null,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('transactions.receipt-pdf', [
            'transaction' => $this->transaction,
            'receiptTitle' => 'RECIBO DE ANTICIPO',
            'amountInWords' => SpanishMoney::toWords((float) $this->transaction->amount),
            'logoPath' => public_path('images/hacienda-cinco-logo.png'),
            'brandGreen' => '#243834',
            'publicUrl' => $this->transaction->receipt_token ? route('receipts.public.show', $this->transaction->receipt_token) : null,
        ])->setPaper('letter');

        $filename = 'recibo-'.$this->transaction->id.'-'.Str::slug($this->transaction->client?->full_name ?? 'cliente').'.pdf';

        return [
            Attachment::fromData(fn () => $pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}
