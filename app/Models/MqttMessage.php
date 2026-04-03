<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MqttMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'topic',
        'payload',
        'device_id',
        'action',
        'data',
    ];

    public function isRegistered()
    {
        return Device::where('device_id', $this->device_id)->exists();
    }
}
