<?php

namespace App\Providers;

use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
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

        // One filter design everywhere: a Filters button in the table toolbar
        // (with a count) opens a panel of one-click chip rows; while it's
        // closed, removable tags under the toolbar show what's active.
        Table::configureUsing(fn (Table $table) => $table
            ->filtersLayout(FiltersLayout::Dropdown)
            ->filtersFormWidth(MaxWidth::ThreeExtraLarge)
            ->filtersFormColumns(1)
            ->filtersTriggerAction(fn (Action $action) => $action
                ->button()
                ->outlined()
                ->color('gray')
                ->label('Filters')
                ->icon('heroicon-m-adjustments-horizontal')));

        if ($this->app->isProduction()) {
            URL::forceHttps();
        }
    }
}
