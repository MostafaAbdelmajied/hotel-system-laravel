<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowReservationFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Client') ?? false;
    }

    public function rules(): array
    {
        return [
            'check_in' => ['required', 'date', 'after_or_equal:today', 'before:check_out'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ];
    }
}
