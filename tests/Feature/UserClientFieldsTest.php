<?php

use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Models\User;
use Carbon\CarbonImmutable;

test('it casts gender and status to enums and resolves approver relationship', function () {
    $approver = User::factory()->create();

    $user = User::factory()->create([
        'country' => 'Egypt',
        'gender' => Gender::Female->value,
        'status' => UserStatus::Approved->value,
        'approved_by' => $approver->id,
        'approved_at' => now(),
    ])->fresh();

    expect($user->gender)->toBe(Gender::Female)
        ->and($user->status)->toBe(UserStatus::Approved)
        ->and($user->approvedBy?->is($approver))->toBeTrue()
        ->and($user->approved_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('it defaults status to pending when not provided', function () {
    $user = User::factory()->create(['country' => 'Egypt'])->fresh();

    expect($user->status)->toBe(UserStatus::Pending);
});
