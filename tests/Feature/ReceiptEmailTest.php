<?php

namespace Tests\Feature;

use App\Mail\IncomeReceiptMail;
use App\Models\Client;
use App\Models\Event;
use App\Models\ReceiptEmailLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReceiptEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('mail.receipt_copy', 'info@haciendacinco.mx');
    }

    public function test_paid_income_can_be_sent_to_a_gmail_and_is_logged(): void
    {
        Mail::fake();
        $user = $this->authorizedUser();
        $client = $this->client();

        $this->actingAs($user)->post(route('transactions.store'), $this->payload($client, [
            'receipt_to' => 'persona@gmail.com',
        ]))->assertRedirect(route('transactions.index'));

        $transaction = Transaction::firstOrFail();

        Mail::assertSent(IncomeReceiptMail::class, fn (IncomeReceiptMail $mail) => $mail->hasTo('persona@gmail.com')
            && $mail->hasCc('info@haciendacinco.mx'));
        $this->assertDatabaseHas('receipt_email_logs', [
            'transaction_id' => $transaction->id,
            'sent_by' => $user->id,
            'status' => ReceiptEmailLog::STATUS_SENT,
        ]);
        $this->assertNotNull($transaction->receiptEmailLogs()->first()->sent_at);
    }

    public function test_multiple_recipients_cc_and_duplicates_are_normalized(): void
    {
        Mail::fake();
        $user = $this->authorizedUser();
        $client = $this->client();

        $this->actingAs($user)->post(route('transactions.store'), $this->payload($client, [
            'receipt_to' => 'UNO@gmail.com, dos@example.com, uno@gmail.com, info@haciendacinco.mx',
            'receipt_cc' => "dos@example.com; copia@example.com\nCOPIA@example.com, info@haciendacinco.mx",
        ]))->assertRedirect();

        $log = ReceiptEmailLog::firstOrFail();

        $this->assertSame(['UNO@gmail.com', 'dos@example.com', 'info@haciendacinco.mx'], $log->to_recipients);
        $this->assertSame(['copia@example.com'], $log->cc_recipients);

        Mail::assertSent(IncomeReceiptMail::class, function (IncomeReceiptMail $mail): bool {
            return $mail->hasTo('UNO@gmail.com')
                && $mail->hasTo('dos@example.com')
                && $mail->hasTo('info@haciendacinco.mx')
                && $mail->hasCc('copia@example.com');
        });
    }

    public function test_institutional_copy_is_added_once_when_it_is_already_in_cc(): void
    {
        Mail::fake();
        $user = $this->authorizedUser();
        $client = $this->client();

        $this->actingAs($user)->post(route('transactions.store'), $this->payload($client, [
            'receipt_to' => 'cliente@example.com',
            'receipt_cc' => 'INFO@haciendacinco.mx, info@haciendacinco.mx',
        ]));

        $log = ReceiptEmailLog::firstOrFail();
        $this->assertCount(1, $log->cc_recipients);
        $this->assertSame('INFO@haciendacinco.mx', $log->cc_recipients[0]);
    }

    public function test_no_recipients_saves_movement_without_sending_or_logging_failure(): void
    {
        Mail::fake();
        $user = $this->authorizedUser();
        $client = $this->client();

        $response = $this->actingAs($user)->post(route('transactions.store'), $this->payload($client));

        $response->assertRedirect(route('transactions.index'))
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'no se envió'));
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('receipt_email_logs', 0);
        Mail::assertNothingSent();
    }

    public function test_every_email_is_validated_before_the_movement_is_created(): void
    {
        Mail::fake();
        $user = $this->authorizedUser();
        $client = $this->client();

        $this->actingAs($user)->from(route('transactions.create'))->post(
            route('transactions.store'),
            $this->payload($client, ['receipt_to' => 'valido@gmail.com, correo-invalido']),
        )->assertRedirect(route('transactions.create'))->assertSessionHasErrors('receipt_to');

        $this->assertDatabaseCount('transactions', 0);
        Mail::assertNothingSent();
    }

    public function test_smtp_failure_keeps_movement_and_records_failed_attempt(): void
    {
        $user = $this->authorizedUser();
        $client = $this->client();

        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP no disponible'));

        $this->actingAs($user)->post(route('transactions.store'), $this->payload($client, [
            'receipt_to' => 'cliente@gmail.com',
        ]))->assertRedirect(route('transactions.index'))->assertSessionHas('warning');

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('receipt_email_logs', [
            'status' => ReceiptEmailLog::STATUS_FAILED,
            'error_message' => 'SMTP no disponible',
        ]);
    }

    public function test_failed_receipt_can_be_retried_with_editable_recipients(): void
    {
        Mail::fake();
        $user = $this->authorizedUser();
        $transaction = $this->transaction($this->client());
        $transaction->receiptEmailLogs()->create([
            'sent_by' => $user->id,
            'to_recipients' => ['anterior@example.com'],
            'cc_recipients' => ['info@haciendacinco.mx'],
            'status' => ReceiptEmailLog::STATUS_FAILED,
            'error_message' => 'Error previo',
        ]);

        $this->actingAs($user)->get(route('transactions.email.create', $transaction))
            ->assertOk()
            ->assertSee('Reenviar recibo')
            ->assertSee('anterior@example.com');

        $this->actingAs($user)->post(route('transactions.email.store', $transaction), [
            'receipt_to' => 'nuevo@gmail.com, segundo@example.com',
            'receipt_cc' => 'contabilidad@example.com',
        ])->assertRedirect(route('transactions.show', $transaction))->assertSessionHas('success');

        $this->assertDatabaseCount('receipt_email_logs', 2);
        $this->assertSame(ReceiptEmailLog::STATUS_SENT, $transaction->receiptEmailLogs()->first()->status);
        Mail::assertSent(IncomeReceiptMail::class, fn (IncomeReceiptMail $mail) => $mail->hasTo('nuevo@gmail.com')
            && $mail->hasTo('segundo@example.com')
            && $mail->hasCc('contabilidad@example.com'));
    }

    public function test_editing_a_movement_never_sends_email(): void
    {
        Mail::fake();
        $user = $this->authorizedUser();
        $transaction = $this->transaction($this->client());

        $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'client_id' => $transaction->client_id,
            'type' => Transaction::TYPE_INCOME,
            'scope' => 'operation',
            'transaction_date' => '2026-07-15',
            'amount' => 200,
            'method' => 'transfer',
            'category' => 'Actualizado',
            'status' => 'paid',
        ])->assertRedirect(route('transactions.index'));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('receipt_email_logs', 0);
    }

    public function test_creation_suggests_client_and_portal_emails_for_selected_event(): void
    {
        $user = $this->authorizedUser();
        $portal = User::factory()->create(['email' => 'portal@example.com']);
        $client = $this->client(['email' => 'cliente@example.com', 'user_id' => $portal->id]);
        $event = Event::create([
            'client_id' => $client->id,
            'title' => 'Evento con correos',
            'event_type' => 'Boda',
            'status' => Event::STATUS_CONFIRMED,
            'event_date' => '2026-09-01',
            'total_amount' => 1000,
        ]);

        $this->actingAs($user)->get(route('transactions.create', [
            'type' => Transaction::TYPE_INCOME,
            'event_id' => $event->id,
        ]))->assertOk()->assertSee('cliente@example.com')->assertSee('portal@example.com');
    }

    public function test_email_routes_keep_manage_payments_permission(): void
    {
        $transaction = $this->transaction($this->client());
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('transactions.email.create', $transaction))->assertForbidden();
        $this->actingAs($user)->post(route('transactions.email.store', $transaction), [
            'receipt_to' => 'cliente@gmail.com',
        ])->assertForbidden();
    }

    private function payload(Client $client, array $attributes = []): array
    {
        return array_merge([
            'client_id' => $client->id,
            'type' => Transaction::TYPE_INCOME,
            'scope' => 'operation',
            'transaction_date' => '2026-07-14',
            'amount' => 100,
            'method' => 'transfer',
            'category' => 'Anticipo',
            'status' => 'paid',
            'receipt_to' => '',
            'receipt_cc' => '',
        ], $attributes);
    }

    private function transaction(Client $client): Transaction
    {
        return Transaction::create([
            'client_id' => $client->id,
            'type' => Transaction::TYPE_INCOME,
            'scope' => 'operation',
            'transaction_date' => '2026-07-14',
            'amount' => 100,
            'reference' => 'ING-2026-000001',
            'receipt_token' => '11111111-1111-4111-8111-111111111111',
            'status' => 'paid',
        ]);
    }

    private function client(array $attributes = []): Client
    {
        return Client::create(array_merge([
            'type' => 'active',
            'full_name' => 'Cliente de correo',
            'email' => 'cliente@example.com',
        ], $attributes));
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('manage payments');
        $user->givePermissionTo($permission);

        return $user;
    }
}
