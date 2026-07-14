<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\TransactionSequence;
use App\Services\TransactionReferenceGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTransactionReferences extends Command
{
    protected $signature = 'transactions:backfill-references
                            {--dry-run : Muestra las referencias sin modificar la base de datos}';

    protected $description = 'Asigna referencias a movimientos históricos que no tienen una';

    public function handle(TransactionReferenceGenerator $generator): int
    {
        $transactions = Transaction::query()
            ->where(fn ($query) => $query->whereNull('reference')->orWhere('reference', ''))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('No hay movimientos históricos sin referencia.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            return $this->preview($transactions, $generator);
        }

        $updated = 0;

        foreach ($transactions as $transaction) {
            DB::transaction(function () use ($generator, $transaction, &$updated): void {
                $locked = Transaction::query()->lockForUpdate()->find($transaction->id);

                if (! $locked || filled($locked->reference)) {
                    return;
                }

                $reference = $generator->next($locked->type, $locked->transaction_date);

                DB::table('transactions')
                    ->where('id', $locked->id)
                    ->where(fn ($query) => $query->whereNull('reference')->orWhere('reference', ''))
                    ->update(['reference' => $reference, 'updated_at' => now()]);

                $this->line('#'.$locked->id.' -> '.$reference);
                $updated++;
            }, 5);
        }

        $this->info($updated.' movimientos actualizados.');

        return self::SUCCESS;
    }

    private function preview($transactions, TransactionReferenceGenerator $generator): int
    {
        $counters = [];

        foreach ($transactions as $transaction) {
            $year = (int) $transaction->transaction_date->format('Y');
            $key = $year.'|'.$transaction->type;

            if (! array_key_exists($key, $counters)) {
                $prefix = Transaction::referencePrefix($transaction->type);
                $stored = (int) (TransactionSequence::query()
                    ->where('year', $year)
                    ->where('type', $transaction->type)
                    ->value('last_number') ?? 0);
                $counters[$key] = max($stored, $generator->lastExistingNumber($prefix, $year));
            }

            $counters[$key]++;
            $reference = sprintf(
                '%s-%04d-%06d',
                Transaction::referencePrefix($transaction->type),
                $year,
                $counters[$key],
            );

            $this->line('#'.$transaction->id.' -> '.$reference);
        }

        $this->info('Dry-run: '.$transactions->count().' movimientos por actualizar. No se realizaron cambios.');

        return self::SUCCESS;
    }
}
