{{-- A regular filter select (long or relationship lists) opened from a pill. --}}
@php
    $filterName = explode('.', $getStatePath())[1] ?? null;
    $clear = $filterName ? "removeTableFilter('{$filterName}')" : null;
@endphp

<x-filter-pill
    :label="$getLabel()"
    :summary="\App\Filament\Support\FilterSummary::forSelect($field)"
    :clear="$clear"
    width="xs"
>
    @include('filament-forms::components.select')
</x-filter-pill>
