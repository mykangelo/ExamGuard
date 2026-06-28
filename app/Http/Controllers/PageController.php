<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

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
        return view('pages.professor');
    }

    public function student()
    {
        return view('pages.student');
    }

    public function createExam()
    {
        return view('pages.create-exam');
    }

    public function professorClasses()
    {
        return view('pages.professor-classes');
    }

    public function takeExam()
    {
        return view('pages.take-exam');
    }

    public function examRoom()
    {
        return view('pages.exam-room');
    }
}
