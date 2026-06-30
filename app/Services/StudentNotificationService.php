<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\StudentNotification;
use Illuminate\Support\Collection;

class StudentNotificationService
{
    public static function notifyClassJoined(Classroom $classroom, int $studentId): void
    {
        StudentNotification::create([
            'student_id' => $studentId,
            'type' => StudentNotification::TYPE_CLASS_JOINED,
            'title' => 'Joined class',
            'message' => "You are now enrolled in {$classroom->name}.",
            'classroom_id' => $classroom->id,
            'classroom_name' => $classroom->name,
        ]);
    }

    public static function notifyExamAssigned(Exam $exam, Classroom $classroom): void
    {
        if ($exam->status === Exam::STATUS_DRAFT) {
            return;
        }

        $studentIds = Enrollment::where('classroom_id', $classroom->id)->pluck('student_id');

        self::createForStudents($studentIds, [
            'type' => StudentNotification::TYPE_EXAM_ASSIGNED,
            'title' => 'New exam assigned',
            'message' => "{$exam->title} was assigned to {$classroom->name}.",
            'classroom_id' => $classroom->id,
            'exam_id' => $exam->id,
            'classroom_name' => $classroom->name,
            'exam_title' => $exam->title,
        ], fn (int $studentId) => StudentNotification::query()
            ->where('student_id', $studentId)
            ->where('type', StudentNotification::TYPE_EXAM_ASSIGNED)
            ->where('exam_id', $exam->id)
            ->where('classroom_id', $classroom->id)
            ->where('created_at', '>=', now()->subHour())
            ->exists());
    }

    public static function notifyExamDeleted(Exam $exam): void
    {
        $assignments = ExamAssignment::where('exam_id', $exam->id)->get();

        foreach ($assignments as $assignment) {
            $classroom = Classroom::find($assignment->classroom_id);
            if (! $classroom) {
                continue;
            }

            $studentIds = Enrollment::where('classroom_id', $classroom->id)->pluck('student_id');

            self::createForStudents($studentIds, [
                'type' => StudentNotification::TYPE_EXAM_DELETED,
                'title' => 'Exam removed',
                'message' => "{$exam->title} was removed from {$classroom->name}.",
                'classroom_id' => $classroom->id,
                'exam_id' => null,
                'classroom_name' => $classroom->name,
                'exam_title' => $exam->title,
            ]);
        }
    }

    public static function notifyClassDeleted(Classroom $classroom): void
    {
        $studentIds = Enrollment::where('classroom_id', $classroom->id)->pluck('student_id');

        self::createForStudents($studentIds, [
            'type' => StudentNotification::TYPE_CLASS_DELETED,
            'title' => 'Class removed',
            'message' => "{$classroom->name} was removed by your professor.",
            'classroom_id' => null,
            'exam_id' => null,
            'classroom_name' => $classroom->name,
            'exam_title' => null,
        ]);
    }

    private static function createForStudents(
        Collection $studentIds,
        array $payload,
        ?callable $skipIf = null
    ): void {
        foreach ($studentIds as $studentId) {
            if ($skipIf && $skipIf((int) $studentId)) {
                continue;
            }

            StudentNotification::create(array_merge($payload, [
                'student_id' => (int) $studentId,
            ]));
        }
    }
}
