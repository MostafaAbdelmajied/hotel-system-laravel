<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
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

        return back()->with('success', 'Client approved successfully.');
    }
}
