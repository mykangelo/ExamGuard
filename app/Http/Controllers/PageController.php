<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home()
    {
        if (Auth::check()) {
            $role = Auth::user()->role;
            return redirect($role === 'professor' ? '/professor' : '/student');
        }

        return view('pages.home');
    }

    public function login()
    {
        return view('pages.login');
    }

    public function register()
    {
        return view('pages.register');
    }

    public function forgotPassword()
    {
        return view('pages.forgot-password');
    }

    public function resetPassword()
    {
        return view('pages.reset-password');
    }

    public function verifyEmail()
    {
        return view('pages.verify-email');
    }

    public function tour()
    {
        return view('pages.tour');
    }

    public function pricing()
    {
        return view('pages.pricing');
    }

    public function faq()
    {
        return view('pages.faq');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function professor()
    {
        $user = Auth::user();

        $exams = Exam::where('professor_id', $user->id)
            ->with(['attempts.student', 'assignments.classroom.enrollments', 'questions'])
            ->withCount('questions')
            ->orderByDesc('updated_at')
            ->get()
            ->each(fn (Exam $exam) => $exam->syncLifecycleStatus());

        return view('pages.professor', [
            'user'                 => $user,
            'exams'                => $exams,
            'pendingNotifications' => 0,
        ]);
    }

    public function student()
    {
        return view('pages.student', [
            'user' => Auth::user(),
        ]);
    }

    public function createExam(Request $request)
    {
        $user = Auth::user();
        $examId = $request->query('id');

        if ($examId) {
            $exam = Exam::where('professor_id', $user->id)->find($examId);
            if ($exam && ! $exam->isEditable()) {
                return redirect('/professor?view=exams');
            }
        }

        $query = http_build_query(array_filter([
            'view' => 'create-exam',
            'id' => $examId,
        ]));

        return redirect('/professor?'.$query);
    }

    public function takeExam()
    {
        return view('pages.take-exam', [
            'proctoringDemo' => config('proctoring.demo_mode'),
        ]);
    }

    public function examRoom()
    {
        return view('pages.exam-room');
    }
}
