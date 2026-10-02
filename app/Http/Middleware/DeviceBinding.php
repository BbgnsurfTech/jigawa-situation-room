<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceBinding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isFieldAgent() && $user->device_id) {
            $requestDeviceId = $request->header('X-Device-ID') ?? $request->input('device_id');

            // SEC-03: reject if device_id is absent OR doesn't match the bound device
            if ($requestDeviceId !== $user->device_id) {
                return response()->json([
                    'message' => 'Device mismatch. Submit from your registered device.',
                ], 403);
            }
        }

        return $next($request);
    }
}
