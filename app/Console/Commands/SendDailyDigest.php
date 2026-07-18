<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Filament\Pages\ClinicFollowUpsPage;
use App\Filament\Resources\MdtDiscussionResource;
use App\Models\Clinic;
use App\Models\ClinicVisit;
use App\Models\MdtDiscussion;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class SendDailyDigest extends Command
{
    protected $signature = 'pccekau:daily-digest';

    protected $description = 'Notify doctors and admins about due clinic follow-ups and today\'s case discussions';

    public function handle(): int
    {
        $sent = 0;

        // Console has no tenant context: the clinic global scope is inactive
        // here, so iterate clinics explicitly and scope every query by hand.
        foreach (Clinic::query()->cursor() as $clinic) {
            $dueFollowUps = ClinicVisit::forClinic($clinic)->dueFollowUps()->count();
            $todayMdts = MdtDiscussion::forClinic($clinic)->whereDate('discussion_date', today())->count();

            if ($dueFollowUps === 0 && $todayMdts === 0) {
                continue;
            }

            $recipients = User::whereBelongsTo($clinic)
                ->whereIn('role', [UserRole::Admin, UserRole::Doctor])
                ->get();

            $lines = array_filter([
                $dueFollowUps > 0 ? "{$dueFollowUps} clinic follow-up(s) due or overdue" : null,
                $todayMdts > 0 ? "{$todayMdts} case discussion(s) scheduled today" : null,
            ]);

            $notification = Notification::make()
                ->title('Daily clinical digest')
                ->body(implode(' — ', $lines))
                ->icon('heroicon-o-bell-alert')
                ->actions([
                    Action::make('followUps')
                        ->label('Follow-Ups')
                        ->url(ClinicFollowUpsPage::getUrl(tenant: $clinic))
                        ->visible($dueFollowUps > 0),
                    Action::make('mdt')
                        ->label('Case Discussions')
                        ->url(MdtDiscussionResource::getUrl(tenant: $clinic))
                        ->visible($todayMdts > 0),
                ]);

            // notifyNow: Filament's DatabaseNotification is queued by default,
            // but a daily command should not depend on a queue worker running.
            foreach ($recipients as $recipient) {
                $recipient->notifyNow($notification->toDatabase());
            }

            $sent += $recipients->count();
            $this->info("{$clinic->name}: digest sent to {$recipients->count()} user(s): " . implode('; ', $lines));
        }

        if ($sent === 0) {
            $this->info('Nothing due today — no notifications sent.');
        }

        return self::SUCCESS;
    }
}
