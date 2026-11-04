<x-filament-widgets::widget>
    <x-filament::section heading="Today's Coverage — {{ $this->getTodayLabel() }}, {{ now()->format('d M Y') }}">
        @php $schedule = $this->getSchedule() @endphp

        @if ($schedule)
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ([
                    'On-Call'      => $schedule->staffForToday(\App\Enums\CoverageRole::Oncall)?->name,
                    'Clinic'       => $schedule->staffForToday(\App\Enums\CoverageRole::Clinic)?->name,
                    'Inpatient'    => $schedule->staffForToday(\App\Enums\CoverageRole::Inpatient)?->name,
                    'Consultation' => $schedule->staffForToday(\App\Enums\CoverageRole::Consultation)?->name,
                    'Cath'         => $schedule->staffForToday(\App\Enums\CoverageRole::Cath)?->name,
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

        @php $consultants = $this->getConsultantSchedule() @endphp
        @if ($consultants)
            <div class="mt-6">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Today's Consultants</p>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ([
                        'Service' => $consultants->staffForToday(\App\Enums\CoverageRole::ConsultantService)?->name,
                        'Cath'    => $consultants->staffForToday(\App\Enums\CoverageRole::ConsultantCath)?->name,
                        'EP'      => $consultants->staffForToday(\App\Enums\CoverageRole::ConsultantEp)?->name,
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
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
