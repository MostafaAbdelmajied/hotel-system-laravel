<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvailableRoomsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'check_in' => ['required', 'date', 'after_or_equal:today', 'before:check_out'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'check_in' => $this->input('check_in', now()->toDateString()),
            'check_out' => $this->input('check_out', now()->addDay()->toDateString()),
        ]);
    }
}
