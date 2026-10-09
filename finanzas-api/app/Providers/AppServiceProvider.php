<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));
            $ip = $request->ip();
            $tooManyAttempts = fn (Request $request, array $headers) => response()->json([
                'message' => 'Has realizado demasiados intentos de inicio de sesión. Intenta de nuevo en un minuto.',
            ], 429, $headers);

            return [
                Limit::perMinute(5)->by('email:'.$email.'|'.$ip)->response($tooManyAttempts),
                Limit::perMinute(20)->by('ip:'.$ip)->response($tooManyAttempts),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(fn (Request $request, array $headers) => response()->json([
                    'message' => 'Has realizado demasiados registros. Intenta de nuevo en un minuto.',
                ], 429, $headers));
        });
    }
}
