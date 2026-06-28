<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('status', 20)->default('submitted')->after('student_id');
            $table->timestamp('last_heartbeat_at')->nullable()->after('started_at');
        });

        DB::table('exam_attempts')->update(['status' => 'submitted']);

        Schema::create('violation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('severity', 20);
            $table->string('message');
            $table->string('snapshot_path')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['exam_attempt_id', 'occurred_at']);
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_events');

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['status', 'last_heartbeat_at']);
        });
    }
};
