<?php
/**
 * Upload to htdocs/public/diag.php (exact filename).
 * DELETE after debugging.
 */
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);

echo "=== ExamGuard diagnostics ===\n\n";
echo 'PHP: ' . PHP_VERSION . "\n";
echo 'Script: ' . __FILE__ . "\n";
echo 'App root: ' . $root . "\n\n";

echo ".env exists: " . (is_file($root . '/.env') ? 'yes' : 'NO') . "\n";

$env = is_file($root . '/.env') ? file_get_contents($root . '/.env') : '';
if ($env !== '') {
    echo 'APP_KEY set: ' . (preg_match('/^APP_KEY=base64:.+/m', $env) ? 'yes' : 'NO') . "\n";
    preg_match('/^APP_URL=(.+)$/m', $env, $url);
    preg_match('/^DB_DATABASE=(.+)$/m', $env, $db);
    preg_match('/^DB_USERNAME=(.+)$/m', $env, $user);
    echo 'APP_URL: ' . trim($url[1] ?? '?', "\"'") . "\n";
    echo 'DB_DATABASE: ' . trim($db[1] ?? '?', "\"'") . "\n";
    echo 'DB_USERNAME: ' . trim($user[1] ?? '?', "\"'") . "\n";
}

echo "\n--- Writable ---\n";
foreach (['storage', 'storage/logs', 'bootstrap/cache'] as $rel) {
    $p = $root . '/' . $rel;
    echo $rel . ': ' . (is_dir($p) ? (is_writable($p) ? 'writable' : 'NOT WRITABLE') : 'MISSING') . "\n";
}

$installed = $root . '/vendor/composer/installed.json';
if (is_file($installed)) {
    $pkgs = json_decode(file_get_contents($installed), true)['packages'] ?? [];
    foreach ($pkgs as $pkg) {
        if (($pkg['name'] ?? '') === 'symfony/http-foundation') {
            echo "\nsymfony/http-foundation: " . ($pkg['version'] ?? '?') . "\n";
            if (str_starts_with($pkg['version'] ?? '', 'v8.')) {
                echo "ERROR: Symfony 8 needs PHP 8.4+. Re-upload vendor from dist/infinityfree (PHP 8.3 build).\n";
            }
            break;
        }
    }
}

echo "\n--- platform_check ---\n";
$pc = $root . '/vendor/composer/platform_check.php';
if (!is_file($pc)) {
    echo "MISSING $pc\n";
} else {
    $src = file_get_contents($pc);
    echo (strpos($src, '80401') !== false) ? "BAD: PHP 8.4 required\n" : "OK: PHP 8.3 check\n";
}

echo "\n--- autoload ---\n";
try {
    require $root . '/vendor/autoload.php';
    echo "autoload OK\n";
} catch (Throwable $e) {
    echo 'autoload FAIL: ' . $e->getMessage() . "\n";
    exit;
}

echo "\n--- Laravel boot ---\n";
try {
    $app = require $root . '/bootstrap/app.php';
    echo "bootstrap OK\n";
} catch (Throwable $e) {
    echo 'bootstrap FAIL: ' . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
}

$log = $root . '/storage/logs/laravel.log';
echo "\n--- laravel.log (last 20 lines) ---\n";
if (is_file($log)) {
    $lines = file($log);
    echo implode('', array_slice($lines, -20));
} else {
    echo "(no log file)\n";
}

echo "\n=== Done. Delete public/diag.php ===\n";
