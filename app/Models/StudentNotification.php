<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentNotification extends Model
{
    public const TYPE_EXAM_ASSIGNED = 'exam_assigned';

    public const TYPE_EXAM_DELETED = 'exam_deleted';

    public const TYPE_CLASS_DELETED = 'class_deleted';

    protected $fillable = [
        'student_id',
        'type',
        'title',
        'message',
        'classroom_id',
        'exam_id',
        'classroom_name',
        'exam_title',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function toStudentArray(): array
    {
        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'time' => $this->created_at?->toIso8601String(),
            'timeLabel' => $this->created_at?->diffForHumans() ?? 'Recently',
            'read' => $this->read_at !== null,
            'action' => $this->actionPayload(),
        ];
    }

    private function actionPayload(): array
    {
        return match ($this->type) {
            self::TYPE_EXAM_ASSIGNED => [
                'view' => 'class',
                'classId' => $this->classroom_id,
            ],
            self::TYPE_EXAM_DELETED, self::TYPE_CLASS_DELETED => [
                'view' => 'home',
            ],
            default => ['view' => 'home'],
        };
    }
}
