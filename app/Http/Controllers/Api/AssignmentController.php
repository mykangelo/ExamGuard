<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Services\StudentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'examId' => ['required', 'integer', 'exists:exams,id'],
            'classId' => ['required', 'integer', 'exists:classrooms,id'],
        ]);

        $user = $request->user()->id;

        $valid = Exam::query()
            ->join('classrooms', 'classrooms.professor_id', '=', 'exams.professor_id')
            ->where('exams.id', $input['examId'])
            ->where('classrooms.id', $input['classId'])
            ->where('exams.professor_id', $user)
            ->exists();

        if (! $valid) {
            return response()->json(['error' => 'Exam or class not found.'], 404);
        }

        $assignment = ExamAssignment::firstOrCreate([
            'exam_id' => $input['examId'],
            'classroom_id' => $input['classId'],
        ], ['assigned_at' => now()]);

        if ($assignment->wasRecentlyCreated) {
            $exam = Exam::find($input['examId']);
            $classroom = Classroom::find($input['classId']);
            if ($exam && $classroom) {
                StudentNotificationService::notifyExamAssigned($exam, $classroom);
            }
        }

        return response()->json(['ok' => true], 201);
    }
}
