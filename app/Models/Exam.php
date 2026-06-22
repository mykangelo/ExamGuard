<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'professor_id', 'title', 'instructions', 'time_limit', 'warning_limit',
    ];

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

    public function toProfessorArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'timeLimit' => $this->time_limit,
            'warningLimit' => $this->warning_limit,
            'questions' => $this->questions->map(fn (Question $q) => $q->toArrayWithAnswers())->values(),
        ];
    }

    public function toStudentArray(bool $includeAnswers = false): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'timeLimit' => $this->time_limit,
            'warningLimit' => $this->warning_limit,
            'questions' => $this->questions->map(fn (Question $q) => $q->toArrayForStudent($includeAnswers))->values(),
        ];
    }
}
