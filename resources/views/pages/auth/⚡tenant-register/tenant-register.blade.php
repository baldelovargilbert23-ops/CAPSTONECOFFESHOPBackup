@extends('layouts.auth')

@section('title', 'Register Owner')

@section('content')
<div class="min-h-screen bg-[#f5f0e8] flex items-center justify-center p-4 sm:p-6 antialiased text-[#1a3a3a] relative overflow-hidden">

    {{-- Background Ambience --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -left-40 w-[500px] h-[500px] bg-[#c8965f]/10 rounded-full blur-[120px]"></div>
        <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] bg-[#c8965f]/10 rounded-full blur-[120px]"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-[#c8965f]/5 rounded-full blur-[150px]"></div>
    </div>

    {{-- Main Card --}}
    <div class="relative w-full max-w-6xl grid lg:grid-cols-2 bg-[#faf6ef] rounded-[2rem] overflow-hidden shadow-[0_40px_120px_-30px_rgba(26,58,58,0.15)] border border-[#1a3a3a]/10">

        {{-- LEFT PANEL — Brand Visual --}}
        <div class="relative hidden lg:flex flex-col justify-between p-12 overflow-hidden bg-[#1a3a3a]">

            {{-- Background photo with fallback --}}
            <div class="absolute inset-0 bg-cover bg-center scale-105 bg-[#1a3a3a]"
                 style="background-image: url('https://images.unsplash.com/photo-1442512595331-e89e73853f31?w=1200&q=85');"></div>

            <div class="absolute inset-0 bg-gradient-to-br from-[#f5f0e8]/90 via-[#f5f0e8]/75 to-[#c8965f]/50"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,_var(--tw-gradient-stops))] from-[#c8965f]/20 via-transparent to-transparent"></div>
            <div class="absolute inset-0 opacity-[0.04]"
                 style="background-image: url(&quot;data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E&quot;);"></div>

            <div class="relative z-10 flex items-center gap-3">
                <div class="w-11 h-11 flex-shrink-0">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <defs>
                            <linearGradient id="brandGradLg" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#c8965f"/>
                                <stop offset="100%" stop-color="#1a3a3a"/>
                            </linearGradient>
                        </defs>
                        <circle cx="50" cy="50" r="48" fill="url(#brandGradLg)"/>
                        <circle cx="50" cy="50" r="44" fill="none" stroke="rgba(245,240,232,0.15)" stroke-width="1"/>
                        <path d="M 22 30 L 50 84 L 78 30 L 68 30 L 50 66 L 32 30 Z" fill="#f5f0e8"/>
                        <g stroke="#f5f0e8" stroke-width="2.5" fill="none" stroke-linecap="round">
                            <path d="M 50 28 Q 46 24 50 20 Q 54 16 50 12"/>
                            <path d="M 43 26 Q 40 22 43 18 Q 46 14 42 11" opacity="0.9"/>
                            <path d="M 57 26 Q 60 22 57 18 Q 54 14 58 11" opacity="0.9"/>
                        </g>
                    </svg>
                </div>
                <div>
                    <p class="text-base font-black tracking-tighter text-[#1a3a3a] uppercase italic leading-none">
                        Victorias<span class="text-[#c8965f]">Hub</span>
                    </p>
                    <p class="text-[10px] text-[#c8965f]/80 font-bold uppercase tracking-[0.2em] mt-1">Partner Program</p>
                </div>
            </div>

            <div class="relative z-10 space-y-6">
                <div class="flex items-center gap-2">
                    <span class="text-[#c8965f] text-2xl">✦</span>
                    <span class="text-xs font-bold tracking-[0.3em] text-[#c8965f] uppercase">Become a Partner</span>
                </div>

                <h1 class="text-5xl xl:text-6xl font-black tracking-tighter leading-[1.05] text-[#1a3a3a]">
                    Open your<br>
                    <span class="bg-gradient-to-r from-[#c8965f] to-[#a87a4a] bg-clip-text text-transparent">coffee shop.</span>
                </h1>

                <p class="text-[#1a3a3a]/70 text-base leading-relaxed max-w-md">
                    Join our multi-tenant collective. Get your shop in front of thousands of coffee lovers in Victorias City.
                </p>

                <div class="space-y-3 pt-4">
                    @foreach([
                        ['icon' => 'fa-chart-line',    'text' => 'Real-time sales analytics'],
                        ['icon' => 'fa-users',         'text' => 'Reach local coffee lovers'],
                        ['icon' => 'fa-crown',         'text' => 'Full control over your menu'],
                        ['icon' => 'fa-shield-alt',    'text' => 'Secure online payments'],
                    ] as $benefit)
                        <div class="flex items-center gap-3 text-sm text-[#1a3a3a]/80">
                            <div class="w-8 h-8 rounded-xl bg-[#1a3a3a]/5 backdrop-blur-md border border-[#1a3a3a]/10 flex items-center justify-center flex-shrink-0">
                                <i class="fa {{ $benefit['icon'] }} text-[#c8965f] text-xs"></i>
                            </div>
                            <span>{{ $benefit['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative z-10 flex items-center gap-3 text-[11px] text-[#1a3a3a]/50">
                <span class="w-8 h-px bg-[#1a3a3a]/20"></span>
                <span class="font-semibold uppercase tracking-widest">Free to apply · Approval within 24h</span>
            </div>
        </div>

        {{-- RIGHT PANEL — Owner Register Form --}}
        <div class="relative p-8 sm:p-12 lg:p-14 flex flex-col justify-center bg-[#faf6ef] overflow-y-auto max-h-screen lg:max-h-none"
             x-data="registerWizard()">

            {{-- Mobile logo --}}
            <div class="lg:hidden flex flex-col items-center mb-8">
                <div class="w-20 h-20">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <defs>
                            <linearGradient id="brandGradMob" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#c8965f"/>
                                <stop offset="100%" stop-color="#1a3a3a"/>
                            </linearGradient>
                        </defs>
                        <circle cx="50" cy="50" r="48" fill="url(#brandGradMob)"/>
                        <circle cx="50" cy="50" r="44" fill="none" stroke="rgba(245,240,232,0.15)" stroke-width="1"/>
                        <path d="M 22 30 L 50 84 L 78 30 L 68 30 L 50 66 L 32 30 Z" fill="#f5f0e8"/>
                        <g stroke="#f5f0e8" stroke-width="2.5" fill="none" stroke-linecap="round">
                            <path d="M 50 28 Q 46 24 50 20 Q 54 16 50 12"/>
                            <path d="M 43 26 Q 40 22 43 18 Q 46 14 42 11" opacity="0.9"/>
                            <path d="M 57 26 Q 60 22 57 18 Q 54 14 58 11" opacity="0.9"/>
                        </g>
                    </svg>
                </div>
                <p class="text-lg font-black tracking-tighter text-[#1a3a3a] uppercase italic mt-3">
                    Victorias<span class="text-[#c8965f]">Hub</span>
                </p>
                <p class="text-[10px] font-bold tracking-[0.3em] text-[#c8965f]/70 uppercase mt-1">Become a Partner</p>
            </div>

            {{-- STATE A — CODE ENTRY --}}
            @if(session('code_sent'))

                <div class="mb-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-[#c8965f]/10 border border-[#c8965f]/20 flex items-center justify-center mb-4">
                        <i class="fa fa-envelope-circle-check text-2xl text-[#c8965f]"></i>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black tracking-tight text-[#1a3a3a] mb-2">Check your email</h2>
                    <p class="text-[#1a3a3a]/60 text-sm">
                        We sent a 6-digit code to your inbox. Enter it below to finish your application.
                    </p>
                </div>

                @if(session('info'))
                    <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-700 flex items-start gap-3">
                        <i class="fa fa-check-circle text-emerald-500 mt-0.5"></i>
                        <p>{{ session('info') }}</p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-5 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-sm text-red-700 flex items-start gap-3">
                        <i class="fa fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <p>{{ session('error') }}</p>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-5 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-sm text-red-700 flex items-start gap-3">
                        <i class="fa fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <div class="space-y-1">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.owner') }}" class="space-y-5"
                      onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                    @csrf

                    <div class="space-y-2">
                        <label for="code" class="block text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em] text-center">
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
                               class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-2xl py-4 px-5 text-center text-2xl font-black tracking-[0.5em] text-[#1a3a3a] placeholder:text-[#1a3a3a]/20 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none @error('code') border-red-500/50 @enderror">
                        @error('code')
                            <p class="text-xs text-red-500 text-center flex items-center justify-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="group relative w-full py-4 px-6 bg-[#1a3a3a] text-[#f5f0e8] font-bold rounded-2xl hover:bg-[#0f2922] transition-all duration-300 shadow-lg shadow-[#1a3a3a]/15 hover:shadow-xl active:scale-[0.98] flex items-center justify-center gap-2 overflow-hidden disabled:opacity-60">
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                        <i class="fa fa-check text-sm"></i>
                        <span>Create Account</span>
                    </button>
                </form>

                <div class="flex items-center gap-3 my-6">
                    <span class="flex-1 h-px bg-[#1a3a3a]/10"></span>
                    <span class="text-[10px] font-bold text-[#1a3a3a]/40 uppercase tracking-widest">Didn't get it?</span>
                    <span class="flex-1 h-px bg-[#1a3a3a]/10"></span>
                </div>

                <a href="{{ route('register.owner.fresh') }}"
                   class="w-full py-3 px-4 bg-[#f5f0e8]/60 hover:bg-[#f5f0e8] border border-[#1a3a3a]/10 hover:border-[#c8965f]/40 rounded-xl transition-all text-xs font-bold text-[#1a3a3a]/70 hover:text-[#c8965f] flex items-center justify-center gap-2">
                    <i class="fa fa-arrow-left text-[10px]"></i>
                    <span>Start over</span>
                </a>

            @else

            {{-- STATE B — WIZARD --}}

            {{-- Header --}}
            <div class="mb-6">
                <span class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.2em] text-[#c8965f] uppercase bg-[#c8965f]/10 px-3 py-1.5 rounded-full border border-[#c8965f]/20 mb-4">
                    <i class="fa fa-store text-[9px]"></i> Shop Owner Registration
                </span>
                <h2 class="text-3xl sm:text-4xl font-black tracking-tight text-[#1a3a3a] mb-2">Set up your shop.</h2>
                <p class="text-[#1a3a3a]/60 text-sm">Takes about 3 minutes to complete</p>
            </div>

            {{-- STEP PROGRESS INDICATOR --}}
            <div class="mb-8">
                <div class="flex items-center justify-between relative">
                    <div class="absolute top-5 left-0 right-0 h-0.5 bg-[#1a3a3a]/10 -z-0"></div>
                    <div class="absolute top-5 left-0 h-0.5 bg-gradient-to-r from-[#c8965f] to-[#a87a4a] transition-all duration-500 -z-0"
                         :style="`width: ${((step - 1) / 2) * 100}%`"></div>

                    <div class="flex flex-col items-center relative z-10 flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all duration-300 font-bold text-xs"
                             :class="step >= 1 ? 'bg-gradient-to-br from-[#c8965f] to-[#a87a4a] border-[#c8965f] text-[#f5f0e8] shadow-lg shadow-[#c8965f]/30' : 'bg-[#f5f0e8] border-[#1a3a3a]/20 text-[#1a3a3a]/40'">
                            <template x-if="step > 1"><i class="fa fa-check"></i></template>
                            <template x-if="step <= 1"><span>1</span></template>
                        </div>
                        <span class="mt-2 text-[10px] font-bold uppercase tracking-wider transition-colors"
                              :class="step >= 1 ? 'text-[#c8965f]' : 'text-[#1a3a3a]/40'">Personal</span>
                    </div>

                    <div class="flex flex-col items-center relative z-10 flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all duration-300 font-bold text-xs"
                             :class="step >= 2 ? 'bg-gradient-to-br from-[#c8965f] to-[#a87a4a] border-[#c8965f] text-[#f5f0e8] shadow-lg shadow-[#c8965f]/30' : 'bg-[#f5f0e8] border-[#1a3a3a]/20 text-[#1a3a3a]/40'">
                            <template x-if="step > 2"><i class="fa fa-check"></i></template>
                            <template x-if="step <= 2"><span>2</span></template>
                        </div>
                        <span class="mt-2 text-[10px] font-bold uppercase tracking-wider transition-colors"
                              :class="step >= 2 ? 'text-[#c8965f]' : 'text-[#1a3a3a]/40'">Business</span>
                    </div>

                    <div class="flex flex-col items-center relative z-10 flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all duration-300 font-bold text-xs"
                             :class="step >= 3 ? 'bg-gradient-to-br from-[#c8965f] to-[#a87a4a] border-[#c8965f] text-[#f5f0e8] shadow-lg shadow-[#c8965f]/30' : 'bg-[#f5f0e8] border-[#1a3a3a]/20 text-[#1a3a3a]/40'">
                            <span>3</span>
                        </div>
                        <span class="mt-2 text-[10px] font-bold uppercase tracking-wider transition-colors"
                              :class="step >= 3 ? 'text-[#c8965f]' : 'text-[#1a3a3a]/40'">Verify</span>
                    </div>
                </div>
            </div>

            {{-- Server-side error banner --}}
            @if($errors->any())
                <div class="mb-5 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-sm text-red-700 flex items-start gap-3">
                    <i class="fa fa-exclamation-circle text-red-500 mt-0.5"></i>
                    <div class="space-y-1">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register.owner.send-code') }}" enctype="multipart/form-data" class="space-y-5" id="ownerRegisterForm"
                  @submit="if (!validateStep(3)) { $event.preventDefault(); return false; } else { $nextTick(() => { document.getElementById('ownerSubmitBtn').disabled = true; document.getElementById('ownerSubmitBtn').innerHTML = '<i class=\'fa fa-spinner fa-spin\'></i> Sending...'; }); }">
                @csrf

                {{-- ============================================= --}}
                {{-- STEP 1: PERSONAL INFORMATION --}}
                {{-- ============================================= --}}
                <div x-show="step === 1" x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0">

                    <div class="mb-4">
                        <p class="flex items-center gap-2 text-[10px] font-bold text-[#c8965f] uppercase tracking-[0.2em] mb-1">
                            <span class="w-5 h-px bg-[#c8965f]/50"></span>
                            Step 1 of 3
                        </p>
                        <h3 class="text-lg font-bold text-[#1a3a3a]">Tell us about yourself</h3>
                        <p class="text-xs text-[#1a3a3a]/60 mt-1">We'll use this to contact you about your application.</p>
                    </div>

                    {{-- First Name + Last Name row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">

                        {{-- First Name --}}
                        <div class="space-y-2">
                            <label for="first_name" class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em] whitespace-nowrap">
                                    <i class="fa fa-user text-[#c8965f]"></i> First Name
                                </span>
                                <span class="text-[9px] font-mono font-bold shrink-0"
                                      :class="(form.first_name || '').length > 40 ? 'text-[#d97706]' : 'text-[#1a3a3a]/40'">
                                    <span x-text="(form.first_name || '').length"></span>/50
                                </span>
                            </label>
                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                value="{{ old('first_name') }}"
                                x-model="form.first_name"
                                @input="form.first_name = capitalizeWords($event.target.value)"
                                @blur="validateField('first_name')"
                                maxlength="50"
                                placeholder="Gilbert"
                                autocomplete="given-name"
                                :class="errors.first_name ? 'border-red-500/60' : 'border-[#1a3a3a]/10'"
                                class="w-full bg-[#f5f0e8] border rounded-2xl py-3.5 px-5 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                            <p x-show="errors.first_name" x-transition class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i>
                                <span x-text="errors.first_name"></span>
                            </p>
                            @error('first_name')
                                <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                    <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Last Name --}}
                        <div class="space-y-2">
                            <label for="last_name" class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em] whitespace-nowrap">
                                    <i class="fa fa-user text-[#c8965f]"></i> Last Name
                                </span>
                                <span class="text-[9px] font-mono font-bold shrink-0"
                                      :class="(form.last_name || '').length > 40 ? 'text-[#d97706]' : 'text-[#1a3a3a]/40'">
                                    <span x-text="(form.last_name || '').length"></span>/50
                                </span>
                            </label>
                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                value="{{ old('last_name') }}"
                                x-model="form.last_name"
                                @input="form.last_name = capitalizeWords($event.target.value)"
                                @blur="validateField('last_name')"
                                maxlength="50"
                                placeholder="Baldelovar"
                                autocomplete="family-name"
                                :class="errors.last_name ? 'border-red-500/60' : 'border-[#1a3a3a]/10'"
                                class="w-full bg-[#f5f0e8] border rounded-2xl py-3.5 px-5 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                            <p x-show="errors.last_name" x-transition class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i>
                                <span x-text="errors.last_name"></span>
                            </p>
                            @error('last_name')
                                <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                    <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    {{-- Middle Name (full width, optional) --}}
                    <div class="space-y-2 mb-4">
                        <label for="middle_name" class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em] whitespace-nowrap">
                                <i class="fa fa-user text-[#c8965f]/60"></i> Middle Name
                                <span class="text-[9px] font-bold text-[#c8965f] uppercase tracking-wider bg-[#c8965f]/10 px-2 py-0.5 rounded-md border border-[#c8965f]/20">
                                    Optional
                                </span>
                            </span>
                            <span class="text-[9px] font-mono font-bold shrink-0 text-[#1a3a3a]/40">
                                <span x-text="(form.middle_name || '').length"></span>/50
                            </span>
                        </label>
                        <input
                            type="text"
                            id="middle_name"
                            name="middle_name"
                            value="{{ old('middle_name') }}"
                            x-model="form.middle_name"
                            @input="form.middle_name = capitalizeWords($event.target.value)"
                            maxlength="50"
                            placeholder="Anieve"
                            autocomplete="additional-name"
                            class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-2xl py-3.5 px-5 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                        @error('middle_name')
                            <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Full Name Preview --}}
                    <div x-show="form.first_name && form.last_name"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mb-4 p-3 rounded-2xl flex items-center gap-3"
                         style="background-color: color-mix(in srgb, #c8965f 6%, transparent); border: 1px dashed color-mix(in srgb, #c8965f 30%, transparent);">
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                             style="background-color: color-mix(in srgb, #c8965f 15%, transparent); color: #c8965f;">
                            <i class="fa fa-id-badge text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-[0.15em] mb-0.5"
                               style="color: #c8965f;">
                                Your full name
                            </p>
                            <p class="text-sm font-bold truncate" style="color: #1a3a3a;"
                               x-text="fullName"></p>
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="space-y-2 mb-4">
                        <label for="email" class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em]">
                                <i class="fa fa-envelope text-[#c8965f]"></i> Email Address
                            </span>
                            <span class="text-[9px] font-mono font-bold text-[#1a3a3a]/40">
                                <span x-text="(form.email || '').length"></span>/255
                            </span>
                        </label>
                        <div class="relative">
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                x-model="form.email"
                                @blur="validateEmailField()"
                                maxlength="255"
                                placeholder="you@example.com"
                                autocomplete="email"
                                :class="errors.email ? 'border-red-500/60' : (emailStatus === 'available' ? 'border-emerald-500/50' : 'border-[#1a3a3a]/10')"
                                class="w-full bg-[#f5f0e8] border rounded-2xl py-3.5 px-5 pr-11 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                <i x-show="emailStatus === 'checking'" class="fa fa-spinner fa-spin text-[#c8965f] text-xs"></i>
                                <i x-show="emailStatus === 'available'" class="fa fa-check-circle text-emerald-500 text-sm"></i>
                                <i x-show="emailStatus === 'taken'" class="fa fa-times-circle text-red-500 text-sm"></i>
                            </div>
                        </div>
                        <p x-show="errors.email" x-transition class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                            <i class="fa fa-exclamation-circle text-[10px]"></i>
                            <span x-text="errors.email"></span>
                        </p>
                        <p x-show="emailStatus === 'available' && !errors.email" x-transition class="text-xs text-emerald-600 flex items-center gap-1.5 mt-1">
                            <i class="fa fa-check-circle text-[10px]"></i>
                            <span>Email is available</span>
                        </p>
                        @error('email')
                            <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Phone — STRICT 11-digit PH format --}}
                    <div class="space-y-2">
                        <label for="owner_phone" class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em]">
                                <i class="fa fa-phone text-[#c8965f]"></i> Phone Number
                            </span>
                            <span class="text-[9px] font-mono font-bold"
                                  :class="{
                                      'text-emerald-600': isPhoneValid(),
                                      'text-red-500': (form.owner_phone || '').length > 0 && !isPhoneValid(),
                                      'text-[#1a3a3a]/40': (form.owner_phone || '').length === 0
                                  }">
                                <span x-text="(form.owner_phone || '').replace(/[^0-9]/g, '').length"></span>/11
                            </span>
                        </label>
                        <input
                            type="tel"
                            id="owner_phone"
                            name="owner_phone"
                            value="{{ old('owner_phone') }}"
                            x-model="form.owner_phone"
                            @input="form.owner_phone = sanitizePhone($event.target.value)"
                            @blur="validateField('owner_phone')"
                            maxlength="13"
                            inputmode="numeric"
                            placeholder="09123456789"
                            autocomplete="tel"
                            :class="errors.owner_phone ? 'border-red-500/60' : (isPhoneValid() ? 'border-emerald-500/50' : 'border-[#1a3a3a]/10')"
                            class="w-full bg-[#f5f0e8] border rounded-2xl py-3.5 px-5 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                        <p class="text-[10px] text-[#1a3a3a]/50 flex items-start gap-1.5 mt-1">
                            <i class="fa fa-info-circle text-[#c8965f] mt-0.5"></i>
                            <span>Format: <strong>09123456789</strong> (11 digits) or <strong>+639123456789</strong></span>
                        </p>
                        <p x-show="errors.owner_phone" x-transition class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                            <i class="fa fa-exclamation-circle text-[10px]"></i>
                            <span x-text="errors.owner_phone"></span>
                        </p>
                        @error('owner_phone')
                            <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- ============================================= --}}
                {{-- STEP 2: SECURITY + BUSINESS --}}
                {{-- ============================================= --}}
                <div x-show="step === 2" x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-data="{ show1: false, show2: false }"
                     style="display: none;">

                    <div class="mb-4">
                        <p class="flex items-center gap-2 text-[10px] font-bold text-[#c8965f] uppercase tracking-[0.2em] mb-1">
                            <span class="w-5 h-px bg-[#c8965f]/50"></span>
                            Step 2 of 3
                        </p>
                        <h3 class="text-lg font-bold text-[#1a3a3a]">Secure your account & shop details</h3>
                        <p class="text-xs text-[#1a3a3a]/60 mt-1">Set a password and tell us about your business.</p>
                    </div>

                    {{-- SECTION: PASSWORD --}}
                    <p class="flex items-center gap-2 text-[10px] font-bold text-[#c8965f] uppercase tracking-[0.2em] mb-4">
                        <span class="w-5 h-px bg-[#c8965f]/50"></span>
                        Account Security
                    </p>

                    {{-- Password --}}
                    <div class="space-y-2 mb-4">
                        <label for="password" class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em]">
                                <i class="fa fa-key text-[#c8965f]"></i> Password
                            </span>
                            <span class="text-[9px] font-mono font-bold"
                                  :class="(form.password || '').length >= 8 ? 'text-emerald-600' : 'text-[#1a3a3a]/40'">
                                <span x-text="(form.password || '').length"></span>/64
                            </span>
                        </label>
                        <div class="relative">
                            <input
                                :type="show1 ? 'text' : 'password'"
                                id="password"
                                name="password"
                                x-model="form.password"
                                @blur="validateField('password')"
                                @input="if (form.password_confirmation) validateField('password_confirmation')"
                                minlength="8"
                                maxlength="64"
                                placeholder="At least 8 characters"
                                :class="errors.password ? 'border-red-500/60' : 'border-[#1a3a3a]/10'"
                                class="w-full bg-[#f5f0e8] border rounded-2xl py-3.5 px-5 pr-12 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                            <button type="button"
                                    @click="show1 = !show1"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 hover:text-[#c8965f] transition-colors">
                                <i class="fa" :class="show1 ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <p x-show="errors.password" x-transition class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                            <i class="fa fa-exclamation-circle text-[10px]"></i>
                            <span x-text="errors.password"></span>
                        </p>
                        @error('password')
                            <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div class="space-y-2 mb-6">
                        <label for="password_confirmation" class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em]">
                                <i class="fa fa-lock text-[#c8965f]"></i> Confirm Password
                            </span>
                            <span class="text-[9px] font-mono font-bold"
                                  :class="form.password_confirmation && form.password_confirmation === form.password ? 'text-emerald-600' : 'text-[#1a3a3a]/40'">
                                <span x-text="(form.password_confirmation || '').length"></span>/64
                            </span>
                        </label>
                        <div class="relative">
                            <input
                                :type="show2 ? 'text' : 'password'"
                                id="password_confirmation"
                                name="password_confirmation"
                                x-model="form.password_confirmation"
                                @blur="validateField('password_confirmation')"
                                @input="if (form.password) validateField('password_confirmation')"
                                maxlength="64"
                                placeholder="Re-enter password"
                                :class="errors.password_confirmation ? 'border-red-500/60' : 'border-[#1a3a3a]/10'"
                                class="w-full bg-[#f5f0e8] border rounded-2xl py-3.5 px-5 pr-12 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                            <button type="button"
                                    @click="show2 = !show2"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 hover:text-[#c8965f] transition-colors">
                                <i class="fa" :class="show2 ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <p x-show="errors.password_confirmation" x-transition class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                            <i class="fa fa-exclamation-circle text-[10px]"></i>
                            <span x-text="errors.password_confirmation"></span>
                        </p>
                        @error('password_confirmation')
                            <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- SECTION: BUSINESS INFO --}}
                    <p class="flex items-center gap-2 text-[10px] font-bold text-[#c8965f] uppercase tracking-[0.2em] mb-4">
                        <span class="w-5 h-px bg-[#c8965f]/50"></span>
                        Business Information
                    </p>

                    {{-- Shop Name --}}
                    <div class="space-y-2 mb-4">
                        <label for="shop_name" class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em]">
                                <i class="fa fa-store text-[#c8965f]"></i> Shop Name
                            </span>
                            <span class="text-[9px] font-mono font-bold"
                                  :class="(form.shop_name || '').length > 80 ? 'text-[#d97706]' : 'text-[#1a3a3a]/40'">
                                <span x-text="(form.shop_name || '').length"></span>/100
                            </span>
                        </label>
                        <input
                            type="text"
                            id="shop_name"
                            name="shop_name"
                            value="{{ old('shop_name') }}"
                            x-model="form.shop_name"
                            @blur="validateField('shop_name')"
                            maxlength="100"
                            placeholder="Victorias Coffee Co."
                            :class="errors.shop_name ? 'border-red-500/60' : 'border-[#1a3a3a]/10'"
                            class="w-full bg-[#f5f0e8] border rounded-2xl py-3.5 px-5 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                        <p x-show="errors.shop_name" x-transition class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                            <i class="fa fa-exclamation-circle text-[10px]"></i>
                            <span x-text="errors.shop_name"></span>
                        </p>
                        @error('shop_name')
                            <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Business Type --}}
                    <div class="space-y-2 mb-4">
                        <label for="business_type" class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em]">
                            <i class="fa fa-briefcase text-[#c8965f]"></i> Business Type
                        </label>
                        <select
                            id="business_type"
                            name="business_type"
                            x-model="form.business_type"
                            @change="validateField('business_type')"
                            :class="errors.business_type ? 'border-red-500/60' : 'border-[#1a3a3a]/10'"
                            class="w-full bg-[#f5f0e8] border rounded-2xl py-3.5 px-5 text-[#1a3a3a] focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                            <option value="" class="bg-[#faf6ef]">— Select —</option>
                            <option value="sole_proprietor" class="bg-[#faf6ef]">Sole Proprietor</option>
                            <option value="partnership"     class="bg-[#faf6ef]">Partnership</option>
                            <option value="corporation"     class="bg-[#faf6ef]">Corporation</option>
                            <option value="individual"      class="bg-[#faf6ef]">Individual / Solo</option>
                        </select>
                        <p x-show="errors.business_type" x-transition class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                            <i class="fa fa-exclamation-circle text-[10px]"></i>
                            <span x-text="errors.business_type"></span>
                        </p>
                        @error('business_type')
                            <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- BUSINESS ADDRESS --}}
                    <div class="space-y-4">
                        <label class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em]">
                            <i class="fa fa-map-pin text-[#c8965f]"></i> Business Address
                        </label>

                        {{-- Fixed Location Badge --}}
                        <div class="flex items-center gap-2 p-3 bg-[#c8965f]/5 border border-[#c8965f]/20 rounded-2xl">
                            <i class="fa fa-location-dot text-[#c8965f]"></i>
                            <span class="text-xs font-semibold text-[#1a3a3a]/70">
                                Victorias City, Negros Occidental
                            </span>
                        </div>

                        {{-- 1️⃣ Barangay (Required) --}}
                        <div class="space-y-2">
                            <label for="business_barangay" class="flex items-center justify-between">
                                <span class="block text-[10px] font-bold text-[#1a3a3a]/50 uppercase tracking-wider">
                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[#c8965f] text-white text-[9px] font-black mr-1.5">1</span>
                                    Select Barangay <span class="text-[#c8965f]">*</span>
                                </span>
                            </label>
                            <select
                                id="business_barangay"
                                name="business_barangay"
                                x-model="form.business_barangay"
                                @change="validateField('business_barangay')"
                                :class="errors.business_barangay ? 'border-red-500/60' : 'border-[#1a3a3a]/10'"
                                class="w-full bg-[#f5f0e8] border rounded-2xl py-3.5 px-5 text-[#1a3a3a] focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                                <option value="">— Choose Barangay —</option>
                                <template x-for="brgy in barangays" :key="brgy">
                                    <option :value="brgy" x-text="brgy"></option>
                                </template>
                            </select>
                            <p x-show="errors.business_barangay" x-transition class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i>
                                <span x-text="errors.business_barangay"></span>
                            </p>
                        </div>

                        {{-- 2️⃣ Street / Purok / Sitio / Block (Optional) --}}
                        <div class="space-y-2" x-show="form.business_barangay">
                            <label for="business_street" class="flex items-center justify-between">
                                <span class="block text-[10px] font-bold text-[#1a3a3a]/50 uppercase tracking-wider">
                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[#c8965f]/30 text-[#c8965f] text-[9px] font-black mr-1.5">2</span>
                                    Street / Purok / Sitio / Block
                                </span>
                                <span class="text-[9px] font-mono font-bold text-[#1a3a3a]/40">
                                    <span x-text="(form.business_street || '').length"></span>/255
                                </span>
                            </label>
                            <input
                                type="text"
                                id="business_street"
                                name="business_street"
                                x-model="form.business_street"
                                maxlength="255"
                                placeholder="e.g. Block 5 Lot 12, Rizal St., Bldg A, Unit 4"
                                class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-2xl py-3.5 px-5 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                            <p class="text-[10px] text-[#1a3a3a]/50 flex items-start gap-1.5 mt-1.5">
                                <i class="fa fa-info-circle text-[#c8965f] mt-0.5"></i>
                                <span>Optional. Add your block, lot, building, or unit number so customers can find your shop easily.</span>
                            </p>
                        </div>

                        {{-- LIVE ADDRESS PREVIEW --}}
                        <div x-show="form.business_barangay"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 -translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="p-4 rounded-2xl flex items-start gap-3"
                             style="background-color: color-mix(in srgb, #c8965f 8%, transparent); border: 1px dashed color-mix(in srgb, #c8965f 40%, transparent);">

                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                                 style="background-color: color-mix(in srgb, #c8965f 15%, transparent); color: #c8965f;">
                                <i class="fa fa-location-dot text-xs"></i>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-[0.15em] mb-1.5"
                                   style="color: #c8965f;">
                                    <i class="fa fa-eye mr-1"></i> Your full address preview
                                </p>

                                <p class="text-sm font-bold leading-relaxed"
                                   style="color: #1a3a3a;"
                                   x-text="fullAddress">
                                </p>

                                <div class="flex flex-wrap items-center gap-1.5 mt-2.5">
                                    <template x-if="form.business_street && form.business_street.trim() !== ''">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-mono font-bold"
                                              style="background-color: rgba(200, 150, 95, 0.15); color: #a87a4a; border: 1px solid rgba(200, 150, 95, 0.3);">
                                            <i class="fa fa-road text-[8px]"></i>
                                            <span x-text="form.business_street"></span>
                                        </span>
                                    </template>

                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-mono font-bold"
                                          style="background-color: rgba(26, 58, 58, 0.08); color: #1a3a3a; border: 1px solid rgba(26, 58, 58, 0.15);">
                                        <i class="fa fa-map-pin text-[8px]"></i>
                                        <span x-text="form.business_barangay"></span>
                                    </span>

                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-mono font-bold"
                                          style="background-color: rgba(26, 58, 58, 0.08); color: #1a3a3a; border: 1px solid rgba(26, 58, 58, 0.15);">
                                        <i class="fa fa-city text-[8px]"></i>
                                        Victorias City
                                    </span>

                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-mono font-bold"
                                          style="background-color: rgba(26, 58, 58, 0.08); color: #1a3a3a; border: 1px solid rgba(26, 58, 58, 0.15);">
                                        <i class="fa fa-flag text-[8px]"></i>
                                        Negros Occidental
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Hidden combined address for backend --}}
                        <input type="hidden" name="business_address" :value="fullAddress">
                    </div>
                </div>

                {{-- ============================================= --}}
                {{-- STEP 3: ID VERIFICATION --}}
                {{-- ============================================= --}}
                <div x-show="step === 3" x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     style="display: none;">

                    <div class="mb-4">
                        <p class="flex items-center gap-2 text-[10px] font-bold text-[#c8965f] uppercase tracking-[0.2em] mb-1">
                            <span class="w-5 h-px bg-[#c8965f]/50"></span>
                            Step 3 of 3
                        </p>
                        <h3 class="text-lg font-bold text-[#1a3a3a]">Verify your identity</h3>
                        <p class="text-xs text-[#1a3a3a]/60 mt-1">One last step before we send your code.</p>
                    </div>

                    <p class="flex items-center gap-2 text-[10px] font-bold text-[#c8965f] uppercase tracking-[0.2em] mb-4">
                        <span class="w-5 h-px bg-[#c8965f]/50"></span>
                        Identity Verification
                    </p>

                    <div class="space-y-2">
                        <label for="owner_id" class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em]">
                            <i class="fa fa-id-card text-[#c8965f]"></i> Owner Valid ID <span class="text-[#c8965f]">*</span>
                        </label>

                        <label for="owner_id"
                               :class="errors.owner_id ? 'border-red-500/60' : 'border-[#1a3a3a]/20 hover:border-[#c8965f]'"
                               class="group relative flex flex-col items-center justify-center w-full h-32 bg-[#f5f0e8] border-2 border-dashed rounded-2xl cursor-pointer transition-all">
                            <div class="flex flex-col items-center gap-2 pointer-events-none">
                                <div class="w-10 h-10 rounded-xl bg-[#c8965f]/10 border border-[#c8965f]/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="fa fa-cloud-upload-alt text-[#c8965f]"></i>
                                </div>
                                <p class="text-xs font-semibold text-[#1a3a3a]/70">
                                    <span class="text-[#c8965f]">Click to upload</span> or drag & drop
                                </p>
                                <p class="text-[10px] text-[#1a3a3a]/40">JPG, PNG, or PDF · Max 5 MB</p>
                            </div>
                            <input
                                type="file"
                                id="owner_id"
                                name="owner_id"
                                accept="image/*,application/pdf"
                                class="sr-only"
                                @change="handleFile($event)">
                        </label>

                        <div x-show="form.owner_id_name" x-transition
                             class="flex items-center gap-2 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                            <i class="fa fa-check-circle text-emerald-500"></i>
                            <span x-text="form.owner_id_name" class="text-xs text-emerald-700 font-semibold truncate flex-1"></span>
                            <button type="button" @click="clearFile()" class="text-red-500 hover:text-red-700 text-xs">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>

                        <p x-show="errors.owner_id" x-transition class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                            <i class="fa fa-exclamation-circle text-[10px]"></i>
                            <span x-text="errors.owner_id"></span>
                        </p>

                        <p class="text-[10px] text-[#1a3a3a]/50 flex items-start gap-1.5 mt-2">
                            <i class="fa fa-info-circle text-[#c8965f] mt-0.5"></i>
                            <span>Government-issued ID preferred. Your documents are encrypted and used only for verification.</span>
                        </p>

                        @error('owner_id')
                            <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="p-4 bg-[#c8965f]/5 border border-[#c8965f]/20 rounded-2xl flex items-start gap-3 mt-5">
                        <i class="fa fa-info-circle text-[#c8965f] mt-0.5"></i>
                        <p class="text-xs text-[#1a3a3a]/70 leading-relaxed">
                            After you submit, we'll email you a <strong class="text-[#c8965f]">6-digit verification code</strong>. Enter it to complete your registration. Our team reviews and approves applications within <strong class="text-[#c8965f]">24 hours</strong>.
                        </p>
                    </div>
                </div>

                {{-- NAVIGATION BUTTONS --}}
                <div class="flex items-center gap-3 pt-2">
                    <button type="button"
                            x-show="step > 1"
                            @click="prevStep()"
                            class="flex-1 py-4 px-6 bg-[#f5f0e8] hover:bg-[#efe9dd] border border-[#1a3a3a]/10 hover:border-[#1a3a3a]/20 rounded-2xl transition-all text-sm font-bold text-[#1a3a3a]/70 hover:text-[#1a3a3a] flex items-center justify-center gap-2"
                            style="display: none;">
                        <i class="fa fa-arrow-left text-xs"></i>
                        <span>Back</span>
                    </button>

                    <button type="button"
                            x-show="step < 3"
                            @click="nextStep()"
                            :disabled="isCheckingEmail"
                            class="group flex-1 py-4 px-6 bg-[#1a3a3a] text-[#f5f0e8] font-bold rounded-2xl hover:bg-[#0f2922] transition-all duration-300 shadow-lg shadow-[#1a3a3a]/15 hover:shadow-xl hover:shadow-[#1a3a3a]/25 active:scale-[0.98] flex items-center justify-center gap-2 overflow-hidden disabled:opacity-60 disabled:cursor-not-allowed">
                        <template x-if="!isCheckingEmail">
                            <span class="flex items-center gap-2">
                                <span>Continue</span>
                                <i class="fa fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                            </span>
                        </template>
                        <template x-if="isCheckingEmail">
                            <span class="flex items-center gap-2">
                                <i class="fa fa-spinner fa-spin"></i>
                                <span>Checking...</span>
                            </span>
                        </template>
                    </button>

                    <button type="submit"
                            id="ownerSubmitBtn"
                            x-show="step === 3"
                            class="group relative flex-1 py-4 px-6 bg-[#1a3a3a] text-[#f5f0e8] font-bold rounded-2xl hover:bg-[#0f2922] transition-all duration-300 shadow-lg shadow-[#1a3a3a]/15 hover:shadow-xl hover:shadow-[#1a3a3a]/25 active:scale-[0.98] flex items-center justify-center gap-2 overflow-hidden disabled:opacity-60"
                            style="display: none;">
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                        <i class="fa fa-paper-plane text-sm"></i>
                        <span>Send Verification Code</span>
                        <i class="fa fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>

                <p class="text-center text-[10px] text-[#1a3a3a]/50 pt-1" x-show="step < 3">
                    Step <span x-text="step"></span> of 3 · You can go back anytime
                </p>
            </form>

            @endif

            {{-- Divider --}}
            <div class="flex items-center gap-3 my-7">
                <span class="flex-1 h-px bg-[#1a3a3a]/10"></span>
                <span class="text-[10px] font-bold text-[#1a3a3a]/40 uppercase tracking-widest">Already registered?</span>
                <span class="flex-1 h-px bg-[#1a3a3a]/10"></span>
            </div>

            <a href="{{ route('login') }}"
               class="group flex items-center justify-center gap-2 py-3 px-4 bg-[#f5f0e8]/60 hover:bg-[#f5f0e8] border border-[#1a3a3a]/10 hover:border-[#c8965f]/40 rounded-xl transition-all text-xs font-bold text-[#1a3a3a]/70 hover:text-[#c8965f]">
                <i class="fa fa-sign-in-alt text-[10px]"></i>
                <span>Sign in to your account</span>
            </a>

            <div class="mt-8 flex items-center justify-center gap-2 text-[10px] text-[#1a3a3a]/40">
                <i class="fa fa-shield-alt text-[#c8965f]"></i>
                <span class="font-semibold uppercase tracking-wider">Secured with 256-bit encryption</span>
            </div>
        </div>
    </div>
</div>

{{-- ============================================= --}}
{{-- WIZARD SCRIPT --}}
{{-- ============================================= --}}
<script>
    function registerWizard() {
        return {
            step: 1,
            totalSteps: 3,
            isCheckingEmail: false,
            emailStatus: '',
            errors: {},

            barangays: [
                'Barangay I (Pob.)',
                'Barangay II (Pob.)',
                'Barangay III (Pob.)',
                'Barangay IV (Pob.)',
                'Barangay V (Pob.)',
                'Barangay VI (Pob.)',
                'Barangay VI-A',
                'Barangay VII (Pob.)',
                'Barangay VIII (Pob.)',
                'Barangay IX (Daan Banwa)',
                'Barangay X (Estado)',
                'Barangay XI (Gawahon)',
                'Barangay XII',
                'Barangay XIII (Gloryville)',
                'Barangay XIV',
                'Barangay XV',
                'Barangay XV-A',
                'Barangay XVI',
                'Barangay XVI-A',
                'Barangay XVII',
                'Barangay XVIII',
                'Barangay XVIII-A',
                'Barangay XIX',
                'Barangay XIX-A (Canetown Subdivision)',
                'Barangay XX',
                'Barangay XXI',
            ],

            form: {
                first_name: '',
                middle_name: '',
                last_name: '',
                email: '',
                owner_phone: '',
                password: '',
                password_confirmation: '',
                shop_name: '',
                business_type: '',
                business_barangay: '',
                business_street: '',
                owner_id_name: '',
            },

            get fullName() {
                return [
                    this.form.first_name,
                    this.form.middle_name,
                    this.form.last_name,
                ]
                    .map(s => (s || '').trim())
                    .filter(Boolean)
                    .join(' ');
            },

            get fullAddress() {
                const parts = [
                    this.form.business_street ? this.form.business_street.trim() : null,
                    this.form.business_barangay,
                    'Victorias City',
                    'Negros Occidental',
                ].filter(Boolean);
                return parts.join(', ');
            },

            capitalizeWords(value) {
                if (!value) return '';
                return value.replace(/\b\w/g, function(char) {
                    return char.toUpperCase();
                });
            },

            sanitizePhone(value) {
                if (!value) return '';
                let cleaned = value.replace(/[^\d+]/g, '');
                cleaned = cleaned.replace(/(?!^)\+/g, '');
                return cleaned.slice(0, 13);
            },

            isPhoneValid() {
                const v = (this.form.owner_phone || '').replace(/[^0-9+]/g, '');
                if (!v) return false;
                if (/^09\d{9}$/.test(v)) return true;
                if (/^\+639\d{9}$/.test(v)) return true;
                return false;
            },

            validateField(field) {
                delete this.errors[field];
                const v = (this.form[field] || '').trim();

                switch (field) {
                    case 'first_name':
                        if (!v) this.errors.first_name = 'First name is required.';
                        else if (v.length < 2) this.errors.first_name = 'First name must be at least 2 characters.';
                        else if (v.length > 50) this.errors.first_name = 'First name cannot exceed 50 characters.';
                        else if (!/^[a-zA-Z\s\.\-\']+$/.test(v)) this.errors.first_name = 'Only letters, spaces, dots, hyphens, and apostrophes are allowed.';
                        break;

                    case 'middle_name':
                        if (v) {
                            if (v.length > 50) this.errors.middle_name = 'Middle name cannot exceed 50 characters.';
                            else if (!/^[a-zA-Z\s\.\-\']+$/.test(v)) this.errors.middle_name = 'Only letters, spaces, dots, hyphens, and apostrophes are allowed.';
                        }
                        break;

                    case 'last_name':
                        if (!v) this.errors.last_name = 'Last name is required.';
                        else if (v.length < 2) this.errors.last_name = 'Last name must be at least 2 characters.';
                        else if (v.length > 50) this.errors.last_name = 'Last name cannot exceed 50 characters.';
                        else if (!/^[a-zA-Z\s\.\-\']+$/.test(v)) this.errors.last_name = 'Only letters, spaces, dots, hyphens, and apostrophes are allowed.';
                        break;

                    case 'owner_phone':
                        if (!v) this.errors.owner_phone = 'Phone number is required.';
                        else if (!this.isPhoneValid()) this.errors.owner_phone = 'Enter a valid PH number (e.g. 09123456789 or +639123456789).';
                        break;

                    case 'password':
                        if (!v) this.errors.password = 'Password is required.';
                        else if (v.length < 8) this.errors.password = 'Password must be at least 8 characters.';
                        else if (v.length > 64) this.errors.password = 'Password cannot exceed 64 characters.';
                        break;

                    case 'password_confirmation':
                        if (!v) this.errors.password_confirmation = 'Please confirm your password.';
                        else if (v !== this.form.password) this.errors.password_confirmation = 'Passwords do not match.';
                        break;

                    case 'shop_name':
                        if (!v) this.errors.shop_name = 'Shop name is required.';
                        else if (v.length < 2) this.errors.shop_name = 'Shop name must be at least 2 characters.';
                        else if (v.length > 100) this.errors.shop_name = 'Shop name cannot exceed 100 characters.';
                        break;

                    case 'business_type':
                        if (!v) this.errors.business_type = 'Please select a business type.';
                        break;

                    case 'business_barangay':
                        if (!v) this.errors.business_barangay = 'Please select a barangay.';
                        break;
                }

                return !this.errors[field];
            },

            async validateEmailField() {
                delete this.errors.email;
                this.emailStatus = '';
                const v = (this.form.email || '').trim();

                if (!v) {
                    this.errors.email = 'Email address is required.';
                    return false;
                }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) {
                    this.errors.email = 'Please enter a valid email address.';
                    return false;
                }
                if (v.length > 255) {
                    this.errors.email = 'Email cannot exceed 255 characters.';
                    return false;
                }

                this.isCheckingEmail = true;
                this.emailStatus = 'checking';

                try {
                    const res = await fetch('{{ route('register.check-email') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                          || '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ email: v }),
                    });

                    if (!res.ok) {
                        this.emailStatus = '';
                        return true;
                    }

                    const data = await res.json();
                    if (data.available) {
                        this.emailStatus = 'available';
                        return true;
                    } else {
                        this.emailStatus = 'taken';
                        this.errors.email = 'This email is already registered. Try logging in instead.';
                        return false;
                    }
                } catch (e) {
                    this.emailStatus = '';
                    return true;
                } finally {
                    this.isCheckingEmail = false;
                }
            },

            handleFile(event) {
                delete this.errors.owner_id;
                const file = event.target.files[0];
                if (!file) {
                    this.form.owner_id_name = '';
                    return;
                }

                const allowed = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
                const maxSize = 5 * 1024 * 1024;

                if (!allowed.includes(file.type)) {
                    this.errors.owner_id = 'Only JPG, PNG, or PDF files are allowed.';
                    event.target.value = '';
                    this.form.owner_id_name = '';
                    return;
                }
                if (file.size > maxSize) {
                    this.errors.owner_id = 'File is too large. Max size is 5 MB.';
                    event.target.value = '';
                    this.form.owner_id_name = '';
                    return;
                }

                this.form.owner_id_name = file.name;
            },

            clearFile() {
                const input = document.getElementById('owner_id');
                if (input) input.value = '';
                this.form.owner_id_name = '';
                delete this.errors.owner_id;
            },

            validateStep(step) {
                this.errors = {};

                if (step === 1) {
                    this.validateField('first_name');
                    this.validateField('middle_name');
                    this.validateField('last_name');
                    this.validateField('owner_phone');
                    if (!this.form.email) this.errors.email = 'Email address is required.';
                    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
                        this.errors.email = 'Please enter a valid email address.';
                    }
                }

                if (step === 2) {
                    this.validateField('password');
                    this.validateField('password_confirmation');
                    this.validateField('shop_name');
                    this.validateField('business_type');
                    this.validateField('business_barangay');
                }

                if (step === 3) {
                    if (!this.form.owner_id_name) {
                        this.errors.owner_id = 'Please upload a valid ID.';
                    }
                }

                return Object.keys(this.errors).length === 0;
            },

            async nextStep() {
                if (this.step >= this.totalSteps) return;

                if (!this.validateStep(this.step)) {
                    this.scrollToTop();
                    return;
                }

                if (this.step === 1) {
                    const ok = await this.validateEmailField();
                    if (!ok) {
                        this.scrollToTop();
                        return;
                    }
                }

                this.step++;
                this.scrollToTop();
            },

            prevStep() {
                if (this.step > 1) {
                    this.step--;
                    this.scrollToTop();
                }
            },

            scrollToTop() {
                const panel = document.querySelector('.overflow-y-auto');
                if (panel) {
                    panel.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }
    }
</script>
@endsection