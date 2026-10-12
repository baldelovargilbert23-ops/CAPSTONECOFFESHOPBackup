
// resources/js/app.js

// ⚠️ DO NOT import Livewire or Alpine here.
// They are loaded by @livewireScripts in the layout.
// Importing them here causes "multiple instances" errors and breaks all buttons.

/* ---------- wire:navigate on internal links ---------- */
const LINK_SELECTOR =
    'a[href^="/"]:not([href^="//"]):not([wire\\:navigate]):not([target="_blank"]):not([download])';

let scheduled = false;
const applyNavigate = () => {
    if (scheduled) return;
    scheduled = true;
    requestAnimationFrame(() => {
        scheduled = false;
        document.querySelectorAll(LINK_SELECTOR).forEach((l) => l.setAttribute('wire:navigate', ''));
    });
};

/* ---------- theme ---------- */
const themeStore = {
    get() { try { return localStorage.getItem('theme'); } catch (e) { return null; } },
    set(v) { try { localStorage.setItem('theme', v); } catch (e) {} },
};

// Same function under both names the layouts used
window.toggleTheme = window.toggleDarkMode = function () {
    const isDark = document.documentElement.classList.toggle('dark');
    themeStore.set(isDark ? 'dark' : 'light');
    window.dispatchEvent(new CustomEvent('theme-changed', {
        detail: { theme: isDark ? 'dark' : 'light' },
    }));
};

/* ---------- sidebar accordion (only when Preline is NOT on the page) ---------- */
document.addEventListener('click', (e) => {
    if (window.HSAccordion) return;
    const toggle = e.target.closest('.hs-accordion-toggle');
    if (!toggle) return;

    e.preventDefault();
    e.stopPropagation();

    const parent = toggle.closest('.hs-accordion');
    const content = parent && parent.querySelector('.hs-accordion-content');
    if (!content) return;

    const isOpen = !content.classList.contains('hidden');

    document.querySelectorAll('.hs-accordion-content').forEach((c) => {
        if (c !== content) { c.classList.add('hidden'); c.style.height = ''; }
    });

    if (isOpen) {
        content.classList.add('hidden');
        content.style.height = '';
        toggle.classList.remove('hs-accordion-active');
    } else {
        content.classList.remove('hidden');
        content.style.height = content.scrollHeight + 'px';
        toggle.classList.add('hs-accordion-active');
    }
});

/* ---------- Livewire hooks ---------- */
document.addEventListener('livewire:init', () => {
    applyNavigate();
    Livewire.hook('morphed', () => {
        applyNavigate();
        const t = themeStore.get();
        if (t) document.documentElement.classList.toggle('dark', t === 'dark');
    });
});
document.addEventListener('livewire:navigated', applyNavigate);