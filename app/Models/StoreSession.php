<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id', 'opened_at', 'closed_at', 'duration_seconds', 'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Закрити сесію — встановити closed_at і обрахувати тривалість.
     */
    public function close(Carbon $closedAt = null): void
    {
        $closedAt = $closedAt ?? now();

        $this->update([
            'closed_at'        => $closedAt,
            'duration_seconds' => $closedAt->diffInSeconds($this->opened_at),
        ]);
    }

    /**
     * Тривалість у форматі "2 год 15 хв".
     */
    public function getDurationHumanAttribute(): string
    {
        if (!$this->duration_seconds) {
            return 'в процесі';
        }

        $hours   = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);

        return $hours > 0
            ? "{$hours} год {$minutes} хв"
            : "{$minutes} хв";
    }

    public function isOpen(): bool
    {
        return is_null($this->closed_at);
    }
}
