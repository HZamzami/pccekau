<?php

namespace App\Providers;

use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(12)->letters()->numbers());

        // One filter design everywhere: an always-open bar above the table
        // whose chips apply on click. The chips already show what's active,
        // so Filament's separate indicator badges are hidden.
        Table::configureUsing(fn (Table $table) => $table
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(['md' => 2, 'xl' => 4])
            ->hiddenFilterIndicators());

        if ($this->app->isProduction()) {
            URL::forceHttps();
        }
    }
}
