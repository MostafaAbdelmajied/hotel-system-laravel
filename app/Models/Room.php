<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function getPriceInDollarsAttribute(): string
    {
        return number_format($this->price / 100, 2, '.', '');
    }
}
