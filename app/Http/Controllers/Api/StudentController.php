<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function notifications(Request $request): JsonResponse
    {
        $studentId = $request->user()->id;

        $notifications = StudentNotification::query()
            ->where('student_id', $studentId)
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn (StudentNotification $n) => $n->toStudentArray())
            ->values()
            ->all();

        $unreadCount = StudentNotification::query()
            ->where('student_id', $studentId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markNotificationsRead(Request $request): JsonResponse
    {
        $input = $request->validate([
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['integer'],
            'all' => ['sometimes', 'boolean'],
        ]);

        $studentId = $request->user()->id;
        $query = StudentNotification::query()->where('student_id', $studentId);

        if (! empty($input['all'])) {
            $query->whereNull('read_at')->update(['read_at' => now()]);
        } elseif (! empty($input['ids'])) {
            $query->whereIn('id', $input['ids'])->whereNull('read_at')->update(['read_at' => now()]);
        }

        return $this->notifications($request);
    }
}
