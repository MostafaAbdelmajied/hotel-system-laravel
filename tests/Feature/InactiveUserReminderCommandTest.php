<?php

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\InactiveUserReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

test('inactive approved users receive reminder notifications', function () {
    Notification::fake();

    $inactiveUser = User::factory()->create([
        'status' => UserStatus::Approved,
        'last_login' => now()->subDays(31),
    ]);

    $this->artisan('app:send-inactive-user-reminders')->assertSuccessful();

    Notification::assertSentTo($inactiveUser, InactiveUserReminderNotification::class);
});

test('reminders are not sent to recent never-logged-or-pending users', function () {
    Notification::fake();

    $recentUser = User::factory()->create([
        'status' => UserStatus::Approved,
        'last_login' => now()->subDays(10),
    ]);

    $neverLoggedInUser = User::factory()->create([
        'status' => UserStatus::Approved,
        'last_login' => null,
    ]);

    $pendingUser = User::factory()->create([
        'status' => UserStatus::Pending,
        'last_login' => now()->subDays(90),
    ]);

    $this->artisan('app:send-inactive-user-reminders')->assertSuccessful();

    Notification::assertNotSentTo($recentUser, InactiveUserReminderNotification::class);
    Notification::assertNotSentTo($neverLoggedInUser, InactiveUserReminderNotification::class);
    Notification::assertNotSentTo($pendingUser, InactiveUserReminderNotification::class);
});

test('inactive user reminder notification is queued', function () {
    expect(new InactiveUserReminderNotification)->toBeInstanceOf(ShouldQueue::class);
});
