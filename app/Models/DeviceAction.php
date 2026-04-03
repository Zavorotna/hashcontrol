<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceAction extends Model
{
    protected $fillable = [
        'device_id',
        'action_id',
        'payload',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function action()
    {
        return $this->belongsTo(Action::class);
    }
}
