<?php

use App\Enums\UserStatus;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyFeature(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $country = collect(cachedCountries())->first() ?? 'Egypt';

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'mobile_number' => '+201012345678',
        'country' => $country,
        'gender' => 'male',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->status)->toBe(UserStatus::Pending)
        ->and($user->hasRole('Client'))->toBeTrue();
});
