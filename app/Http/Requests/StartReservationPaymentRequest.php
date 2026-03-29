<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StartReservationPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Client') ?? false;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today', 'before:check_out'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'accompany_number' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Please select a room.',
            'room_id.exists' => 'The selected room is invalid.',
            'check_in.required' => 'Please select a check in date.',
            'check_in.date' => 'Check in must be a valid date.',
            'check_in.after_or_equal' => 'Check in must be today or a future date.',
            'check_in.before' => 'Check in must be before check out.',
            'check_out.required' => 'Please select a check out date.',
            'check_out.date' => 'Check out must be a valid date.',
            'check_out.after' => 'Check out must be after check in.',
            'accompany_number.required' => 'Please enter the number of accompanying guests.',
            'accompany_number.integer' => 'Accompanying guests must be a whole number.',
            'accompany_number.min' => 'Accompanying guests cannot be negative.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $roomId = (int) $this->input('room_id');
            $room = Room::query()->find($roomId);

            if ($room === null) {
                return;
            }

            $routeRoom = $this->route('room');
            $routeRoomId = $routeRoom instanceof Room ? $routeRoom->id : (is_numeric($routeRoom) ? (int) $routeRoom : null);

            if ($routeRoomId !== null && $routeRoomId !== $room->id) {
                $validator->errors()->add('room_id', 'Room mismatch detected. Please retry from the room list.');

                return;
            }

            $accompanyNumber = (int) $this->input('accompany_number', 0);
            $totalGuests = $accompanyNumber + 1;

            if ($totalGuests > $room->capacity) {
                $validator->errors()->add(
                    'accompany_number',
                    "Room capacity is {$room->capacity} guest(s), but {$totalGuests} guest(s) were selected."
                );
            }

            $checkIn = (string) $this->input('check_in');
            $checkOut = (string) $this->input('check_out');

            if (! $room->isAvailableBetween($checkIn, $checkOut)) {
                $validator->errors()->add(
                    'check_in',
                    'This room is no longer available for the selected date range. Please choose different dates or another room.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $routeRoom = $this->route('room');
        $routeRoomId = $routeRoom instanceof Room ? $routeRoom->id : (is_numeric($routeRoom) ? (int) $routeRoom : null);

        if ($routeRoomId !== null && ! $this->has('room_id')) {
            $this->merge([
                'room_id' => $routeRoomId,
            ]);
        }
    }
}
