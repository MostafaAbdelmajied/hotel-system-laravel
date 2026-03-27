<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Http\Requests\StoreClientRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        $request = new StoreClientRequest;

        Validator::make($input, $request->rules(), $request->messages())->validate();

        return DB::transaction(function () use ($input): User {
            $avatarPath = null;

            if (($input['avatar'] ?? null) instanceof UploadedFile) {
                $avatarPath = $input['avatar']->store('avatars', 'public');
            }

            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'mobile_number' => $input['mobile_number'],
                'password' => $input['password'],
                'country' => $input['country'],
                'gender' => Gender::from($input['gender']),
                'avatar' => $avatarPath,
                'status' => UserStatus::Pending,
            ]);

            Role::findOrCreate('Client');
            $user->assignRole('Client');

            return $user;
        });
    }
}
