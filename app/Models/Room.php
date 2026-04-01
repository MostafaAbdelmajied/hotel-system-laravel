<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use App\Policies\RoomPolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(RoomPolicy::class)]
class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'capacity',
        'price',
        'floor_id',
        'created_by',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'price' => 'integer',
        'floor_id' => 'integer',
        'created_by' => 'integer',
    ];

    protected $appends = [
        'price_in_dollars',
    ];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeAvailableBetween(Builder $query, string|CarbonInterface $checkIn, string|CarbonInterface $checkOut): Builder
    {
        $checkInDate = $checkIn instanceof CarbonInterface ? $checkIn->toDateString() : $checkIn;
        $checkOutDate = $checkOut instanceof CarbonInterface ? $checkOut->toDateString() : $checkOut;

        return $query->whereDoesntHave('reservations', function (Builder $reservationQuery) use ($checkInDate, $checkOutDate) {
            $reservationQuery->overlapping($checkInDate, $checkOutDate);
        });
    }

    public function isAvailableBetween(string|CarbonInterface $checkIn, string|CarbonInterface $checkOut): bool
    {
        $checkInDate = $checkIn instanceof CarbonInterface ? $checkIn->toDateString() : $checkIn;
        $checkOutDate = $checkOut instanceof CarbonInterface ? $checkOut->toDateString() : $checkOut;

        return ! $this->reservations()
            ->overlapping($checkInDate, $checkOutDate)
            ->where('status', ReservationStatus::CONFIRMED)
            ->orWhere(function (Builder $query) use ($checkInDate, $checkOutDate) {
                $query->where('status', ReservationStatus::PENDING)
                    ->where('created_at', '>=', now()->subMinutes(30));
            })
            ->exists();
    }

    public function getPriceInDollarsAttribute(): string
    {
        return number_format($this->price / 100, 2, '.', '');
    }

    public function hasActiveReservation(): bool
    {
        return $this->reservations()
            ->where('check_out', '>', now())
            ->exists();
    }
}
