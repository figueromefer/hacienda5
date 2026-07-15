<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Transaction;
use App\Services\TransactionReferenceGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionReferenceMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_repairs_historical_references_after_a_partial_attempt(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['reference']);
        });
        Schema::drop('transaction_sequences');
        $this->createSequenceTableLeftByPartialAttempt();

        $client = Client::create(['type' => 'active', 'full_name' => 'Cliente de prueba']);
        $ids = [
            'historical_one' => $this->insertTransaction($client->id, 'income', 'NOMBRE DEL CLIENTE'),
            'historical_two' => $this->insertTransaction($client->id, 'income', 'NOMBRE DEL CLIENTE'),
            'null' => $this->insertTransaction($client->id, 'income', null),
            'empty' => $this->insertTransaction($client->id, 'income', ''),
            'valid' => $this->insertTransaction($client->id, 'income', 'ING-2026-000025'),
            'valid_duplicate_one' => $this->insertTransaction($client->id, 'income', 'ING-2026-000010'),
            'valid_duplicate_two' => $this->insertTransaction($client->id, 'income', 'ING-2026-000010'),
            'expense_valid' => $this->insertTransaction($client->id, 'expense', 'GAS-2026-000007'),
            'expense_historical' => $this->insertTransaction($client->id, 'expense', 'PROVEEDOR HISTORICO'),
        ];
        DB::table('transaction_sequences')->insert([
            'year' => 2026,
            'type' => Transaction::TYPE_INCOME,
            'last_number' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $countBefore = DB::table('transactions')->count();

        $this->migration()->up();

        $this->assertSame($countBefore, DB::table('transactions')->count());
        $references = DB::table('transactions')->pluck('reference', 'id');
        $this->assertCount($countBefore, $references->unique());
        $this->assertFalse($references->contains(fn (?string $reference) => blank($reference)));
        $this->assertSame('ING-2026-000025', $references[$ids['valid']]);
        $this->assertSame('ING-2026-000010', $references[$ids['valid_duplicate_one']]);
        $this->assertNotSame('ING-2026-000010', $references[$ids['valid_duplicate_two']]);
        $this->assertSame('GAS-2026-000007', $references[$ids['expense_valid']]);
        $this->assertSame(35, DB::table('transaction_sequences')->where('year', 2026)
            ->where('type', Transaction::TYPE_INCOME)->value('last_number'));
        $this->assertSame(8, DB::table('transaction_sequences')->where('year', 2026)
            ->where('type', Transaction::TYPE_EXPENSE)->value('last_number'));
        $this->assertTrue($this->hasUniqueReferenceIndex());

        $referencesAfterFirstRun = $references->all();
        $this->migration()->up();
        $this->assertSame($referencesAfterFirstRun, DB::table('transactions')->pluck('reference', 'id')->all());

        $generator = app(TransactionReferenceGenerator::class);
        $this->assertSame('ING-2026-000036', DB::transaction(
            fn () => $generator->next(Transaction::TYPE_INCOME, '2026-07-15'),
        ));
        $this->assertSame('GAS-2026-000009', DB::transaction(
            fn () => $generator->next(Transaction::TYPE_EXPENSE, '2026-07-15'),
        ));
    }

    private function insertTransaction(int $clientId, string $type, ?string $reference): int
    {
        return DB::table('transactions')->insertGetId([
            'client_id' => $clientId,
            'type' => $type,
            'scope' => 'operation',
            'transaction_date' => '2026-07-14',
            'amount' => 100,
            'reference' => $reference,
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSequenceTableLeftByPartialAttempt(): void
    {
        Schema::create('transaction_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('type', 20);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['year', 'type']);
        });
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_07_14_000000_create_transaction_sequences_table.php');
    }

    private function hasUniqueReferenceIndex(): bool
    {
        return collect(Schema::getIndexes('transactions'))->contains(
            fn (array $index): bool => $index['unique'] && $index['columns'] === ['reference'],
        );
    }
}
