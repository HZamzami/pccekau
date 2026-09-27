<x-filament-widgets::widget>
    <x-filament::section heading="My Worklist">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($this->getCards() as $card)
                <a href="{{ $card['url'] }}" class="rounded-xl border border-gray-200 dark:border-white/10 p-4 text-center space-y-1 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                    <p class="text-2xl font-bold {{ $card['count'] > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}">
                        {{ $card['count'] }}
                    </p>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        {{ $card['label'] }}
                    </p>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
