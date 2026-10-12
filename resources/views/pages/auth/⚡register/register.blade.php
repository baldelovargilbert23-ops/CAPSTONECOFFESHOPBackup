@extends('layouts.auth')

@section('title', 'Register Customer')

@section('content')
<div class="min-h-screen bg-[#f5f0e8] flex items-center justify-center p-4 sm:p-6 antialiased text-[#1a3a3a] relative overflow-hidden"
     x-data="customerRegister()">

    {{-- Background Ambience --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -left-40 w-[500px] h-[500px] bg-[#c8965f]/10 rounded-full blur-[120px]"></div>
        <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] bg-[#c8965f]/10 rounded-full blur-[120px]"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-[#c8965f]/5 rounded-full blur-[150px]"></div>
    </div>

    <div class="relative w-full max-w-6xl grid lg:grid-cols-2 bg-[#faf6ef] rounded-[2rem] overflow-hidden shadow-[0_40px_120px_-30px_rgba(26,58,58,0.15)] border border-[#1a3a3a]/10">

        {{-- LEFT PANEL --}}
        <div class="relative hidden lg:flex flex-col justify-between p-12 overflow-hidden">
            <div class="absolute inset-0 bg-cover bg-center scale-105 bg-[#1a3a3a]"
                 style="background-image: url(https://i.pinimg.com/1200x/01/d4/b2/01d4b25ca1acdccaa7b96eb92552c6f7.jpg);"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-[#f5f0e8]/90 via-[#f5f0e8]/75 to-[#c8965f]/50"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,_var(--tw-gradient-stops))] from-[#c8965f]/20 via-transparent to-transparent"></div>

            <div class="relative z-10 flex items-center gap-3">
                <div class="w-11 h-11 flex-shrink-0">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <defs>
                            <linearGradient id="brandGradLgCust" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#c8965f"/>
                                <stop offset="100%" stop-color="#1a3a3a"/>
                            </linearGradient>
                        </defs>
                        <circle cx="50" cy="50" r="48" fill="url(#brandGradLgCust)"/>
                        <circle cx="50" cy="50" r="44" fill="none" stroke="rgba(245,240,232,0.15)" stroke-width="1"/>
                        <path d="M 22 30 L 50 84 L 78 30 L 68 30 L 50 66 L 32 30 Z" fill="#f5f0e8"/>
                    </svg>
                </div>
                <div>
                    <p class="text-base font-black tracking-tighter text-[#1a3a3a] uppercase italic leading-none">
                        Victorias<span class="text-[#c8965f]">Hub</span>
                    </p>
                    <p class="text-[10px] text-[#c8965f]/80 font-bold uppercase tracking-[0.2em] mt-1">Coffee Hub</p>
                </div>
            </div>

            <div class="relative z-10 space-y-6">
                <div class="flex items-center gap-2">
                    <span class="text-[#c8965f] text-2xl">✦</span>
                    <span class="text-xs font-bold tracking-[0.3em] text-[#c8965f] uppercase">Join the Club</span>
                </div>

                <h1 class="text-5xl xl:text-6xl font-black tracking-tighter leading-[1.05] text-[#1a3a3a]">
                    Start your<br>
                    <span class="bg-gradient-to-r from-[#c8965f] to-[#a87a4a] bg-clip-text text-transparent">coffee journey.</span>
                </h1>

                <p class="text-[#1a3a3a]/70 text-base leading-relaxed max-w-md">
                    Create a free account to order from every shop, earn loyalty points, and leave reviews — all in one place.
                </p>

                <div class="space-y-3 pt-4">
                    @foreach([
                        ['icon' => 'fa-shopping-bag', 'text' => 'Order from any partner shop'],
                        ['icon' => 'fa-star',         'text' => 'Earn points on every purchase'],
                        ['icon' => 'fa-gift',         'text' => 'Redeem rewards & unlock perks'],
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
                <span class="font-semibold uppercase tracking-widest">Free forever · No card required</span>
            </div>
        </div>

        {{-- RIGHT PANEL --}}
        <div class="relative p-8 sm:p-12 lg:p-14 flex flex-col justify-center bg-[#faf6ef] overflow-y-auto max-h-screen lg:max-h-none">

            <div class="lg:hidden flex flex-col items-center mb-8">
                <div class="w-20 h-20">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <defs>
                            <linearGradient id="brandGradMobCust" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#c8965f"/>
                                <stop offset="100%" stop-color="#1a3a3a"/>
                            </linearGradient>
                        </defs>
                        <circle cx="50" cy="50" r="48" fill="url(#brandGradMobCust)"/>
                        <circle cx="50" cy="50" r="44" fill="none" stroke="rgba(245,240,232,0.15)" stroke-width="1"/>
                        <path d="M 22 30 L 50 84 L 78 30 L 68 30 L 50 66 L 32 30 Z" fill="#f5f0e8"/>
                    </svg>
                </div>
                <p class="text-lg font-black tracking-tighter text-[#1a3a3a] uppercase italic mt-3">
                    Victorias<span class="text-[#c8965f]">Hub</span>
                </p>
                <p class="text-[10px] font-bold tracking-[0.3em] text-[#c8965f]/70 uppercase mt-1">Coffee Hub</p>
            </div>

            {{-- SESSION MESSAGES --}}
            @if(session('info'))
                <div class="mb-5 p-4 bg-blue-500/10 border border-blue-500/20 rounded-2xl text-sm text-blue-700 flex items-start gap-3">
                    <i class="fa fa-info-circle text-blue-500 mt-0.5"></i>
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

            {{-- STATE A: Code Sent → Show code entry --}}
            @if(session('code_sent'))
                <div class="mb-8">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-[#c8965f]/10 border border-[#c8965f]/20 flex items-center justify-center mb-4">
                        <i class="fa fa-envelope-circle-check text-xl text-[#c8965f]"></i>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black tracking-tight text-[#1a3a3a] mb-2 text-center">
                        Check your email
                    </h2>
                    <p class="text-[#1a3a3a]/60 text-sm text-center">
                        We sent a 6-digit code to your inbox. It expires in 15 minutes.
                    </p>
                </div>

                <form method="POST" action="{{ route('register.customer') }}" class="space-y-5"
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

                <form method="POST" action="{{ route('register.customer.send-code') }}"
                      onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                    @csrf
                    <input type="hidden" name="resend" value="1">
                    <button type="submit"
                            class="w-full py-3 px-4 bg-[#f5f0e8]/60 hover:bg-[#f5f0e8] border border-[#1a3a3a]/10 hover:border-[#c8965f]/40 rounded-xl transition-all text-xs font-bold text-[#1a3a3a]/70 hover:text-[#c8965f] flex items-center justify-center gap-2 disabled:opacity-60">
                        <i class="fa fa-rotate-right text-[10px]"></i>
                        <span>Resend code</span>
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <a href="{{ route('register.customer.fresh') }}"
                       class="text-xs text-[#1a3a3a]/50 hover:text-[#c8965f] transition">
                        <i class="fa fa-arrow-left text-[10px] mr-1"></i> Start over
                    </a>
                </div>

            @else
                {{-- STATE B: No code yet → Show register form --}}
                <div class="mb-8">
                    <span class="inline-flex items-center gap-2 text-[10px] font-bold tracking-[0.2em] text-[#c8965f] uppercase bg-[#c8965f]/10 px-3 py-1.5 rounded-full border border-[#c8965f]/20 mb-4">
                        <i class="fa fa-user-plus text-[9px]"></i> Create Account
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black tracking-tight text-[#1a3a3a] mb-2">Get started.</h2>
                    <p class="text-[#1a3a3a]/60 text-sm">It only takes a minute to join</p>
                </div>

                <form method="POST" action="{{ route('register.customer.send-code') }}" class="space-y-5"
                      onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit] span:last-child').textContent='Sending...';">
                    @csrf

                    {{-- First + Last Name row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

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
                                maxlength="50"
                                placeholder="Juan"
                                autocomplete="given-name"
                                class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-2xl py-3.5 px-5 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none @error('first_name') border-red-500/50 @enderror">
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
                                maxlength="50"
                                placeholder="Dela Cruz"
                                autocomplete="family-name"
                                class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-2xl py-3.5 px-5 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none @error('last_name') border-red-500/50 @enderror">
                            @error('last_name')
                                <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                    <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    {{-- Middle Name (full width, optional) --}}
                    <div class="space-y-2">
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
                            placeholder="Dela"
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
                         class="p-3 rounded-2xl flex items-center gap-3"
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
                    <div class="space-y-2">
                        <label for="email" class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em]">
                            <i class="fa fa-envelope text-[#c8965f]"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com"
                               autocomplete="email"
                               class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-2xl py-3.5 px-5 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none @error('email') border-red-500/50 @enderror">
                        @error('email')
                            <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-data="{ show1: false, show2: false }">
                        <div class="space-y-2">
                            <label for="password" class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em]">
                                <i class="fa fa-key text-[#c8965f]"></i> Password
                            </label>
                            <div class="relative">
                                <input :type="show1 ? 'text' : 'password'" id="password" name="password" required placeholder="••••••••" minlength="8" maxlength="64"
                                       class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-2xl py-3.5 px-5 pr-11 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none @error('password') border-red-500/50 @enderror">
                                <button type="button" @click="show1 = !show1"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 hover:text-[#c8965f] transition-colors">
                                    <i class="fa text-sm" :class="show1 ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="text-xs text-red-500 flex items-center gap-1.5 mt-1">
                                    <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="password_confirmation" class="flex items-center gap-2 text-[11px] font-bold text-[#1a3a3a]/60 uppercase tracking-[0.15em]">
                                <i class="fa fa-lock text-[#c8965f]"></i> Confirm
                            </label>
                            <div class="relative">
                                <input :type="show2 ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required placeholder="••••••••" maxlength="64"
                                       class="w-full bg-[#f5f0e8] border border-[#1a3a3a]/10 rounded-2xl py-3.5 px-5 pr-11 text-[#1a3a3a] placeholder:text-[#1a3a3a]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 transition-all outline-none">
                                <button type="button" @click="show2 = !show2"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 hover:text-[#c8965f] transition-colors">
                                    <i class="fa text-sm" :class="show2 ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <label class="flex items-start gap-3 p-4 rounded-2xl bg-[#f5f0e8]/60 border border-[#1a3a3a]/10 hover:border-[#c8965f]/30 cursor-pointer transition-all">
                        <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required
                               class="mt-0.5 w-4 h-4 rounded text-[#c8965f] focus:ring-[#c8965f]/30 accent-[#c8965f] @error('terms') ring-2 ring-red-500/50 @enderror">
                        <span class="text-[11px] text-[#1a3a3a]/60 leading-relaxed">
                            I agree to the
                            <a href="#" class="text-[#c8965f] hover:underline font-semibold">Terms of Service</a>
                            and
                            <a href="#" class="text-[#c8965f] hover:underline font-semibold">Privacy Policy</a>,
                            and consent to the processing of my personal data.
                        </span>
                    </label>
                    @error('terms')
                        <p class="text-xs text-red-500 flex items-center gap-1.5 -mt-3">
                            <i class="fa fa-exclamation-circle text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror

                    <button type="submit"
                            class="group relative w-full py-4 px-6 mt-2 bg-[#1a3a3a] text-[#f5f0e8] font-bold rounded-2xl hover:bg-[#0f2922] transition-all duration-300 shadow-lg shadow-[#1a3a3a]/15 hover:shadow-xl hover:shadow-[#1a3a3a]/25 active:scale-[0.98] flex items-center justify-center gap-2 overflow-hidden disabled:opacity-60">
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                        <i class="fa fa-paper-plane text-sm"></i>
                        <span>Send Verification Code</span>
                        <i class="fa fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>
            @endif

            <div class="flex items-center gap-3 my-7">
                <span class="flex-1 h-px bg-[#1a3a3a]/10"></span>
                <span class="text-[10px] font-bold text-[#1a3a3a]/40 uppercase tracking-widest">Already a member?</span>
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
    function customerRegister() {
        return {
            form: {
                first_name: '',
                middle_name: '',
                last_name: '',
            },

            // Compose the full name: "Juan Dela Cruz" or "Juan Cruz"
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

            // Capitalize the first letter of every word
            capitalizeWords(value) {
                if (!value) return '';
                return value.replace(/\b\w/g, function(char) {
                    return char.toUpperCase();
                });
            },
        }
    }
</script>
@endsection