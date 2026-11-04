<?php

namespace App\Providers;

use App\Filament\Tables\Filters\ChipFilter;
use Filament\Forms\Components\Select;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
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

        // One filter design everywhere: an always-visible bar above the table.
        // The page's main filter sits in it as one-tap chips; every other
        // filter is a pill that opens its editor in place and shows its value
        // once set, so no separate "active filters" row is needed.
        Table::configureUsing(fn (Table $table) => $table
            ->filtersLayout(FiltersLayout::AboveContent)
            ->hiddenFilterIndicators());

        // Plain selects (Active, Trashed, Consultant…) become pills too.
        SelectFilter::configureUsing(fn (SelectFilter $filter) => $filter instanceof ChipFilter ? null : $filter
            ->modifyFormFieldUsing(fn (Select $field) => $field
                ->hiddenLabel()
                ->view('filament.forms.components.pill-select')));

        if ($this->app->isProduction()) {
            URL::forceHttps();
        }
    }
}
