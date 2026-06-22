<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'exam_id', 'student_id', 'score', 'total', 'warning_count',
        'answers_json', 'started_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'answers_json' => 'array',
            'started_at' => 'datetime',
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
}
