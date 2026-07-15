<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Services\FinancialBalanceWorkbook;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinancialBalanceExportController extends Controller
{
    public function event(Event $event, FinancialBalanceWorkbook $workbook): BinaryFileResponse
    {
        $event->load([
            'client',
            'transactions' => fn ($query) => $query->with(['supplier', 'expenseConcept'])->orderBy('transaction_date')->orderBy('id'),
        ]);

        $path = $workbook->save($workbook->event($event));
        $filename = sprintf(
            'balance-evento-%d-%s.xlsx',
            $event->id,
            Str::slug($event->client->full_name),
        );

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function client(Client $client, FinancialBalanceWorkbook $workbook): BinaryFileResponse
    {
        $client->load([
            'events' => fn ($query) => $query->orderBy('event_date')->orderBy('id'),
            'transactions' => fn ($query) => $query->with(['supplier', 'expenseConcept'])->orderBy('transaction_date')->orderBy('id'),
        ]);

        $eventsById = $client->events->keyBy('id');
        $transactionsByEvent = $client->transactions->groupBy('event_id');

        foreach ($client->events as $event) {
            $event->setRelation('client', $client);
            $event->setRelation('transactions', $transactionsByEvent->get($event->id, collect()));
        }

        foreach ($client->transactions as $transaction) {
            $transaction->setRelation('event', $eventsById->get($transaction->event_id));
        }

        $path = $workbook->save($workbook->client($client));
        $filename = 'balance-cliente-'.Str::slug($client->full_name).'.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
