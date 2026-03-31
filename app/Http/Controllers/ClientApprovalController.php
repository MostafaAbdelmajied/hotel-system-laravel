<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Events\ClientApproved;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientApprovalController extends Controller
{
    public function update(Request $request, User $client): RedirectResponse
    {
        $this->authorize('approveClient', $client);

        $updated = User::query()
            ->whereKey($client->id)
            ->where('status', UserStatus::Pending)
            ->update([
                'status' => UserStatus::Approved,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

        if ($updated === 0) {
            return back()->with('warning', 'Client has already been approved.');
        }

        $client->refresh();

        event(new ClientApproved($client));

        return back()->with('success', 'Client approved successfully.');
    }
}
