{{--
    The filter bar shared by every table and the booking calendar. Plain CSS
    on Filament's colour variables (this panel has no compiled theme, so new
    Tailwind classes wouldn't exist).

    Layout: [quick chips] | [+ Status] [+ Consultant] [Date · 1 Nov – 7 Nov ✕]   Clear all
--}}
<style>
    /* ---------- The bar ---------- */
    .fi-ta-filters-above-content-ctn { padding: 0.625rem 1rem !important; }
    @media (min-width: 640px) { .fi-ta-filters-above-content-ctn { padding: 0.625rem 1.5rem !important; } }

    .fi-ta-filters,
    .pc-bar {
        display: flex !important;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    /* Filament's header (title + Clear all) moves to the end of the row */
    .fi-ta-filters > div:first-child { order: 2; margin-inline-start: auto; }
    .fi-ta-filters > div:first-child h4 { display: none; }
    .fi-ta-filters:not(:has([data-active], .pc-quick input:checked)) > div:first-child .fi-link { display: none; }

    /* Flatten the form grid so every filter is one item in the row */
    .fi-ta-filters > .fi-fo-component-ctn {
        order: 1;
        flex: 1 1 auto;
        display: flex !important;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }
    .fi-ta-filters > .fi-fo-component-ctn > div,
    .fi-ta-filters > .fi-fo-component-ctn > div > .fi-fo-component-ctn,
    .fi-ta-filters > .fi-fo-component-ctn > div > .fi-fo-component-ctn > div { display: contents; }

    /* ---------- Quick chips: the page's main filter, one tap ---------- */
    .pc-quick { display: flex; align-items: center; min-width: 0; }
    .fi-ta-filters:has(.pc-pill-group) .pc-quick,
    .pc-bar:has(.pc-pill-group) .pc-quick {
        padding-inline-end: 0.75rem;
        margin-inline-end: 0.25rem;
        border-inline-end: 1px solid rgba(var(--gray-200), 1);
    }
    .dark .pc-quick { border-color: rgba(255, 255, 255, 0.1) !important; }

    .pc-chips { display: flex; flex-wrap: wrap; gap: 0.375rem; }

    .pc-chip {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        height: 2rem;
        padding: 0 0.75rem;
        border-radius: 9999px;
        border: 1px solid transparent;
        background: rgba(var(--gray-100), 1);
        color: rgba(var(--gray-700), 1);
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1;
        white-space: nowrap;
        cursor: pointer;
        user-select: none;
        transition: background-color 100ms, color 100ms;
    }
    .pc-chip:hover { background: rgba(var(--gray-200), 1); color: rgba(var(--gray-950), 1); }

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

    .pc-chip:has(input:checked) { background: rgba(var(--primary-600), 1); color: #fff; }
    .pc-chip:has(input:checked):hover { background: rgba(var(--primary-500), 1); }
    .pc-chip:has(input:checked) .pc-chip-check { display: block; }

    .pc-chip-more {
        background: transparent;
        border: 1px dashed rgba(var(--gray-300), 1);
        color: rgba(var(--gray-500), 1);
    }
    .pc-chip-more:hover { background: rgba(var(--gray-50), 1); }

    .dark .pc-chip { background: rgba(255, 255, 255, 0.06); color: rgba(var(--gray-200), 1); }
    .dark .pc-chip:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }
    .dark .pc-chip:has(input:checked) { background: rgba(var(--primary-500), 1); color: #fff; }
    .dark .pc-chip-more { background: transparent; border-color: rgba(255, 255, 255, 0.15); color: rgba(var(--gray-400), 1); }

    /* ---------- Pills: every other filter, editor opens in place ---------- */
    .pc-pill-group { display: inline-flex; align-items: stretch; }

    .pc-pill,
    .pc-pill-clear {
        display: inline-flex;
        align-items: center;
        height: 2rem;
        border: 1px dashed rgba(var(--gray-300), 1);
        background: transparent;
        color: rgba(var(--gray-600), 1);
        font-size: 0.8125rem;
        font-weight: 500;
        white-space: nowrap;
        cursor: pointer;
        transition: background-color 100ms, border-color 100ms, color 100ms;
    }
    .pc-pill { gap: 0.375rem; padding: 0 0.625rem 0 0.75rem; border-radius: 9999px; }
    .pc-pill:hover { border-style: solid; border-color: rgba(var(--gray-400), 1); color: rgba(var(--gray-950), 1); }

    .pc-pill svg,
    .pc-pill-clear svg {
        width: 0.875rem; height: 0.875rem;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        flex-shrink: 0;
    }
    .pc-pill-caret { opacity: 0.5; }
    .pc-pill-value {
        max-width: 14rem;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 600;
    }

    /* Set: solid, tinted, value shown, with its own ✕ joined on */
    .pc-pill-group[data-active] .pc-pill,
    .pc-pill-group[data-active] .pc-pill-clear {
        border-style: solid;
        border-color: rgba(var(--primary-200), 1);
        background: rgba(var(--primary-50), 1);
        color: rgba(var(--primary-700), 1);
    }
    .pc-pill-group[data-active] .pc-pill { border-start-end-radius: 0; border-end-end-radius: 0; border-inline-end: 0; }
    .pc-pill-group[data-active] .pc-pill-label { opacity: 0.75; }
    .pc-pill-group[data-active] .pc-pill-label::after { content: ' ·'; }
    .pc-pill-clear { padding: 0 0.5rem 0 0.375rem; border-radius: 0 9999px 9999px 0; }
    .pc-pill-group[data-active] .pc-pill:hover,
    .pc-pill-group[data-active] .pc-pill-clear:hover { background: rgba(var(--primary-100), 1); }

    .dark .pc-pill { border-color: rgba(255, 255, 255, 0.18); color: rgba(var(--gray-300), 1); }
    .dark .pc-pill:hover { border-color: rgba(255, 255, 255, 0.35); color: #fff; }
    .dark .pc-pill-group[data-active] .pc-pill,
    .dark .pc-pill-group[data-active] .pc-pill-clear {
        border-color: rgba(var(--primary-400), 0.4);
        background: rgba(var(--primary-500), 0.15);
        color: rgba(var(--primary-300), 1);
    }
    .dark .pc-pill-group[data-active] .pc-pill:hover,
    .dark .pc-pill-group[data-active] .pc-pill-clear:hover { background: rgba(var(--primary-500), 0.25); }

    .pc-chip:has(input:focus-visible),
    .pc-chip-more:focus-visible,
    .pc-pill:focus-visible,
    .pc-pill-clear:focus-visible {
        outline: 2px solid rgba(var(--primary-500), 1);
        outline-offset: 2px;
    }

    /* ---------- Popover ---------- */
    .fi-dropdown-panel:has(.pc-pop) { overflow: visible !important; }
    .pc-pop { padding: 0.75rem; display: grid; gap: 0.625rem; }
    .pc-pop-title {
        font-size: 0.75rem;
        font-weight: 600;
        color: rgba(var(--gray-500), 1);
    }
    .dark .pc-pop-title { color: rgba(var(--gray-400), 1); }
    .pc-pop .pc-chip { height: 1.875rem; }
    .pc-range .fi-fo-component-ctn {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 0.5rem !important;
    }

    /* ---------- Small screens: quick chips scroll sideways ---------- */
    @media (max-width: 640px) {
        .pc-quick { flex-basis: 100%; border-inline-end: 0 !important; padding-inline-end: 0 !important; margin-inline-end: 0 !important; }
        .pc-quick .pc-chips { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; }
    }

    /* ---------- Calendar toolbar ---------- */
    .pc-filter-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
    .pc-bar {
        padding: 0.625rem 1rem;
        border-radius: 0.75rem;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        outline: 1px solid rgba(var(--gray-950), 0.05);
    }
    .dark .pc-bar { background: rgba(var(--gray-900), 1); outline-color: rgba(255, 255, 255, 0.1); }
    .pc-bar .pc-clear { margin-inline-start: auto; }

    @media (prefers-reduced-motion: reduce) {
        .pc-chip, .pc-pill, .pc-pill-clear { transition: none; }
    }
</style>
