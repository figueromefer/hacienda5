<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->enum('status', ['reserved', 'tentative', 'confirmed', 'completed', 'cancelled'])
                ->default('tentative')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('events')->where('status', 'reserved')->update(['status' => 'tentative']);

        Schema::table('events', function (Blueprint $table) {
            $table->enum('status', ['tentative', 'confirmed', 'completed', 'cancelled'])
                ->default('tentative')
                ->change();
        });
    }
};
