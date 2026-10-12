@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="min-h-screen bg-[#f5f0e8] flex items-center justify-center p-4 sm:p-6 lg:p-8 antialiased text-[#1a3a3a] relative">

    {{-- Main container --}}
    <div class="relative w-full max-w-7xl grid grid-cols-1 lg:grid-cols-2 gap-0 lg:gap-12 xl:gap-20 items-center">

        {{-- ═══════════════════════════════════════════ --}}
        {{-- LEFT PANEL — Brand Visual --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="relative hidden lg:flex flex-col justify-between h-[85vh] min-h-[600px] rounded-3xl overflow-hidden p-10 xl:p-12">

            {{-- Background photo --}}
            <div class="absolute inset-0 bg-cover bg-center"
                 style="background-image: url('https://images.unsplash.com/photo-1442512595331-e89e73853f31?w=1200&q=85');"></div>

            {{-- Warm cream overlay --}}
            <div class="absolute inset-0 bg-gradient-to-br from-[#f5f0e8]/75 via-[#f5f0e8]/55 to-[#c8965f]/35"></div>

            {{-- Subtle amber glow --}}
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_right,_var(--tw-gradient-stops))] from-[#c8965f]/25 via-transparent to-transparent"></div>

            {{-- Top: Brand --}}
            <div class="relative z-10 flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-[#1a3a3a] flex items-center justify-center flex-shrink-0 shadow-lg">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 text-[#f5f0e8]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 8h1a4 4 0 1 1 0 8h-1"/>
                        <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                        <line x1="6" x2="6" y1="2" y2="4"/><line x1="10" x2="10" y1="2" y2="4"/><line x1="14" x2="14" y1="2" y2="4"/>
                    </svg>
                </div>
                <span class="text-lg font-black tracking-tight text-[#1a3a3a]">
                    Victorias<span class="text-[#c8965f]">Hub</span>
                </span>
            </div>

            {{-- Middle: Big tagline --}}
            <div class="relative z-10 max-w-md">
                <h1 class="text-5xl xl:text-6xl font-black tracking-tight leading-[1.05] text-[#1a3a3a] mb-4">
                    Every sip<br>
                    starts <span class="text-[#c8965f]">small.</span>
                </h1>
                <p class="text-[#1a3a3a]/70 text-base leading-relaxed">
                    Log in and continue your coffee journey — order from the best local shops in Victorias City.
                </p>
            </div>

            {{-- Bottom: Small note --}}
            <div class="relative z-10 flex items-center gap-3 text-[11px] text-[#1a3a3a]/60">
                <span class="w-8 h-px bg-[#c8965f]/50"></span>
                <span class="font-semibold uppercase tracking-widest">Est. 2024 · Victorias City</span>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- RIGHT PANEL — Login Form --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="w-full flex flex-col justify-center">

            {{-- Mobile logo --}}
            <div class="lg:hidden flex items-center justify-center gap-2 mb-8">
                <div class="w-10 h-10 rounded-xl bg-[#1a3a3a] flex items-center justify-center flex-shrink-0">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 text-[#f5f0e8]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 8h1a4 4 0 1 1 0 8h-1"/>
                        <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
                        <line x1="6" x2="6" y1="2" y2="4"/><line x1="10" x2="10" y1="2" y2="4"/><line x1="14" x2="14" y1="2" y2="4"/>
                    </svg>
                </div>
                <span class="text-lg font-black tracking-tight text-[#1a3a3a]">
                    Victorias<span class="text-[#c8965f]">Hub</span>
                </span>
            </div>

            {{-- Card wrapper --}}
            <div class="bg-[#faf6ef] rounded-3xl border border-[#1a3a3a]/10 shadow-lg shadow-[#1a3a3a]/5 p-8 sm:p-10 lg:p-12 w-full max-w-md mx-auto">

                {{-- Header --}}
                <div class="mb-8 text-center">
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-[#1a3a3a] mb-2">
                        Log in to your account
                    </h2>
                    <p class="text-sm text-[#1a3a3a]/60">
                        Welcome back! Please enter your details.
                    </p>
                </div>

                {{-- Session messages --}}
                @if(session('info'))
                    <div class="mb-5 p-3 bg-blue-500/10 border border-blue-500/20 rounded-xl text-xs text-blue-700 flex items-start gap-2">
                        <i class="fa fa-info-circle text-blue-500 mt-0.5"></i>
                        <p>{{ session('info') }}</p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-5 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-700 flex items-start gap-2">
                        <i class="fa fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <p>{{ session('error') }}</p>
                    </div>
                @endif

                @if(session('pending_message'))
                    <div class="mb-5 p-3 bg-[#c8965f]/10 border border-[#c8965f]/20 rounded-xl text-xs text-[#c8965f]">
                        <p class="font-bold mb-1">⏳ Pending Approval</p>
                        <p class="opacity-80">{{ session('pending_message') }}</p>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-5 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-700 flex items-start gap-2">
                        <i class="fa fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <div class="space-y-0.5">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Login Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div class="space-y-2">
                        <label for="email" class="block text-xs font-bold text-[#1a3a3a]">
                            Email address
                        </label>
                        <div class="relative">
                            <i class="fa fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 text-xs pointer-events-none"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                placeholder="Enter your email"
                                class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-xl py-3 pl-10 pr-4 text-sm text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none @error('email') border-red-500/50 @enderror">
                        </div>
                        @error('email')
                            <p class="text-[11px] text-red-500 flex items-center gap-1">
                                <i class="fa fa-exclamation-circle text-[9px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="space-y-2">
                        <label for="password" class="block text-xs font-bold text-[#1a3a3a]">
                            Password
                        </label>
                        <div class="relative">
                            <i class="fa fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 text-xs pointer-events-none"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                placeholder="Enter your password"
                                class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-xl py-3 pl-10 pr-11 text-sm text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none @error('password') border-red-500/50 @enderror">
                            <button type="button"
                                    id="togglePassword"
                                    aria-label="Toggle password visibility"
                                    style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: transparent; border: 0; cursor: pointer; padding: 4px; color: rgba(26, 58, 58, 0.4);">
                                <i id="eyeIcon" class="fa fa-eye text-xs"></i>
                                <i id="eyeOffIcon" class="fa fa-eye-slash text-xs" style="display: none;"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-[11px] text-red-500 flex items-center gap-1">
                                <i class="fa fa-exclamation-circle text-[9px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Remember + Forgot --}}
                    <div class="flex items-center justify-between">
                        <label for="remember" class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox"
                                   id="remember"
                                   name="remember"
                                   class="w-3.5 h-3.5 rounded border-[#1a3a3a]/20 bg-[#f5f0e8] text-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/30 focus:ring-offset-0 transition cursor-pointer">
                            <span class="text-[11px] text-[#1a3a3a]/70 group-hover:text-[#1a3a3a] transition-colors">
                                Remember me
                            </span>
                        </label>

                        @if(Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-[11px] font-bold text-[#c8965f] hover:opacity-80 transition">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="group w-full py-3.5 bg-[#1a3a3a] text-[#f5f0e8] font-bold rounded-xl hover:bg-[#0f2922] transition-all shadow-lg shadow-[#1a3a3a]/15 hover:shadow-xl active:scale-[0.98] flex items-center justify-center gap-2 text-sm">
                        <i class="fa fa-sign-in-alt text-xs"></i>
                        <span>Log in</span>
                    </button>
                </form>

                {{-- Register link --}}
                <p class="text-center text-xs text-[#1a3a3a]/60 mt-6">
                    Don't have an account?
                    <a href="{{ route('register.customer.form') }}"
                       class="font-bold text-[#c8965f] hover:opacity-80 transition">
                        Sign up
                    </a>
                </p>

                {{-- Owner signup hint --}}
                <p class="text-center text-xs text-[#1a3a3a]/50 mt-3 pt-4 border-t border-[#1a3a3a]/5">
                    Own a coffee shop?
                    <a href="{{ route('register.owner.form') }}"
                       class="font-bold text-[#c8965f] hover:opacity-80 transition">
                        Become a partner
                    </a>
                </p>
            </div>

        </div>
    </div>
</div>

{{-- Password show/hide toggle script --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggleBtn     = document.getElementById('togglePassword');
        var passwordInput = document.getElementById('password');
        var eyeIcon       = document.getElementById('eyeIcon');
        var eyeOffIcon    = document.getElementById('eyeOffIcon');

        if (!toggleBtn || !passwordInput || !eyeIcon || !eyeOffIcon) return;

        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();

            var isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';

            // Use inline styles so nothing can override them
            eyeIcon.style.display    = isHidden ? 'none' : 'inline-block';
            eyeOffIcon.style.display = isHidden ? 'inline-block' : 'none';

            passwordInput.focus();
        });
    });
</script>
@endsection