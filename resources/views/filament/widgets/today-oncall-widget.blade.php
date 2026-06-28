<x-filament-widgets::widget>
    <x-filament::section heading="Today's On-Call — {{ $this->getTodayLabel() }}, {{ now()->format('d M Y') }}">
        @php $schedule = $this->getSchedule() @endphp

        @if ($schedule)
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ([
                    'On-Call'      => $schedule->today_oncall,
                    'Service'      => $schedule->service_doctor,
                    'Cath'         => $schedule->cath_doctor,
                    'EP'           => $schedule->ep_doctor,
                    'Clinic'       => $schedule->clinic_doctor,
                    'Inpatient'    => $schedule->inpatient_doctor,
                ] as $role => $doctor)
                    <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4 text-center space-y-1">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            {{ $role }}
                        </p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $doctor ?: '—' }}
                        </p>
                    </div>
                @endforeach
            </div>

            @if ($schedule->notes)
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 italic">{{ $schedule->notes }}</p>
            @endif
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">No on-call schedule entered for this week.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
