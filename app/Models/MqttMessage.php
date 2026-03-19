<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MqttMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic', 'device_id', 'payload', 'processed', 'error', 'received_at',
    ];

    protected $casts = [
        'processed'   => 'boolean',
        'received_at' => 'datetime',
    ];
}
