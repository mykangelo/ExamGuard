<?php
/**
 * One-time SMTP test on InfinityFree (no SSH/artisan).
 * Upload to htdocs/public/mail-test.php
 * Visit: https://examguard.site.je/mail-test.php?key=YOUR_SECRET&to=you@example.com
 * DELETE this file after a successful send.
 */
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);
$key = $_GET['key'] ?? '';
$to = strtolower(trim($_GET['to'] ?? ''));

if ($key === '' || $to === '') {
    echo "Usage: mail-test.php?key=SECRET&to=recipient@example.com\n";
    echo "       mail-test.php?key=SECRET&to=recipient@example.com&mode=verify\n";
    echo "Set MAIL_TEST_SECRET in .env, then DELETE this file when done.\n";
    exit;
}

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if ($key !== env('MAIL_TEST_SECRET')) {
    http_response_code(403);
    echo "Forbidden.\n";
    exit;
}

if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email.\n";
    exit;
}

echo "Mailer: " . config('mail.default') . "\n";
echo "Host:   " . config('mail.mailers.smtp.host') . "\n";
echo "From:   " . config('mail.from.address') . "\n";
echo "To:     {$to}\n";
echo "APP_URL: " . config('app.url') . "\n\n";

$mode = $_GET['mode'] ?? 'raw';

try {
    if ($mode === 'verify') {
        $user = new \App\Models\User([
            'name' => 'Verify Test',
            'email' => $to,
            'password' => 'unused',
            'role' => 'professor',
        ]);
        $user->id = 1;
        $user->sendEmailVerificationNotification();
        echo "VerifyEmail notification sent. Check inbox and spam.\n";
    } else {
        \Illuminate\Support\Facades\Mail::raw(
            "ExamGuard SMTP test from mail-test.php\n\nIf you received this, SMTP works.",
            fn ($m) => $m->to($to)->subject('ExamGuard SMTP test')
        );
        echo "Sent. Check inbox and spam.\n";
    }
} catch (Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
