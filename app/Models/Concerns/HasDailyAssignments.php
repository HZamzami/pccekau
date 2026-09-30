<?php

namespace App\Models\Concerns;

use App\Enums\CoverageRole;
use App\Models\ScheduleAssignment;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A weekly schedule whose roles are assigned per day (0 = Sunday … 6 = Saturday).
 */
trait HasDailyAssignments
{
    /** @return array<CoverageRole> */
    abstract public static function roles(): array;

    public function assignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class);
    }

    public function staffFor(CoverageRole $role, int $day): ?Staff
    {
        return $this->assignments
            ->first(fn (ScheduleAssignment $a) => $a->role === $role && $a->day === $day)
            ?->staff;
    }

    public function staffForToday(CoverageRole $role): ?Staff
    {
        return $this->isCurrentWeek() ? $this->staffFor($role, now()->dayOfWeek) : null;
    }

    public function isCurrentWeek(): bool
    {
        return $this->week_start->isSameDay(Carbon::now()->startOfWeek(Carbon::SUNDAY));
    }

    /** @return array<string, array<int, ?Staff>> role value => [day => staff] */
    public function grid(): array
    {
        return collect(static::roles())
            ->mapWithKeys(fn (CoverageRole $role) => [
                $role->value => array_map(fn (int $day) => $this->staffFor($role, $day), range(0, 6)),
            ])
            ->all();
    }

    /** @return array<string, array<int, ?int>> role value => [day => staff id], the shape the form binds to */
    public function assignmentFormState(): array
    {
        return collect(static::roles())
            ->mapWithKeys(fn (CoverageRole $role) => [
                $role->value => array_map(fn (int $day) => $this->staffFor($role, $day)?->id, range(0, 6)),
            ])
            ->all();
    }

    /** @param array<string, array<int|string, mixed>> $state role value => [day => staff id|null] */
    public function syncAssignments(array $state): void
    {
        foreach (static::roles() as $role) {
            foreach (range(0, 6) as $day) {
                $staffId = $state[$role->value][$day] ?? null;
                $keys = ['day' => $day, 'role' => $role->value];

                if ($staffId) {
                    $this->assignments()->updateOrCreate($keys, ['staff_id' => (int) $staffId]);
                } else {
                    $this->assignments()->where($keys)->delete();
                }
            }
        }

        $this->unsetRelation('assignments');
    }
}
