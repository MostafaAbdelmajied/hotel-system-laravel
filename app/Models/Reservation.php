<?php

namespace App\Models;

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
        'paid_price',
        'check_in',
        'check_out',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'room_id' => 'integer',
        'accompany_number' => 'integer',
        'paid_price' => 'integer',
        'check_in' => 'date',
        'check_out' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn);
    }
}
