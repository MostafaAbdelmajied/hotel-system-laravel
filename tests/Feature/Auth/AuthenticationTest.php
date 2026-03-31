<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Role;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('pending users can not authenticate and see approval message', function () {
    Role::findOrCreate('Client');

    $user = User::factory()->create([
        'status' => UserStatus::Pending,
    ]);
    $user->assignRole('Client');

    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors([
        'email' => 'Your account is pending approval. Please wait for an administrator to approve it.',
    ]);
});

test('pending non-client users can authenticate', function () {
    Role::findOrCreate('Admin');

    $user = User::factory()->create([
        'status' => UserStatus::Pending,
    ]);
    $user->assignRole('Admin');

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});

test('users are rate limited', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Approved,
    ]);

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});

test('authenticated pending users are logged out from protected routes', function () {
    Role::findOrCreate('Client');

    $user = User::factory()->create([
        'status' => UserStatus::Pending,
    ]);
    $user->assignRole('Client');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors([
        'email' => 'Your account is pending approval. Please wait for an administrator to approve it.',
    ]);
    $this->assertGuest();
});

test('authenticated pending non-client users can access protected routes', function () {
    Role::findOrCreate('Admin');

    $user = User::factory()->create([
        'status' => UserStatus::Pending,
    ]);
    $user->assignRole('Admin');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $this->assertAuthenticatedAs($user);
});

test('successful login updates user last login timestamp', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Approved,
        'last_login' => null,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user->refresh();

    expect($user->last_login)->not->toBeNull();
});
