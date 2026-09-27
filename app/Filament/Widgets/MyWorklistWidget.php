<?php

namespace App\Filament\Widgets;

use App\Enums\ApprovalStatus;
use App\Enums\ProcedureStatus;
use App\Enums\ReportStatus;
use App\Enums\WaitlistStatus;
use App\Filament\Resources\ApprovalRequestResource;
use App\Filament\Resources\ImagingReportResource;
use App\Filament\Resources\MdtDiscussionResource;
use App\Filament\Resources\ProcedureBookingResource;
use App\Filament\Resources\WaitlistEntryResource;
use App\Models\ApprovalRequest;
use App\Models\EpStudy;
use App\Models\ImagingReport;
use App\Models\MdtDiscussion;
use App\Models\ProcedureBooking;
use App\Models\Staff;
use App\Models\WaitlistEntry;
use Filament\Widgets\Widget;

class MyWorklistWidget extends Widget
{
    protected static string $view = 'filament.widgets.my-worklist-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->canWrite() && $user->staff !== null;
    }

    protected function staff(): ?Staff
    {
        return auth()->user()?->staff;
    }

    public function getReportsAwaitingSignature(): int
    {
        $staff = $this->staff();

        if (! $staff) {
            return 0;
        }

        $imaging = ImagingReport::whereIn('status', [ReportStatus::Draft, ReportStatus::Preliminary])
            ->where(fn ($q) => $q->whereRelation('readers', 'id', $staff->id)
                ->orWhereRelation('performers', 'id', $staff->id))
            ->count();

        $ep = EpStudy::whereIn('status', [ReportStatus::Draft, ReportStatus::Preliminary])
            ->where(fn ($q) => $q->where('performed_by_id', $staff->id)->orWhere('signed_by', $staff->id))
            ->count();

        return $imaging + $ep;
    }

    public function getPendingApprovals(): int
    {
        return ApprovalRequest::where('requested_by_id', auth()->id())
            ->where('status', ApprovalStatus::Pending)
            ->count();
    }

    public function getUpcomingCaseDiscussions(): int
    {
        $staff = $this->staff();

        if (! $staff) {
            return 0;
        }

        return MdtDiscussion::where('specialist_fellow_id', $staff->id)
            ->whereDate('discussion_date', '>=', today())
            ->count();
    }

    public function getUpcomingBookings(): int
    {
        $staff = $this->staff();

        if (! $staff) {
            return 0;
        }

        return ProcedureBooking::where('staff_id', $staff->id)
            ->whereDate('booking_date', '>=', today())
            ->whereIn('procedure_status', [ProcedureStatus::Ordered, ProcedureStatus::Confirmed])
            ->count();
    }

    public function getWaitlistCount(): int
    {
        $staff = $this->staff();

        if (! $staff) {
            return 0;
        }

        return WaitlistEntry::where('staff_id', $staff->id)
            ->where('status', WaitlistStatus::Waiting)
            ->count();
    }

    /** @return array<int, array{label: string, count: int, url: string}> */
    public function getCards(): array
    {
        return [
            ['label' => 'Reports awaiting my signature', 'count' => $this->getReportsAwaitingSignature(), 'url' => ImagingReportResource::getUrl()],
            ['label' => 'My pending approval requests', 'count' => $this->getPendingApprovals(), 'url' => ApprovalRequestResource::getUrl()],
            ['label' => 'My upcoming case discussions', 'count' => $this->getUpcomingCaseDiscussions(), 'url' => MdtDiscussionResource::getUrl()],
            ['label' => 'My upcoming procedure bookings', 'count' => $this->getUpcomingBookings(), 'url' => ProcedureBookingResource::getUrl()],
            ['label' => 'My wait-list patients', 'count' => $this->getWaitlistCount(), 'url' => WaitlistEntryResource::getUrl()],
        ];
    }
}
