<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id', 'mqtt_device_id', 'name', 'employee_name', 'location', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Відносини ────────────────────────────────────────────────

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Всі сесії цього магазину (поліморфний зв'язок).
     */
    public function workSessions(): MorphMany
    {
        return $this->morphMany(WorkSession::class, 'subject');
    }
        public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user')->withTimestamps();
    }
        // ─── Хелпери ──────────────────────────────────────────────────

    /**
     * Активна (незакрита) сесія магазину.
     */
    public function activeSession(): ?WorkSession
    {
        return $this->workSessions()
            ->ofType(WorkSession::TYPE_STORE_OPEN)
            ->active()
            ->latest('started_at')
            ->first();
    }

    /**
     * Чи відкритий магазин зараз.
     */
    public function isOpen(): bool
    {
        return $this->activeSession() !== null;
    }

    /**
     * Загальний час роботи за період (секунди).
     */
    public function totalWorkSeconds(?string $from = null, ?string $to = null): int
    {
        $query = $this->workSessions()
            ->ofType(WorkSession::TYPE_STORE_OPEN)
            ->completed();

        if ($from) $query->where('started_at', '>=', $from);
        if ($to)   $query->where('ended_at',   '<=', $to);

        return (int) $query->get()->sum('duration_seconds');
    }
}