<?php

namespace App\Filament\Forms;

use App\Enums\ProcedureCategory;
use App\Models\Patient;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;

// Fields shared by the booking form and the wait-list form: what is being
// ordered, for whom, and by which consultant.
class ProcedureOrderFields
{
    public static function category(Get $get): ?ProcedureCategory
    {
        return ProcedureCategory::tryFrom((string) $get('category'));
    }

    public static function patient(): Select
    {
        return Select::make('patient_id')
            ->default(fn () => request()->integer('patient_id') ?: null)
            ->label('Patient')
            ->relationship('patient', 'name')
            ->searchable(['name', 'mrn'])
            ->preload()
            ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}");
    }

    public static function categorySelect(): Select
    {
        // The "Add to wait-list" buttons open the form pre-set to one kind of
        // procedure; the page keeps that in its URL-bound $group property.
        $group = fn ($livewire) => array_key_exists((string) ($livewire->group ?? null), ProcedureCategory::GROUP_LABELS) ? $livewire->group : null;

        return Select::make('category')
            ->options(fn ($livewire) => ProcedureCategory::options($group($livewire)))
            ->in(fn (Select $component) => array_keys($component->getOptions()))
            ->default(function ($livewire) use ($group) {
                $options = ProcedureCategory::options($group($livewire));

                return count($options) === 1 ? array_key_first($options) : null;
            })
            ->required()
            ->live()
            ->afterStateUpdated(function ($state, Set $set) {
                $set('intervention_type', null);
                $set('slot_type', ProcedureCategory::tryFrom((string) $state)?->defaultSlotType());
            });
    }

    public static function interventionType(): Select
    {
        return Select::make('intervention_type')
            ->label('Exact procedure')
            ->options(fn (Get $get) => self::category($get)?->interventionTypeOptions() ?? [])
            ->visible(fn (Get $get) => self::category($get)?->needsInterventionType() ?? false)
            ->required();
    }

    // For case discussions this is the reason for discussion, which the Case
    // Discussions page requires.
    public static function procedure(): TextInput
    {
        return TextInput::make('procedure')
            ->label(fn (Get $get) => self::category($get) === ProcedureCategory::CaseDiscussion ? 'Reason for discussion' : 'Procedure')
            ->required(fn (Get $get) => self::category($get) === ProcedureCategory::CaseDiscussion)
            ->maxLength(255);
    }

    public static function diagnosis(): Textarea
    {
        return Textarea::make('diagnosis')
            ->rows(2)
            ->required(fn (Get $get) => self::category($get) === ProcedureCategory::CaseDiscussion)
            ->columnSpanFull();
    }

    public static function consultant(): Select
    {
        return Select::make('staff_id')
            ->label('Consultant')
            ->relationship('staff', 'name', fn ($query) => $query->active())
            ->searchable()
            ->preload();
    }
}
