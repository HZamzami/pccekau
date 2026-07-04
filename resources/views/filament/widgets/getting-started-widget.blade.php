<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    Getting started with PCCEKAU
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Work through these steps in order — items tick themselves off as your department's data takes shape.
                </p>
            </div>

            <x-filament::icon-button
                icon="heroicon-o-x-mark"
                color="gray"
                label="Dismiss"
                wire:click="dismiss"
                tooltip="Hide this checklist permanently"
            />
        </div>

        <ul class="mt-4 space-y-3">
            @foreach ($this->getSteps() as $index => $step)
                <li class="flex items-start gap-3">
                    @if ($step['done'])
                        <x-filament::icon
                            icon="heroicon-s-check-circle"
                            class="h-6 w-6 shrink-0 text-success-500"
                        />
                    @else
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-gray-300 text-xs font-semibold text-gray-500 dark:border-gray-600 dark:text-gray-400">
                            {{ $index + 1 }}
                        </span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <span @class([
                            'text-sm font-medium',
                            'text-gray-400 line-through dark:text-gray-500' => $step['done'],
                            'text-gray-950 dark:text-white' => ! $step['done'],
                        ])>
                            {{ $step['label'] }}
                        </span>
                        @unless ($step['done'])
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $step['description'] }}
                            </p>
                        @endunless
                    </div>

                    @if (! $step['done'] && $step['url'])
                        <x-filament::button
                            tag="a"
                            :href="$step['url']"
                            size="xs"
                            color="primary"
                            outlined
                        >
                            Start
                        </x-filament::button>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
