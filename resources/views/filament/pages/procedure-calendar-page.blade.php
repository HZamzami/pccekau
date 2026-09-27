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

        <x-filament::section heading="Cath Lab & Imaging Bookings">
            @php $grid = $this->getGrid() @endphp
            @php $today = now()->format('l') @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10 text-xs text-gray-500 uppercase tracking-wide">
                            <th class="py-2 pr-4 text-left font-medium"></th>
                            @foreach (\App\Filament\Pages\ProcedureCalendarPage::$columns as $column)
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
                                                <x-filament::badge :color="$booking->procedure_status->getColor()" size="xs">
                                                    {{ $booking->procedure_status->getLabel() }}
                                                </x-filament::badge>
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
