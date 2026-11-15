<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TicketAssignmentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Ticket $ticket): TicketResource
    {
        Gate::authorize('assign', $ticket);

        $data = $request->validate([
            'assignee_id' => [
                'present',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    'company_id',
                    $request->user()->company_id
                ),
            ],
        ]);

        $user = $request->user();
        $assigneeId = $data['assignee_id'];

        if (
            $user->role === User::ROLE_SUPERVISOR
            && $assigneeId !== $user->id
        ) {
            abort(403, 'Puoi assegnare il ticket soltanto a te stesso.');
        }

        $ticket->assignee_id = $assigneeId;
        $ticket->save();

        $ticket->load(['category', 'assignee']);

        return new TicketResource($ticket);
    }
}