<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    private const MAX_ROOM_CAPACITY = 2147483647;

    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Manager']) ?? false;
    }

    public function rules(): array
    {
        return [
            'floor_id' => ['required', 'exists:floors,id'],
            'number' => ['required', 'string', 'min:4', 'unique:rooms,number'],
            'capacity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_ROOM_CAPACITY],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'capacity.max' => 'Capacity is too large. Please enter a smaller number.',
        ];
    }
}
