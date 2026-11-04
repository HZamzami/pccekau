<x-filament-panels::page>
    <div class="space-y-4">
        @php $tags = $this->getFilterTags() @endphp

        <div class="pc-filter-toolbar">
            <x-filament::button wire:click="previousWeek" size="sm" color="gray" outlined>← Prev</x-filament::button>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                Week of {{ \Illuminate\Support\Carbon::parse($weekStart)->format('d M Y') }}
            </span>
            <x-filament::button wire:click="nextWeek" size="sm" color="gray" outlined>Next →</x-filament::button>
            <x-filament::button wire:click="currentWeek" size="sm" color="gray">This Week</x-filament::button>

            <x-filament::dropdown placement="bottom-end" width="3xl" class="ms-auto">
                <x-slot name="trigger">
                    <x-filament::button color="gray" outlined size="sm" icon="heroicon-m-adjustments-horizontal" :badge="count($tags) ?: null">
                        Filters
                    </x-filament::button>
                </x-slot>

                <div class="pc-filter-panel">
                    <div class="pc-filter-panel-header">
                        <h4 class="text-gray-950 dark:text-white">Filters</h4>

                        <x-filament::link tag="button" wire:click="clearFilters" color="danger" size="sm">Clear all</x-filament::link>
                    </div>

                    <x-chip-group label="Category" :options="$this->getCategoryOptions()" :selected="$categories" state-path="categories" />

                    <x-chip-group label="Status" :options="$this->getStatusOptions()" :selected="$statuses" state-path="statuses" />

                    <div class="pc-filter-row pc-filter-row-center">
                        <span class="pc-filter-label">Consultant</span>

                        <x-filament::input.wrapper class="pc-select">
                            <x-filament::input.select wire:model.live="staffId">
                                <option value="">Everyone</option>
                                @foreach ($this->getStaffOptions() as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </x-filament::dropdown>
        </div>

        @if ($tags)
            {{-- Same tags as the tables show while their Filters panel is closed --}}
            <div class="fi-ta-filter-indicators flex items-start justify-between gap-x-3 rounded-xl bg-gray-50 px-3 py-1.5 dark:bg-white/5">
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($tags as $tag)
                        <x-filament::badge color="gray">
                            {{ $tag['label'] }}

                            <x-slot name="deleteButton" label="Remove filter" wire:click="removeFilterValue('{{ $tag['filter'] }}', '{{ $tag['value'] }}')"></x-slot>
                        </x-filament::badge>
                    @endforeach
                </div>

                <x-filament::icon-button color="gray" icon="heroicon-m-x-mark" size="sm" tooltip="Remove all filters" wire:click="clearFilters" />
            </div>
        @endif

        <x-filament::section heading="Procedure Bookings">
            @php $grid = $this->getGrid() @endphp
            @php $today = now()->format('l') @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10 text-xs text-gray-500 uppercase tracking-wide">
                            <th class="py-2 pr-4 text-left font-medium"></th>
                            @foreach ($this->getVisibleColumns() as $column)
                                <th class="py-2 pr-4 text-left font-medium">{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($grid as $day)
                            <tr @class([
                                'bg-primary-50 dark:bg-primary-900/20' => $today === $day['label'],
                            ])>
                                <td class="py-2.5 pr-4 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ $day['label'] }}
                                    <span class="text-xs text-gray-400 font-normal">{{ $day['date']->format('d M') }}</span>
                                </td>

                                @foreach ($day['cells'] as $bookings)
                                    <td class="py-2.5 pr-4 align-top">
                                        @forelse ($bookings as $booking)
                                            <a href="{{ $this->getBookingUrl($booking) }}" @class(['block hover:underline', 'mt-3' => ! $loop->first])>
                                                @if ($booking->slot_number)
                                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">#{{ $booking->slot_number }}</span>
                                                @endif
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $booking->patient?->name ?? 'Unassigned' }}</span>
                                                @if ($booking->procedure)
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $booking->procedure }}</span>
                                                @endif
                                                @if ($booking->staff)
                                                    <span class="block text-xs text-gray-400">{{ $booking->staff->name }}</span>
                                                @endif
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @if ($booking->category)
                                                        <x-filament::badge :color="$booking->category->getColor()" size="xs">
                                                            {{ $booking->category->getLabel() }}
                                                        </x-filament::badge>
                                                    @endif
                                                    <x-filament::badge :color="$booking->procedure_status->getColor()" size="xs">
                                                        {{ $booking->procedure_status->getLabel() }}
                                                    </x-filament::badge>
                                                </div>
                                            </a>
                                        @empty
                                            <span class="text-gray-400">—</span>
                                        @endforelse
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
