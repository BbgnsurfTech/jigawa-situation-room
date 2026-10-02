<?php
namespace App\Livewire\Auth;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class LoginPage extends Component
{
    public string $phone    = '';
    public string $password = '';
    public bool   $loading  = false;
    public string $error    = '';

    public function login(): void
    {
        $this->validate([
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        // SEC-04: Rate-limit web login — 10 attempts per minute per phone+IP
        $throttleKey = 'web-login:' . Str::lower($this->phone) . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds     = RateLimiter::availableIn($throttleKey);
            $this->error = "Too many login attempts. Try again in {$seconds} seconds.";
            return;
        }

        $this->loading = true;
        $this->error   = '';

        $user = User::where('phone', $this->phone)
            ->orWhere('email', $this->phone)
            ->first();

        if (!$user || !Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            $this->loading = false;
            $this->error   = 'The provided credentials are incorrect.';
            return;
        }

        if (!$user->is_active) {
            $this->loading = false;
            $this->error   = 'Account is deactivated. Contact your administrator.';
            return;
        }

        // SEC-05: Enforce MFA for any user with mfa_enabled (including super admin)
        if ($user->mfa_enabled) {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->update([
                'mfa_otp'            => $otp,
                'mfa_otp_expires_at' => now()->addMinutes(5),
            ]);

            app(SmsService::class)->send(
                $user->phone,
                "JESR MFA code: {$otp}. Valid for 5 minutes. Do not share."
            );

            // Store pending MFA state in session (expires at same time as OTP)
            session([
                'mfa_pending_user_id'    => $user->id,
                'mfa_pending_expires_at' => now()->addMinutes(5)->timestamp,
            ]);

            $this->loading = false;
            $this->redirect(route('mfa.verify'), navigate: true);
            return;
        }

        RateLimiter::clear($throttleKey);
        $this->completeLogin($user);
    }

    public static function completeLogin(User $user): void
    {
        \Illuminate\Support\Facades\Auth::login($user, remember: false);
        $user->update(['last_login_at' => now()]);

        AuditLog::create([
            'actor_user_id' => $user->id,
            'actor_role'    => $user->role,
            'action_type'   => 'login',
            'entity_type'   => 'User',
            'entity_id'     => $user->id,
            'ip_address'    => request()->ip(),
            'device_info'   => request()->userAgent(),
        ]);
    }

    public function render()
    {
        return view('livewire.auth.login-page')
            ->layout('layouts.guest');
    }
}
