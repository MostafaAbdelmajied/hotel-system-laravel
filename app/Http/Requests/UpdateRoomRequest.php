<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $room = $this->route('room');

        if (! $room instanceof Room || $user === null) {
            return false;
        }

        return $user->hasRole('Admin') || $room->created_by === $user->id;
    }

    public function rules(): array
    {
        return [
            'floor_id' => ['required', 'exists:floors,id'],
            'number' => ['required', 'string', 'min:4', Rule::unique('rooms', 'number')->ignore($this->route('room'))],
            'capacity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
