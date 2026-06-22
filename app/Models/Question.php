<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'exam_id', 'position', 'prompt', 'explanation', 'correct_choice',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class)->orderBy('position');
    }

    public function toArrayWithAnswers(): array
    {
        return [
            'id' => $this->id,
            'question' => $this->prompt,
            'choices' => $this->choices->pluck('choice_text')->values(),
            'correctAnswer' => $this->correct_choice,
            'explanation' => $this->explanation,
        ];
    }

    public function toArrayForStudent(bool $includeAnswers = false): array
    {
        $data = [
            'id' => $this->id,
            'question' => $this->prompt,
            'choices' => $this->choices->pluck('choice_text')->values(),
        ];

        if ($includeAnswers) {
            $data['correctAnswer'] = $this->correct_choice;
            $data['explanation'] = $this->explanation;
        }

        return $data;
    }
}
