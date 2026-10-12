<header class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-slate-900 via-zinc-950 to-black border-b border-[#d2964b]/20 shadow-lg h-16">
    <div class="flex items-center justify-between px-4 h-full">
        {{-- Left Section --}}
        <div class="flex items-center gap-3">
            {{-- Mobile Menu Toggle --}}
            <button @click="toggleSidebar()" 
                    class="lg:hidden p-2 rounded-lg bg-[#d2964b]/20 text-[#d2964b] hover:bg-[#d2964b]/30 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            
            {{-- Brand --}}
            <a href="{{ route('branch.dashboard') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#d2964b] to-[#8b5a2b] flex items-center justify-center shadow-lg shadow-[#d2964b]/30">
                    <i class="fa fa-store text-white text-sm"></i>
                </div>
                <span class="text-white font-bold text-sm hidden sm:block tracking-wide">Branch Portal</span>
            </a>
        </div>
        
        {{-- Center Section --}}
        <div class="hidden md:flex items-center gap-4">
            {{-- Live Status --}}
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Live</span>
            </div>
            
            {{-- Branch Name --}}
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#d2964b]/10 border border-[#d2964b]/20">
                <i class="fa fa-code-branch text-[#d2964b] text-xs"></i>
                <span class="text-xs font-bold text-[#d2964b]">{{ auth()->user()->branch->name ?? 'Branch' }}</span>
            </div>
            
            {{-- Role Badge --}}
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-purple-500/10 border border-purple-500/20">
                <i class="fa fa-user-tie text-purple-400 text-xs"></i>
                <span class="text-[10px] font-bold text-purple-400 uppercase tracking-wider">
                    {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
                </span>
            </div>
        </div>
        
        {{-- Right Section --}}
        <div class="flex items-center gap-3">
            @include('layouts.partials.notification-link')

            {{-- User Dropdown --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-white/5 transition-all duration-200">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-[#d2964b] to-[#8b5a2b] flex items-center justify-center text-black font-bold text-sm shadow-lg shadow-[#d2964b]/30">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="hidden md:block text-left">
                        <p class="text-xs font-semibold text-white leading-tight">{{ auth()->user()->name }}</p>
                        <p class="text-[9px] text-gray-400">{{ auth()->user()->email }}</p>
                    </div>
                    <i class="fa fa-chevron-down text-gray-400 text-[10px] ml-0.5 hidden md:block"></i>
                </button>
                
                {{-- Dropdown Menu --}}
                <div x-show="open" 
                     @click.away="open = false" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="absolute right-0 mt-2 w-56 bg-slate-900/95 backdrop-blur-xl border border-[#d2964b]/20 rounded-xl shadow-2xl overflow-hidden">
                    
                    <div class="p-3 border-b border-[#d2964b]/10">
                        <p class="text-sm font-bold text-white">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-gray-400">{{ auth()->user()->email }}</p>
                        <p class="text-[10px] text-[#d2964b] mt-1 font-medium">
                            <i class="fa fa-user-tie mr-1"></i>
                            {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
                        </p>
                    </div>
                    
                    <div class="p-2">
                        <a href="{{ route('branch.dashboard') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-300 hover:bg-white/5 hover:text-white transition-all">
                            <i class="fa fa-home text-[#d2964b] w-4 text-center"></i>
                            Dashboard
                        </a>
                        
                        <a href="#" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-300 hover:bg-white/5 hover:text-white transition-all">
                            <i class="fa fa-user w-4 text-center"></i>
                            Profile Settings
                        </a>
                        
                        <hr class="border-[#d2964b]/10 my-1">
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" 
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all">
                                <i class="fa fa-sign-out-alt w-4 text-center"></i>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
