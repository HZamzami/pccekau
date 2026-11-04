{{--
    The one chip filter used everywhere: table filter panels (via the Chips
    form field) and the procedure calendar. Each chip is a real checkbox or
    radio, so keyboard and screen readers work, and :has(:checked) styles it
    the moment it's clicked. Long lists show the first few options and a
    "+N more" chip; selected options always stay visible.
--}}
@props([
    'label',
    'options' => [],
    'selected' => [],
    'multiple' => true,
    'statePath',
    'binding' => 'wire:model.live',
    'visible' => 6,
])

@php
    $name = 'chips-'.str_replace('.', '-', $statePath);
    $selected = array_map('strval', array_filter(\Illuminate\Support\Arr::wrap($selected), 'filled'));
    $collapsible = count($options) > $visible + 2;
    $hiddenCount = 0;
@endphp

<div {{ $attributes->class(['pc-filter-row']) }} role="group" aria-label="{{ $label }}" x-data="{ more: false }">
    <span class="pc-filter-label">{{ $label }}</span>

    <div class="pc-chips">
        @unless ($multiple)
            <label class="pc-chip">
                <input type="radio" name="{{ $name }}" value="" {{ $binding }}="{{ $statePath }}">
                <svg class="pc-chip-check" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 8.5l3 3 6-7" /></svg>
                <span>All</span>
            </label>
        @endunless

        @foreach ($options as $value => $optionLabel)
            @php
                $tucked = $collapsible && $loop->index >= $visible && ! in_array((string) $value, $selected, true);
                $hiddenCount += $tucked ? 1 : 0;
            @endphp

            <label class="pc-chip" @if ($tucked) x-show="more" x-cloak @endif>
                <input
                    type="{{ $multiple ? 'checkbox' : 'radio' }}"
                    name="{{ $name }}{{ $multiple ? '[]' : '' }}"
                    value="{{ $value }}"
                    {{ $binding }}="{{ $statePath }}"
                >
                <svg class="pc-chip-check" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 8.5l3 3 6-7" /></svg>
                <span>{{ $optionLabel }}</span>
            </label>
        @endforeach

        @if ($hiddenCount)
            <button type="button" class="pc-chip pc-chip-more" x-on:click="more = ! more" x-bind:aria-expanded="more">
                <span x-show="! more">+{{ $hiddenCount }} more</span>
                <span x-show="more" x-cloak>Show less</span>
            </button>
        @endif
    </div>
</div>
