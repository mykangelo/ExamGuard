<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Exam extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'professor_id', 'title', 'instructions', 'time_limit', 'warning_limit', 'max_warning_action', 'proctoring_triggers_json', 'status',
        'opens_at', 'closes_at', 'closed_at', 'exam_key',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'closed_at' => 'datetime',
            'proctoring_triggers_json' => 'array',
        ];
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ExamAssignment::class);
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'exam_assignments')
            ->withPivot('assigned_at');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function isEditable(): bool
    {
        return $this->displayStatus() === self::STATUS_DRAFT;
    }

    public function isAvailableToStudents(): bool
    {
        return $this->displayStatus() === self::STATUS_ACTIVE;
    }

    public function acceptsExamKeyEntry(): bool
    {
        return in_array($this->displayStatus(), [self::STATUS_ACTIVE, self::STATUS_SCHEDULED], true);
    }

    public function displayStatus(): string
    {
        $status = $this->status ?? self::STATUS_DRAFT;

        if ($status === self::STATUS_DRAFT) {
            return self::STATUS_DRAFT;
        }

        if ($status === self::STATUS_CLOSED || $this->closed_at) {
            return self::STATUS_CLOSED;
        }

        if ($this->closes_at && $this->closes_at->isPast()) {
            return self::STATUS_CLOSED;
        }

        if ($this->opens_at && $this->opens_at->isFuture()) {
            return self::STATUS_SCHEDULED;
        }

        if (in_array($status, [self::STATUS_SCHEDULED, 'published'], true) && $this->opens_at && $this->opens_at->isFuture()) {
            return self::STATUS_SCHEDULED;
        }

        if (in_array($status, [self::STATUS_ACTIVE, self::STATUS_SCHEDULED, 'published'], true)) {
            return self::STATUS_ACTIVE;
        }

        return $status;
    }

    public function syncLifecycleStatus(): void
    {
        $display = $this->displayStatus();

        if ($display === self::STATUS_CLOSED && $this->status !== self::STATUS_CLOSED) {
            $this->forceFill([
                'status' => self::STATUS_CLOSED,
                'closed_at' => $this->closed_at ?? now(),
            ])->save();

            return;
        }

        if ($display === self::STATUS_ACTIVE && $this->status !== self::STATUS_ACTIVE) {
            $this->forceFill(['status' => self::STATUS_ACTIVE])->save();

            return;
        }

        if ($display === self::STATUS_SCHEDULED && $this->status !== self::STATUS_SCHEDULED) {
            $this->forceFill(['status' => self::STATUS_SCHEDULED])->save();
        }
    }

    public static function generateExamKey(): string
    {
        do {
            $key = strtoupper(Str::random(8));
        } while (static::where('exam_key', $key)->exists());

        return $key;
    }

    public function studentCanAccess(int $studentId, ?\Illuminate\Http\Request $request = null): bool
    {
        if ($request?->session()->get("exam_key_access.{$this->id}")) {
            return true;
        }

        return static::query()
            ->where('exams.id', $this->id)
            ->join('exam_assignments', 'exam_assignments.exam_id', '=', 'exams.id')
            ->join('enrollments', 'enrollments.classroom_id', '=', 'exam_assignments.classroom_id')
            ->where('enrollments.student_id', $studentId)
            ->exists();
    }

    public function assignedClassroom(): ?Classroom
    {
        if ($this->relationLoaded('assignments')) {
            return $this->assignments->first()?->classroom;
        }

        return $this->assignments()->with('classroom')->first()?->classroom;
    }

    public function toProfessorArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'timeLimit' => $this->time_limit,
            'warningLimit' => $this->warning_limit,
            'maxWarningAction' => $this->max_warning_action ?? 'notify',
            'proctoringTriggers' => $this->proctoring_triggers_json,
            'status' => $this->displayStatus(),
            'examKey' => $this->exam_key,
            'opensAt' => $this->opens_at?->toIso8601String(),
            'closesAt' => $this->closes_at?->toIso8601String(),
            'classId' => $this->relationLoaded('assignments')
                ? $this->assignments->first()?->classroom_id
                : null,
            'questions' => $this->questions->map(fn (Question $q) => $q->toArrayWithAnswers())->values(),
        ];
    }

    public function toStudentArray(bool $includeAnswers = false, ?ExamAttempt $attempt = null): array
    {
        $classroom = $this->relationLoaded('assignments')
            ? $this->assignments->first()?->classroom
            : $this->assignedClassroom();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'timeLimit' => $this->time_limit,
            'warningLimit' => $this->warning_limit,
            'maxWarningAction' => $this->max_warning_action ?? 'notify',
            'proctoringTriggers' => $this->proctoring_triggers_json,
            'className' => $classroom?->name,
            'professorName' => $this->relationLoaded('professor')
                ? $this->professor?->name
                : null,
            'questionCount' => $this->questions->count(),
            'closesAt' => $this->closes_at?->toIso8601String(),
            'questions' => $this->questions->map(fn (Question $q) => $q->toArrayForStudent($includeAnswers))->values(),
            'attempt' => $attempt ? [
                'id' => $attempt->id,
                'status' => $attempt->displayStatus(),
                'warningCount' => $attempt->warning_count,
                'warningLimit' => $this->warning_limit,
                'violationLocked' => $attempt->isViolationLocked($this),
                'startedAt' => $attempt->started_at?->toIso8601String(),
            ] : null,
        ];
    }
}
