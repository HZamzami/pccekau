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
                @if ($current)
                    <x-filament::section
                        heading="This Week — {{ $current->week_start->format('d M Y') }}{{ $current->hijri_date_range ? ' · ' . $current->hijri_date_range : '' }}"
                    >
                        <div class="grid grid-cols-3 gap-3 sm:grid-cols-6 mb-4">
                            @foreach ([
                                'On-Call Today' => $current->today_oncall,
                                'Service'       => $current->service_doctor,
                                'Cath'          => $current->cath_doctor,
                                'EP'            => $current->ep_doctor,
                                'Clinic'        => $current->clinic_doctor,
                                'Inpatient'     => $current->inpatient_doctor,
                            ] as $role => $doctor)
                                <div class="rounded-xl border border-gray-200 dark:border-white/10 p-3 text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $role }}</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $doctor ?: '—' }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-7 gap-2 text-center text-sm">
                            @foreach ([
                                'Sun' => $current->oncall_sunday,
                                'Mon' => $current->oncall_monday,
                                'Tue' => $current->oncall_tuesday,
                                'Wed' => $current->oncall_wednesday,
                                'Thu' => $current->oncall_thursday,
                                'Fri' => $current->oncall_friday,
                                'Sat' => $current->oncall_saturday,
                            ] as $day => $doctor)
                                <div @class([
                                    'rounded-lg border p-2',
                                    'border-primary-400 bg-primary-50 dark:bg-primary-900/20' => strtolower(now()->format('D')) === strtolower($day),
                                    'border-gray-200 dark:border-white/10' => strtolower(now()->format('D')) !== strtolower($day),
                                ])>
                                    <p class="text-xs text-gray-500 font-medium">{{ $day }}</p>
                                    <p class="font-semibold text-gray-900 dark:text-white text-xs mt-1">{{ $doctor ?: '—' }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if ($current->notes)
                            <p class="mt-3 text-xs italic text-gray-500">{{ $current->notes }}</p>
                        @endif
                    </x-filament::section>
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
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $rotation->fellow_name }}</p>
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
                                @foreach ($fellows as $fellow)
                                    <th class="pb-3 pr-4 text-left font-medium">{{ $fellow }}</th>
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
                                    @foreach ($fellows as $fellow)
                                        @php $row = $blockRows->firstWhere('fellow_name', $fellow) @endphp
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
