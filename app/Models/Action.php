<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $fillable = [
        'name',
        'title',
        'description',
    ];

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'device_actions')->withPivot('payload')->withTimestamps();
    }
}
