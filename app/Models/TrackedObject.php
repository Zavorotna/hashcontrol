<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TrackedObject extends Model
{
    protected $fillable = [
        'external_id',
        'company_id',
        'name',
        'type',
        'tenant_name',
        'email',
        'phone',
        'address',
        'notes',
    ];

    // ─── Відносини ──────────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Всі DeviceLog записи де data = external_id цього об'єкта
    public function logs()
    {
        return DeviceLog::where('data', $this->external_id);
    }

    // ─── Скоупи ─────────────────────────────────────────────────────────────────

    public function scopeShops(Builder $query): Builder
    {
        return $query->where('type', 'shop');
    }

    public function scopeGenerators(Builder $query): Builder
    {
        return $query->where('type', 'generator');
    }

    // ─── Статистика для одного об'єкта ──────────────────────────────────────────

    /**
     * Кількість подій за вказаний період.
     */
    public function eventCount(\Carbon\Carbon $from, \Carbon\Carbon $to): int
    {
        return DeviceLog::where('data', $this->external_id)
            ->whereBetween('logged_at', [$from, $to])
            ->count();
    }

    /**
     * Кількість подій цього об'єкта в ті дні, коли працював генератор.
     *
     * Логіка: вважаємо генератор «активним» у кожен день, де є хоча б один
     * його лог. Потім рахуємо події цього об'єкта лише в ці дні.
     *
     * @param  \Illuminate\Support\Collection  $generatorObjects  колекція TrackedObject типу generator
     */
    public function eventsDuringGenerator(
        \Carbon\Carbon $from,
        \Carbon\Carbon $to,
        \Illuminate\Support\Collection $generatorObjects
    ): int {
        if ($generatorObjects->isEmpty()) {
            return 0;
        }

        // Дні коли хоч один генератор мав активність
        $generatorDays = DeviceLog::whereIn('data', $generatorObjects->pluck('external_id'))
            ->whereBetween('logged_at', [$from, $to])
            ->selectRaw('DATE(logged_at) as day')
            ->distinct()
            ->pluck('day');

        if ($generatorDays->isEmpty()) {
            return 0;
        }

        return DeviceLog::where('data', $this->external_id)
            ->whereBetween('logged_at', [$from, $to])
            ->whereRaw('DATE(logged_at) IN (' . implode(',', array_fill(0, $generatorDays->count(), '?')) . ')', $generatorDays->toArray())
            ->count();
    }
}