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
            <td class="label">Date of birth</td><td>{{ $patient->date_of_birth->format('d M Y') }}</td>
            <td class="label">Age at study</td><td>{{ \App\Models\Patient::computeAgeLabel($patient->date_of_birth, $report->date) }}</td>
        </tr>
        <tr>
            <td class="label">Gender</td><td>{{ ucfirst($patient->gender) }}</td>
            <td class="label">Diagnosis</td><td>{{ $patient->primary_diagnosis }}</td>
        </tr>
    </table>

    <h2>Study</h2>
    <table class="meta">
        <tr>
            <td class="label">Type</td><td>{{ $report->type->getLabel() }}</td>
            <td class="label">Date</td><td>{{ $report->date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Performed by</td><td>{{ $report->performedBy?->name ?? '—' }}</td>
            <td class="label">Status</td><td>{{ $report->status->getLabel() }}</td>
        </tr>
    </table>

    @if ($measurement)
        <h2>Echo Measurements</h2>
        <table class="meta">
            <tr>
                <td class="label">Height</td><td>{{ $measurement->height_cm ? $measurement->height_cm . ' cm' : '—' }}</td>
                <td class="label">Weight</td><td>{{ $measurement->weight_kg ? $measurement->weight_kg . ' kg' : '—' }}</td>
                <td class="label">BSA (Haycock)</td><td>{{ $bsa ? $bsa . ' m²' : '—' }}</td>
            </tr>
        </table>
        <table class="measurements">
            <tr><th>Measurement</th><th>Value</th><th>Z-score</th></tr>
            @foreach ([
                'ivsd' => 'IVSd (cm)', 'lvidd' => 'LVIDd (cm)', 'lvpwd' => 'LVPWd (cm)', 'lvids' => 'LVIDs (cm)',
                'la' => 'LA (cm)', 'ao_annulus' => 'Ao annulus (cm)', 'ao_root' => 'Ao root (cm)',
                'ef' => 'EF (%)', 'fs' => 'FS (%)', 'tapse' => 'TAPSE (cm)', 'rv_function' => 'RV function',
                'mv_peak_velocity' => 'MV Vmax (m/s)', 'mv_peak_gradient' => 'MV PG (mmHg)',
                'mv_mean_gradient' => 'MV mean gradient (mmHg)', 'mv_regurg' => 'MR grade',
                'tv_peak_velocity' => 'TV Vmax (m/s)', 'tv_peak_gradient' => 'TV PG (mmHg)', 'tv_regurg' => 'TR grade',
                'av_peak_velocity' => 'AV Vmax (m/s)', 'av_peak_gradient' => 'AV PG (mmHg)',
                'av_mean_gradient' => 'AV mean gradient (mmHg)', 'av_regurg' => 'AI grade',
                'pv_peak_velocity' => 'PV Vmax (m/s)', 'pv_peak_gradient' => 'PV PG (mmHg)', 'pv_regurg' => 'PI grade',
                'coarct_peak_gradient' => 'Coarctation PG (mmHg)', 'coarct_mean_gradient' => 'Coarctation mean (mmHg)',
                'pda_size_mm' => 'PDA size (mm)',
            ] as $key => $label)
                @continue($measurement->{$key} === null)
                @php
                    $value = $measurement->{$key};
                    $display = $value instanceof \Filament\Support\Contracts\HasLabel ? $value->getLabel() : $value;
                    $z = $bsa ? \App\Services\ZScoreService::zScore($key, (float) (is_object($value) ? 0 : $value), $bsa) : null;
                @endphp
                <tr>
                    <td>{{ $label }}</td>
                    <td>{{ $display }}</td>
                    <td @class(['z-abnormal' => $z !== null && abs($z) > 2])>{{ $z ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Report</h2>
    <div class="report-body">{{ $report->report }}</div>

    @if ($report->notes)
        <h2>Notes</h2>
        <div class="report-body">{{ $report->notes }}</div>
    @endif

    <div class="signature">
        {{ $report->signedBy?->name ?? 'Unsigned' }}<br>
        @if ($report->finalized_at)
            Finalized {{ $report->finalized_at->format('d M Y H:i') }}
        @endif
    </div>
</body>
</html>
