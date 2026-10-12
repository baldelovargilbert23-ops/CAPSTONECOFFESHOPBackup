<div id="hs-application-sidebar"
     x-data
     :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
     class="fixed inset-y-0 start-0 z-50 w-64 h-dvh max-h-dvh
            border-e shadow-lg
            transform transition-transform duration-300 ease-in-out
            flex flex-col overflow-hidden"
     style="background-color: var(--bg-elevated); border-color: var(--border-soft);"
     role="dialog" tabindex="-1" aria-label="Sidebar">

    {{-- Mobile Close Button --}}
    <button type="button"
            @click="sidebarOpen = false"
            class="lg:hidden absolute top-3 right-3 z-20 p-2 rounded-lg transition-colors"
            style="color: var(--text-muted);"
            onmouseover="this.style.color='var(--accent)'; this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'"
            onmouseout="this.style.color='var(--text-muted)'; this.style.backgroundColor=''"
            aria-label="Close sidebar">
      <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>

    <!-- Header Section -->
    <div class="px-6 pt-8 pb-4 flex flex-col gap-y-2 flex-shrink-0">
      <div class="flex items-center gap-x-3">
        <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center"
             style="background-color: var(--forest);">
          <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
               style="color: var(--bg-primary);">
            <path d="M17 8h1a4 4 0 1 1 0 8h-1"/>
            <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
            <line x1="6" y1="2" x2="6" y2="4"/>
            <line x1="10" y1="2" x2="10" y2="4"/>
            <line x1="14" y1="2" x2="14" y2="4"/>
          </svg>
        </div>
        <div class="flex flex-col">
          <span class="text-xs font-bold uppercase tracking-widest" style="color: var(--accent);">Victorias City</span>
          <h2 class="text-lg font-black tracking-tight" style="color: var(--text-primary);">Coffee Shops</h2>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <div class="flex-1 min-h-0 overflow-y-auto p-4">
      <ul class="flex flex-col space-y-1.5">

        <!-- System Overview -->
        <li>
          <a href="{{ route('admin.dashboard') }}"
             class="flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition-all active:scale-[0.98]"
             style="{{ request()->routeIs('admin.dashboard')
                ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                : 'color: var(--text-primary);' }}"
             @if(!request()->routeIs('admin.dashboard'))
               onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
               onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
             @endif>
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="color: {{ request()->routeIs('admin.dashboard') ? 'var(--accent)' : 'var(--text-muted)' }};">
              <rect width="7" height="9" x="3" y="3" rx="1"/>
              <rect width="7" height="5" x="14" y="3" rx="1"/>
              <rect width="7" height="9" x="14" y="12" rx="1"/>
              <rect width="7" height="5" x="3" y="15" rx="1"/>
            </svg>
            System Overview
          </a>
        </li>

        <!-- Section: Core Management -->
        <li class="pt-4 pb-2 px-3">
          <span class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color: var(--text-muted);">Core Management</span>
        </li>

        <li>
          <a href="{{ route('admin.announcements') }}"
             class="flex items-center gap-x-3.5 rounded-lg px-3 py-2.5 text-sm font-medium transition"
             style="{{ request()->routeIs('admin.announcements')
                ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
                : 'color: var(--text-primary);' }}">
            <i class="fa fa-bullhorn w-4 text-center" aria-hidden="true"></i>
            Announcements
          </a>
        </li>

        <!-- Tenant Operations Accordion -->
        <li class="hs-accordion" id="shop-accordion">
          <button type="button"
                  class="hs-accordion-toggle w-full text-start flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition"
                  style="{{ request()->is('admin/tenants*')
                     ? 'color: var(--accent);'
                     : 'color: var(--text-primary);' }}"
                  @if(!request()->is('admin/tenants*'))
                    onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                    onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                  @endif
                  aria-expanded="{{ request()->is('admin/tenants*') ? 'true' : 'false' }}"
                  aria-controls="shop-accordion-child">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="color: {{ request()->is('admin/tenants*') ? 'var(--accent)' : 'var(--text-muted)' }};">
              <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"/>
              <path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/>
              <circle cx="12" cy="15" r="2"/>
            </svg>
            Tenant Operations
            <svg class="hs-accordion-active:-rotate-180 ms-auto w-3.5 h-3.5 transition-transform duration-300"
                 xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="color: {{ request()->is('admin/tenants*') ? 'var(--accent)' : 'var(--text-muted)' }};">
              <path d="m6 9 6 6 6-6"/>
            </svg>
          </button>
          <div id="shop-accordion-child" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300 {{ request()->is('admin/tenants*') ? '' : 'hidden' }}">
            <ul class="ps-8 pt-2 space-y-1 ms-5" style="border-left: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
              <li>
                <a href="{{ route('admin.tenants') }}"
                   class="flex items-center justify-between py-2 px-3 text-sm rounded-lg transition"
                   style="{{ request()->routeIs('admin.tenants')
                      ? 'color: var(--accent); background-color: color-mix(in srgb, var(--accent) 5%, transparent);'
                      : 'color: var(--text-muted);' }}"
                   @if(!request()->routeIs('admin.tenants'))
                     onmouseover="this.style.color='var(--accent)'; this.style.backgroundColor='color-mix(in srgb, var(--accent) 5%, transparent)'"
                     onmouseout="this.style.color='var(--text-muted)'; this.style.backgroundColor=''"
                   @endif>
                  Pending Approvals
                </a>
              </li>
              <li>
                <a href="{{ route('admin.manage-tenant') }}"
                   class="block py-2 px-3 text-sm rounded-lg transition"
                   style="{{ request()->routeIs('admin.manage-tenant')
                      ? 'color: var(--accent); background-color: color-mix(in srgb, var(--accent) 5%, transparent);'
                      : 'color: var(--text-muted);' }}"
                   @if(!request()->routeIs('admin.manage-tenant'))
                     onmouseover="this.style.color='var(--accent)'; this.style.backgroundColor='color-mix(in srgb, var(--accent) 5%, transparent)'"
                     onmouseout="this.style.color='var(--text-muted)'; this.style.backgroundColor=''"
                   @endif>
                  Verified Tenants
                </a>
              </li>
            </ul>
          </div>
        </li>

        <!-- Customer Database Accordion -->
        <li class="hs-accordion" id="customer-accordion">
          <button type="button"
                  class="hs-accordion-toggle w-full text-start flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition"
                  style="{{ request()->is('admin/customers*')
                     ? 'color: var(--accent);'
                     : 'color: var(--text-primary);' }}"
                  @if(!request()->is('admin/customers*'))
                    onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                    onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                  @endif
                  aria-expanded="{{ request()->is('admin/customers*') ? 'true' : 'false' }}"
                  aria-controls="customer-accordion-child">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="color: {{ request()->is('admin/customers*') ? 'var(--accent)' : 'var(--text-muted)' }};">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Customer Database
            <svg class="hs-accordion-active:-rotate-180 ms-auto w-3.5 h-3.5 transition-transform duration-300"
                 xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="color: {{ request()->is('admin/customers*') ? 'var(--accent)' : 'var(--text-muted)' }};">
              <path d="m6 9 6 6 6-6"/>
            </svg>
          </button>
          <div id="customer-accordion-child" class="hs-accordion-content hidden w-full overflow-hidden transition-[height] duration-300 {{ request()->is('admin/customers*') ? '' : 'hidden' }}">
            <ul class="ps-8 pt-2 space-y-1 ms-5" style="border-left: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
              <li>
                <a href="{{ route('admin.manage-customer') }}"
                   class="block py-2 px-3 text-sm rounded-lg transition"
                   style="{{ request()->routeIs('admin.manage-customer')
                      ? 'color: var(--accent); background-color: color-mix(in srgb, var(--accent) 5%, transparent);'
                      : 'color: var(--text-muted);' }}"
                   @if(!request()->routeIs('admin.manage-customer'))
                     onmouseover="this.style.color='var(--accent)'; this.style.backgroundColor='color-mix(in srgb, var(--accent) 5%, transparent)'"
                     onmouseout="this.style.color='var(--text-muted)'; this.style.backgroundColor=''"
                   @endif>
                  Full Directory
                </a>
              </li>
            </ul>
          </div>
        </li>

        <!-- Section: Business Intelligence -->
        <li class="pt-4 pb-2 px-3">
          <span class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color: var(--text-muted);">Business Intelligence</span>
        </li>

        <!-- Analytics Accordion -->
        <li class="hs-accordion" id="analytics-accordion">
          <button type="button"
                  class="hs-accordion-toggle w-full text-start flex items-center gap-x-3.5 py-2.5 px-3 rounded-lg text-sm font-medium transition"
                  style="{{ request()->is('admin/analytics*')
                     ? 'color: var(--accent);'
                     : 'color: var(--text-primary);' }}"
                  @if(!request()->is('admin/analytics*'))
                    onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 10%, transparent)'; this.style.color='var(--accent)'"
                    onmouseout="this.style.backgroundColor=''; this.style.color='var(--text-primary)'"
                  @endif
                  aria-expanded="{{ request()->is('admin/analytics*') ? 'true' : 'false' }}"
                  aria-controls="analytics-accordion-child">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="color: {{ request()->is('admin/analytics*') ? 'var(--accent)' : 'var(--text-muted)' }};">
              <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
              <path d="M22 12A10 10 0 0 0 12 2v10z"/>
            </svg>
            Analytics Dashboard
            <svg class="hs-accordion-active:-rotate-180 ms-auto w-3.5 h-3.5 transition-transform duration-300"
                 xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="color: {{ request()->is('admin/analytics*') ? 'var(--accent)' : 'var(--text-muted)' }};">
              <path d="m6 9 6 6 6-6"/>
            </svg>
          </button>
          <div id="analytics-accordion-child" class="hs-accordion-content hidden w-full overflow-hidden transition-[height] duration-300 {{ request()->is('admin/analytics*') ? '' : 'hidden' }}">
            <ul class="ps-8 pt-2 space-y-1 ms-5" style="border-left: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
              <li>
                <a href="{{ route('admin.analytics') }}"
                   class="block py-2 px-3 text-sm rounded-lg transition"
                   style="{{ request()->routeIs('admin.analytics')
                      ? 'color: var(--accent); background-color: color-mix(in srgb, var(--accent) 5%, transparent);'
                      : 'color: var(--text-muted);' }}"
                   @if(!request()->routeIs('admin.analytics'))
                     onmouseover="this.style.color='var(--accent)'; this.style.backgroundColor='color-mix(in srgb, var(--accent) 5%, transparent)'"
                     onmouseout="this.style.color='var(--text-muted)'; this.style.backgroundColor=''"
                   @endif>
                  Performance Reports
                </a>
              </li>
            </ul>
          </div>
        </li>
      </ul>
    </div>

    <div class="px-4 pb-4">
      <a href="{{ route('admin.theme-settings') }}"
         class="flex items-center gap-x-3.5 rounded-lg px-3 py-2.5 text-sm font-medium transition"
         style="{{ request()->routeIs('admin.theme-settings')
            ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);'
            : 'color: var(--text-primary);' }}">
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.7 2.94-.08-.02a1.7 1.7 0 0 0-1.74.72l-.04.07h-3.4l-.04-.07a1.7 1.7 0 0 0-1.74-.72l-.08.02-1.7-2.94.06-.06A1.7 1.7 0 0 0 9.7 15l-.08-.02v-3.4l.08-.02a1.7 1.7 0 0 0 .34-1.88l-.06-.06 1.7-2.94.08.02a1.7 1.7 0 0 0 1.74-.72l.04-.07h3.4l.04.07a1.7 1.7 0 0 0 1.74.72l.08-.02 1.7 2.94-.06.06A1.7 1.7 0 0 0 20.6 12l.08.02v3.4z" transform="translate(-2.2 -2.2) scale(1.18)"/>
        </svg>
        Global appearance
      </a>
    </div>

    <!-- Footer (Theme + User + Logout) -->
    <div class="flex-shrink-0 p-4"
         style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">

      <div class="flex items-center justify-between mb-3 px-2">
        <span class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">
          Theme
        </span>
        <button type="button"
                onclick="toggleTheme()"
                class="flex items-center justify-center w-9 h-9 rounded-full transition-colors"
                style="color: var(--text-primary);"
                title="Toggle theme"
                aria-label="Toggle theme">
          <svg class="w-4 h-4 dark:hidden" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
          </svg>
          <svg class="w-4 h-4 hidden dark:block" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2"/>
            <path d="M12 20v2"/>
            <path d="m4.93 4.93 1.41 1.41"/>
            <path d="m17.66 17.66 1.41 1.41"/>
            <path d="M2 12h2"/>
            <path d="M20 12h2"/>
            <path d="m6.34 17.66-1.41 1.41"/>
            <path d="m19.07 4.93-1.41 1.41"/>
          </svg>
        </button>
      </div>

      <div class="flex items-center justify-between gap-x-3 p-2">
        <div class="flex items-center gap-x-3">
          <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black"
               style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
            AD
          </div>
          <div class="flex flex-col">
            <span class="text-xs font-semibold" style="color: var(--text-primary);">Administrator</span>
            <span class="text-[10px]" style="color: var(--text-muted);">Primary Node</span>
          </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit"
                  class="p-2 rounded-lg transition-colors"
                  style="color: var(--text-muted);"
                  onmouseover="this.style.color='var(--bad)'; this.style.backgroundColor='color-mix(in srgb, var(--bad) 10%, transparent)'"
                  onmouseout="this.style.color='var(--text-muted)'; this.style.backgroundColor=''"
                  title="Logout"
                  aria-label="Logout">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
          </button>
        </form>
      </div>
    </div>
</div>

@once
<style>
    #hs-application-sidebar { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
</style>
@endonce
