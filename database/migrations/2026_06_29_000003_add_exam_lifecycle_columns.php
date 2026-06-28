<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->timestamp('opens_at')->nullable()->after('status');
            $table->timestamp('closes_at')->nullable()->after('opens_at');
            $table->timestamp('closed_at')->nullable()->after('closes_at');
        });

        DB::table('exams')->where('status', 'published')->update(['status' => 'active']);
    }

    public function down(): void
    {
        DB::table('exams')->where('status', 'active')->update(['status' => 'published']);

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['opens_at', 'closes_at', 'closed_at']);
        });
    }
};
