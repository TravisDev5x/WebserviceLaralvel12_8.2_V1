/**
 * EXTENSIÓN CUSTOM — Toggle `data-collapsed` en `.sidebar` (icon-only en desktop).
 * No nativo Basecoat v0.3.11. No toca `aria-hidden` ni el listener de `basecoat:sidebar`.
 */

const STORAGE_KEY = 'sidebar-collapsed';
const BP = '(min-width: 768px)';

function getSidebar() {
    return document.querySelector('.sidebar');
}

function applyCollapsed(sidebar, collapsed) {
    if (!sidebar) return;
    sidebar.dataset.collapsed = collapsed ? 'true' : 'false';
    try {
        localStorage.setItem(STORAGE_KEY, collapsed ? 'true' : 'false');
    } catch (_) {}

    const btn = document.getElementById('sidebar-collapse-toggle');
    if (btn) {
        btn.setAttribute(
            'aria-label',
            collapsed ? 'Expandir barra lateral' : 'Contraer barra lateral',
        );
        btn.setAttribute(
            'data-tooltip',
            collapsed ? 'Expandir barra lateral' : 'Contraer barra lateral',
        );
    }
}

function restoreFromStorage() {
    const sidebar = getSidebar();
    if (!sidebar) return;

    let saved = null;
    try {
        saved = localStorage.getItem(STORAGE_KEY);
    } catch (_) {}

    const mq = window.matchMedia(BP);
    const collapsed = saved === 'true' && mq.matches;
    applyCollapsed(sidebar, collapsed);
}

document.addEventListener('basecoat:sidebar-collapse', (e) => {
    const sidebar = getSidebar();
    if (!sidebar) return;

    if (!window.matchMedia(BP).matches) {
        return;
    }

    const action = e.detail?.action;
    const cur = sidebar.dataset.collapsed === 'true';

    if (action === 'collapse') {
        applyCollapsed(sidebar, true);
    } else if (action === 'expand') {
        applyCollapsed(sidebar, false);
    } else {
        applyCollapsed(sidebar, !cur);
    }
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', restoreFromStorage);
} else {
    restoreFromStorage();
}

window.matchMedia(BP).addEventListener('change', () => {
    restoreFromStorage();
});
