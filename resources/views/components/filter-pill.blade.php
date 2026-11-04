{{--
    One filter in the always-visible bar: a quiet dashed "+ Label" pill when
    empty, a solid "Label · value" pill when set (with its own ✕). Clicking it
    opens the filter's editor in place, Linear/Stripe style.
--}}
@props([
    'label',
    'summary' => null,
    'clear' => null,
    'width' => 'xs',
])

<div class="pc-pill-group" @if ($summary) data-active @endif>
    <x-filament::dropdown placement="bottom-start" :width="$width" :offset="6">
        <x-slot name="trigger">
            <button type="button" class="pc-pill" aria-haspopup="dialog">
                @unless ($summary)
                    <svg class="pc-pill-plus" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3.5v9M3.5 8h9" /></svg>
                @endunless

                <span class="pc-pill-label">{{ $label }}</span>

                @if ($summary)
                    <span class="pc-pill-value">{{ $summary }}</span>
                @endif

                <svg class="pc-pill-caret" viewBox="0 0 16 16" aria-hidden="true"><path d="M4.5 6.5L8 10l3.5-3.5" /></svg>
            </button>
        </x-slot>

        <div class="pc-pop">
            <p class="pc-pop-title">{{ $label }}</p>

            {{ $slot }}
        </div>
    </x-filament::dropdown>

    @if ($summary && $clear)
        <button type="button" class="pc-pill-clear" wire:click="{{ $clear }}" aria-label="Clear {{ $label }}">
            <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4.5 4.5l7 7M11.5 4.5l-7 7" /></svg>
        </button>
    @endif
</div>
