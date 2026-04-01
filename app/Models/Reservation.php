<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'accompany_number',
        'total_price',
        'check_in',
        'check_out',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'room_id' => 'integer',
        'accompany_number' => 'integer',
        'paid_price' => 'integer',
        'check_in' => 'date',
        'check_out' => 'date',
        'status' => ReservationStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client(): BelongsTo
    {
        return $this->user();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function scopeOverlapping(Builder $query, string $checkIn, string $checkOut): Builder
    {
        return $query
            ->where('status', '!=', ReservationStatus::CANCELLED->value)
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function is_expired(): bool
    {
        return $this->status == ReservationStatus::PENDING && $this->created_at->addMinutes(10)->isPast();
    }
}
