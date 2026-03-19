<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GeneratorSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id', 'started_at', 'stopped_at', 'duration_seconds',
        'fuel_consumed_total', 'notes',
    ];

    protected $casts = [
        'started_at'          => 'datetime',
        'stopped_at'          => 'datetime',
        'fuel_consumed_total' => 'decimal:2',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Зупинити генератор і обрахувати витрату пального.
     */
    public function stop(\Carbon\Carbon $stoppedAt = null): void
{
    $stoppedAt = $stoppedAt ?? now();

    // Забезпечуємо, що started_at і stopped_at це Carbon об'єкти
    $startedAt = $this->started_at instanceof Carbon ? $this->started_at : Carbon::parse($this->started_at);
    $stoppedAt = $stoppedAt instanceof Carbon ? $stoppedAt : Carbon::parse($stoppedAt);

    // Використовуємо абсолютну різницю, щоб уникнути негативу
    $durationSeconds = max(0, $stoppedAt->timestamp - $startedAt->timestamp);

    $fuelConsumed = 0;
    if ($this->topic && $durationSeconds > 0) {
        $hours = $durationSeconds / 3600;
        $fuelConsumed = round($hours * $this->topic->fuel_rate_per_hour, 2);
    }

    $this->update([
        'stopped_at'          => $stoppedAt,
        'duration_seconds'    => $durationSeconds,
        'fuel_consumed_total' => $fuelConsumed,
    ]);
}

    public function getDurationHumanAttribute(): string
    {
        if (!$this->duration_seconds) {
            return 'працює';
        }

        $hours   = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);

        return $hours > 0
            ? "{$hours} год {$minutes} хв"
            : "{$minutes} хв";
    }

    public function isRunning(): bool
    {
        return is_null($this->stopped_at);
    }
}
