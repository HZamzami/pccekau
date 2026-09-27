<x-filament-panels::page>
    <div class="space-y-4">

        {{-- Tab bar --}}
        <div class="flex gap-2 flex-wrap">
            @foreach ([
                'oncall'      => 'On-Call',
                'fellows'     => 'Fellows Rotation',
                'consultants' => 'Consultants',
            ] as $key => $label)
                <button
                    wire:click="setTab('{{ $key }}')"
                    @class([
                        'rounded-lg px-4 py-2 text-sm font-medium transition border',
                        'bg-primary-600 text-white border-primary-600' => $activeTab === $key,
                        'bg-white dark:bg-white/5 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/10' => $activeTab !== $key,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- On-Call & Consultant tabs: use Filament's native table --}}
        @if (in_array($activeTab, ['oncall', 'consultants']))

            {{-- Current week summary card for on-call --}}
            @if ($activeTab === 'oncall')
                @php $current = $this->getCurrentOncall() @endphp

                <div class="flex items-center gap-3">
                    <x-filament::button wire:click="previousGridWeek" size="sm" color="gray" outlined>← Prev</x-filament::button>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        Week of {{ \Illuminate\Support\Carbon::parse($gridWeekStart)->format('d M Y') }}
                    </span>
                    <x-filament::button wire:click="nextGridWeek" size="sm" color="gray" outlined>Next →</x-filament::button>
                    <x-filament::button wire:click="currentGridWeek" size="sm" color="gray">This Week</x-filament::button>
                </div>

                @if ($current)
                    <x-filament::section
                        heading="Specialists/Fellows Weekly Coverage — {{ $current->week_start->format('d M Y') }}"
                    >
                        @php
                            $days = [
                                'Sunday'    => $current->oncallSundayStaff?->name,
                                'Monday'    => $current->oncallMondayStaff?->name,
                                'Tuesday'   => $current->oncallTuesdayStaff?->name,
                                'Wednesday' => $current->oncallWednesdayStaff?->name,
                                'Thursday'  => $current->oncallThursdayStaff?->name,
                                'Friday'    => $current->oncallFridayStaff?->name,
                                'Saturday'  => $current->oncallSaturdayStaff?->name,
                            ];
                            $roles = [
                                'Clinic'       => $current->clinicStaff?->name,
                                'Inpatient'    => $current->inpatientStaff?->name,
                                'Consultation' => $current->consultationStaff?->name,
                                'Cath'         => $current->cathStaff?->name,
                            ];
                            $today = $current->week_start->isSameWeek(now()) ? now()->format('l') : null;
                        @endphp

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-white/10 text-xs text-gray-500 uppercase tracking-wide">
                                        <th class="py-2 pr-4 text-left font-medium"></th>
                                        @foreach (array_keys($roles) as $role)
                                            <th class="py-2 pr-4 text-left font-medium">{{ $role }}</th>
                                        @endforeach
                                        <th class="py-2 pr-4 text-left font-medium">On-Call</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                    @foreach ($days as $day => $oncall)
                                        <tr @class([
                                            'bg-primary-50 dark:bg-primary-900/20' => $today === $day,
                                        ])>
                                            <td class="py-2.5 pr-4 font-semibold text-gray-900 dark:text-white">{{ $day }}</td>
                                            @foreach ($roles as $doctor)
                                                <td class="py-2.5 pr-4 text-gray-700 dark:text-gray-200">{{ $doctor ?: '—' }}</td>
                                            @endforeach
                                            <td class="py-2.5 pr-4 font-semibold text-gray-900 dark:text-white">{{ $oncall ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($current->notes)
                            <p class="mt-3 text-xs italic text-gray-500">{{ $current->notes }}</p>
                        @endif
                    </x-filament::section>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No coverage schedule entered for this week.</p>
                @endif
            @endif

            {{ $this->table }}
        @endif

        {{-- Fellows Rotation: pivot grid, not a standard list --}}
        @if ($activeTab === 'fellows')
            @php
                $currentBlock = $this->getCurrentBlock();
                $rotations    = $this->getFellowsRotations()->groupBy('block_number');
                $fellows      = $this->getFellowNames();
            @endphp

            @if ($currentBlock)
                <x-filament::section
                    heading="Current Block — Block {{ $currentBlock->block_number }} ({{ $currentBlock->start_date->format('d M') }} – {{ $currentBlock->end_date->format('d M Y') }})"
                >
                    <div class="flex gap-4 flex-wrap">
                        @foreach ($rotations->get($currentBlock->block_number, collect()) as $rotation)
                            <div class="flex-1 min-w-[120px] rounded-xl border border-primary-300 dark:border-primary-700 bg-primary-50 dark:bg-primary-900/20 p-4 text-center">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $rotation->fellow?->name ?? '—' }}</p>
                                <p class="text-xs text-primary-600 dark:text-primary-400 mt-1 font-medium">{{ $rotation->rotation_label }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif

            <x-filament::section heading="Full Rotation Schedule 2026">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-white/10 text-xs text-gray-500 uppercase tracking-wide">
                                <th class="pb-3 pr-4 text-left font-medium">Block</th>
                                <th class="pb-3 pr-4 text-left font-medium">Dates</th>
                                @foreach ($fellows as $fellowId => $fellowName)
                                    <th class="pb-3 pr-4 text-left font-medium">{{ $fellowName }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($rotations as $blockNum => $blockRows)
                                @php $first = $blockRows->first() @endphp
                                <tr @class([
                                    'transition',
                                    'bg-primary-50 dark:bg-primary-900/20' => optional($currentBlock)->block_number === $blockNum,
                                    'hover:bg-gray-50 dark:hover:bg-white/5' => optional($currentBlock)->block_number !== $blockNum,
                                ])>
                                    <td class="py-3 pr-4 font-semibold text-gray-900 dark:text-white">{{ $blockNum }}</td>
                                    <td class="py-3 pr-4 text-xs text-gray-500">
                                        {{ $first->start_date->format('d M') }} – {{ $first->end_date->format('d M') }}
                                    </td>
                                    @foreach ($fellows as $fellowId => $fellowName)
                                        @php $row = $blockRows->firstWhere('fellow_id', $fellowId) @endphp
                                        <td class="py-3 pr-4">
                                            @if ($row)
                                                <span @class([
                                                    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                                    'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-600' => in_array($row->rotation, ['vacation', 'elective']),
                                                    'bg-danger-100 text-danger-700 ring-danger-200 dark:bg-danger-900/30 dark:text-danger-400' => $row->rotation === 'icu',
                                                    'bg-warning-100 text-warning-700 ring-warning-200 dark:bg-warning-900/30 dark:text-warning-400' => $row->rotation === 'research',
                                                    'bg-info-100 text-info-700 ring-info-200 dark:bg-info-900/30 dark:text-info-400' => !in_array($row->rotation, ['vacation', 'elective', 'icu', 'research']),
                                                ])>
                                                    {{ $row->rotation_label }}
                                                </span>
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
        @endif

    </div>
</x-filament-panels::page>
