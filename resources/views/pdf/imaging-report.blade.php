<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Imaging Report — {{ $patient->mrn }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h2 { font-size: 13px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin: 18px 0 8px; }
        table.meta td { padding: 2px 12px 2px 0; font-size: 11px; }
        table.meta td.label { color: #666; }
        table.measurements { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.measurements th, table.measurements td { border: 1px solid #ddd; padding: 4px 8px; font-size: 10px; text-align: left; }
        table.measurements th { background: #f3f4f6; }
        .report-body { white-space: pre-wrap; line-height: 1.5; }
        .watermark {
            position: fixed; top: 40%; left: 15%; font-size: 72px; color: rgba(200, 30, 30, 0.12);
            transform: rotate(-30deg); z-index: -1; font-weight: bold;
        }
        .signature { margin-top: 36px; border-top: 1px solid #999; padding-top: 6px; width: 260px; font-size: 11px; }
        .z-abnormal { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    @unless ($report->status === \App\Enums\ReportStatus::Final)
        <div class="watermark">{{ strtoupper($report->status->getLabel()) }}</div>
    @endunless

    @include('pdf.partials.letterhead')

    <h2>Patient</h2>
    <table class="meta">
        <tr>
            <td class="label">MRN</td><td>{{ $patient->mrn }}</td>
            <td class="label">Name</td><td>{{ $patient->name }}</td>
        </tr>
        <tr>
            <td class="label">Date of birth</td><td>{{ $patient->date_of_birth?->format('d M Y') ?? '—' }}</td>
            <td class="label">Age at study</td><td>{{ $patient->date_of_birth ? \App\Models\Patient::computeAgeLabel($patient->date_of_birth, $report->date) : '—' }}</td>
        </tr>
        <tr>
            <td class="label">Gender</td><td>{{ $patient->gender ? ucfirst($patient->gender) : '—' }}</td>
            <td class="label">Non cardiac diagnosis</td><td>{{ $patient->primary_diagnosis }}</td>
        </tr>
    </table>

    <h2>Study</h2>
    <table class="meta">
        <tr>
            <td class="label">Type</td><td>{{ $report->type->getLabel() }}</td>
            <td class="label">Date</td><td>{{ $report->date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Performed by</td><td>{{ $report->performers->pluck('name')->join(', ') ?: '—' }}</td>
            <td class="label">Status</td><td>{{ $report->status->getLabel() }}</td>
        </tr>
    </table>

    <h2>Report</h2>
    <div class="report-body">{{ $report->report }}</div>

    @if ($report->notes)
        <h2>Notes</h2>
        <div class="report-body">{{ $report->notes }}</div>
    @endif

    <div class="signature">
        {{ $report->readers->pluck('name')->join(', ') ?: 'Unsigned' }}<br>
        @if ($report->finalized_at)
            Finalized {{ $report->finalized_at->format('d M Y H:i') }}
        @endif
    </div>
</body>
</html>
