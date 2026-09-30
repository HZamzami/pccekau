<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex items-center gap-3">
            <x-filament::button wire:click="previousWeek" size="sm" color="gray" outlined>← Prev</x-filament::button>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                Week of {{ \Illuminate\Support\Carbon::parse($weekStart)->format('d M Y') }}
            </span>
            <x-filament::button wire:click="nextWeek" size="sm" color="gray" outlined>Next →</x-filament::button>
            <x-filament::button wire:click="currentWeek" size="sm" color="gray">This Week</x-filament::button>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <label class="space-y-1">
                <span class="block text-xs font-medium text-gray-500 dark:text-gray-400">Category</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="category">
                        <option value="">All categories</option>
                        @foreach ($this->getCategoryOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            <label class="space-y-1">
                <span class="block text-xs font-medium text-gray-500 dark:text-gray-400">Interventionist</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="staffId">
                        <option value="">Everyone</option>
                        @foreach ($this->getStaffOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            <label class="space-y-1">
                <span class="block text-xs font-medium text-gray-500 dark:text-gray-400">Status</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="status">
                        <option value="">Any status</option>
                        @foreach ($this->getStatusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            @if ($category || $staffId || $status)
                <x-filament::button wire:click="clearFilters" size="sm" color="gray" outlined>Clear filters</x-filament::button>
            @endif
        </div>

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

                                @foreach ($day['cells'] as $booking)
                                    <td class="py-2.5 pr-4 align-top">
                                        @if ($booking)
                                            <a href="{{ $this->getBookingUrl($booking) }}" class="block hover:underline">
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
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
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
