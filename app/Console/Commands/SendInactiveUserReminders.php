<?php

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\InactiveUserReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-inactive-user-reminders')]
#[Description('Queue reminder notifications for users inactive for at least one month')]
class SendInactiveUserReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $oneMonthAgo = now()->subMonth();
        $sentCount = 0;

        User::query()
            ->where('status', UserStatus::Approved->value)
            ->whereNotNull('last_login')
            ->where('last_login', '<', $oneMonthAgo)
            ->select(['id', 'name', 'email'])
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$sentCount): void {
                foreach ($users as $user) {
                    $user->notify(new InactiveUserReminderNotification);
                    $sentCount++;
                }
            });

        $this->info("Queued {$sentCount} inactive user reminder notification(s).");

        return self::SUCCESS;
    }
}
