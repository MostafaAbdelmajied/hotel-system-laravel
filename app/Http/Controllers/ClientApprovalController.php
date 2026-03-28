<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Events\ClientApproved;
use App\Models\User;
use App\Notifications\AccountApprovedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientApprovalController extends Controller
{
    public function update(Request $request, User $client): RedirectResponse
    {
        $this->authorize('approveClient', $client);

        $client->update([
            'status' => UserStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        event(new ClientApproved($client));

        return back()->with('success', 'Client approved successfully.');
    }
}
