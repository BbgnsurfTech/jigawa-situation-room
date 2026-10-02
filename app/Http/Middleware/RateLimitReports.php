<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitReports
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'reports:' . ($request->user()?->id ?? $request->ip());
        $limit = config('jesr.report_submission_rate_limit', 10);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Too many submissions. Try again in {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($key, 60); // 1 minute window

        return $next($request);
    }
}
