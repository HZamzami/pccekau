<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\ClinicVisit;
use App\Models\MdtDiscussion;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class SendDailyDigest extends Command
{
    protected $signature = 'pccekau:daily-digest';

    protected $description = 'Notify doctors and admins about due clinic follow-ups and today\'s MDT discussions';

    public function handle(): int
    {
        $dueFollowUps = ClinicVisit::dueFollowUps()->count();
        $todayMdts = MdtDiscussion::whereDate('discussion_date', today())->count();

        if ($dueFollowUps === 0 && $todayMdts === 0) {
            $this->info('Nothing due today — no notifications sent.');

            return self::SUCCESS;
        }

        $recipients = User::whereIn('role', [UserRole::Admin, UserRole::Doctor])->get();

        $lines = array_filter([
            $dueFollowUps > 0 ? "{$dueFollowUps} clinic follow-up(s) due or overdue" : null,
            $todayMdts > 0 ? "{$todayMdts} MDT discussion(s) scheduled today" : null,
        ]);

        Notification::make()
            ->title('Daily clinical digest')
            ->body(implode(' — ', $lines))
            ->icon('heroicon-o-bell-alert')
            ->actions([
                Action::make('followUps')
                    ->label('Follow-Ups')
                    ->url('/admin/clinic-follow-ups-page')
                    ->visible($dueFollowUps > 0),
                Action::make('mdt')
                    ->label('MDT')
                    ->url('/admin/mdt-discussions')
                    ->visible($todayMdts > 0),
            ])
            ->sendToDatabase($recipients);

        $this->info("Digest sent to {$recipients->count()} user(s): " . implode('; ', $lines));

        return self::SUCCESS;
    }
}
