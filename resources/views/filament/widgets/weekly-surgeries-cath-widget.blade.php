<x-filament-widgets::widget>
    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($this->getSections() as $section)
            <x-filament::section>
                <x-slot name="heading">
                    {{ $section['title'] }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $section['bookings']->count() }})</span>
                </x-slot>

                <x-slot name="headerEnd">
                    <x-filament::link :href="$section['url']" size="sm">View all →</x-filament::link>
                </x-slot>

                @if ($section['bookings']->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nothing booked this week.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-white/10 text-xs text-gray-500 uppercase tracking-wide">
                                    <th class="py-2 pr-3 text-left font-medium">Date</th>
                                    <th class="py-2 pr-3 text-left font-medium">Slot</th>
                                    <th class="py-2 pr-3 text-left font-medium">Patient</th>
                                    <th class="py-2 pr-3 text-left font-medium">Procedure</th>
                                    <th class="py-2 pr-3 text-left font-medium">Consultant</th>
                                    <th class="py-2 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach ($section['bookings'] as $booking)
                                    <tr @class(['bg-primary-50 dark:bg-primary-900/20' => $booking->booking_date->isToday()])>
                                        <td class="py-2 pr-3 whitespace-nowrap">
                                            <a href="{{ $this->getBookingUrl($booking) }}" class="hover:underline">{{ $booking->booking_date->format('D d M') }}</a>
                                        </td>
                                        <td class="py-2 pr-3 text-gray-500 dark:text-gray-400">{{ $booking->slot_number ? '#'.$booking->slot_number : '—' }}</td>
                                        <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white">
                                            @if ($url = $this->getPatientUrl($booking))
                                                <a href="{{ $url }}" class="hover:underline">{{ $booking->patient->name }}</a>
                                            @else
                                                Unassigned
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $booking->procedure ?: ($booking->intervention_type?->getLabel() ?? '—') }}</td>
                                        <td class="py-2 pr-3 text-gray-600 dark:text-gray-300">{{ $booking->staff?->name ?? '—' }}</td>
                                        <td class="py-2">
                                            <x-filament::badge :color="$booking->procedure_status->getColor()" size="xs">
                                                {{ $booking->procedure_status->getLabel() }}
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        @endforeach
    </div>
</x-filament-widgets::widget>
