<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('receipt_token')->nullable()->unique()->after('reference');
        });

        DB::table('transactions')->orderBy('id')->chunkById(100, function ($transactions) {
            foreach ($transactions as $transaction) {
                DB::table('transactions')
                    ->where('id', $transaction->id)
                    ->update(['receipt_token' => (string) Str::uuid()]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['receipt_token']);
            $table->dropColumn('receipt_token');
        });
    }
};
