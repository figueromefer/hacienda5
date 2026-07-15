<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaction_sequences')) {
            Schema::create('transaction_sequences', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('year');
                $table->string('type', 20);
                $table->unsignedBigInteger('last_number')->default(0);
                $table->timestamps();

                $table->unique(['year', 'type']);
            });
        }

        DB::transaction(function (): void {
            $transactions = DB::table('transactions')->select(['id', 'type', 'transaction_date', 'reference'])
                ->orderBy('transaction_date')->orderBy('id')->lockForUpdate()->get();
            $validReferences = [];
            $maximumNumbers = [];
            $transactionsToBackfill = [];

            foreach ($transactions as $transaction) {
                $year = (int) substr((string) $transaction->transaction_date, 0, 4);
                $prefix = $transaction->type === 'income' ? 'ING' : 'GAS';
                $pattern = sprintf('/^%s-%04d-(\d{6})$/', $prefix, $year);

                if (is_string($transaction->reference)
                    && preg_match($pattern, $transaction->reference, $matches) === 1
                    && ! isset($validReferences[$transaction->reference])) {
                    $validReferences[$transaction->reference] = true;
                    $key = $year.'|'.$transaction->type;
                    $maximumNumbers[$key] = max($maximumNumbers[$key] ?? 0, (int) $matches[1]);

                    continue;
                }

                $transactionsToBackfill[] = [$transaction->id, $transaction->type, $year, $prefix];
            }

            foreach ($transactionsToBackfill as [$id, $type, $year, $prefix]) {
                $key = $year.'|'.$type;
                $now = now();
                DB::table('transaction_sequences')->insertOrIgnore([
                    'year' => $year, 'type' => $type, 'last_number' => $maximumNumbers[$key] ?? 0,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $sequence = DB::table('transaction_sequences')->where('year', $year)
                    ->where('type', $type)->lockForUpdate()->first();
                $nextNumber = max((int) $sequence->last_number, $maximumNumbers[$key] ?? 0) + 1;
                DB::table('transaction_sequences')->where('id', $sequence->id)
                    ->update(['last_number' => $nextNumber, 'updated_at' => $now]);
                DB::table('transactions')->where('id', $id)
                    ->update(['reference' => sprintf('%s-%04d-%06d', $prefix, $year, $nextNumber)]);
                $maximumNumbers[$key] = $nextNumber;
            }

            foreach ($maximumNumbers as $key => $maximumNumber) {
                [$year, $type] = explode('|', $key, 2);
                $now = now();
                DB::table('transaction_sequences')->insertOrIgnore([
                    'year' => $year, 'type' => $type, 'last_number' => $maximumNumber,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('transaction_sequences')->where('year', $year)->where('type', $type)
                    ->where('last_number', '<', $maximumNumber)
                    ->update(['last_number' => $maximumNumber, 'updated_at' => $now]);
            }
        }, 5);

        if (! $this->hasUniqueReferenceIndex()) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unique('reference');
            });
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['reference']);
        });

        Schema::dropIfExists('transaction_sequences');
    }

    private function hasUniqueReferenceIndex(): bool
    {
        foreach (Schema::getIndexes('transactions') as $index) {
            if ($index['unique'] && $index['columns'] === ['reference']) {
                return true;
            }
        }

        return false;
    }
};
