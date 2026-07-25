<?php

namespace App\Models\Concerns;

use App\Enums\ReportStatus;

/**
 * Draft → Preliminary → Final → Amended lifecycle shared by signable
 * reports (imaging, electrophysiology). Requires status:ReportStatus and
 * finalized_at casts on the model.
 */
trait HasReportWorkflow
{
    public function isLocked(): bool
    {
        return $this->status?->isLocked() ?? false;
    }

    // Finalizing requires a signing physician. Models with multiple
    // signers (imaging reports) override this.
    public function hasSigner(): bool
    {
        return filled($this->signed_by);
    }

    public function markPreliminary(): void
    {
        $this->update(['status' => ReportStatus::Preliminary]);
    }

    public function finalize(): void
    {
        $this->update([
            'status' => ReportStatus::Final,
            'finalized_at' => now(),
        ]);
    }

    public function amend(string $reason): void
    {
        // forceFill bypasses the locked-report update policy deliberately:
        // amending is the sanctioned way to reopen a final report.
        $this->forceFill(['status' => ReportStatus::Amended])->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $reason])
            ->log('amended');
    }
}
