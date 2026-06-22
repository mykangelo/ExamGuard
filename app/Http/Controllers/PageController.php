<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function home()
    {
        return view('pages.home');
    }

    public function login()
    {
        return view('pages.login');
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
