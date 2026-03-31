<?php

namespace App\Listeners;

use App\Events\ClientApproved;
use App\Notifications\AccountApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendClientApprovedNotification implements ShouldQueue
{
    public bool $afterCommit = true;

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
        $event->client->notify(new AccountApprovedNotification);
    }
}
