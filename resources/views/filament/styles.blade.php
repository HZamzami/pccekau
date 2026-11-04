{{--
    Shared filter-bar styling. Plain CSS on Filament's colour variables
    (this panel has no compiled theme, so new Tailwind classes wouldn't exist).
--}}
<style>
    /* The bar: one soft panel above every table and on the calendar */
    .fi-ta-filters-above-content-ctn,
    .pc-filter-bar {
        background: rgba(var(--gray-50), 1);
        border-bottom: 1px solid rgba(var(--gray-200), 1);
    }
    .pc-filter-bar {
        border: 1px solid rgba(var(--gray-200), 1);
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        display: grid;
        gap: 0.75rem;
    }
    .dark .fi-ta-filters-above-content-ctn,
    .dark .pc-filter-bar {
        background: rgba(255, 255, 255, 0.02);
        border-color: rgba(255, 255, 255, 0.08);
    }

    /* Filament's own "Filters" heading is noise here; keep its Reset link */
    .fi-ta-filters > div:first-child { justify-content: flex-end; margin-bottom: -0.5rem; }
    .fi-ta-filters > div:first-child h4 { display: none; }
    .fi-ta-filters { row-gap: 0.75rem; }
    .fi-ta-filters .fi-fo-component-ctn { row-gap: 0.75rem; }

    /* Field labels in the bar match the chip-row labels */
    .fi-ta-filters .fi-fo-field-wrp-label span,
    .pc-filter-label {
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: rgba(var(--gray-500), 1);
    }
    .dark .fi-ta-filters .fi-fo-field-wrp-label span,
    .dark .pc-filter-label { color: rgba(var(--gray-400), 1); }

    /* A row: label column, then the chips */
    .pc-filter-row {
        display: grid;
        grid-template-columns: 7.5rem minmax(0, 1fr);
        align-items: start;
        gap: 0.5rem 1rem;
    }
    .pc-filter-label { padding-top: 0.4rem; }
    @media (max-width: 640px) {
        .pc-filter-row { grid-template-columns: minmax(0, 1fr); }
        .pc-filter-label { padding-top: 0; }
    }

    .pc-chips { display: flex; flex-wrap: wrap; gap: 0.375rem; }

    .pc-chip {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        height: 1.75rem;
        padding: 0 0.75rem;
        border-radius: 9999px;
        border: 1px solid rgba(var(--gray-200), 1);
        background: #fff;
        color: rgba(var(--gray-700), 1);
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1;
        white-space: nowrap;
        cursor: pointer;
        user-select: none;
        transition: background-color 120ms, border-color 120ms, color 120ms, box-shadow 120ms;
    }
    .pc-chip:hover { background: rgba(var(--gray-50), 1); border-color: rgba(var(--gray-300), 1); }

    .pc-chip input {
        position: absolute;
        width: 1px; height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .pc-chip-check {
        width: 0; height: 0.875rem;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        opacity: 0;
        transition: width 120ms, opacity 120ms;
    }

    .pc-chip:has(input:checked) {
        background: rgba(var(--primary-50), 1);
        border-color: rgba(var(--primary-300), 1);
        color: rgba(var(--primary-700), 1);
    }
    .pc-chip:has(input:checked) .pc-chip-check { width: 0.875rem; opacity: 1; }

    .pc-chip:has(input:focus-visible) {
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px rgba(var(--primary-500), 1);
    }

    .dark .pc-chip {
        background: rgba(255, 255, 255, 0.03);
        border-color: rgba(255, 255, 255, 0.1);
        color: rgba(var(--gray-200), 1);
    }
    .dark .pc-chip:hover { background: rgba(255, 255, 255, 0.07); border-color: rgba(255, 255, 255, 0.2); }
    .dark .pc-chip:has(input:checked) {
        background: rgba(var(--primary-500), 0.15);
        border-color: rgba(var(--primary-400), 0.5);
        color: rgba(var(--primary-300), 1);
    }
    .dark .pc-chip:has(input:focus-visible) {
        box-shadow: 0 0 0 2px rgba(var(--gray-900), 1), 0 0 0 4px rgba(var(--primary-400), 1);
    }

    .pc-filter-footer { display: flex; flex-wrap: wrap; align-items: end; gap: 0.75rem 1rem; }
    .pc-filter-footer .pc-clear { margin-inline-start: auto; }
    .pc-select { width: 100%; max-width: 16rem; }

    @media (prefers-reduced-motion: reduce) {
        .pc-chip, .pc-chip-check { transition: none; }
    }
</style>
