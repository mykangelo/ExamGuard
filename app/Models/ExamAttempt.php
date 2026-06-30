<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_DISCONNECTED = 'disconnected';

    public $timestamps = false;

    protected $fillable = [
        'exam_id', 'student_id', 'status', 'score', 'total', 'warning_count',
        'answers_json', 'started_at', 'last_heartbeat_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'answers_json' => 'array',
            'started_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function violationEvents(): HasMany
    {
        return $this->hasMany(ViolationEvent::class)->orderByDesc('occurred_at');
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isDisconnected(): bool
    {
        if ($this->status === self::STATUS_DISCONNECTED) {
            return true;
        }

        if ($this->status !== self::STATUS_IN_PROGRESS || ! $this->last_heartbeat_at) {
            return false;
        }

        return $this->last_heartbeat_at->lt(now()->subSeconds(45));
    }

    public function displayStatus(): string
    {
        if ($this->status === self::STATUS_SUBMITTED) {
            return self::STATUS_SUBMITTED;
        }

        if ($this->isDisconnected()) {
            return self::STATUS_DISCONNECTED;
        }

        return self::STATUS_IN_PROGRESS;
    }

    public function severitySummary(): array
    {
        $counts = $this->violationEvents()
            ->selectRaw('severity, COUNT(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        return [
            'minor' => (int) ($counts[ViolationEvent::SEVERITY_MINOR] ?? 0),
            'moderate' => (int) ($counts[ViolationEvent::SEVERITY_MODERATE] ?? 0),
            'critical' => (int) ($counts[ViolationEvent::SEVERITY_CRITICAL] ?? 0),
        ];
    }

    public function severitySummaryLabel(): string
    {
        $parts = [];
        $summary = $this->relationLoaded('violationEvents')
            ? $this->summarizeLoadedEvents()
            : $this->severitySummary();

        foreach (['minor' => 'Minor', 'moderate' => 'Moderate', 'critical' => 'Critical'] as $key => $label) {
            if (($summary[$key] ?? 0) > 0) {
                $parts[] = $summary[$key].' '.$label;
            }
        }

        return $parts ? implode(', ', $parts) : '0 violations';
    }

    public function summarizeLoadedEvents(): array
    {
        $summary = ['minor' => 0, 'moderate' => 0, 'critical' => 0];
        foreach ($this->violationEvents as $event) {
            if (isset($summary[$event->severity])) {
                $summary[$event->severity]++;
            }
        }

        return $summary;
    }

    /** Server-authoritative warning total (never trust client submit). */
    public function authoritativeWarningCount(): int
    {
        if ($this->relationLoaded('violationEvents')) {
            return $this->violationEvents->count();
        }

        return (int) $this->violationEvents()->count();
    }

    public function syncWarningCountFromEvents(): void
    {
        $count = $this->authoritativeWarningCount();
        if ((int) $this->warning_count !== $count) {
            $this->forceFill(['warning_count' => $count])->save();
        }
    }

    public function isViolationLocked(?Exam $exam = null): bool
    {
        if ($this->status === self::STATUS_SUBMITTED) {
            return false;
        }

        $exam = $exam ?? $this->exam;
        $limit = max(1, (int) ($exam?->warning_limit ?? 3));

        return (int) $this->warning_count >= $limit;
    }

    /**
     * Elapsed/remaining for live proctoring UI.
     * Freezes at last heartbeat when disconnected and caps at the exam time limit.
     */
    public function sessionTiming(): array
    {
        $timeLimitSeconds = max(0, (int) (($this->exam->time_limit ?? 0) * 60));
        $startedAt = $this->started_at;

        if (! $startedAt) {
            return ['elapsedSeconds' => 0, 'remainingSeconds' => $timeLimitSeconds];
        }

        $endAt = now();

        if ($this->displayStatus() === self::STATUS_DISCONNECTED) {
            $endAt = $this->last_heartbeat_at ?? $startedAt;
        } elseif ($this->status === self::STATUS_SUBMITTED && $this->submitted_at) {
            $endAt = $this->submitted_at;
        }

        if ($timeLimitSeconds > 0) {
            $hardEnd = $startedAt->copy()->addSeconds($timeLimitSeconds);
            if ($endAt->gt($hardEnd)) {
                $endAt = $hardEnd;
            }
        }

        $elapsedSeconds = (int) $startedAt->diffInSeconds($endAt);
        if ($timeLimitSeconds > 0) {
            $elapsedSeconds = min($elapsedSeconds, $timeLimitSeconds);
        }

        return [
            'elapsedSeconds' => $elapsedSeconds,
            'remainingSeconds' => max(0, $timeLimitSeconds - $elapsedSeconds),
        ];
    }
}
