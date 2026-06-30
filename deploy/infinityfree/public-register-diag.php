<?php
/**
 * Registration diagnostic — upload to htdocs/public/register-diag.php
 * Visit: https://examguard.site.je/register-diag.php?key=MAIL_TEST_SECRET
 * DELETE after debugging.
 */
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);
$key = $_GET['key'] ?? '';

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if ($key === '' || $key !== env('MAIL_TEST_SECRET')) {
    http_response_code(403);
    echo "Forbidden. Use ?key=MAIL_TEST_SECRET from .env\n";
    exit;
}

echo "=== Register diagnostic ===\n\n";
echo 'APP_URL: ' . config('app.url') . "\n";
echo 'MAIL_MAILER: ' . config('mail.default') . "\n\n";

echo "--- users table columns ---\n";
try {
    $cols = Illuminate\Support\Facades\Schema::getColumnListing('users');
    echo implode(', ', $cols) . "\n";
    foreach (['email_verified_at', 'preferences', 'avatar_path'] as $need) {
        echo ($need . ': ' . (in_array($need, $cols, true) ? 'OK' : 'MISSING')) . "\n";
    }
} catch (Throwable $e) {
    echo 'Schema error: ' . $e->getMessage() . "\n";
}

$probe = 'diag-' . time() . '@example.com';
echo "\n--- test User::create ({$probe}) ---\n";
try {
    Illuminate\Support\Facades\DB::beginTransaction();
    $user = App\Models\User::create([
        'name' => 'Diag Test',
        'email' => $probe,
        'password' => 'DiagPass123!',
        'role' => 'professor',
    ]);
    echo "create OK (id {$user->id})\n";
    Illuminate\Support\Facades\DB::rollBack();
    echo "rolled back (test user not kept)\n";
} catch (Throwable $e) {
    Illuminate\Support\Facades\DB::rollBack();
    echo 'create FAIL: ' . $e->getMessage() . "\n";
}

echo "\n--- test VerifyEmail send ---\n";
try {
    $user = new App\Models\User([
        'name' => 'Diag Test',
        'email' => $probe,
        'password' => 'unused',
        'role' => 'professor',
    ]);
    $user->id = 999999;
    $user->sendEmailVerificationNotification();
    echo "VerifyEmail send OK\n";
} catch (Throwable $e) {
    echo 'VerifyEmail FAIL: ' . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
}

$check = strtolower(trim($_GET['email'] ?? 'mmykangelo@gmail.com'));
echo "\n--- lookup {$check} ---\n";
$row = App\Models\User::where('email', $check)->first();
if ($row) {
    echo "EXISTS id={$row->id} verified=" . ($row->email_verified_at ? 'yes' : 'no') . "\n";
    echo "Delete this row in phpMyAdmin if signup keeps failing.\n";
} else {
    echo "not in database\n";
}

$log = $root . '/storage/logs/laravel.log';
echo "\n--- laravel.log (last 15 lines) ---\n";
if (is_file($log)) {
    $lines = file($log);
    echo implode('', array_slice($lines, -15));
} else {
    echo "(no log file)\n";
}

echo "\n=== Done. Delete register-diag.php ===\n";
