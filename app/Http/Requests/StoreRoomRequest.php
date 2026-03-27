<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Manager']) ?? false;
    }

    public function rules(): array
    {
        return [
            'floor_id' => ['required', 'exists:floors,id'],
            'number' => ['required', 'string', 'min:4', 'unique:rooms,number'],
            'capacity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
