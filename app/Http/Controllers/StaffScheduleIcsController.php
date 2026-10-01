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
            ...$this->assignmentEvents(OncallSchedule::class, $staff, $start, $end),
            ...$this->assignmentEvents(ConsultantSchedule::class, $staff, $start, $end),
        ];

        return response($this->buildIcs($staff, $events))
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="'.$staff->name.'-schedule.ics"');
    }

    /**
     * @param  class-string<OncallSchedule|ConsultantSchedule>  $scheduleClass
     * @return array<array{date: Carbon, endDate: ?Carbon, summary: string}>
     */
    private function assignmentEvents(string $scheduleClass, Staff $staff, Carbon $start, Carbon $end): array
    {
        $weeks = $scheduleClass::forClinic($staff->clinic_id)
            ->whereDate('week_start', '>=', $start)
            ->whereDate('week_start', '<=', $end)
            ->with(['assignments' => fn ($query) => $query->withoutGlobalScope('clinic')->where('staff_id', $staff->id)])
            ->get();

        $events = [];

        foreach ($weeks as $week) {
            foreach ($week->assignments as $assignment) {
                $day = $week->week_start->copy()->addDays($assignment->day);
                $events[] = [
                    'date' => $day,
                    'endDate' => $day->copy()->addDay(),
                    'summary' => $assignment->role->calendarSummary(),
                ];
            }
        }

        return $events;
    }

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
