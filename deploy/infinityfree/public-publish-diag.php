<?php
/**
 * Exam publish diagnostic — upload to htdocs/public/publish-diag.php
 * Visit: https://examguard.site.je/publish-diag.php?key=MAIL_TEST_SECRET
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
    echo "Forbidden. Use ?key=MAIL_TEST_SECRET\n";
    exit;
}

$tables = [
    'exams', 'questions', 'choices', 'exam_assignments',
    'classrooms', 'enrollments', 'exam_attempts',
    'violation_events', 'student_notifications',
];

echo "=== Publish diagnostic ===\n\n";

echo "--- AUTO_INCREMENT on id ---\n";
foreach ($tables as $table) {
    if (! Illuminate\Support\Facades\Schema::hasTable($table)) {
        echo "{$table}: (table missing)\n";
        continue;
    }
    $row = Illuminate\Support\Facades\DB::selectOne(
        "SHOW COLUMNS FROM `{$table}` WHERE Field = 'id'"
    );
    $extra = $row->Extra ?? '';
    echo "{$table}: " . (stripos($extra, 'auto_increment') !== false ? 'OK' : 'MISSING AUTO_INCREMENT') . "\n";
}

echo "\n--- exams columns ---\n";
foreach (['status', 'opens_at', 'closes_at', 'closed_at', 'exam_key', 'max_warning_action', 'proctoring_triggers_json'] as $col) {
    echo "{$col}: " . (Illuminate\Support\Facades\Schema::hasColumn('exams', $col) ? 'OK' : 'MISSING') . "\n";
}

echo "\n--- test exam insert (rolled back) ---\n";
try {
    Illuminate\Support\Facades\DB::beginTransaction();

    $prof = App\Models\User::where('role', 'professor')->first();
    if (! $prof) {
        throw new RuntimeException('No professor user in database.');
    }

    $exam = App\Models\Exam::create([
        'professor_id' => $prof->id,
        'title' => 'Diag Exam',
        'instructions' => 'Test',
        'time_limit' => 30,
        'warning_limit' => 3,
        'status' => 'draft',
    ]);
    echo "exam create OK (id {$exam->id})\n";

    $question = App\Models\Question::create([
        'exam_id' => $exam->id,
        'position' => 0,
        'prompt' => 'Q?',
        'explanation' => 'Because.',
        'correct_choice' => 0,
    ]);
    echo "question create OK (id {$question->id})\n";

    App\Models\Choice::create([
        'question_id' => $question->id,
        'position' => 0,
        'choice_text' => 'A',
    ]);
    echo "choice create OK\n";

    Illuminate\Support\Facades\DB::rollBack();
    echo "rolled back (test data not kept)\n";
} catch (Throwable $e) {
    Illuminate\Support\Facades\DB::rollBack();
    echo 'FAIL: ' . $e->getMessage() . "\n";
}

$log = $root . '/storage/logs/laravel.log';
echo "\n--- laravel.log (last 10 lines) ---\n";
if (is_file($log)) {
    $lines = file($log);
    echo implode('', array_slice($lines, -10));
} else {
    echo "(no log file)\n";
}

echo "\n=== Fix: run deploy/infinityfree/fix-auto-increment.sql in phpMyAdmin ===\n";
