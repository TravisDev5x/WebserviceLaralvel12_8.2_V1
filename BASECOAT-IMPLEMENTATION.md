# Basecoat UI — Implementación en el proyecto

Este documento describe el estado final de la integración **Basecoat UI** (paquete `basecoat-css`) en el webservice **Laravel 12 + Livewire 4**, tras las fases de auditoría, infraestructura, markup y theming.

## Versión instalada

| Paquete        | Versión   | Rol                                      |
|----------------|-----------|------------------------------------------|
| `basecoat-css` | **0.3.11**| Componentes CSS + JS (ESM)             |
| `tailwindcss`  | ^4.0.0    | Motor de utilidades y `@theme`         |
| `@tailwindcss/vite` | ^4.0.0 | Plugin Vite para Tailwind v4      |
| `mallardduck/blade-lucide-icons` | **1.26.12** | Iconos Lucide en Blade (`<x-lucide-… />`) vía Composer |

Referencias oficiales:

- [Introducción Basecoat](https://basecoatui.com/introduction/)
- [Instalación](https://basecoatui.com/installation/)
- [Repositorio GitHub](https://github.com/hunvreus/basecoat)
- [AGENTS.md (guía para contribuir al upstream)](https://github.com/hunvreus/basecoat/blob/main/AGENTS.md)

## Método de instalación

**Principal: NPM + Vite (recomendado por la documentación para Laravel).**

1. **CSS** (`resources/css/app.css`), orden fijo:

   ```css
   @import 'tailwindcss';
   @import 'basecoat-css';
   @import './theme.css';
   ```

2. **JavaScript** (`resources/js/app.js`):

   - `import 'basecoat-css/all'` — registra e inicializa los componentes que requieren JS (dropdown, popover, select, sidebar, tabs, toast, etc.), según [instalación NPM](https://basecoatui.com/installation/).

3. **Layouts** que cargan el bundle:

   - `resources/views/layouts/app.blade.php` — panel monitor (`@vite`)
   - `resources/views/layouts/auth.blade.php` — login / registro (`@vite`)
   - `resources/views/layouts/docs-public.blade.php` — manual público (`@vite`)

**Excepción (CDN):** `resources/views/errors/500-friendly.blade.php` usa solo la hoja **CDN** `basecoat-css@0.3.11` (`basecoat.cdn.min.css`) para que la página de error no dependa del manifest de Vite si el despliegue falla.

## Tema y modo oscuro

- **Tokens de aplicación** (`--app-*`) en `resources/css/theme.css`: **puente** a variables semánticas Basecoat (`--background`, `--card`, `--foreground`, `--muted`, `--border`, `--sidebar`, …) para que el shell (layouts Blade) reaccione a **`data-theme`** y a **`html.dark`** igual que los componentes.
- **Basecoat:** persistencia `localStorage` clave **`themeMode`** (`light` | `dark`), script en `<head>` según [Theme Switcher](https://basecoatui.com/components/theme-switcher), conmutación con evento **`basecoat:theme`** (y `onclick` que lo dispara en el panel y en `docs-public`).
- **Migración:** si existía la clave antigua `ui-theme`, se copia una vez a `themeMode` al cargar el layout.
- **Auth:** `html.auth-layout` quita `class="dark"` (siempre **lado claro** del tema); **`data-theme`** y el puente `--app-*` siguen aplicando la paleta activa en login/registro (`layouts/auth.blade.php`).

## Temas visuales (`data-theme`, híbrido shadcn)

Independiente del modo claro/oscuro: **`data-theme`** en `<html>` + **`localStorage`** **`colorTheme`** (`default` \| `claude` \| `doom64` \| `supabase` \| `blue` \| `violet`). **`default`** quita el atributo y usa solo el bundle **`basecoat-css`**.

- **Forma y radio:** cada archivo en `resources/css/themes/` define **`--radius`** (resto de temas: **`0.625rem`**, alineado al default Basecoat). **Doom 64** usa **`--radius: 0`** y **`--radius-sm` … `--radius-xl: 0`** para esquinas cuadradas sin `calc(0 - npx)` negativo (Tailwind/Basecoat derivan la escala desde `--radius`).
- **Tipografía (opcional por tema):** Doom 64 redefine **`--font-sans`** a stack monoespaciado en el mismo scope que los colores (mayor especificidad que el bloque `@theme` de `app.css` para `<html>` con `data-theme`).
- **Anti-flash:** `partials/theme-color-head.blade.php` antes de `@vite` (`app`, `docs-public`, `auth`).
- **CSS:** import `./themes/_index.css` en `app.css` tras `theme.css`. Tokens en **OKLCH**. Los **`--app-*`** son **alias** de los tokens semánticos (misma apariencia global que en la doc Basecoat/shadcn).
- **JS:** `theme-color-switcher.js` — **`basecoat:color-theme`**, **`[data-color-theme-id]`**, **`basecoat:color-theme-applied`**, **`Livewire.hook('morph.updated')`** para **`aria-checked`**.
- **UI:** `partials/theme-color-picker.blade.php`: swatches con colores fijos; **Doom 64** usa **`rounded-none`** en los muestreos para indicar esquinas cuadradas.

## Sidebar (panel)

- Markup **`aside.sidebar`** + **`nav` > `section.scrollbar`** + grupos **`role="group"`** / **`h3`** / **`ul`**, **`footer`** con **`dropdown-menu`** oficial; **`main`** es hermano directo del `aside` (márgenes según CSS de Basecoat).
- Toggle móvil: **`CustomEvent('basecoat:sidebar', { detail: { id: 'main-navigation' } })`**; breakpoint por defecto del JS **768px** (sin `data-breakpoint` custom).
- **Extensión no upstream:** modo colapsado (solo iconos) con **`data-collapsed`** en el `aside`, evento **`basecoat:sidebar-collapse`**, `resources/css/sidebar-collapsed.css` + `resources/js/sidebar-collapsed.js`, y tooltips vía **`data-tooltip`** ([Tooltip](https://basecoatui.com/components/tooltip)); persistencia `localStorage` **`sidebar-collapsed`**. No sustituye al toggle móvil nativo.

## Iconos (Lucide)

- Inclusión vía **Composer** con **`mallardduck/blade-lucide-icons`** (Blade Icons): componentes `<x-lucide-nombre-en-kebab />` en las vistas, alineado con [la guía de iconos de Basecoat](https://basecoatui.com/installation/) en cuanto a trazo y `currentColor`.
- Atributos por defecto del set (`stroke`, `stroke-width`, tamaño, etc.) en `config/blade-lucide-icons.php`.
- Al añadir iconos nuevos en Blade, usar el nombre del icono en Lucide en kebab-case como etiqueta; no hace falta mapa en JavaScript ni re-ejecutar nada tras Livewire (el SVG viene del servidor).

## Componentes y patrones usados en Blade

Patrones **Basecoat** frecuentes:

- `btn`, `btn-primary`, `btn-secondary`, `btn-destructive`, `btn-outline`, `btn-ghost`, `btn-sm`, …
- `card`, `card-pad` (utilidad de padding del shell)
- `alert`, `alert-destructive`
- `input`, `select`, `textarea`, `label`, `field` / ayudas `.field-help` / `.muted`
- `badge-*` oficiales (`badge-secondary`, `badge-destructive`, `badge-outline`, …)
- Paginación: `resources/views/vendor/pagination/basecoat.blade.php` y `simple-basecoat.blade.php` (registrados en `AppServiceProvider`)

**Patrones propios del proyecto** (documentados, no son macros Basecoat):

- `im-card` — manual de integración (`integration-manual.blade.php`)
- `badge-soft`, `table-clean`, `settings-hub-card`, KPI `article.kpi-card`, estilos de breadcrumb `app-breadcrumb-*`, utilidades de shell (`app-main`, `content-shell`, `app-header`) en `<style>` del layout

## Teclado y accesibilidad (capa propia)

Archivos: `resources/js/keyboard-navigation.js` (delegación en `document`, compatible con morphs de Livewire), `resources/css/keyboard-accessibility.css` (skip link). No modifica el bundle de Basecoat. El skip link y `#main-content` están en `layouts/app.blade.php`, `layouts/docs-public.blade.php` y `layouts/auth.blade.php`.

| Acción | Comportamiento |
|--------|----------------|
| Primer **Tab** | Skip link “Saltar al contenido principal” → activación lleva el foco a `#main-content` (`tabindex="-1"`). |
| **Esc** | Si existe `dialog[open]`, botón de menú con `aria-expanded="true"` en `.dropdown-menu`, select abierto (`.select > button[aria-expanded="true"]`) o capa `[data-popover][aria-hidden="false"]`, el script **no hace nada** (prioridad a navegador/Basecoat). Si la sidebar móvil está abierta (&lt;768px y `aria-hidden="false"` en `.sidebar`), dispara `basecoat:sidebar` con `action: 'close'` y devuelve foco a `#sidebar-mobile-toggle`. |
| **Ctrl+B** / **Cmd+B** | En desktop, si existe `.sidebar`, y fuera de campos editables: `basecoat:sidebar-collapse`. |
| **Alt+Shift+M** | Fuera de campos editables: `basecoat:theme`. |

**Sin implementar** (no hay uso actual o prioridad baja): atajo **Ctrl/Cmd+K** para Command palette, cierre de toasts con Esc, rejilla de tabla por flechas, polyfill de restauración de foco en `dialog` (se asume comportamiento nativo de `showModal()`).

**Nativo / JS Basecoat** (no duplicar): `<dialog>` + Esc, `dropdown-menu.js`, `select.js`, tabs, acordeón `<details>`, orden de tabulación en enlaces y botones.

## Integración Livewire

- `wire:click`, `wire:model.live`, etc. se mantienen en botones, formularios y tablas; no sustituyen al JS de Basecoat salvo en componentes complejos (p. ej. select avanzado con listbox): en esos casos conviene probar tras cada cambio de DOM.
- Los iconos Lucide del panel son Blade estáticos; Livewire no requiere JS adicional para pintarlos.
- Los listeners de teclado del panel están en `document` y no dependen de nodos concretos del slot Livewire.

## Comprobaciones automáticas (CI / local)

Última verificación documentada:

- `npm run build` — OK (CSS ~186 kB gzip ~22 kB, JS entrada ~56 kB gzip ~20 kB).
- `php artisan test` — OK (suite actual del repositorio).

## Comprobaciones manuales recomendadas

1. **Panel** (`/monitor` y subrutas): tema claro/oscuro, sidebar móvil, tablas con scroll horizontal, modales Livewire, paginación.
2. **Auth** (`/login`, etc.): contraste y campos con clases Basecoat.
3. **Manual público** (layout `docs-public`): toggle de tema y lectura.
4. **Consola del navegador**: sin errores al cargar y al navegar con Livewire.
5. **Componentes JS Basecoat** usados en páginas concretas: si se añaden dropdown/select/tabs custom del upstream, validar según la página del componente en [basecoatui.com/components](https://basecoatui.com/components/).

## Historial de fases (resumen)

| Fase | Enfoque |
|------|----------|
| 1    | Auditoría: CDN vs NPM, Tailwind v4, uso de clases, riesgos. |
| 2    | `basecoat-css` + `@import` + Vite; `import 'basecoat-css/all'`; layouts con `@vite`. |
| 3    | `btn-destructive`, badges y alerts oficiales, contenedores `card` como `div`, mensajes flash. |
| 4    | `theme.css`, `html.dark`, tarjetas con `--card`/`--border`, Lucide npm tree-shaken, auth con variables. |
| 5    | Este documento + tabla de cierre en la documentación interna / PR. |

---

*Última actualización del documento: al cierre de la Fase 5 del plan Basecoat en este repositorio.*
