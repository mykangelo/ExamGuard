<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Models\ViolationEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProctoringWarningCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_ignores_client_warning_count_and_uses_server_events(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create([
            'professor_id' => User::factory()->create(['role' => 'professor'])->id,
            'title' => 'Test Exam',
            'time_limit' => 30,
            'warning_limit' => 3,
            'status' => Exam::STATUS_ACTIVE,
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'status' => ExamAttempt::STATUS_IN_PROGRESS,
            'score' => 0,
            'total' => 1,
            'warning_count' => 0,
            'answers_json' => [],
            'started_at' => now(),
            'last_heartbeat_at' => now(),
        ]);

        ViolationEvent::create([
            'exam_attempt_id' => $attempt->id,
            'type' => 'tab_switch',
            'severity' => ViolationEvent::SEVERITY_MINOR,
            'message' => 'Tab switching detected',
            'occurred_at' => now(),
        ]);
        $attempt->syncWarningCountFromEvents();

        $response = $this->actingAs($student)->postJson("/api/exams/{$exam->id}/attempts", [
            'answers' => [],
            'warningCount' => 0,
        ]);

        $response->assertOk();
        $attempt->refresh();
        $this->assertSame(1, $attempt->warning_count);
        $this->assertSame(ExamAttempt::STATUS_SUBMITTED, $attempt->status);
    }
}
