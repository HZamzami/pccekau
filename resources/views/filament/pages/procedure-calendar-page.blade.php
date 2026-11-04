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

        <div class="space-y-3">
            <div class="space-y-1">
                <span class="block text-xs font-medium text-gray-500 dark:text-gray-400">Category <span class="font-normal">(pick any number)</span></span>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->getCategoryOptions() as $value => $label)
                        <label @class([
                            'cursor-pointer select-none rounded-full border px-3 py-1 text-xs font-medium transition',
                            'border-primary-600 bg-primary-600 text-white' => in_array($value, $categories, true),
                            'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-white/20 dark:text-gray-200 dark:hover:bg-white/5' => ! in_array($value, $categories, true),
                        ])>
                            <input type="checkbox" class="sr-only" value="{{ $value }}" wire:model.live="categories">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="space-y-1">
                <span class="block text-xs font-medium text-gray-500 dark:text-gray-400">Status <span class="font-normal">(pick any number)</span></span>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->getStatusOptions() as $value => $label)
                        <label @class([
                            'cursor-pointer select-none rounded-full border px-3 py-1 text-xs font-medium transition',
                            'border-primary-600 bg-primary-600 text-white' => in_array($value, $statuses, true),
                            'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-white/20 dark:text-gray-200 dark:hover:bg-white/5' => ! in_array($value, $statuses, true),
                        ])>
                            <input type="checkbox" class="sr-only" value="{{ $value }}" wire:model.live="statuses">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <label class="space-y-1">
                    <span class="block text-xs font-medium text-gray-500 dark:text-gray-400">Consultant</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="staffId">
                            <option value="">Everyone</option>
                            @foreach ($this->getStaffOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>

                @if ($categories || $staffId || $statuses)
                    <x-filament::button wire:click="clearFilters" size="sm" color="gray" outlined>Clear filters</x-filament::button>
                @endif
            </div>
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
