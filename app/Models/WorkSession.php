<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class WorkSession extends Model
{
    protected $table = 'work_sessions';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'device_id',
        'topic_id',
        'type',
        'started_at',
        'ended_at',
        'metric_rate_per_hour',
        'metric_consumed',
        'metric_unit',
        'metric_label',
        'metadata',
    ];

    protected $casts = [
        'started_at'           => 'datetime',
        'ended_at'             => 'datetime',
        'metric_rate_per_hour' => 'decimal:2',
        'metric_consumed'      => 'decimal:4',
        'metadata'             => 'array',
    ];

    // ─── Типи сесій ───────────────────────────────────────────────

    const TYPE_STORE_OPEN   = 'store_open';
    const TYPE_GENERATOR    = 'generator';
    const TYPE_REFRIGERATOR = 'refrigerator';
    const TYPE_SHIFT        = 'shift';
    const TYPE_SECURITY     = 'security';
    const TYPE_GENERIC      = 'generic';

    // ─── Відносини ────────────────────────────────────────────────

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    // ─── Скоупи ───────────────────────────────────────────────────

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('ended_at');
    }

    public function scopeForTopic(Builder $query, int $topicId): Builder
    {
        return $query->where('topic_id', $topicId);
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id);
    }

    public function scopeForPeriod(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('started_at', [$from, $to]);
    }

    // ─── Бізнес-логіка ────────────────────────────────────────────

    public function close(Carbon $endTime): void
    {
        $seconds = $this->started_at->diffInSeconds($endTime);
        $hours   = $seconds / 3600;

        $this->update([
            'ended_at'        => $endTime,
            'metric_consumed' => $this->metric_rate_per_hour
                ? round($this->metric_rate_per_hour * $hours, 4)
                : null,
        ]);
    }

    // ─── Атрибути ────────────────────────────────────────────────

    public function getDurationSecondsAttribute(): int
    {
        $end = $this->ended_at ?? now();
        return (int) $this->started_at->diffInSeconds($end);
    }

    public function getDurationHumanAttribute(): string
    {
        $s = $this->duration_seconds;
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        return $h > 0 ? "{$h} год {$m} хв" : "{$m} хв";
    }

    public function getIsActiveAttribute(): bool
    {
        return is_null($this->ended_at);
    }

    public function getMetricConsumedLabelAttribute(): ?string
    {
        if (is_null($this->metric_consumed)) return null;
        return $this->metric_consumed . ' ' . ($this->metric_unit ?? '');
    }

    public function getLiveMetricAttribute(): ?float
    {
        if (!$this->is_active || !$this->metric_rate_per_hour) return null;
        return round($this->metric_rate_per_hour * ($this->duration_seconds / 3600), 4);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_STORE_OPEN   => 'Відкриття магазину',
            self::TYPE_GENERATOR    => 'Генератор',
            self::TYPE_REFRIGERATOR => 'Холодильник',
            self::TYPE_SHIFT        => 'Зміна',
            self::TYPE_SECURITY     => 'Охорона',
            self::TYPE_GENERIC      => 'Загальне',
            default                 => $this->type,
        };
    }
}