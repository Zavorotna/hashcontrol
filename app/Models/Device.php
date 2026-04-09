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
        'is_on_off',
    ];

    protected $casts = [
        'is_range_start' => 'boolean', // null=single record, true=range start, false=range end
        'is_on_off'      => 'boolean', // true=device sends on/off signals (generator, relay, etc.)
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

    public function trackedObjects()
    {
        return $this->belongsToMany(TrackedObject::class, 'device_tracked_object')->withTimestamps();
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
