<?php

use App\Http\Requests\StoreClientRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

function storeClientRequestValidator(array $data, User|int|null $client = null): Illuminate\Validation\Validator
{
    $request = new StoreClientRequest;

    if ($client !== null) {
        $request->setRouteResolver(fn () => new class($client)
        {
            public function __construct(private readonly User|int $client) {}

            public function parameter(string $name): User|int|null
            {
                return $name === 'client' ? $this->client : null;
            }
        });
    }

    return Validator::make($data, $request->rules(), $request->messages());
}

function validCountry(): string
{
    return collect(cachedCountries())->first() ?? 'Egypt';
}

test('it validates required client registration fields', function () {
    $validator = storeClientRequestValidator([]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue()
        ->and($validator->errors()->has('mobile_number'))->toBeTrue()
        ->and($validator->errors()->has('password'))->toBeTrue()
        ->and($validator->errors()->has('country'))->toBeTrue()
        ->and($validator->errors()->has('gender'))->toBeTrue();
});

test('it returns custom gender validation message for invalid value', function () {
    $validator = storeClientRequestValidator([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'mobile_number' => '+201012345678',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country' => validCountry(),
        'gender' => 'other',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('gender'))->toBe('The selected gender is invalid. Please choose male or female.');
});

test('it returns custom avatar type message when avatar extension is not allowed', function () {
    $validator = storeClientRequestValidator([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'mobile_number' => '+201012345678',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country' => validCountry(),
        'gender' => 'male',
        'avatar' => UploadedFile::fake()->image('avatar.gif'),
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('avatar'))->toBe('The avatar must be a file of type: jpg, jpeg, png.');
});

test('it requires unique email for new clients', function () {
    User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $validator = storeClientRequestValidator([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'mobile_number' => '+201012345678',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country' => validCountry(),
        'gender' => 'male',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});

test('it ignores current client id in unique email validation for updates', function () {
    $client = User::factory()->create([
        'email' => 'client@example.com',
    ]);

    $validator = storeClientRequestValidator([
        'name' => 'Client User',
        'email' => 'client@example.com',
        'mobile_number' => '+201055555555',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country' => validCountry(),
        'gender' => 'female',
    ], $client);

    expect($validator->errors()->has('email'))->toBeFalse();
});

test('it allows empty avatar value', function () {
    $validator = storeClientRequestValidator([
        'name' => 'Test User',
        'email' => 'test2@example.com',
        'mobile_number' => '+201066666666',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country' => validCountry(),
        'gender' => 'male',
        'avatar' => null,
    ]);

    expect($validator->errors()->has('avatar'))->toBeFalse();
});
