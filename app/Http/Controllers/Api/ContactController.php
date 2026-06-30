<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function submit(Request $request): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['error' => 'Invalid request.'], 400);
        }

        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $to = config('contact.to');
        if (! $to) {
            return response()->json([
                'error' => 'Contact form is not configured. Please email support directly.',
            ], 503);
        }

        try {
            Mail::to($to)->send(new ContactMessageMail($input));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'Unable to send your message right now. Please try again or email us directly.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Thank you! We received your message and will respond within 1–2 business days.',
        ]);
    }
}
