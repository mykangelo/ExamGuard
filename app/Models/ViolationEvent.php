<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViolationEvent extends Model
{
    public const SEVERITY_MINOR = 'minor';

    public const SEVERITY_MODERATE = 'moderate';

    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'exam_attempt_id',
        'type',
        'severity',
        'message',
        'snapshot_path',
        'occurred_at',
        'time_remaining_seconds',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'meta_json' => 'array',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function snapshotUrl(): ?string
    {
        if (! $this->snapshot_path) {
            return null;
        }

        return PublicStorageUrl::for($this->snapshot_path);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'severity' => $this->severity,
            'message' => $this->message,
            'snapshotUrl' => $this->snapshotUrl(),
            'occurredAt' => $this->occurred_at?->toIso8601String(),
            'timeRemainingSeconds' => $this->time_remaining_seconds,
        ];
    }
}
