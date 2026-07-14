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
            'transactions' => fn ($query) => $query->orderBy('transaction_date')->orderBy('id'),
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
            'events.client',
            'events.transactions' => fn ($query) => $query->orderBy('transaction_date')->orderBy('id'),
            'transactions.event',
        ]);

        $path = $workbook->save($workbook->client($client));
        $filename = 'balance-cliente-'.Str::slug($client->full_name).'.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
