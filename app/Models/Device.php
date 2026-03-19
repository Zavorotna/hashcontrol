<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'store_id',
        'mqtt_device_id',
        'name',
        'is_active',

        // Тип події
        'type',

        // Тип сесії яку створює цей пристрій
        'session_type',

        // Метрика
        'metric_rate_per_hour',
        'metric_unit',
        'metric_label',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'metric_rate_per_hour' => 'decimal:2',
    ];

    // ─── Типи подій (що надходить з MQTT) ────────────────────────

    const TYPE_READER_OPEN   = 'reader_open';
    const TYPE_READER_CLOSE  = 'reader_close';
    const TYPE_GENERATOR_ON  = 'generator_on';
    const TYPE_GENERATOR_OFF = 'generator_off';
    const TYPE_GENERIC_ON    = 'generic_on';   // для будь-якого нового типу
    const TYPE_GENERIC_OFF   = 'generic_off';

    // ─── Типи сесій (що записується в work_sessions) ─────────────

    const SESSION_TYPE_STORE_OPEN   = 'store_open';
    const SESSION_TYPE_GENERATOR    = 'generator';
    const SESSION_TYPE_REFRIGERATOR = 'refrigerator';
    const SESSION_TYPE_SHIFT        = 'shift';
    const SESSION_TYPE_SECURITY     = 'security';
    const SESSION_TYPE_GENERIC      = 'generic';

    // ─── Відносини ────────────────────────────────────────────────

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function workSessions(): HasMany
    {
        return $this->hasMany(WorkSession::class, 'device_id');
    }

    // ─── Хелпери: тип події ───────────────────────────────────────

    /**
     * Чи запускає цей пристрій нову сесію.
     */
    public function isStartEvent(): bool
    {
        return in_array($this->type, [
            self::TYPE_READER_OPEN,
            self::TYPE_GENERATOR_ON,
            self::TYPE_GENERIC_ON,
        ]);
    }

    /**
     * Чи зупиняє цей пристрій активну сесію.
     */
    public function isStopEvent(): bool
    {
        return in_array($this->type, [
            self::TYPE_READER_CLOSE,
            self::TYPE_GENERATOR_OFF,
            self::TYPE_GENERIC_OFF,
        ]);
    }

    public function isGeneratorDevice(): bool
    {
        return in_array($this->type, [self::TYPE_GENERATOR_ON, self::TYPE_GENERATOR_OFF]);
    }

    public function isStoreDevice(): bool
    {
        return in_array($this->type, [self::TYPE_READER_OPEN, self::TYPE_READER_CLOSE]);
    }

    // ─── Subject для сесії ────────────────────────────────────────

    /**
     * Повертає об'єкт до якого прив'язується сесія.
     * Рідери → Store, решта → Topic.
     */
    public function sessionSubject(): Model|null
    {
        if ($this->isStoreDevice()) {
            return $this->store;
        }

        return $this->topic;
    }

    // ─── Лейбли для UI ───────────────────────────────────────────

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_READER_OPEN   => 'Рідер (відкриття)',
            self::TYPE_READER_CLOSE  => 'Рідер (закриття)',
            self::TYPE_GENERATOR_ON  => 'Генератор (увімкнення)',
            self::TYPE_GENERATOR_OFF => 'Генератор (вимкнення)',
            self::TYPE_GENERIC_ON    => 'Пристрій (увімкнення)',
            self::TYPE_GENERIC_OFF   => 'Пристрій (вимкнення)',
            default                  => $this->type,
        };
    }

    public function getSessionTypeLabel(): string
    {
        return match($this->session_type) {
            self::SESSION_TYPE_STORE_OPEN   => 'Відкриття магазину',
            self::SESSION_TYPE_GENERATOR    => 'Генератор',
            self::SESSION_TYPE_REFRIGERATOR => 'Холодильник',
            self::SESSION_TYPE_SHIFT        => 'Зміна',
            self::SESSION_TYPE_SECURITY     => 'Охорона',
            self::SESSION_TYPE_GENERIC      => 'Загальне',
            default                         => $this->session_type,
        };
    }
}