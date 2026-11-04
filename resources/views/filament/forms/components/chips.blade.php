@php
    $options = $getOptions();
    $state = array_map('strval', array_filter(\Illuminate\Support\Arr::wrap($getState()), 'filled'));
    $filterName = explode('.', $getStatePath())[1] ?? null;
    $clear = $filterName ? "removeTableFilter('{$filterName}')" : null;
@endphp

@if ($isQuick())
    <div class="pc-quick">
        <x-chip-group
            bare
            :label="$getLabel()"
            :options="$options"
            :selected="$state"
            :multiple="$isMultiple()"
            :state-path="$getStatePath()"
            :binding="$applyStateBindingModifiers('wire:model')"
        />
    </div>
@else
    <x-filter-pill
        :label="$getLabel()"
        :summary="\App\Filament\Support\FilterSummary::labels(array_map(fn ($value) => (string) ($options[$value] ?? $value), $state))"
        :clear="$clear"
        :width="count($options) > 6 ? 'md' : 'xs'"
    >
        <x-chip-group
            bare
            :label="$getLabel()"
            :options="$options"
            :selected="$state"
            :multiple="$isMultiple()"
            :state-path="$getStatePath()"
            :binding="$applyStateBindingModifiers('wire:model')"
            :visible="100"
        />
    </x-filter-pill>
@endif
