<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->clientIdForUniqueEmail())],
            'mobile_number' => ['required', 'string', 'max:20', Rule::unique('users', 'mobile_number')->ignore($this->clientIdForUniqueEmail())],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'country' => ['required', 'string', Rule::in(cachedCountries())],
            'gender' => ['required', Rule::enum(Gender::class)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
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

    private function clientIdForUniqueEmail(): ?int
    {
        $client = $this->route('client');

        if ($client instanceof User) {
            return $client->id;
        }

        if (is_numeric($client)) {
            return (int) $client;
        }

        return null;
    }
}
