<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    private const MAX_ROOM_CAPACITY = 2147483647;

    public function authorize(): bool
    {
        $user = $this->user();
        $room = $this->route('room');

        if (! $room instanceof Room || $user === null) {
            return false;
        }

        return $user->hasRole(['Admin', 'Manager']) || $room->created_by === $user->id;
    }

    public function rules(): array
    {
        return [
            'floor_id' => ['required', 'exists:floors,id'],
            'number' => ['required', 'string', 'min:4', Rule::unique('rooms', 'number')->ignore($this->route('room'))],
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
