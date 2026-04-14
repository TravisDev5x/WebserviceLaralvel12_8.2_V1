/**
 * Capa de accesibilidad / teclado — NO modifica Basecoat.
 * Delegación en `document` para sobrevivir a morphs de Livewire.
 *
 * Atajos documentados en BASECOAT-IMPLEMENTATION.md:
 * - Esc (móvil): cierra sidebar si no hay dialog/dropdown/select abierto.
 * - Ctrl+B / Cmd+B: toggle sidebar colapsado (desktop; no en inputs editables).
 * - Alt+Shift+M: alternar tema (basecoat:theme; no en inputs editables).
 */

const BP_MOBILE = 768;

function isEditableTarget(el) {
    if (!el || !(el instanceof Element)) return false;
    const tag = el.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return true;
    if (el.isContentEditable) return true;
    if (el.closest('[contenteditable="true"]')) return true;
    return false;
}

function hasOpenDialog() {
    return Boolean(document.querySelector('dialog[open]'));
}

function hasOpenSelectListbox() {
    return Boolean(document.querySelector('.select > button[aria-expanded="true"]'));
}

function hasOpenPopoverLayer() {
    return Boolean(document.querySelector('[data-popover][aria-hidden="false"]'));
}

function isMobileSidebarOverlayOpen() {
    if (window.innerWidth >= BP_MOBILE) return false;
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return false;
    return sidebar.getAttribute('aria-hidden') === 'false';
}

document.addEventListener('click', (e) => {
    const link = e.target.closest?.('a.skip-link');
    if (!link) return;
    const href = link.getAttribute('href');
    if (!href || href.charAt(0) !== '#') return;
    const id = href.slice(1);
    const target = document.getElementById(id);
    if (!target) return;
    e.preventDefault();
    target.focus({ preventScroll: true });
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
});

document.addEventListener('keydown', (e) => {
    if (e.defaultPrevented) return;

    if (e.key === 'Escape') {
        if (hasOpenDialog()) return;
        if (document.querySelector('.dropdown-menu button[aria-expanded="true"]')) return;
        if (hasOpenSelectListbox()) return;
        if (hasOpenPopoverLayer()) return;

        if (isMobileSidebarOverlayOpen()) {
            e.preventDefault();
            document.dispatchEvent(
                new CustomEvent('basecoat:sidebar', {
                    detail: { id: 'main-navigation', action: 'close' },
                }),
            );
            const toggle = document.getElementById('sidebar-mobile-toggle');
            if (toggle && typeof toggle.focus === 'function') {
                window.setTimeout(() => toggle.focus(), 0);
            }
        }
        return;
    }

    const active = document.activeElement;

    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
        if (isEditableTarget(active)) return;
        if (window.innerWidth < BP_MOBILE) return;
        if (!document.querySelector('.sidebar')) return;
        e.preventDefault();
        document.dispatchEvent(new CustomEvent('basecoat:sidebar-collapse'));
        return;
    }

    if (e.altKey && e.shiftKey && e.key.toLowerCase() === 'm') {
        if (isEditableTarget(active)) return;
        e.preventDefault();
        document.dispatchEvent(new CustomEvent('basecoat:theme'));
    }
});
