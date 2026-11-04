{{--
    The one chip filter used everywhere: table filters (via the Chips form
    field) and the procedure calendar. Each chip is a real checkbox or radio,
    so keyboard and screen readers work, and :has(:checked) styles it the
    moment it's clicked, before the server answers.
--}}
@props([
    'label',
    'options' => [],
    'multiple' => true,
    'statePath',
    'binding' => 'wire:model.live',
])

@php
    $name = 'chips-'.str_replace('.', '-', $statePath);
@endphp

<div {{ $attributes->class(['pc-filter-row']) }} role="group" aria-label="{{ $label }}">
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
            <label class="pc-chip">
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
    </div>
</div>
