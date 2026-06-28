<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex gap-2 flex-wrap">
            <button
                wire:click="setTab('today')"
                @class([
                    'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition border',
                    'bg-primary-600 text-white border-primary-600' => $activeTab === 'today',
                    'bg-white dark:bg-white/5 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/10' => $activeTab !== 'today',
                ])
            >
                Today
                <span class="rounded-full bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400 px-2 py-0.5 text-xs font-semibold">
                    {{ $this->getTodayCount() }}
                </span>
            </button>

            <button
                wire:click="setTab('upcoming')"
                @class([
                    'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition border',
                    'bg-primary-600 text-white border-primary-600' => $activeTab === 'upcoming',
                    'bg-white dark:bg-white/5 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/10' => $activeTab !== 'upcoming',
                ])
            >
                Upcoming
                <span class="rounded-full bg-info-100 text-info-700 dark:bg-info-900/30 dark:text-info-400 px-2 py-0.5 text-xs font-semibold">
                    {{ $this->getUpcomingCount() }}
                </span>
            </button>

            <button
                wire:click="setTab('overdue')"
                @class([
                    'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition border',
                    'bg-primary-600 text-white border-primary-600' => $activeTab === 'overdue',
                    'bg-white dark:bg-white/5 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/10' => $activeTab !== 'overdue',
                ])
            >
                Overdue
                <span class="rounded-full bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400 px-2 py-0.5 text-xs font-semibold">
                    {{ $this->getOverdueCount() }}
                </span>
            </button>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
