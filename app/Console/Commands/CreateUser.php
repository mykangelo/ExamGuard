<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'examguard:create-user {name} {email} {password} {role}';

    protected $description = 'Create a professor or student account';

    public function handle(): int
    {
        $validator = Validator::make([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => $this->argument('password'),
            'role' => $this->argument('role'),
        ], [
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:10'],
            'role' => ['required', 'in:professor,student'],
        ]);

        if ($validator->fails()) {
            $this->error('Usage: php artisan examguard:create-user "Full Name" email@example.com "StrongPassword" professor|student');

            return self::FAILURE;
        }

        $data = $validator->validated();

        User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        $this->info("Created {$data['role']}: {$data['email']}");

        return self::SUCCESS;
    }
}
