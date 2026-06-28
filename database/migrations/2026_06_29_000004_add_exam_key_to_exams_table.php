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
        Schema::table('exams', function (Blueprint $table) {
            $table->string('exam_key', 8)->nullable()->unique()->after('closed_at');
        });

        $existing = DB::table('exams')
            ->whereIn('status', ['active', 'scheduled', 'published', 'closed'])
            ->whereNull('exam_key')
            ->pluck('id');

        foreach ($existing as $id) {
            DB::table('exams')->where('id', $id)->update([
                'exam_key' => $this->generateUniqueKey(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('exam_key');
        });
    }

    private function generateUniqueKey(): string
    {
        do {
            $key = strtoupper(Str::random(8));
        } while (DB::table('exams')->where('exam_key', $key)->exists());

        return $key;
    }
};
