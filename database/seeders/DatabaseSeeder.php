<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'professor@examguard.local'],
            [
                'name' => 'ExamGuard Professor',
                'password' => Hash::make(env('SEED_PROFESSOR_PASSWORD', 'Professor123!')),
                'role' => 'professor',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'student@examguard.local'],
            [
                'name' => 'ExamGuard Student',
                'password' => Hash::make(env('SEED_STUDENT_PASSWORD', 'Student123!')),
                'role' => 'student',
                'email_verified_at' => now(),
            ]
        );
    }
}
