<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('max_warning_action', 20)->default('notify')->after('warning_limit');
            $table->json('proctoring_triggers_json')->nullable()->after('max_warning_action');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['max_warning_action', 'proctoring_triggers_json']);
        });
    }
};

