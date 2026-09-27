<?php

namespace App\Http\Controllers;

use App\Models\ConsultantSchedule;
use App\Models\OncallSchedule;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class StaffScheduleIcsController extends Controller
{
    public function __invoke(Request $request, Staff $staff): Response
    {
        abort_unless($staff->ics_token && hash_equals($staff->ics_token, (string) $request->query('token')), 404);

        $start = Carbon::now()->startOfWeek(Carbon::SUNDAY)->subWeeks(4);
        $end = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addWeeks(12);

        $events = [
            ...$this->oncallEvents($staff, $start, $end),
            ...$this->consultantEvents($staff, $start, $end),
        ];

        return response($this->buildIcs($staff, $events))
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="'.$staff->name.'-schedule.ics"');
    }

    /** @return array<array{date: Carbon, endDate: ?Carbon, summary: string}> */
    private function oncallEvents(Staff $staff, Carbon $start, Carbon $end): array
    {
        $weeks = OncallSchedule::forClinic($staff->clinic_id)
            ->whereBetween('week_start', [$start->toDateString(), $end->toDateString()])
            ->get();

        $weeklyRoles = [
            'clinic_staff_id' => 'Clinic',
            'inpatient_staff_id' => 'Inpatient',
            'consultation_staff_id' => 'Consultation',
            'cath_staff_id' => 'Cath',
        ];

        $dailyOncall = [
            'oncall_sunday_id' => 0,
            'oncall_monday_id' => 1,
            'oncall_tuesday_id' => 2,
            'oncall_wednesday_id' => 3,
            'oncall_thursday_id' => 4,
            'oncall_friday_id' => 5,
            'oncall_saturday_id' => 6,
        ];

        $events = [];

        foreach ($weeks as $week) {
            foreach ($weeklyRoles as $column => $label) {
                if ((int) $week->$column === $staff->id) {
                    $events[] = [
                        'date' => $week->week_start->copy(),
                        'endDate' => $week->week_start->copy()->addDays(7),
                        'summary' => "{$label} Coverage",
                    ];
                }
            }

            foreach ($dailyOncall as $column => $dayOffset) {
                if ((int) $week->$column === $staff->id) {
                    $day = $week->week_start->copy()->addDays($dayOffset);
                    $events[] = [
                        'date' => $day,
                        'endDate' => $day->copy()->addDay(),
                        'summary' => 'On-Call',
                    ];
                }
            }
        }

        return $events;
    }

    /** @return array<array{date: Carbon, endDate: ?Carbon, summary: string}> */
    private function consultantEvents(Staff $staff, Carbon $start, Carbon $end): array
    {
        $weeks = ConsultantSchedule::forClinic($staff->clinic_id)
            ->whereBetween('week_start', [$start->toDateString(), $end->toDateString()])
            ->get();

        $roles = [
            'service_staff_id' => 'Service Consultant',
            'cath_staff_id' => 'Cath Consultant',
            'ep_staff_id' => 'EP Consultant',
        ];

        $events = [];

        foreach ($weeks as $week) {
            foreach ($roles as $column => $label) {
                if ((int) $week->$column === $staff->id) {
                    $events[] = [
                        'date' => $week->week_start->copy(),
                        'endDate' => $week->week_start->copy()->addDays(7),
                        'summary' => $label,
                    ];
                }
            }
        }

        return $events;
    }

    /** @param array<array{date: Carbon, endDate: ?Carbon, summary: string}> $events */
    private function buildIcs(Staff $staff, array $events): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//PCCEKAU//Staff Schedule//EN',
            'CALSCALE:GREGORIAN',
            'X-WR-CALNAME:'.$staff->name.' — Coverage Schedule',
        ];

        foreach ($events as $index => $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:staff-'.$staff->id.'-'.$event['date']->toDateString().'-'.$index.'@pccekau';
            $lines[] = 'DTSTAMP:'.Carbon::now()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART;VALUE=DATE:'.$event['date']->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$event['endDate']->format('Ymd');
            $lines[] = 'SUMMARY:'.$event['summary'];
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }
}
