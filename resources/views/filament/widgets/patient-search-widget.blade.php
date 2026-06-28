<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search patient by MRN or name..."
                    autofocus
                />
            </x-filament::input.wrapper>

            @php $results = $this->getResults() @endphp

            @if (strlen(trim($search)) >= 2)
                @if ($results->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No patients found.</p>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-white/10 rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden">
                        @foreach ($results as $patient)
                            <a
                                href="{{ route('filament.admin.resources.patients.edit', $patient) }}"
                                class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-white/5 transition"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $patient->mrn }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $patient->name }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ $patient->age }} · {{ ucfirst($patient->gender) }} · {{ $patient->nationality }}
                                    </div>
                                </div>
                                <x-filament::badge :color="match($patient->status) {
                                    'active'     => 'success',
                                    'follow-up'  => 'warning',
                                    'post-op'    => 'info',
                                    'discharged' => 'gray',
                                    default      => 'gray',
                                }">
                                    {{ ucfirst($patient->status) }}
                                </x-filament::badge>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
