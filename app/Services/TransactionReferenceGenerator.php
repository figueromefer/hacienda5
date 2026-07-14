<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionSequence;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class TransactionReferenceGenerator
{
    public function next(string $type, CarbonInterface|DateTimeInterface|string $date): string
    {
        $year = (int) date_create_immutable($date instanceof DateTimeInterface ? $date->format('Y-m-d') : $date)->format('Y');
        $prefix = Transaction::referencePrefix($type);

        $lastExistingNumber = $this->lastExistingNumber($prefix, $year);
        $now = now();

        DB::table('transaction_sequences')->insertOrIgnore([
            'year' => $year,
            'type' => $type,
            'last_number' => $lastExistingNumber,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sequence = TransactionSequence::query()
            ->where('year', $year)
            ->where('type', $type)
            ->lockForUpdate()
            ->firstOrFail();

        if ($sequence->last_number < $lastExistingNumber) {
            $sequence->last_number = $lastExistingNumber;
        }

        $sequence->last_number++;
        $sequence->save();

        return sprintf('%s-%04d-%06d', $prefix, $year, $sequence->last_number);
    }

    public function lastExistingNumber(string $prefix, int $year): int
    {
        $pattern = sprintf('/^%s-%04d-(\d{6,})$/', preg_quote($prefix, '/'), $year);

        return Transaction::query()
            ->where('reference', 'like', sprintf('%s-%04d-%%', $prefix, $year))
            ->pluck('reference')
            ->reduce(function (int $maximum, ?string $reference) use ($pattern): int {
                if ($reference !== null && preg_match($pattern, $reference, $matches) === 1) {
                    return max($maximum, (int) $matches[1]);
                }

                return $maximum;
            }, 0);
    }
}
