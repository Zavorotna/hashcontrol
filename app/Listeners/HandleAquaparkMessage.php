<?php

namespace App\Listeners;

use App\Events\AquaparkMessageReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleAquaparkMessage
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AquaparkMessageReceived $event): void
    {
        //
    }
}
