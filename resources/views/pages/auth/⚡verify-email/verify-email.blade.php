
<div class="min-h-screen bg-[#f5f0e8] flex items-center justify-center p-4 sm:p-6 lg:p-8 antialiased text-[#1a3a3a]">

    <div class="w-full max-w-md">

        <div class="flex items-center justify-center gap-2 mb-8">
            <div class="w-10 h-10 rounded-xl bg-[#1a3a3a] flex items-center justify-center flex-shrink-0">
                <svg viewBox="0 0 24 24" class="w-5 h-5 text-[#f5f0e8]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 8h1a4 4 0 1 1 0 8h-1"/>
                    <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                    <line x1="6" x2="6" y1="2" y2="4"/><line x1="10" x2="10" y1="2" y2="4"/><line x1="14" x2="14" y1="2" y2="4"/>
                </svg>
            </div>
            <span class="text-lg font-black tracking-tight">
                Victorias<span class="text-[#c8965f]">Hub</span>
            </span>
        </div>

        <div class="bg-[#faf6ef] rounded-3xl border border-[#1a3a3a]/10 shadow-lg p-8 sm:p-10">

            <div class="w-16 h-16 mx-auto rounded-2xl bg-[#c8965f]/10 border border-[#c8965f]/20 flex items-center justify-center mb-5">
                <i class="fa fa-envelope-circle-check text-2xl text-[#c8965f]"></i>
            </div>

            <div class="text-center mb-6">
                <h2 class="text-2xl font-black tracking-tight mb-2">Verify your email</h2>
                <p class="text-sm text-[#1a3a3a]/60">
                    We sent a 6-digit code to <strong>{{ auth()->user()->email }}</strong>
                </p>
            </div>

            @if(session('info'))
                <div class="mb-5 p-3 bg-blue-500/10 border border-blue-500/20 rounded-xl text-xs text-blue-700 flex items-start gap-2">
                    <i class="fa fa-info-circle text-blue-500 mt-0.5"></i>
                    <p>{{ session('info') }}</p>
                </div>
            @endif

            @if($resent)
                <div class="mb-5 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-xs text-emerald-700 flex items-start gap-2">
                    <i class="fa fa-check-circle text-emerald-500 mt-0.5"></i>
                    <p>New code sent! Check your inbox (and spam folder).</p>
                </div>
            @endif

            <form wire:submit.prevent="verify" class="space-y-5">

                <div class="space-y-2">
                    <label for="code" class="block text-xs font-bold text-center">Enter the 6-digit code</label>
                    <input type="text"
                           id="code"
                           wire:model="code"
                           maxlength="6"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           autocomplete="one-time-code"
                           autofocus
                           placeholder="000000"
                           class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-xl py-4 px-4 text-center text-2xl font-black tracking-[0.5em] text-[#1a3a3a] placeholder:text-[#1a3a3a]/20 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none @error('code') border-red-500/50 @enderror">

                    @error('code')
                        <p class="text-[11px] text-red-500 text-center flex items-center justify-center gap-1">
                            <i class="fa fa-exclamation-circle text-[9px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="verify"
                        class="w-full py-3.5 bg-[#1a3a3a] text-[#f5f0e8] font-bold rounded-xl hover:bg-[#0f2922] transition-all active:scale-[0.98] flex items-center justify-center gap-2 text-sm disabled:opacity-60">
                    <span wire:loading.remove wire:target="verify">
                        <i class="fa fa-check text-xs mr-1"></i> Verify email
                    </span>
                    <span wire:loading wire:target="verify">
                        <i class="fa fa-spinner fa-spin text-xs mr-1"></i> Verifying...
                    </span>
                </button>
            </form>

            <div class="text-center mt-6 pt-6 border-t border-[#1a3a3a]/5 space-y-2">
                <p class="text-xs text-[#1a3a3a]/60">Didn't receive the code?</p>
                <button type="button"
                        wire:click="resend"
                        wire:loading.attr="disabled"
                        wire:target="resend"
                        class="text-xs font-bold text-[#c8965f] hover:opacity-80 transition disabled:opacity-60">
                    <span wire:loading.remove wire:target="resend">
                        <i class="fa fa-rotate-right text-[10px] mr-1"></i> Resend code
                    </span>
                    <span wire:loading wire:target="resend">
                        <i class="fa fa-spinner fa-spin text-[10px] mr-1"></i> Sending...
                    </span>
                </button>

                <div class="pt-2">
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-[11px] text-[#1a3a3a]/50 hover:text-[#c8965f] transition">
                            Use a different account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>