<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proctoring demo mode
    |--------------------------------------------------------------------------
    |
    | When true, ExamGuard shows a notice that browser-based proctoring is a
    | demonstration aid — not production exam security. Violation counts on
    | submit are always taken from server-side ViolationEvent records, never
    | from the client payload.
    |
    | Set PROCTORING_DEMO_MODE=false when deploying hardened proctoring.
    |
    */

    'demo_mode' => env('PROCTORING_DEMO_MODE', true),

];
