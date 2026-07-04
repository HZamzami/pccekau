<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>MDT Summary — {{ $patient->mrn }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h2 { font-size: 13px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin: 18px 0 8px; }
        table.meta td { padding: 2px 12px 2px 0; font-size: 11px; }
        table.meta td.label { color: #666; }
        .block { white-space: pre-wrap; line-height: 1.5; margin-bottom: 4px; }
    </style>
</head>
<body>
    @include('pdf.partials.letterhead')

    <h2>MDT Discussion Summary</h2>
    <table class="meta">
        <tr>
            <td class="label">MRN</td><td>{{ $patient->mrn }}</td>
            <td class="label">Name</td><td>{{ $patient->name }}</td>
        </tr>
        <tr>
            <td class="label">Discussion date</td><td>{{ $discussion->discussion_date->format('d M Y') }}</td>
            <td class="label">Age at discussion</td><td>{{ $discussion->age_snapshot }}</td>
        </tr>
        <tr>
            <td class="label">Weight</td><td>{{ $discussion->weight_kg ? $discussion->weight_kg . ' kg' : '—' }}</td>
            <td class="label">O₂ saturation</td><td>{{ $discussion->oxygen_saturation ? $discussion->oxygen_saturation . '%' : '—' }}</td>
        </tr>
        <tr>
            <td class="label">Specialist / Fellow</td><td>{{ $discussion->specialistFellow?->name ?? '—' }}</td>
            <td class="label">Diagnosis</td><td>{{ $discussion->diagnosis }}</td>
        </tr>
    </table>

    @foreach ([
        'reason_for_discussion' => 'Reason for Discussion',
        'history' => 'History',
        'exam_findings' => 'Examination Findings',
        'echo_findings' => 'Echo Findings',
        'cath_findings' => 'Cath Findings',
        'discussion_results' => 'Discussion Outcome / Plan',
    ] as $field => $heading)
        @continue(blank($discussion->{$field}))
        <h2>{{ $heading }}</h2>
        <div class="block">{{ $discussion->{$field} }}</div>
    @endforeach
</body>
</html>
