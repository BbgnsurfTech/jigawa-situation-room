<?php
namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class MfaVerifyPage extends Component
{
    public string $otp        = '';
    public string $error      = '';
    public string $infoMsg    = '';
    public bool   $loading    = false;
    public int    $expiresAt  = 0;   // unix timestamp — used by JS countdown

    public function mount(): void
    {
        // If no pending MFA session exists, send back to login
        if (!session()->has('mfa_pending_user_id')) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        // If the session itself has expired (belt-and-suspenders check)
        if (now()->timestamp > (int) session('mfa_pending_expires_at', 0)) {
            session()->forget(['mfa_pending_user_id', 'mfa_pending_expires_at']);
            $this->redirect(route('login'), navigate: true);
            return;
        }

        $this->expiresAt = (int) session('mfa_pending_expires_at', 0);
    }

    // P1: Resend OTP — P0 fix; user was previously stuck if SMS was lost
    public function resendOtp(): void
    {
        $userId = session('mfa_pending_user_id');
        if (!$userId) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        $throttleKey = 'mfa-resend:' . $userId . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds     = RateLimiter::availableIn($throttleKey);
            $this->error = "Too many resend attempts. Try again in {$seconds} seconds.";
            return;
        }
        RateLimiter::hit($throttleKey, 120);

        $user = User::find($userId);
        if (!$user) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['mfa_otp' => $otp, 'mfa_otp_expires_at' => now()->addMinutes(5)]);
        $newExpiry = now()->addMinutes(5)->timestamp;
        session(['mfa_pending_expires_at' => $newExpiry]);
        $this->expiresAt = $newExpiry;

        app(SmsService::class)->send(
            $user->phone,
            "JESR MFA code: {$otp}. Valid for 5 minutes. Do not share."
        );

        $this->error   = '';
        $this->infoMsg = 'A new code has been sent to your phone.';
        $this->otp     = '';
    }

    public function verify(): void
    {
        $this->validate(['otp' => 'required|string|size:6']);

        $userId = session('mfa_pending_user_id');

        // Rate-limit OTP guessing — 5 attempts per minute
        $throttleKey = 'mfa-web:' . $userId . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds     = RateLimiter::availableIn($throttleKey);
            $this->error = "Too many attempts. Try again in {$seconds} seconds.";
            return;
        }

        $user = User::find($userId);

        $valid = $user
            && $user->mfa_otp
            && $user->mfa_otp_expires_at
            && now()->isBefore($user->mfa_otp_expires_at)
            && hash_equals($user->mfa_otp, $this->otp);

        if (!$valid) {
            RateLimiter::hit($throttleKey, 60);
            $this->error = 'Invalid or expired OTP.';
            return;
        }

        // Success — clear state and complete login
        RateLimiter::clear($throttleKey);

        $user->update(['mfa_otp' => null, 'mfa_otp_expires_at' => null]);
        session()->forget(['mfa_pending_user_id', 'mfa_pending_expires_at']);

        LoginPage::completeLogin($user);

        $this->redirect(
            $user->isWardAgent() ? route('reports.create') : route('dashboard'),
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.auth.mfa-verify-page')
            ->layout('layouts.guest');
    }
}
