<header class="bg-white/80 dark:bg-[#11131A]/80 backdrop-blur-md border-b border-gray-200 dark:border-[#d2964b]/20 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20 gap-8">
            
            <!-- Logo / Brand -->
            <a href="{{ route('customer.dashboard') }}" class="flex-shrink-0 flex items-center">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#d2964b] to-[#8b5a2b] flex items-center justify-center shadow-lg shadow-[#d2964b]/30">
                        <i class="fa fa-mug-hot text-white text-sm"></i>
                    </div>
                    <span class="text-xl font-bold bg-gradient-to-r from-[#d2964b] to-[#f5a623] bg-clip-text text-transparent">
                        CoffeeHub
                    </span>
                </div>
            </a>


            <!-- Right Side Icons -->
            <div class="flex items-center gap-2">
                <!-- Mobile Search Button -->
                <button wire:click="openSearchModal" class="md:hidden p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-[#d2964b]/10 rounded-full transition-all">
                    <i class="fa fa-search text-lg"></i>
                </button>

                <!-- Dark Mode Toggle -->
                <button onclick="toggleDarkMode()" 
                    class="p-2 text-gray-600 dark:text-gray-400 hover:text-[#d2964b] hover:bg-gray-100 dark:hover:bg-[#d2964b]/10 rounded-full transition-all">
                    <i class="fa fa-moon dark:hidden text-lg"></i>
                    <i class="fa fa-sun hidden dark:inline-block text-lg"></i>
                </button>

                <!-- Mobile Menu Button -->
                <button type="button" 
                    class="lg:hidden p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-[#d2964b]/10 rounded-full transition-all"
                    data-hs-overlay="#customer-sidebar"
                    aria-controls="customer-sidebar">
                    <i class="fa fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </div>
</header>