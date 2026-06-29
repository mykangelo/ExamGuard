<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_events', function (Blueprint $table) {
            $table->unsignedInteger('time_remaining_seconds')->nullable()->after('occurred_at');
            $table->json('meta_json')->nullable()->after('time_remaining_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('violation_events', function (Blueprint $table) {
            $table->dropColumn(['time_remaining_seconds', 'meta_json']);
        });
    }
};

