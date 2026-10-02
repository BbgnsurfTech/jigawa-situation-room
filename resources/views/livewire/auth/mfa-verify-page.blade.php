<div class="min-h-screen flex items-center justify-center bg-green-950 px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="text-white text-3xl font-bold tracking-wide">JESR</div>
            <div class="text-green-300 text-sm mt-1">Jigawa State Election Situation Room</div>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mx-auto mb-4">
                <svg class="w-6 h-6 text-green-700" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>

            <h2 class="text-xl font-semibold text-gray-800 mb-2 text-center">Two-Factor Verification</h2>

            {{-- P1: live countdown timer replaces static "expires in 5 minutes" text --}}
            <div
                x-data="{
                    remaining: 0,
                    expiresAt: {{ $expiresAt }},
                    interval: null,
                    init() {
                        this.tick();
                        this.interval = setInterval(() => this.tick(), 1000);
                    },
                    tick() {
                        this.remaining = Math.max(0, this.expiresAt - Math.floor(Date.now() / 1000));
                        if (this.remaining === 0) clearInterval(this.interval);
                    },
                    format(s) {
                        const m = Math.floor(s / 60);
                        const sec = s % 60;
                        return m + ':' + String(sec).padStart(2, '0');
                    }
                }"
                class="text-sm text-gray-500 text-center mb-6"
            >
                <span x-show="remaining > 0">
                    A 6-digit code was sent to your phone. Expires in
                    <span x-text="format(remaining)" class="font-mono font-semibold text-gray-700"></span>.
                </span>
                <span x-show="remaining === 0" class="text-red-600 font-medium">
                    Your code has expired. Please request a new one below.
                </span>
            </div>

            @if($error)
                <div role="alert" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                    {{ $error }}
                </div>
            @endif

            @if($infoMsg)
                <div role="status" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ $infoMsg }}
                </div>
            @endif

            {{-- P2: label/id linkage, autofocus --}}
            <form wire:submit="verify" class="space-y-4">
                <div>
                    <label for="otp-input" class="block text-sm font-medium text-gray-700 mb-1">One-Time Code</label>
                    <input
                        wire:model="otp"
                        id="otp-input"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        placeholder="000000"
                        autocomplete="one-time-code"
                        autofocus
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 text-2xl text-center tracking-[0.5em] font-mono
                               focus:ring-2 focus:ring-green-600 focus:border-transparent outline-none"
                    />
                    @error('otp') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                    class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg
                           transition text-sm disabled:opacity-60">
                    <span wire:loading.remove>Verify Code</span>
                    <span wire:loading aria-live="polite">Verifying...</span>
                </button>
            </form>

            {{-- P1: Resend OTP option — was missing; users were stuck if SMS was lost --}}
            <div class="mt-4 text-center space-y-2">
                <button wire:click="resendOtp" wire:loading.attr="disabled"
                    class="text-xs text-green-700 hover:text-green-800 underline disabled:opacity-50">
                    Didn't receive a code? Resend
                </button>
                <div>
                    <a href="{{ route('login') }}" class="text-xs text-gray-400 hover:text-gray-600 transition">
                        ← Back to login
                    </a>
                </div>
            </div>

            <p class="text-center text-xs text-gray-400 mt-6">
                Independent National Electoral Commission — Jigawa State
            </p>
        </div>
    </div>
</div>
