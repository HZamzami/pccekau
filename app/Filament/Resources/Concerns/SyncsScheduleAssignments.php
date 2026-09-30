<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Support\Arr;

/**
 * For Create/Edit pages of a HasDailyAssignments model: the form binds the
 * per-day grid to `assignments`, which is stored as child rows, not columns.
 */
trait SyncsScheduleAssignments
{
    protected array $pendingAssignments = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['assignments'] = $this->record->assignmentFormState();

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingAssignments = Arr::pull($data, 'assignments', []);

        return Arr::except($data, 'fill_week');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingAssignments = Arr::pull($data, 'assignments', []);

        return Arr::except($data, 'fill_week');
    }

    protected function afterCreate(): void
    {
        $this->record->syncAssignments($this->pendingAssignments);
    }

    protected function afterSave(): void
    {
        $this->record->syncAssignments($this->pendingAssignments);
    }
}
