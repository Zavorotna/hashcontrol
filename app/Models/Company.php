<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('position')->withTimestamps();
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
