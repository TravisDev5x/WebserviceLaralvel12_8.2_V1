/**
 * Temas visuales (paleta + --radius / tokens derivados; Doom 64 también --font-sans) vía data-theme en <html>.
 * Independiente de basecoat:theme (clase dark) y themeMode en localStorage.
 */

const VALID_COLOR_THEMES = new Set(['default', 'claude', 'doom64', 'supabase', 'blue', 'violet']);

function currentColorThemeId() {
    return document.documentElement.getAttribute('data-theme') || 'default';
}

function applyColorTheme(themeId) {
    const id = VALID_COLOR_THEMES.has(themeId) ? themeId : 'default';
    if (id === 'default') {
        document.documentElement.removeAttribute('data-theme');
    } else {
        document.documentElement.setAttribute('data-theme', id);
    }
    try {
        localStorage.setItem('colorTheme', id);
    } catch (_) {}
    syncColorThemeMenuRadios();
    document.dispatchEvent(new CustomEvent('basecoat:color-theme-applied', { detail: { theme: id } }));
}

function syncColorThemeMenuRadios() {
    const cur = currentColorThemeId();
    document.querySelectorAll('[data-color-theme-id]').forEach((el) => {
        const id = el.getAttribute('data-color-theme-id');
        el.setAttribute('aria-checked', id === cur ? 'true' : 'false');
    });
}

function onColorThemeEvent(e) {
    const t = e.detail?.theme;
    if (t) applyColorTheme(t);
}

function onDocumentClick(e) {
    const btn = e.target instanceof Element ? e.target.closest('[data-color-theme-id]') : null;
    if (!btn) return;
    const id = btn.getAttribute('data-color-theme-id');
    if (id) applyColorTheme(id);
}

let morphHookRegistered = false;

function registerMorphResync() {
    if (morphHookRegistered) return;
    const lw = window.Livewire;
    if (!lw || typeof lw.hook !== 'function') return;
    morphHookRegistered = true;
    lw.hook('morph.updated', () => syncColorThemeMenuRadios());
}

function init() {
    document.addEventListener('basecoat:color-theme', onColorThemeEvent);
    document.addEventListener('click', onDocumentClick);
    syncColorThemeMenuRadios();
    registerMorphResync();
    document.addEventListener('livewire:init', registerMorphResync, { once: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
} else {
    init();
}
