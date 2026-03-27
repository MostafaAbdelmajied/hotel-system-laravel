<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $manager = $this->route('manager');

        if (! $manager instanceof User) {
            return false;
        }

        return ($this->user()?->hasRole('Admin') ?? false) && $manager->hasRole('Manager');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->route('manager'))],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'country' => ['required', 'string', Rule::in(cachedCountries())],
            'gender' => ['required', Rule::enum(Gender::class)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gender.in' => 'The selected gender is invalid. Please choose male or female.',
            'gender.enum' => 'The selected gender is invalid. Please choose male or female.',
            'avatar.mimes' => 'The avatar must be a file of type: jpg, jpeg, png.',
            'avatar.image' => 'The avatar must be a valid image file.',
            'country.in' => 'The selected country is invalid.',
        ];
    }
}
