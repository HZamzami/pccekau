<x-filament-panels::page>
    <x-filament::section heading="Upload">
        <form wire:submit.prevent="preview">
            {{ $this->form }}

            <div class="mt-4 flex gap-3">
                <x-filament::button type="submit">
                    Preview
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @if ($previewRows !== null)
        @php
            $errorCount = count(array_filter($previewRows, fn ($r) => $r['status'] === 'error'));
            $newCount = count(array_filter($previewRows, fn ($r) => $r['status'] === 'new'));
            $updateCount = count(array_filter($previewRows, fn ($r) => $r['status'] === 'update'));
        @endphp

        <x-filament::section heading="Preview — {{ count($previewRows) }} row(s) parsed">
            <div class="flex gap-4 mb-4 text-sm">
                <span class="text-success-600 font-medium">{{ $newCount }} new</span>
                <span class="text-info-600 font-medium">{{ $updateCount }} update</span>
                <span class="text-danger-600 font-medium">{{ $errorCount }} error</span>
            </div>

            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10 text-xs text-gray-500 uppercase tracking-wide">
                            <th class="py-2 pr-4 text-left font-medium">Status</th>
                            <th class="py-2 pr-4 text-left font-medium">Patient</th>
                            <th class="py-2 pr-4 text-left font-medium">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($previewRows as $row)
                            <tr>
                                <td class="py-2 pr-4">
                                    <x-filament::badge :color="match ($row['status']) {
                                        'new' => 'success',
                                        'update' => 'info',
                                        'error' => 'danger',
                                        default => 'gray',
                                    }">
                                        {{ ucfirst($row['status']) }}
                                    </x-filament::badge>
                                </td>
                                <td class="py-2 pr-4 text-gray-700 dark:text-gray-200">{{ $row['label'] }}</td>
                                <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ $row['message'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($newCount + $updateCount > 0)
                <div class="mt-4">
                    <x-filament::button wire:click="confirm" color="success">
                        Confirm Import ({{ $newCount + $updateCount }} row(s))
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    @endif

    @if ($summary !== null)
        <x-filament::section heading="Result">
            <p class="text-sm text-gray-700 dark:text-gray-200">
                Created {{ $summary['created'] }} new patient(s), updated {{ $summary['updated'] }} existing patient(s),
                {{ $summary['failed'] }} row(s) failed.
            </p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
