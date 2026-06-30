<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $appUrl = (string) config('app.url', '');
        if (preg_match('#^https?:https?://#i', $appUrl)) {
            $appUrl = preg_replace('#^(https?):https?://#i', '$1://', $appUrl);
            URL::forceRootUrl(rtrim($appUrl, '/'));
        }

        /*
         * Login: 10 attempts / minute per IP.
         * Fine-grained per-account lockout is handled inside AuthController.
         */
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        /*
         * Register: 5 new accounts / hour per IP.
         */
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        /*
         * Email resend: 6 attempts / minute per IP (already on route, this is the named version).
         */
        RateLimiter::for('email-resend', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });

        RateLimiter::for('contact', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });
    }
}
