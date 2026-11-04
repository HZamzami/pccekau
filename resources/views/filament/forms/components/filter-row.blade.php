{{-- The From – Until date range, opened from a pill like every other filter. --}}
@php
    $state = data_get($getLivewire(), 'tableFilters.'.$filterName, []);
    $format = fn ($date) => filled($date) ? \Illuminate\Support\Carbon::parse($date)->format('d M') : null;
    $summary = match (true) {
        filled($state['from'] ?? null) && filled($state['until'] ?? null) => $format($state['from']).' – '.$format($state['until']),
        filled($state['from'] ?? null) => 'From '.$format($state['from']),
        filled($state['until'] ?? null) => 'Until '.$format($state['until']),
        default => null,
    };
    $clear = "removeTableFilter('{$filterName}')";
@endphp

<x-filter-pill :label="$getLabel()" :summary="$summary" :clear="$clear" width="sm">
    <div class="pc-range">
        {{ $getChildComponentContainer() }}
    </div>
</x-filter-pill>
