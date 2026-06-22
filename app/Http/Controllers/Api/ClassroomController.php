<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassroomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'professor') {
            $classes = Classroom::where('professor_id', $user->id)
                ->latest()
                ->get(['id', 'name', 'subject', 'class_code'])
                ->map(fn (Classroom $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'subject' => $c->subject,
                    'code' => $c->class_code,
                ]);
        } else {
            $classes = Classroom::query()
                ->join('enrollments', 'enrollments.classroom_id', '=', 'classrooms.id')
                ->where('enrollments.student_id', $user->id)
                ->get(['classrooms.id', 'classrooms.name', 'classrooms.subject', 'classrooms.class_code'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'subject' => $c->subject,
                    'code' => $c->class_code,
                ]);
        }

        return response()->json(['classes' => $classes]);
    }

    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string'],
            'subject' => ['required', 'string'],
        ]);

        $classroom = Classroom::create([
            'professor_id' => $request->user()->id,
            'name' => trim($input['name']),
            'subject' => trim($input['subject']),
            'class_code' => $this->generateClassCode(),
        ]);

        return response()->json([
            'classroom' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'subject' => $classroom->subject,
                'code' => $classroom->class_code,
            ],
        ], 201);
    }

    public function join(Request $request): JsonResponse
    {
        $input = $request->validate(['code' => ['required', 'string']]);

        $classroom = Classroom::where('class_code', strtoupper(trim($input['code'])))->first();

        if (! $classroom) {
            return response()->json(['error' => 'Class code not found.'], 404);
        }

        Enrollment::firstOrCreate([
            'classroom_id' => $classroom->id,
            'student_id' => $request->user()->id,
        ], ['joined_at' => now()]);

        return response()->json([
            'classroom' => [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'subject' => $classroom->subject,
                'code' => $classroom->class_code,
            ],
        ]);
    }

    public function destroy(Request $request, Classroom $classroom): JsonResponse
    {
        if ($classroom->professor_id !== $request->user()->id) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        $classroom->delete();

        return response()->json(['ok' => true]);
    }

    public function professorIndex(Request $request): JsonResponse
    {
        $classes = Classroom::where('professor_id', $request->user()->id)
            ->with(['students:id,name,email', 'exams:id,title'])
            ->get()
            ->map(fn (Classroom $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'subject' => $c->subject,
                'code' => $c->class_code,
                'students' => $c->students->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'email' => $s->email,
                ])->values(),
                'exams' => $c->exams->map(fn ($e) => [
                    'id' => $e->id,
                    'title' => $e->title,
                ])->values(),
            ]);

        return response()->json(['classes' => $classes]);
    }

    private function generateClassCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Classroom::where('class_code', $code)->exists());

        return $code;
    }
}
