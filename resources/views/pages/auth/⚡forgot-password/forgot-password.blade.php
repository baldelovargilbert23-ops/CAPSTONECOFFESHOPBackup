@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="min-h-screen bg-[#f5f0e8] flex items-center justify-center p-4 sm:p-6 lg:p-8 antialiased text-[#1a3a3a] relative">

    {{-- Main container --}}
    <div class="relative w-full max-w-7xl grid grid-cols-1 lg:grid-cols-2 gap-0 lg:gap-12 xl:gap-20 items-center">

        {{-- ═══════════════════════════════════════════ --}}
        {{-- LEFT PANEL — Brand Visual --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="relative hidden lg:flex flex-col justify-between h-[85vh] min-h-[600px] rounded-3xl overflow-hidden p-10 xl:p-12">

            <div class="absolute inset-0 bg-cover bg-center"
                 style="background-image: url('https://images.unsplash.com/photo-1442512595331-e89e73853f31?w=1200&q=85');"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-[#f5f0e8]/75 via-[#f5f0e8]/55 to-[#c8965f]/35"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_right,_var(--tw-gradient-stops))] from-[#c8965f]/25 via-transparent to-transparent"></div>

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

            <div class="relative z-10 max-w-md">
                <h1 class="text-5xl xl:text-6xl font-black tracking-tight leading-[1.05] text-[#1a3a3a] mb-4">
                    No worries.<br>
                    We'll get you <span class="text-[#c8965f]">back in.</span>
                </h1>
                <p class="text-[#1a3a3a]/70 text-base leading-relaxed">
                    Enter your email and we'll send a 6-digit code so you can reset your password safely.
                </p>
            </div>

            <div class="relative z-10 flex items-center gap-3 text-[11px] text-[#1a3a3a]/60">
                <span class="w-8 h-px bg-[#c8965f]/50"></span>
                <span class="font-semibold uppercase tracking-widest">Est. 2024 · Victorias City</span>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- RIGHT PANEL — Forgot Password Form --}}
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

                {{-- Session messages --}}
                @if(session('error'))
                    <div class="mb-5 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-700 flex items-start gap-2">
                        <i class="fa fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <p>{{ session('error') }}</p>
                    </div>
                @endif

                @if(session('info'))
                    <div class="mb-5 p-3 bg-blue-500/10 border border-blue-500/20 rounded-xl text-xs text-blue-700 flex items-start gap-2">
                        <i class="fa fa-info-circle text-blue-500 mt-0.5"></i>
                        <p>{{ session('info') }}</p>
                    </div>
                @endif

                @if(session('code_sent') || $errors->has('code'))

                    {{-- ═══════════ STEP 2: CODE ENTRY ═══════════ --}}
                    <div class="mb-8 text-center">
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-[#1a3a3a] mb-2">
                            Reset your password
                        </h2>
                        <p class="text-sm text-[#1a3a3a]/60">
                            Enter the 6-digit code we sent to your email, then choose a new password.
                        </p>
                    </div>

                    @if($errors->has('code'))
                        <div class="mb-5 p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-700 flex items-start gap-2">
                            <i class="fa fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <p>{{ $errors->first('code') }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.reset') }}" class="space-y-5"
                          onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                        @csrf

                        {{-- Code --}}
                        <div class="space-y-2">
                            <label for="code" class="block text-xs font-bold text-[#1a3a3a] text-center">
                                Enter the 6-digit code
                            </label>
                            <input type="text"
                                   id="code"
                                   name="code"
                                   maxlength="6"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   autocomplete="one-time-code"
                                   autofocus
                                   required
                                   placeholder="000000"
                                   value="{{ old('code') }}"
                                   class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-xl py-3.5 px-4 text-center text-2xl font-black tracking-[0.5em] text-[#1a3a3a] placeholder:text-[#1a3a3a]/20 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none @error('code') border-red-500/50 @enderror">
                        </div>

                        {{-- Password + Confirm --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-data="{ show1: false, show2: false }">
                            <div class="space-y-2">
                                <label for="password" class="block text-xs font-bold text-[#1a3a3a]">
                                    New Password
                                </label>
                                <div class="relative">
                                    <i class="fa fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 text-xs pointer-events-none"></i>
                                    <input :type="show1 ? 'text' : 'password'"
                                           id="password"
                                           name="password"
                                           required
                                           placeholder="••••••••"
                                           class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-xl py-3 pl-10 pr-11 text-sm text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none @error('password') border-red-500/50 @enderror">
                                    <button type="button" @click="show1 = !show1"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 hover:text-[#c8965f] transition-colors p-1">
                                        <i class="fa text-xs" :class="show1 ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="text-[11px] text-red-500 flex items-center gap-1">
                                        <i class="fa fa-exclamation-circle text-[9px]"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="password_confirmation" class="block text-xs font-bold text-[#1a3a3a]">
                                    Confirm Password
                                </label>
                                <div class="relative">
                                    <i class="fa fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 text-xs pointer-events-none"></i>
                                    <input :type="show2 ? 'text' : 'password'"
                                           id="password_confirmation"
                                           name="password_confirmation"
                                           required
                                           placeholder="••••••••"
                                           class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-xl py-3 pl-10 pr-11 text-sm text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                                    <button type="button" @click="show2 = !show2"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 hover:text-[#c8965f] transition-colors p-1">
                                        <i class="fa text-xs" :class="show2 ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <button type="submit"
                                class="group w-full py-3.5 bg-[#1a3a3a] text-[#f5f0e8] font-bold rounded-xl hover:bg-[#0f2922] transition-all shadow-lg shadow-[#1a3a3a]/15 hover:shadow-xl active:scale-[0.98] flex items-center justify-center gap-2 text-sm disabled:opacity-60">
                            <i class="fa fa-check text-xs"></i>
                            <span>Reset password</span>
                        </button>
                    </form>

                    {{-- Start over --}}
                    <div class="text-center mt-6 pt-5 border-t border-[#1a3a3a]/5">
                        <a href="{{ route('password.request') }}"
                           class="text-xs font-bold text-[#c8965f] hover:opacity-80 transition flex items-center justify-center gap-1">
                            <i class="fa fa-arrow-left text-[10px]"></i>
                            <span>Start over</span>
                        </a>
                    </div>

                @else

                    {{-- ═══════════ STEP 1: EMAIL FORM ═══════════ --}}
                    <div class="mb-8 text-center">
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-[#1a3a3a] mb-2">
                            Forgot your password?
                        </h2>
                        <p class="text-sm text-[#1a3a3a]/60">
                            No worries — enter your email and we'll send a reset code.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('password.send-code') }}" class="space-y-5"
                          onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                        @csrf

                        {{-- Email --}}
                        <div class="space-y-2">
                            <label for="email" class="block text-xs font-bold text-[#1a3a3a]">
                                Email address
                            </label>
                            <div class="relative">
                                <i class="fa fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 text-xs pointer-events-none"></i>
                                <input type="email"
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

                        {{-- Submit --}}
                        <button type="submit"
                                class="group w-full py-3.5 bg-[#1a3a3a] text-[#f5f0e8] font-bold rounded-xl hover:bg-[#0f2922] transition-all shadow-lg shadow-[#1a3a3a]/15 hover:shadow-xl active:scale-[0.98] flex items-center justify-center gap-2 text-sm disabled:opacity-60">
                            <i class="fa fa-paper-plane text-xs"></i>
                            <span>Send reset code</span>
                        </button>
                    </form>

                    {{-- Back to login --}}
                    <div class="text-center mt-6 pt-5 border-t border-[#1a3a3a]/5">
                        <a href="{{ route('login') }}"
                           class="text-xs font-bold text-[#c8965f] hover:opacity-80 transition flex items-center justify-center gap-1">
                            <i class="fa fa-arrow-left text-[10px]"></i>
                            <span>Back to login</span>
                        </a>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection