<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', ['income', 'expense']);
            $table->enum('scope', ['event', 'operation'])->default('event');

            $table->date('transaction_date');
            $table->decimal('amount', 12, 2);

            $table->string('method')->nullable();
            $table->string('category')->nullable();
            $table->string('reference')->nullable();
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('paid');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
