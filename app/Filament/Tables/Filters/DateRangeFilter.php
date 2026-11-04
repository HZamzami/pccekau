<?php

namespace App\Filament\Tables\Filters;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

// A From – Until range on the date column of the same name, laid out as one
// labelled row in the filter panel like the chip filters.
class DateRangeFilter extends Filter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Date');

        $this->form(fn () => [
            Fieldset::make($this->getLabel())
                ->view('filament.forms.components.filter-row')
                ->schema([
                    DatePicker::make('from')->hiddenLabel()->placeholder('From'),
                    DatePicker::make('until')->hiddenLabel()->placeholder('Until'),
                ]),
        ]);

        $this->query(fn (Builder $query, array $data) => $query
            ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate($this->getName(), '>=', $date))
            ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate($this->getName(), '<=', $date)));

        $this->indicateUsing(fn (array $data) => array_filter([
            ($data['from'] ?? null) ? 'From '.Carbon::parse($data['from'])->format('d M Y') : null,
            ($data['until'] ?? null) ? 'Until '.Carbon::parse($data['until'])->format('d M Y') : null,
        ]));
    }
}
