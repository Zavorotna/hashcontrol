<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'has_generator', 'is_active',
        // fuel_rate_per_hour видалено — тепер зберігається на Device
    ];

    protected $casts = [
        'has_generator' => 'boolean',
        'is_active'     => 'boolean',
    ];

    // ─── Відносини ────────────────────────────────────────────────

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function mqttMessages(): HasMany
    {
        return $this->hasMany(MqttMessage::class, 'topic', 'slug');
    }

    /**
     * Всі сесії цього топіку (поліморфний зв'язок).
     */
    public function workSessions(): MorphMany
    {
        return $this->morphMany(WorkSession::class, 'subject');
    }

    // ─── Хелпери ──────────────────────────────────────────────────

    /**
     * Активна сесія вказаного типу.
     * Наприклад: $topic->activeSession('generator')
     */
    public function activeSession(string $type): ?WorkSession
    {
        return $this->workSessions()
            ->ofType($type)
            ->active()
            ->latest('started_at')
            ->first();
    }

    /**
     * Загальний час за типом і періодом (в секундах).
     */
    public function totalSeconds(string $type, $from = null, $to = null): int
    {
        $query = $this->workSessions()->ofType($type)->completed();

        if ($from) $query->where('started_at', '>=', $from);
        if ($to)   $query->where('ended_at',   '<=', $to);

        return (int) $query->get()->sum('duration_seconds');
    }

    /**
     * Загальна метрика за типом і періодом.
     * Наприклад: літри пального, кВт·год тощо.
     */
    public function totalMetric(string $type, $from = null, $to = null): float
    {
        $query = $this->workSessions()->ofType($type)->completed();

        if ($from) $query->where('started_at', '>=', $from);
        if ($to)   $query->where('ended_at',   '<=', $to);

        return (float) $query->sum('metric_consumed');
    }

    /**
     * Синхронізує топіки з таблиці mqtt_messages.
     * Викликається з TopicController::index().
     */
    public static function syncFromMqttMessages(): void
    {
        $slugs = MqttMessage::query()
            ->select('topic')
            ->distinct()
            ->pluck('topic');

        foreach ($slugs as $slug) {
            self::firstOrCreate(
                ['slug' => $slug],
                [
                    'name'          => ucfirst($slug),
                    'description'   => "Топік {$slug} з mqtt_messages",
                    'has_generator' => MqttMessage::where('topic', $slug)
                        ->where('device_id', 'like', 'GEN_%')
                        ->exists(),
                    'is_active'     => true,
                ]
            );
        }
    }
}