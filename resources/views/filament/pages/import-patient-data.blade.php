<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Step 1: source + upload — collapses to a summary bar once analyzed --}}
        @if ($previewRows === null && $summary === null)
            <x-filament::section heading="1. Choose a file">
                <form wire:submit.prevent="analyze" class="space-y-6">
                    {{ $this->form }}

                    <div class="flex items-center gap-3">
                        <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                            Analyze file
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>
        @elseif ($sourceLabel)
            <x-filament::section>
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 text-sm">
                        <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 text-gray-400" />
                        <span class="font-medium text-gray-900 dark:text-white">{{ $sourceLabel }}</span>
                    </div>
                    <x-filament::button wire:click="startOver" color="gray" size="sm" icon="heroicon-o-arrow-path">
                        Start over
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif

        {{-- Step 2: review --}}
        @if ($previewRows !== null)
            @php $counts = $this->statusCounts @endphp

            <x-filament::section heading="2. Review before importing">
                <div class="space-y-4">
                    {{-- Stat cards — click to filter the table --}}
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ([
                            ['key' => 'all', 'label' => 'Total', 'count' => $counts['total'], 'text' => 'text-gray-600 dark:text-gray-400'],
                            ['key' => 'new', 'label' => 'New patients', 'count' => $counts['new'], 'text' => 'text-success-600 dark:text-success-400'],
                            ['key' => 'update', 'label' => 'Updates', 'count' => $counts['update'], 'text' => 'text-info-600 dark:text-info-400'],
                            ['key' => 'error', 'label' => 'Errors', 'count' => $counts['error'], 'text' => 'text-danger-600 dark:text-danger-400'],
                        ] as $card)
                            <button
                                type="button"
                                wire:click="setStatusFilter('{{ $card['key'] }}')"
                                @class([
                                    'rounded-xl border p-4 text-left transition',
                                    'border-primary-400 ring-1 ring-primary-400 bg-primary-50 dark:bg-primary-900/20' => $statusFilter === $card['key'],
                                    'border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/5' => $statusFilter !== $card['key'],
                                ])
                            >
                                <p class="text-2xl font-bold {{ $card['text'] }}">{{ $card['count'] }}</p>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-1">{{ $card['label'] }}</p>
                            </button>
                        @endforeach
                    </div>

                    {{-- Search + bulk select --}}
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    type="search"
                                    wire:model.live.debounce.300ms="search"
                                    placeholder="Search by MRN or name…"
                                />
                            </x-filament::input.wrapper>
                        </div>
                        <x-filament::button wire:click="toggleAllVisible(true)" color="gray" size="sm">
                            Select all visible
                        </x-filament::button>
                        <x-filament::button wire:click="toggleAllVisible(false)" color="gray" size="sm">
                            Deselect all visible
                        </x-filament::button>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto border border-gray-200 dark:border-white/10 rounded-xl">
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-white/10 text-xs text-gray-500 uppercase tracking-wide bg-gray-50 dark:bg-white/5">
                                    <th class="py-2.5 px-4 text-left font-medium w-8"></th>
                                    <th class="py-2.5 px-4 text-left font-medium">Status</th>
                                    <th class="py-2.5 px-4 text-left font-medium">Patient</th>
                                    <th class="py-2.5 px-4 text-left font-medium">What will happen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @forelse ($this->filteredRows as $row)
                                    <tr
                                        x-data="{ open: false }"
                                        @class([
                                            'align-top',
                                            'opacity-60' => $row['status'] === 'error',
                                        ])
                                    >
                                        <td class="py-3 px-4">
                                            <input
                                                type="checkbox"
                                                wire:click="toggleRow({{ $row['index'] }})"
                                                @checked($selected[$row['index']] ?? false)
                                                @disabled($row['status'] === 'error')
                                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 disabled:opacity-40"
                                            />
                                        </td>
                                        <td class="py-3 px-4">
                                            <x-filament::badge :color="match ($row['status']) {
                                                'new' => 'success',
                                                'update' => 'info',
                                                'error' => 'danger',
                                                default => 'gray',
                                            }">
                                                {{ ucfirst($row['status']) }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="py-3 px-4">
                                            <button type="button" @click="open = !open" class="text-left hover:underline">
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $row['mrn'] ?? '—' }}</span>
                                                <span class="text-gray-500 dark:text-gray-400">— {{ $row['name'] ?? '—' }}</span>
                                            </button>
                                        </td>
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                            <button type="button" @click="open = !open" class="flex items-start gap-1.5 text-left w-full">
                                                <x-filament::icon
                                                    icon="heroicon-o-chevron-right"
                                                    x-bind:class="open ? 'rotate-90' : ''"
                                                    class="h-4 w-4 mt-0.5 flex-shrink-0 transition-transform text-gray-400"
                                                />
                                                <span class="{{ $row['status'] === 'error' ? 'text-danger-600 dark:text-danger-400' : '' }}">
                                                    {{ $row['changeSummary'] }}
                                                </span>
                                            </button>

                                            {{-- Expandable diff panel --}}
                                            <div x-show="open" x-cloak class="mt-3 rounded-lg bg-gray-50 dark:bg-white/5 p-3 space-y-2 text-xs">
                                                @if (count($row['fieldChanges']) > 0)
                                                    <div>
                                                        <p class="font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Patient fields</p>
                                                        <ul class="space-y-0.5">
                                                            @foreach ($row['fieldChanges'] as $change)
                                                                <li>
                                                                    <span class="text-gray-500 dark:text-gray-400">{{ $change['label'] }}:</span>
                                                                    <span class="text-gray-400">—</span>
                                                                    →
                                                                    <span class="font-medium text-gray-900 dark:text-white">{{ $change['value'] }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif

                                                @if (count($row['childSummary']) > 0)
                                                    <div>
                                                        <p class="font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Clinical record</p>
                                                        <ul class="space-y-0.5">
                                                            @foreach ($row['childSummary'] as $line)
                                                                <li class="text-gray-700 dark:text-gray-200">{{ $line }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif

                                                @if ($row['message'])
                                                    <p class="text-danger-600 dark:text-danger-400">{{ $row['message'] }}</p>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-sm text-gray-400">No rows match this filter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-filament::section>

            {{-- Sticky action bar --}}
            <div class="sticky bottom-4 z-10">
                <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 shadow-lg p-4 flex items-center justify-between gap-4">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $this->selectedCount }} of {{ $counts['new'] + $counts['update'] }} importable row(s) selected
                        @if ($counts['error'] > 0)
                            <span class="text-danger-600 dark:text-danger-400">· {{ $counts['error'] }} will be skipped</span>
                        @endif
                    </p>

                    {{ $this->importAction() }}
                </div>
            </div>
        @endif

        {{-- Step 3: result --}}
        @if ($summary !== null)
            <x-filament::section heading="3. Result">
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4 text-center">
                            <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $summary['created'] }}</p>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-1">Created</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4 text-center">
                            <p class="text-2xl font-bold text-info-600 dark:text-info-400">{{ $summary['updated'] }}</p>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-1">Updated</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4 text-center">
                            <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $summary['failed'] }}</p>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-1">Failed</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @if ($summary['failed'] > 0)
                            <x-filament::button wire:click="downloadFailedReport" color="gray" icon="heroicon-o-arrow-down-tray">
                                Download failed rows (CSV)
                            </x-filament::button>
                        @endif

                        <x-filament::button tag="a" href="{{ \App\Filament\Resources\PatientResource::getUrl() }}" color="gray" icon="heroicon-o-users">
                            View patients
                        </x-filament::button>

                        <x-filament::button wire:click="startOver" icon="heroicon-o-arrow-path">
                            Import another file
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
