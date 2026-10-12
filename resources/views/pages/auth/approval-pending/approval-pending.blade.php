{{-- resources/views/pages/auth/⚡pending/pending.blade.php --}}
@extends('layouts.auth')

@section('content')
<div class="min-h-screen bg-[#f5f0e8] flex flex-col items-center justify-center p-4 sm:p-6 antialiased text-[#1a3a3a] relative overflow-hidden">

    {{-- Background Ambience --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -left-40 w-[500px] h-[500px] bg-[#c8965f]/10 rounded-full blur-[120px]"></div>
        <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] bg-[#c8965f]/10 rounded-full blur-[120px]"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-[#c8965f]/5 rounded-full blur-[150px]"></div>
    </div>

    {{-- Main Card --}}
    <div class="relative w-full max-w-lg bg-[#faf6ef] rounded-[2rem] overflow-hidden shadow-[0_40px_120px_-30px_rgba(26,58,58,0.15)] border border-[#1a3a3a]/10">

        @php
            // Determine current status for accent colors
            $isRejected = ($type === 'tenant' && $tenant && $tenant->status === 'rejected')
                       || ($type === 'branch' && $branch && $branch->approval_status === 'rejected');
            $isApproved = ($type === 'tenant' && $tenant && $tenant->status === 'active')
                       || ($type === 'branch' && $branch && $branch->approval_status === 'approved');
        @endphp

        {{-- ============================================= --}}
        {{-- HEADER WITH ANIMATED ICON --}}
        {{-- ============================================= --}}
        <div class="relative p-8 pb-6 text-center border-b border-[#1a3a3a]/10">

            {{-- Ambient glow behind icon --}}
            <div class="absolute top-8 left-1/2 -translate-x-1/2 w-32 h-32 rounded-full blur-3xl
                        {{ $isRejected ? 'bg-red-500/15' : ($isApproved ? 'bg-emerald-500/15' : 'bg-[#c8965f]/20') }}"></div>

            {{-- Icon with pulse ring --}}
            <div class="relative inline-flex items-center justify-center mb-5">
                {{-- Pulsing ring for pending --}}
                @if(!$isRejected && !$isApproved)
                    <span class="absolute inset-0 rounded-full bg-[#c8965f]/20 animate-ping"></span>
                @endif

                <div class="relative w-20 h-20 rounded-3xl flex items-center justify-center shadow-lg
                            {{ $isRejected
                                ? 'bg-gradient-to-br from-red-500/15 to-red-700/10 border border-red-500/30 shadow-red-600/10'
                                : ($isApproved
                                    ? 'bg-gradient-to-br from-emerald-500/15 to-emerald-700/10 border border-emerald-500/30 shadow-emerald-600/10'
                                    : 'bg-gradient-to-br from-[#c8965f]/20 to-[#a87a4a]/10 border border-[#c8965f]/30 shadow-[#c8965f]/20') }}">

                    @if($isRejected)
                        <i class="fa fa-times-circle text-4xl text-red-500"></i>
                    @elseif($isApproved)
                        <i class="fa fa-check-circle text-4xl text-emerald-500"></i>
                    @else
                        {{-- Animated clock --}}
                        <svg class="w-9 h-9 text-[#c8965f] animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </div>
            </div>

            {{-- Title --}}
            <h1 class="relative text-2xl sm:text-3xl font-black tracking-tight text-[#1a3a3a] mb-2">
                @if($isRejected)
                    Application Declined
                @elseif($isApproved)
                    You're Approved!
                @else
                    @if($type === 'tenant')
                        Shop Pending Approval
                    @else
                        Branch Pending Approval
                    @endif
                @endif
            </h1>

            {{-- Subtitle --}}
            <p class="relative text-sm text-[#1a3a3a]/60 max-w-sm mx-auto leading-relaxed">
                @if($isRejected)
                    Unfortunately, your application wasn't approved this time.
                @elseif($isApproved)
                    Your application has been reviewed and approved. You can now access your dashboard.
                @else
                    @if($type === 'tenant')
                        <strong class="text-[#c8965f]">{{ $tenant->name ?? 'Your Shop' }}</strong> is being reviewed by our admin team.
                    @else
                        <strong class="text-[#c8965f]">{{ $branch->name ?? 'Your Branch' }}</strong> is being reviewed by our admin team.
                    @endif
                    This usually takes 24–48 hours.
                @endif
            </p>
        </div>

        {{-- ============================================= --}}
        {{-- BODY --}}
        {{-- ============================================= --}}
        <div class="p-8 space-y-5">

            {{-- ============================================= --}}
            {{-- STATUS CARD --}}
            {{-- ============================================= --}}
            <div class="relative rounded-2xl p-5 bg-[#f5f0e8]/60 border border-[#1a3a3a]/10 overflow-hidden">
                <div class="flex items-start gap-4">

                    {{-- Entity icon --}}
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                                {{ $isRejected
                                    ? 'bg-red-500/10 border border-red-500/20'
                                    : ($isApproved
                                        ? 'bg-emerald-500/10 border border-emerald-500/20'
                                        : 'bg-[#c8965f]/10 border border-[#c8965f]/20') }}">
                        @if($type === 'tenant')
                            <i class="fa fa-building text-lg {{ $isRejected ? 'text-red-500' : ($isApproved ? 'text-emerald-500' : 'text-[#c8965f]') }}"></i>
                        @else
                            <i class="fa fa-store text-lg {{ $isRejected ? 'text-red-500' : ($isApproved ? 'text-emerald-500' : 'text-[#c8965f]') }}"></i>
                        @endif
                    </div>

                    {{-- Details --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-[#1a3a3a] truncate">
                            @if($type === 'tenant')
                                {{ $tenant->name ?? 'Your Shop' }}
                            @else
                                {{ $branch->name ?? 'Your Branch' }}
                            @endif
                        </p>

                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-[#1a3a3a]/50 bg-[#1a3a3a]/5 px-2 py-0.5 rounded">
                                @if($type === 'tenant')
                                    {{ $tenant->code ?? 'N/A' }}
                                @else
                                    {{ $branch->code ?? 'N/A' }}
                                @endif
                            </span>
                            <span class="text-[#1a3a3a]/20">•</span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider
                                         {{ $isRejected
                                             ? 'text-red-500'
                                             : ($isApproved
                                                 ? 'text-emerald-600'
                                                 : 'text-[#c8965f]') }}">
                                @if(!$isRejected && !$isApproved)
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#c8965f] animate-pulse"></span>
                                @endif
                                @if($type === 'tenant')
                                    @if($tenant && $tenant->status === 'pending')
                                        Pending Review
                                    @elseif($tenant && $tenant->status === 'rejected')
                                        Rejected
                                    @else
                                        Active
                                    @endif
                                @else
                                    @if($branch && $branch->approval_status === 'pending')
                                        Pending Review
                                    @elseif($branch && $branch->approval_status === 'rejected')
                                        Rejected
                                    @else
                                        Active
                                    @endif
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================= --}}
            {{-- REJECTION REASON / INFO BOX --}}
            {{-- ============================================= --}}
            @if($isRejected)
                <div class="relative rounded-2xl p-5 bg-red-500/5 border border-red-500/20 overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-red-500 to-red-700"></div>
                    <div class="flex items-start gap-3 pl-2">
                        <i class="fa fa-exclamation-triangle text-red-500 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="text-xs font-bold uppercase tracking-widest text-red-500 mb-2">
                                Reason for Rejection
                            </p>
                            <p class="text-sm text-red-700/90 leading-relaxed">
                                @if($type === 'tenant')
                                    {{ $tenant->rejection_reason ?? 'No reason provided.' }}
                                @else
                                    {{ $branch->rejection_reason ?? 'No reason provided.' }}
                                @endif
                            </p>
                            <p class="text-[11px] text-red-600/70 mt-3 flex items-center gap-1.5">
                                <i class="fa fa-info-circle"></i>
                                Contact support if you believe this was a mistake.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($isApproved)
                <div class="relative rounded-2xl p-5 bg-emerald-500/5 border border-emerald-500/20 overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-emerald-500 to-emerald-700"></div>
                    <div class="flex items-start gap-3 pl-2">
                        <i class="fa fa-check-circle text-emerald-500 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 mb-1.5">
                                Approved
                            </p>
                            <p class="text-sm text-emerald-700/90 leading-relaxed">
                                Your application has been approved. You can now log in and start using your dashboard.
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="relative rounded-2xl p-5 bg-blue-500/5 border border-blue-500/20 overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-blue-500 to-blue-700"></div>
                    <div class="flex items-start gap-3 pl-2">
                        <i class="fa fa-info-circle text-blue-500 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="text-xs font-bold uppercase tracking-widest text-blue-600 mb-1.5">
                                What happens next?
                            </p>
                            <p class="text-sm text-blue-700/90 leading-relaxed">
                                You'll gain full access to your dashboard once your
                                @if($type === 'tenant') shop @else branch @endif
                                is <strong class="text-blue-700">approved</strong>. We'll email you the moment it's done.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ============================================= --}}
            {{-- TIMELINE STEPS --}}
            {{-- ============================================= --}}
            @if(!$isRejected)
                <div class="pt-2">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-[#1a3a3a]/40 mb-4">Application Progress</p>

                    <div class="space-y-3">
                        {{-- Step 1: Submitted --}}
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center flex-shrink-0">
                                <i class="fa fa-check text-emerald-500 text-xs"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-[#1a3a3a]">Application Submitted</p>
                                <p class="text-[11px] text-[#1a3a3a]/50">Received by our team</p>
                            </div>
                        </div>

                        {{-- Connector --}}
                        <div class="ml-4 w-px h-4 bg-gradient-to-b from-emerald-500/30 to-[#c8965f]/30"></div>

                        {{-- Step 2: Under Review --}}
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 relative
                                        {{ $isApproved
                                            ? 'bg-emerald-500/10 border border-emerald-500/30'
                                            : 'bg-[#c8965f]/10 border border-[#c8965f]/30' }}">
                                @if(!$isApproved)
                                    <span class="absolute inset-0 rounded-full bg-[#c8965f]/20 animate-ping"></span>
                                @endif
                                <i class="fa fa-search text-xs {{ $isApproved ? 'text-emerald-500' : 'text-[#c8965f]' }} relative z-10"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold {{ $isApproved ? 'text-[#1a3a3a]' : 'text-[#c8965f]' }}">
                                    Under Review
                                </p>
                                <p class="text-[11px] text-[#1a3a3a]/50">
                                    {{ $isApproved ? 'Completed' : 'Admin is reviewing your details' }}
                                </p>
                            </div>
                        </div>

                        {{-- Connector --}}
                        <div class="ml-4 w-px h-4 {{ $isApproved ? 'bg-emerald-500/30' : 'bg-[#1a3a3a]/10' }}"></div>

                        {{-- Step 3: Approved --}}
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                        {{ $isApproved
                                            ? 'bg-emerald-500/10 border border-emerald-500/30'
                                            : 'bg-[#f5f0e8] border border-[#1a3a3a]/10' }}">
                                <i class="fa fa-flag text-xs {{ $isApproved ? 'text-emerald-500' : 'text-[#1a3a3a]/30' }}"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold {{ $isApproved ? 'text-emerald-600' : 'text-[#1a3a3a]/60' }}">
                                    Approved & Live
                                </p>
                                <p class="text-[11px] text-[#1a3a3a]/50">Get notified when ready</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ============================================= --}}
            {{-- ACTIONS --}}
            {{-- ============================================= --}}
            <div class="space-y-3 pt-2">

                {{-- If approved: Go to dashboard --}}
                @if($isApproved)
                    <a href="{{ $type === 'tenant' ? route('owner.dashboard') : route('owner.dashboard') }}"
                       class="group relative w-full py-4 px-6 bg-gradient-to-r from-emerald-500 to-emerald-600 text-[#f5f0e8] font-bold rounded-2xl hover:from-emerald-400 hover:to-emerald-500 transition-all duration-300 shadow-lg shadow-emerald-600/20 hover:shadow-xl hover:shadow-emerald-600/30 active:scale-[0.98] flex items-center justify-center gap-2 overflow-hidden">
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                        <i class="fa fa-rocket text-sm"></i>
                        <span>Go to Dashboard</span>
                        <i class="fa fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                @endif

                {{-- Rejected branch: Create new --}}
                @if($type === 'branch' && $branch && $branch->approval_status === 'rejected')
                    <a href="{{ route('owner.branches') }}"
                       class="group relative w-full py-4 px-6 bg-[#1a3a3a] text-[#f5f0e8] font-bold rounded-2xl hover:bg-[#0f2922] transition-all duration-300 shadow-lg shadow-[#1a3a3a]/15 hover:shadow-xl hover:shadow-[#1a3a3a]/25 active:scale-[0.98] flex items-center justify-center gap-2 overflow-hidden">
                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></span>
                        <i class="fa fa-plus text-sm"></i>
                        <span>Create New Branch</span>
                        <i class="fa fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                @endif

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="group w-full py-3.5 px-6 bg-[#f5f0e8]/60 hover:bg-[#f5f0e8] border border-[#1a3a3a]/10 hover:border-[#c8965f]/40 rounded-2xl transition-all text-sm font-bold text-[#1a3a3a]/70 hover:text-[#c8965f] flex items-center justify-center gap-2">
                        <i class="fa fa-sign-out-alt text-xs"></i>
                        <span>Log Out</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- ============================================= --}}
        {{-- FOOTER --}}
        {{-- ============================================= --}}
        <div class="px-8 pb-6 pt-2 border-t border-[#1a3a3a]/10 flex items-center justify-center gap-2 text-[10px] text-[#1a3a3a]/40">
            <i class="fa fa-shield-alt text-[#c8965f]"></i>
            <span class="font-semibold uppercase tracking-wider">Secure Application Review</span>
        </div>
    </div>

    {{-- Bottom copyright --}}
    <p class="mt-8 text-xs text-[#1a3a3a]/40 text-center">
        &copy; {{ date('Y') }} <span class="font-semibold text-[#1a3a3a]/60">Victorias Coffee Hub</span> · All rights reserved.
    </p>
</div>

{{-- 🔄 AUTO-REFRESH: Poll for status changes every 5 seconds --}}
<script>
(function () {
    const POLL_INTERVAL = 5000; // 5 seconds
    const CHECK_URL = @json(route('pending.check-status', ['type' => $type]));

    let isChecking = false;

    async function checkStatus() {
        if (isChecking) return;
        isChecking = true;

        try {
            const res = await fetch(CHECK_URL, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
            });

            if (!res.ok) return;

            const data = await res.json();

            // 🎉 Status changed — redirect!
            if (data.redirect) {
                document.body.style.opacity = '0.6';
                document.body.style.transition = 'opacity 0.3s';
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 400);
            }
        } catch (e) {
            // Silent — retry next interval
        } finally {
            isChecking = false;
        }
    }

    // Pause polling when tab is hidden (saves server resources)
    let intervalId = setInterval(checkStatus, POLL_INTERVAL);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearInterval(intervalId);
        } else {
            checkStatus();
            intervalId = setInterval(checkStatus, POLL_INTERVAL);
        }
    });
})();
</script>
@endsection