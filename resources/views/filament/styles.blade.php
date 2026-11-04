{{--
    Shared filter styling for the Filters panel on every table and on the
    procedure calendar. Plain CSS on Filament's colour variables (this panel
    has no compiled theme, so new Tailwind classes wouldn't exist).
--}}
<style>
    /* ---------- Panel: one row per filter, hairline dividers ---------- */
    .fi-ta-filters { row-gap: 0.5rem; }
    .fi-ta-filters > div:first-child h4 { font-size: 0.875rem; }

    .fi-ta-filters > .fi-fo-component-ctn,
    .pc-filter-panel { gap: 0 !important; }

    .fi-ta-filters > .fi-fo-component-ctn > div + div,
    .pc-filter-panel > * + * {
        border-top: 1px solid rgba(var(--gray-100), 1);
    }
    .dark .fi-ta-filters > .fi-fo-component-ctn > div + div,
    .dark .pc-filter-panel > * + * { border-color: rgba(255, 255, 255, 0.06); }

    .fi-ta-filters > .fi-fo-component-ctn > div,
    .pc-filter-panel > * { padding: 0.625rem 0; }

    /* Filters inside a row don't need their own spacing */
    .fi-ta-filters .fi-fo-component-ctn .fi-fo-component-ctn { gap: 0.5rem; }

    /* ---------- Every row: label on the left, control on the right ---------- */
    .pc-filter-row,
    .fi-ta-filters .fi-fo-field-wrp:has(.fi-fo-field-wrp-label) > .grid {
        display: grid;
        grid-template-columns: 6.5rem minmax(0, 1fr);
        align-items: center;
        gap: 0.375rem 1rem;
    }
    .pc-filter-row { align-items: start; }
    .pc-filter-row-center { align-items: center; }
    .pc-filter-row-center .pc-filter-label { padding-top: 0; }

    .pc-filter-label,
    .fi-ta-filters .fi-fo-field-wrp-label span {
        font-size: 0.75rem;
        font-weight: 500;
        color: rgba(var(--gray-500), 1);
    }
    .pc-filter-label { padding-top: 0.3rem; }
    .dark .pc-filter-label,
    .dark .fi-ta-filters .fi-fo-field-wrp-label span { color: rgba(var(--gray-400), 1); }

    /* Selects sit at a sensible width rather than stretching */
    .fi-ta-filters .fi-fo-field-wrp:has(.fi-fo-field-wrp-label) .fi-input-wrp { max-width: 18rem; }

    .pc-range .fi-fo-component-ctn {
        grid-template-columns: repeat(2, minmax(0, 9.5rem)) !important;
        gap: 0.5rem !important;
    }

    @media (max-width: 640px) {
        .pc-filter-row,
        .fi-ta-filters .fi-fo-field-wrp:has(.fi-fo-field-wrp-label) > .grid { grid-template-columns: minmax(0, 1fr); }
        .pc-filter-label { padding-top: 0; }
    }

    /* ---------- Chips ---------- */
    .pc-chips { display: flex; flex-wrap: wrap; gap: 0.375rem; }

    .pc-chip {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        height: 1.75rem;
        padding: 0 0.625rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(var(--gray-200), 1);
        background: #fff;
        color: rgba(var(--gray-700), 1);
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1;
        white-space: nowrap;
        cursor: pointer;
        user-select: none;
        transition: background-color 100ms, border-color 100ms, color 100ms;
    }
    .pc-chip:hover { border-color: rgba(var(--gray-300), 1); background: rgba(var(--gray-50), 1); }

    .pc-chip input {
        position: absolute;
        width: 1px; height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .pc-chip-check {
        display: none;
        width: 0.875rem; height: 0.875rem;
        margin-inline-start: -0.125rem;
        fill: none;
        stroke: currentColor;
        stroke-width: 2.25;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .pc-chip:has(input:checked) {
        background: rgba(var(--primary-600), 1);
        border-color: rgba(var(--primary-600), 1);
        color: #fff;
    }
    .pc-chip:has(input:checked):hover { background: rgba(var(--primary-500), 1); border-color: rgba(var(--primary-500), 1); }
    .pc-chip:has(input:checked) .pc-chip-check { display: block; }

    .pc-chip:has(input:focus-visible),
    .pc-chip-more:focus-visible {
        outline: 2px solid rgba(var(--primary-500), 1);
        outline-offset: 2px;
    }

    .pc-chip-more {
        border-style: dashed;
        background: transparent;
        color: rgba(var(--gray-500), 1);
    }

    .dark .pc-chip {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.1);
        color: rgba(var(--gray-200), 1);
    }
    .dark .pc-chip:hover { background: rgba(255, 255, 255, 0.08); border-color: rgba(255, 255, 255, 0.18); }
    .dark .pc-chip:has(input:checked) {
        background: rgba(var(--primary-500), 1);
        border-color: rgba(var(--primary-500), 1);
        color: #fff;
    }
    .dark .pc-chip-more { background: transparent; color: rgba(var(--gray-400), 1); }

    /* ---------- Calendar toolbar (same panel, its own trigger) ---------- */
    .pc-filter-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
    .pc-filter-panel { display: grid; padding: 0.25rem 1.25rem 0.5rem; }
    .pc-filter-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.875rem 0 0.375rem;
    }
    .pc-filter-panel-header h4 { font-size: 0.875rem; font-weight: 600; }
    .pc-select { width: 100%; max-width: 18rem; }

    @media (prefers-reduced-motion: reduce) {
        .pc-chip { transition: none; }
    }
</style>
