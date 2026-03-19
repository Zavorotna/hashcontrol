<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'topic_id', 'dashboard_widgets',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'  => 'datetime',
        'password'           => 'hashed',
        'dashboard_widgets'  => 'array',
    ];

    const ROLE_ADMIN = 'admin';
    const ROLE_USER  = 'user';

    // Всі можливі віджети дашборду
    const WIDGETS = [
        'live_status'      => 'Живий статус магазинів',
        'generator'        => 'Стан генератора',
        'stats_today'      => 'Статистика сьогодні',
        'recent_messages'  => 'Останні MQTT повідомлення',
        'chart'            => 'Графік за тиждень',
    ];

    // Віджети за замовчуванням для нових юзерів
    const DEFAULT_WIDGETS = ['live_status', 'stats_today', 'generator'];

    // ─── Відносини ────────────────────────────────────────────────

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Магазини до яких юзер має доступ (через pivot).
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_user')->withTimestamps();
    }

    // ─── Права доступу ────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Чи має юзер доступ до конкретного магазину.
     */
    public function canAccessStore(Store $store): bool
    {
        if ($this->isAdmin()) return true;

        return $this->stores()->where('store_id', $store->id)->exists();
    }

    /**
     * Чи має юзер доступ до конкретного топіку.
     */
    public function canAccessTopic(Topic $topic): bool
    {
        if ($this->isAdmin()) return true;

        return $this->topic_id === $topic->id;
    }

    // ─── Доступні дані ────────────────────────────────────────────

    /**
     * Магазини доступні юзеру.
     * Admin → всі магазини (з фільтром по topic якщо переданий).
     * User  → тільки призначені магазини.
     */
    public function accessibleStores(?int $topicId = null)
    {
        if ($this->isAdmin()) {
            $query = Store::with('topic')->where('is_active', true);
            if ($topicId) $query->where('topic_id', $topicId);
            return $query->get();
        }

        return $this->stores()
            ->with('topic')
            ->where('stores.is_active', true)
            ->when($topicId, fn($q) => $q->where('stores.topic_id', $topicId))
            ->get();
    }

    /**
     * Топіки доступні юзеру.
     */
    public function accessibleTopics()
    {
        if ($this->isAdmin()) {
            return Topic::where('is_active', true)->get();
        }

        if ($this->topic_id) {
            return Topic::where('id', $this->topic_id)->where('is_active', true)->get();
        }

        // Топіки через призначені магазини
        $topicIds = $this->stores()->pluck('stores.topic_id')->unique();
        return Topic::whereIn('id', $topicIds)->where('is_active', true)->get();
    }

    // ─── Віджети дашборду ─────────────────────────────────────────

    /**
     * Повертає список увімкнених віджетів юзера.
     */
    public function activeWidgets(): array
    {
        return $this->dashboard_widgets ?? self::DEFAULT_WIDGETS;
    }

    public function hasWidget(string $widget): bool
    {
        return in_array($widget, $this->activeWidgets());
    }
}
