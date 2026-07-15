<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('google_event_id')->nullable();
            $table->foreignId('google_calendar_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('google_synced_at')->nullable();
            $table->string('google_sync_status')->nullable();
            $table->string('google_sync_error', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('google_calendar_connection_id');
            $table->dropColumn(['google_event_id', 'google_synced_at', 'google_sync_status', 'google_sync_error']);
        });
    }
};
