<?php

namespace App\Filament\Resources\InterventionResource\Pages;

use App\Enums\InterventionType;
use App\Filament\Resources\InterventionResource;
use App\Filament\Resources\WaitlistEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListInterventions extends ListRecords
{
    protected static string $resource = InterventionResource::class;

    /** Set by the Surgery / Cath sidebar items and the Invasive EP link; null lists every procedure. */
    #[Url]
    public ?string $category = null;

    public function mount(): void
    {
        if (! array_key_exists((string) $this->category, InterventionType::CATEGORIES)) {
            $this->category = null;
        }

        parent::mount();
    }

    public function getTitle(): string
    {
        return InterventionType::CATEGORIES[$this->category] ?? 'All procedures';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('allProcedures')
                ->label('All procedures')
                ->link()
                ->url(InterventionResource::getUrl('index'))
                ->visible($this->category !== null),
            WaitlistEntryResource::addAction('interventions'),
            Actions\CreateAction::make()
                ->url(InterventionResource::getUrl('create', array_filter(['category' => $this->category]))),
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->when(
                $this->category,
                fn (Builder $query, string $category) => $query->whereIn('type', InterventionType::ofCategory($category)),
            ));
    }
}
