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

test('it validates required client registration fields', function () {
    $validator = storeClientRequestValidator([]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue()
        ->and($validator->errors()->has('password'))->toBeTrue()
        ->and($validator->errors()->has('country'))->toBeTrue()
        ->and($validator->errors()->has('gender'))->toBeTrue();
});

test('it returns custom gender validation message for invalid value', function () {
    $validator = storeClientRequestValidator([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country' => 'Egypt',
        'gender' => 'other',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('gender'))->toBe('The selected gender is invalid. Please choose male or female.');
});

test('it returns custom avatar type message when avatar extension is not allowed', function () {
    $validator = storeClientRequestValidator([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'country' => 'Egypt',
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
        'password' => 'password',
        'password_confirmation' => 'password',
        'country' => 'Egypt',
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
        'password' => 'password',
        'password_confirmation' => 'password',
        'country' => 'Egypt',
        'gender' => 'female',
    ], $client);

    expect($validator->fails())->toBeFalse();
});
