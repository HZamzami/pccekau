<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Patient Summary — {{ $patient->mrn }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h2 { font-size: 13px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin: 18px 0 8px; }
        table.meta td { padding: 2px 12px 2px 0; font-size: 11px; }
        table.meta td.label { color: #666; }
        table.list { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.list th, table.list td { border: 1px solid #ddd; padding: 4px 8px; font-size: 10px; text-align: left; }
        table.list th { background: #f3f4f6; }
        .block { white-space: pre-wrap; line-height: 1.5; }
        .badge { display: inline-block; border: 1px solid #93c5fd; border-radius: 8px; padding: 1px 7px; margin: 0 3px 3px 0; font-size: 10px; color: #1d4ed8; }
        .z-abnormal { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    @include('pdf.partials.letterhead')

    <h2>Patient Summary</h2>
    <table class="meta">
        <tr>
            <td class="label">MRN</td><td>{{ $patient->mrn }}</td>
            <td class="label">Name</td><td>{{ $patient->name }}</td>
            <td class="label">Age</td><td>{{ $patient->age }}</td>
        </tr>
        <tr>
            <td class="label">DOB</td><td>{{ $patient->date_of_birth->format('d M Y') }}</td>
            <td class="label">Gender</td><td>{{ ucfirst($patient->gender) }}</td>
            <td class="label">Status</td><td>{{ $patient->status?->getLabel() ?? ucfirst((string) $patient->status) }}</td>
        </tr>
        <tr>
            <td class="label">Weight</td><td>{{ $patient->weight_kg ? $patient->weight_kg . ' kg' : '—' }}</td>
            <td class="label">Height</td><td>{{ $patient->height_cm ? $patient->height_cm . ' cm' : '—' }}</td>
            <td class="label">BSA</td><td>{{ $bsa ? $bsa . ' m²' : '—' }}</td>
        </tr>
        <tr>
            @php
                $lastSat = $patient->clinicVisits->whereNotNull('oxygen_saturation')->first()?->oxygen_saturation;
            @endphp
            <td class="label">Last O₂ sat</td><td>{{ $lastSat ? $lastSat . '%' : '—' }}</td>
            <td class="label">Blood type</td><td>{{ $patient->blood_type ?? '—' }}</td>
            <td class="label">Next follow-up</td><td>{{ $nextFollowUp?->format('d M Y') ?? '—' }}</td>
        </tr>
    </table>

    @if (filled($patient->lesions))
        <h2>Cardiac Lesions</h2>
        <div>
            @foreach ($patient->lesionEnums() as $lesion)
                <span class="badge">{{ $lesion?->getLabel() ?? '' }}</span>
            @endforeach
        </div>
    @endif

    @if ($patient->primary_diagnosis)
        <h2>Primary Diagnosis</h2>
        <div class="block">{{ $patient->primary_diagnosis }}</div>
    @endif

    @if ($patient->interventions->isNotEmpty())
        <h2>Interventions Timeline</h2>
        <table class="list">
            <tr><th>Date</th><th>Type</th><th>Procedure</th><th>Operator</th></tr>
            @foreach ($patient->interventions->sortBy('date') as $intervention)
                <tr>
                    <td>{{ $intervention->date->format('d M Y') }}</td>
                    <td>{{ $intervention->type?->getLabel() ?? $intervention->type }}</td>
                    <td>{{ $intervention->name }}</td>
                    <td>{{ $intervention->operator?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    @elseif ($patient->surgical_history)
        <h2>Surgical History</h2>
        <div class="block">{{ $patient->surgical_history }}</div>
    @endif

    @if ($latestEcho?->echoMeasurement)
        <h2>Latest Echo Measurements — {{ $latestEcho->date->format('d M Y') }}</h2>
        <table class="list">
            <tr><th>Measurement</th><th>Value</th><th>Z-score</th></tr>
            @foreach (\App\Models\EchoMeasurement::Z_SCORED as $key)
                @continue($latestEcho->echoMeasurement->{$key} === null)
                @php
                    $z = $bsa ? \App\Services\ZScoreService::zScore($key, (float) $latestEcho->echoMeasurement->{$key}, (float) $bsa) : null;
                @endphp
                <tr>
                    <td>{{ strtoupper(str_replace('_', ' ', $key)) }} (cm)</td>
                    <td>{{ $latestEcho->echoMeasurement->{$key} }}</td>
                    <td @class(['z-abnormal' => $z !== null && abs($z) > 2])>{{ $z ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Recent Imaging Reports</h2>
    @if ($patient->imagingReports->isEmpty())
        <div class="block">No imaging reports.</div>
    @else
        <table class="list">
            <tr><th>Date</th><th>Type</th><th>Status</th><th>Reader</th></tr>
            @foreach ($patient->imagingReports as $report)
                <tr>
                    <td>{{ $report->date->format('d M Y') }}</td>
                    <td>{{ $report->type->getLabel() }}</td>
                    <td>{{ $report->status->getLabel() }}</td>
                    <td>{{ $report->signedBy?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($patient->current_plan)
        <h2>Current Plan</h2>
        <div class="block">{{ $patient->current_plan }}</div>
    @endif
</body>
</html>
