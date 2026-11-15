<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TicketStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(['open', 'in_progress', 'closed']),
            ],
        ]);

        $currentStatus = $ticket->status;
        $newStatus = $data['status'];

        $allowedTransitions = [
            'open' => ['in_progress', 'closed'],
            'in_progress' => ['closed'],
            'closed' => ['open'],
        ];

        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
            abort(409, 'Transizione di stato non consentita.');
        }

        if ($currentStatus === 'closed') {
            Gate::authorize('reopen', $ticket);
        } else {
            Gate::authorize('update', $ticket);
        }

        $ticket->status = $newStatus;

        if ($newStatus === 'closed') {
            $ticket->closed_at = now();
        } elseif ($newStatus === 'open') {
            $ticket->closed_at = null;
        }

        $ticket->save();
        $ticket->load(['category', 'assignee']);

        return new TicketResource($ticket);
    }
}