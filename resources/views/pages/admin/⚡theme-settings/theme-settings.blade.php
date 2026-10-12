<div class="min-h-screen px-3 py-5 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em]" style="color: var(--accent);">Super admin</p>
                <h1 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl" style="color: var(--text-primary);">Global appearance</h1>
                <p class="mt-2 max-w-2xl text-sm" style="color: var(--text-muted);">Choose the colors used across the public site and every owner, branch, staff, and customer page. Review the preview before saving.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-sm font-semibold transition" style="border: 1px solid var(--border-soft); color: var(--text-primary);">
                Back to overview
            </a>
        </div>

        @if (session()->has('success'))
            <div role="status" class="rounded-xl px-4 py-3 text-sm font-medium" style="background-color: color-mix(in srgb, var(--ok) 12%, transparent); color: var(--ok);">
                {{ session('success') }}
            </div>
        @endif

        @if (!$storageReady)
            <div role="alert" class="rounded-xl px-4 py-3 text-sm" style="background-color: color-mix(in srgb, var(--warn) 12%, transparent); color: var(--text-primary);">
                Apply the new database migration before saving colors. The current site palette remains active until then.
            </div>
        @endif

        @error('storage')
            <div role="alert" class="rounded-xl px-4 py-3 text-sm" style="background-color: color-mix(in srgb, var(--bad) 12%, transparent); color: var(--bad);">{{ $message }}</div>
        @enderror

        <form wire:submit="saveTheme" class="grid min-w-0 gap-5 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
            <section class="min-w-0 rounded-2xl p-4 sm:p-6" style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                <div class="mb-5">
                    <h2 class="text-lg font-bold" style="color: var(--text-primary);">Color palette</h2>
                    <p class="mt-1 text-sm" style="color: var(--text-muted);">Light and dark appearances each have their own surfaces and text colors.</p>
                </div>

                <div class="grid gap-x-5 sm:grid-cols-2">
                    @foreach (\App\Models\GlobalThemeSettings::labels() as $key => $label)
                        <label for="theme-color-{{ $key }}" class="mb-4 block min-w-0">
                            <span class="mb-2 flex items-center justify-between gap-3 text-sm font-semibold" style="color: var(--text-primary);">
                                <span>{{ $label }}</span>
                                <span class="font-mono text-xs font-normal uppercase" style="color: var(--text-muted);">{{ $colors[$key] ?? '' }}</span>
                            </span>
                            <span class="flex min-h-12 items-center gap-3 rounded-xl px-3" style="border: 1px solid var(--border-soft); background-color: var(--bg-primary);">
                                <input id="theme-color-{{ $key }}" type="color" wire:model.live.debounce.250ms="colors.{{ $key }}" class="h-8 w-10 cursor-pointer rounded border-0 bg-transparent p-0" aria-label="{{ $label }}">
                                <span class="text-xs" style="color: var(--text-muted);">Select a color</span>
                            </span>
                            @error('colors.' . $key)
                                <span role="alert" class="mt-1 block text-xs" style="color: var(--bad);">Enter a valid six-digit hex color.</span>
                            @enderror
                        </label>
                    @endforeach
                </div>

                <div class="mt-2 flex flex-col-reverse gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between" style="border-color: var(--border-soft);">
                    <button type="button" wire:click="resetDraft" class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-sm font-semibold" style="color: var(--text-muted);">
                        Restore default draft
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveTheme" @disabled(!$storageReady) class="inline-flex min-h-11 items-center justify-center rounded-xl px-5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50" style="background-color: var(--accent);">
                        <span wire:loading.remove wire:target="saveTheme">Save for all pages</span>
                        <span wire:loading wire:target="saveTheme">Saving…</span>
                    </button>
                </div>
            </section>

            @php
                $previewBackground = $previewDark ? ($colors['dark_background'] ?? '#0a1f1a') : ($colors['light_background'] ?? '#f5f0e8');
                $previewSurface = $previewDark ? ($colors['dark_surface'] ?? '#14332c') : ($colors['light_surface'] ?? '#ffffff');
                $previewText = $previewDark ? ($colors['dark_text'] ?? '#f0ebe0') : ($colors['light_text'] ?? '#1a3a3a');
            @endphp

            <section class="min-w-0 rounded-2xl p-4 sm:p-6" style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold" style="color: var(--text-primary);">Page preview</h2>
                        <p class="mt-1 text-sm" style="color: var(--text-muted);">Sample branch dashboard colors</p>
                    </div>
                    <button type="button" wire:click="togglePreviewMode" class="inline-flex min-h-10 items-center justify-center rounded-xl px-3 text-sm font-semibold" style="border: 1px solid var(--border-soft); color: var(--text-primary);">
                        Preview {{ $previewDark ? 'light' : 'dark' }} mode
                    </button>
                </div>

                <div class="overflow-hidden rounded-xl" style="background-color: {{ $previewBackground }}; color: {{ $previewText }}; --preview-surface: {{ $previewSurface }}; --preview-accent: {{ $colors['accent'] ?? '#c8965f' }};">
                    <div class="flex items-center justify-between gap-3 px-4 py-3" style="background-color: var(--preview-surface); border-bottom: 1px solid color-mix(in srgb, {{ $previewText }} 12%, transparent);">
                        <div>
                            <span class="block text-[11px] font-semibold uppercase tracking-wider" style="color: var(--preview-accent);">Central Branch</span>
                            <strong class="text-sm">Branch dashboard</strong>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold" style="background-color: color-mix(in srgb, var(--preview-accent) 16%, transparent);">Today</span>
                    </div>

                    <div class="grid gap-3 p-4 sm:grid-cols-2">
                        <div class="rounded-xl p-4" style="background-color: var(--preview-surface);">
                            <span class="text-xs" style="opacity:.72">Today’s sales</span>
                            <strong class="mt-1 block text-2xl">₱18,420</strong>
                            <span class="text-xs" style="color: var(--preview-accent);">↑ 8.4% from yesterday</span>
                        </div>
                        <div class="rounded-xl p-4" style="background-color: var(--preview-surface);">
                            <span class="text-xs" style="opacity:.72">Orders completed</span>
                            <strong class="mt-1 block text-2xl">64</strong>
                            <span class="text-xs" style="opacity:.72">12 orders in progress</span>
                        </div>
                    </div>

                    <div class="mx-4 mb-4 rounded-xl p-4" style="background-color: var(--preview-surface);">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <strong class="text-sm">Popular today</strong>
                            <span class="text-xs" style="opacity:.72">View report</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t pt-3 text-sm" style="border-color: color-mix(in srgb, {{ $previewText }} 12%, transparent);">
                            <span>Spanish Latte</span><strong>28 orders</strong>
                        </div>
                    </div>

                    <div class="mx-4 mb-4 flex flex-wrap gap-2">
                        <button type="button" class="min-h-10 rounded-lg px-4 text-sm font-bold text-white" style="background-color: var(--preview-accent);">Primary action</button>
                        <button type="button" class="min-h-10 rounded-lg px-4 text-sm font-semibold" style="background-color: var(--preview-surface);">Secondary action</button>
                    </div>
                </div>
                <p class="mt-3 text-xs" style="color: var(--text-muted);">Colors are previewed locally here. Saving applies the selected palette to all site layouts.</p>
            </section>
        </form>
    </div>
</div>
