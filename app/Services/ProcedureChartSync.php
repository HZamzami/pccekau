<?php

namespace App\Services;

use App\Enums\ProcedureStatus;
use App\Models\EpStudy;
use App\Models\ImagingReport;
use App\Models\Intervention;
use App\Models\MdtDiscussion;
use App\Models\ProcedureBooking;
use Illuminate\Database\Eloquent\Model;

/**
 * A booking holds a calendar slot; the procedure itself is a record on the
 * patient's chart (Intervention, Imaging Report, EP study or Case discussion).
 * This keeps the two in step: the booking creates/updates its chart record,
 * and a status changed on the chart flows back to the booking.
 */
class ProcedureChartSync
{
    /** booking column => chart model */
    private const LINKS = [
        'intervention_id' => Intervention::class,
        'imaging_report_id' => ImagingReport::class,
        'ep_study_id' => EpStudy::class,
        'mdt_discussion_id' => MdtDiscussion::class,
    ];

    private static bool $syncing = false;

    public static function fromBooking(ProcedureBooking $booking): void
    {
        if (self::$syncing) {
            return;
        }

        self::$syncing = true;

        try {
            $target = $booking->patient_id ? $booking->category?->chartRecord() : null;

            foreach (self::LINKS as $column => $model) {
                if ($model === $target) {
                    $booking->{$column} = self::syncRecord($booking, $model, self::linked($booking, $column))->getKey();
                } elseif ($booking->{$column}) {
                    self::discard(self::linked($booking, $column));
                    $booking->{$column} = null;
                }
            }

            if ($booking->isDirty(array_keys(self::LINKS))) {
                $booking->saveQuietly();
            }

            foreach (['intervention', 'imagingReport', 'epStudy', 'mdtDiscussion'] as $relation) {
                $booking->unsetRelation($relation);
            }
        } finally {
            self::$syncing = false;
        }
    }

    public static function fromChartRecord(Model $record): void
    {
        $statusColumn = $record instanceof MdtDiscussion ? 'discussed' : 'procedure_status';

        if (self::$syncing || ! $record->wasChanged($statusColumn)) {
            return;
        }

        $column = array_search($record::class, self::LINKS, true);

        self::$syncing = true;

        try {
            ProcedureBooking::where($column, $record->getKey())->get()->each(function (ProcedureBooking $booking) use ($record) {
                $status = $record instanceof MdtDiscussion
                    ? ($record->discussed ? ProcedureStatus::Done : ($booking->procedure_status === ProcedureStatus::Done ? ProcedureStatus::Confirmed : $booking->procedure_status))
                    : $record->procedure_status;

                $booking->update(['procedure_status' => $status]);
            });
        } finally {
            self::$syncing = false;
        }
    }

    public static function bookingDeleted(ProcedureBooking $booking): void
    {
        foreach (array_keys(self::LINKS) as $column) {
            self::discard(self::linked($booking, $column));
        }
    }

    private static function linked(ProcedureBooking $booking, string $column): ?Model
    {
        return $booking->{$column} ? self::LINKS[$column]::find($booking->{$column}) : null;
    }

    /** @param  class-string<Model>  $model */
    private static function syncRecord(ProcedureBooking $booking, string $model, ?Model $record): Model
    {
        $record ??= new $model;
        $record->forceFill(['clinic_id' => $booking->clinic_id, 'patient_id' => $booking->patient_id]);

        match ($model) {
            Intervention::class => self::fillIntervention($record, $booking),
            ImagingReport::class => $record->fill([
                'type' => $booking->category->imagingType(),
                'date' => $booking->booking_date,
                'procedure_status' => $booking->procedure_status,
            ]),
            EpStudy::class => $record->fill([
                'type' => $booking->category->epStudyType(),
                'date' => $booking->booking_date,
                'procedure_status' => $booking->procedure_status,
            ]),
            MdtDiscussion::class => self::fillMdtDiscussion($record, $booking),
        };

        if (in_array($model, [ImagingReport::class, EpStudy::class], true) && ! $record->exists) {
            $record->report = '';
        }

        $record->save();

        return $record;
    }

    private static function fillIntervention(Intervention $record, ProcedureBooking $booking): void
    {
        $type = $booking->intervention_type ?? $booking->category->fallbackInterventionType();

        $record->fill([
            'date' => $booking->booking_date,
            'procedure_status' => $booking->procedure_status,
            'type' => $type,
            'operator_id' => $booking->staff_id,
        ]);

        if ($booking->procedure || ! $record->exists) {
            $record->name = $booking->procedure ?: $type->getLabel();
        }
    }

    // Diagnosis and reason are only overwritten when the booking has them, so
    // details typed on the Case Discussions page aren't wiped by a booking edit.
    private static function fillMdtDiscussion(MdtDiscussion $record, ProcedureBooking $booking): void
    {
        $record->fill([
            'discussion_date' => $booking->booking_date,
            'discussed' => $booking->procedure_status === ProcedureStatus::Done,
        ]);

        if ($booking->diagnosis || ! $record->exists) {
            $record->diagnosis = $booking->diagnosis ?? '';
        }

        if ($booking->procedure || ! $record->exists) {
            $record->reason_for_discussion = $booking->procedure ?? '';
        }
    }

    // A cancelled booking takes its chart entry with it, unless the procedure
    // was already done; then the chart keeps it as the record of what happened.
    private static function discard(?Model $record): void
    {
        if (! $record) {
            return;
        }

        $done = $record instanceof MdtDiscussion
            ? (bool) $record->discussed
            : $record->procedure_status === ProcedureStatus::Done;

        if (! $done) {
            $record->delete();
        }
    }
}
