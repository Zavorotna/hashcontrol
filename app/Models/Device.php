<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'device_id',
        'user_id',
        'company_id',
        'name',
        'is_range_start',
    ];

    protected $casts = [
        'is_range_start' => 'boolean', // null=одиничний, true=початок, false=кінець
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function deviceActions()
    {
        return $this->hasMany(DeviceAction::class);
    }

    public function actions()
    {
        return $this->belongsToMany(Action::class, 'device_actions')->withPivot('payload')->withTimestamps();
    }

    public function blacklisted()
    {
        return $this->hasOne(\App\Models\BlacklistedDevice::class, 'device_id', 'device_id')->withTrashed();
    }

    public function isBlacklisted(): bool
    {
        return $this->blacklisted()->exists();
    }
}
