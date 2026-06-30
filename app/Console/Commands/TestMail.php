<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    protected $signature = 'examguard:test-mail {email : Recipient address}';

    protected $description = 'Send a test email using the configured MAIL_* settings';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');

            return self::FAILURE;
        }

        $mailer = config('mail.default');
        $from = config('mail.from.address');

        $this->line("Mailer: {$mailer}");
        $this->line("From:   {$from}");
        $this->line("To:     {$email}");

        try {
            Mail::raw(
                "This is a test message from ExamGuard.\n\n"
                . 'If you received this, SMTP is configured correctly.',
                function ($message) use ($email) {
                    $message->to($email)->subject('ExamGuard SMTP test');
                }
            );
        } catch (\Throwable $e) {
            $this->error('Send failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Test email sent. Check the inbox (and spam folder).');

        return self::SUCCESS;
    }
}
