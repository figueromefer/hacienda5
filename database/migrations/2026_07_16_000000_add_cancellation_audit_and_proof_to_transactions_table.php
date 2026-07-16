<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('proof_file_path')->nullable()->after('notes');
            $table->string('proof_original_name')->nullable()->after('proof_file_path');
            $table->string('proof_mime_type', 100)->nullable()->after('proof_original_name');
            $table->unsignedBigInteger('proof_file_size')->nullable()->after('proof_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'cancelled_at',
                'proof_file_path',
                'proof_original_name',
                'proof_mime_type',
                'proof_file_size',
            ]);
        });
    }
};
