<?php

// app/Models/Floor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Floor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'number', 'created_by', 'managed_by'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    protected static function booted(): void
    {
        parent::booted();
        static::creating(function (Floor $floor) {
            if (!$floor->name) {
                $floor->name = 'FLR-' . strtoupper(Str::random(3));
            }
            if (!$floor->number) {
                $floor->number = 'F-' . mt_rand(100, 999);
            }
        });
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }
}
