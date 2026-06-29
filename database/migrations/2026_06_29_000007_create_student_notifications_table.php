<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('title');
            $table->text('message');
            $table->unsignedBigInteger('classroom_id')->nullable();
            $table->unsignedBigInteger('exam_id')->nullable();
            $table->string('classroom_name')->nullable();
            $table->string('exam_title')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_notifications');
    }
};
