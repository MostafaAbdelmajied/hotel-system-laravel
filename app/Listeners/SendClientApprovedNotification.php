<?php

namespace App\Listeners;

use App\Events\ClientApproved;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendClientApprovedNotification implements ShouldQueue
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
    public function handle(ClientApproved $event): void
    {
        $event->client->notify(new \App\Notifications\AccountApprovedNotification);
    }
}
