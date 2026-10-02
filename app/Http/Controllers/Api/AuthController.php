<?php
namespace App\Http\Controllers\Api;

use App\Events\AgentLoggedIn;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)
            ->orWhere('email', $request->phone)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->recordFailedLogin($request);
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is deactivated. Contact your administrator.'], 403);
        }

        // Device binding check for field agents (ward + PU agents)
        if ($user->isFieldAgent() && $user->device_id && $request->device_id) {
            if ($user->device_id !== $request->device_id) {
                AuditLog::create([
                    'actor_user_id' => $user->id,
                    'actor_role'    => $user->role,
                    'action_type'   => 'login',
                    'entity_type'   => 'User',
                    'entity_id'     => $user->id,
                    'new_value'     => ['note' => 'Login from unregistered device'],
                    'ip_address'    => $request->ip(),
                    'device_info'   => $request->userAgent(),
                    'reason'        => 'Unregistered device login attempt',
                ]);
                return response()->json([
                    'message'              => 'Login from unregistered device. Contact your supervisor.',
                    'requires_verification' => true,
                ], 403);
            }
        }

        // Bind device on first login for field agents
        if ($user->isFieldAgent() && !$user->device_id && $request->device_id) {
            $user->device_id = $request->device_id;
        }

        // MFA enforcement for Super Admin (and any user with mfa_enabled)
        if ($user->mfa_enabled) {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->mfa_otp            = $otp;
            $user->mfa_otp_expires_at = now()->addMinutes(5);
            $user->save();

            // Send OTP via SMS (sandbox: logged, production: dispatched)
            app(SmsService::class)->send(
                $user->phone,
                "JESR MFA code: {$otp}. Valid for 5 minutes. Do not share."
            );

            return response()->json([
                'mfa_required' => true,
                'mfa_token'    => encrypt($user->id . '|' . now()->timestamp),
                'message'      => 'OTP sent to your registered phone number.',
            ], 200);
        }

        return $this->issueToken($user, $request);
    }

    public function mfaVerify(Request $request): JsonResponse
    {
        $request->validate([
            'mfa_token' => ['required', 'string'],
            'otp'       => ['required', 'string', 'size:6'],
        ]);

        try {
            $payload   = decrypt($request->mfa_token);
            [$userId, $issuedAt] = explode('|', $payload);
            $user = User::findOrFail($userId);
        } catch (\Exception) {
            return response()->json(['message' => 'Invalid or expired MFA session.'], 401);
        }

        // SEC-02: Reject tokens older than 10 minutes regardless of OTP state
        if (now()->timestamp - (int) $issuedAt > 600) {
            return response()->json(['message' => 'MFA session has expired. Please log in again.'], 401);
        }

        if (
            !$user->mfa_otp ||
            !$user->mfa_otp_expires_at ||
            now()->isAfter($user->mfa_otp_expires_at) ||
            !hash_equals($user->mfa_otp, $request->otp)
        ) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 401);
        }

        // Clear OTP after use
        $user->mfa_otp            = null;
        $user->mfa_otp_expires_at = null;
        $user->save();

        return $this->issueToken($user, $request);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke current token and issue a fresh 8-hour token
        $request->user()->currentAccessToken()->delete();
        $token = $user->createToken('api-token', ['*'], now()->addHours(8));

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        AuditLog::create([
            'actor_user_id' => $request->user()->id,
            'actor_role'    => $request->user()->role,
            'action_type'   => 'logout',
            'entity_type'   => 'User',
            'entity_id'     => $request->user()->id,
            'ip_address'    => $request->ip(),
            'device_info'   => $request->userAgent(),
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('lga', 'ward', 'pollingStation'));
    }

    private function issueToken(User $user, Request $request): JsonResponse
    {
        $user->last_login_at = now();
        $user->save();

        $user->tokens()->delete();
        $token = $user->createToken('api-token', ['*'], now()->addHours(8));

        AuditLog::create([
            'actor_user_id' => $user->id,
            'actor_role'    => $user->role,
            'action_type'   => 'login',
            'entity_type'   => 'User',
            'entity_id'     => $user->id,
            'ip_address'    => $request->ip(),
            'device_info'   => $request->userAgent(),
        ]);

        if ($user->isFieldAgent()) {
            broadcast(new AgentLoggedIn($user));
        }

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
            'user'       => [
                'id'                 => $user->id,
                'full_name'          => $user->full_name,
                'phone'              => $user->phone,
                'role'               => $user->role,
                'lga_id'             => $user->lga_id,
                'ward_id'            => $user->ward_id,
                'polling_station_id' => $user->polling_station_id,
                'is_active'          => $user->is_active,
                'mfa_enabled'        => $user->mfa_enabled,
            ],
        ]);
    }

    private function recordFailedLogin(Request $request): void
    {
        // Throttle middleware handles rate-limiting; log for audit trail
        AuditLog::create([
            'actor_user_id' => null,
            'actor_role'    => 'unknown',
            'action_type'   => 'login',
            'entity_type'   => 'User',
            'entity_id'     => 0,
            'reason'        => 'Failed login attempt for: ' . $request->phone,
            'ip_address'    => $request->ip(),
            'device_info'   => $request->userAgent(),
        ]);
    }
}
