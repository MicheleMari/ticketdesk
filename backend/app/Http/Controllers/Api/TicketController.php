<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        $filters = $request->validate([
            'status' => ['sometimes', Rule::in(['open', 'in_progress', 'closed'])],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high'])],
            'category_id' => ['sometimes', 'integer'],
            'assignee_id' => ['sometimes', 'integer'],
            'q' => ['sometimes', 'string'],
            'sort' => ['sometimes', Rule::in(['created_at', 'due_date', 'priority'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $user = $request->user();

        $query = Ticket::query()
            ->with(['category', 'assignee'])
            ->where('company_id', $user->company_id);

        if ($user->role === User::ROLE_REQUESTER) {
            $query->where('creator_id', $user->id);
        }

        foreach (['status', 'priority', 'category_id', 'assignee_id'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['q'])) {
            $query->whereRaw(
                'LOWER(title) LIKE ?',
                ['%' . mb_strtolower($filters['q']) . '%']
            );
        }

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        $tickets = $query
            ->orderBy($sort, $direction)
            ->paginate($filters['per_page'] ?? 10);

        return response()->json([
            'data' => TicketResource::collection($tickets->items())->resolve(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $user = $request->user();

        $ticket = new Ticket($request->safe()->only([
            'title',
            'description',
            'priority',
            'due_date',
        ]));

        $ticket->company_id = $user->company_id;
        $ticket->creator_id = $user->id;
        $ticket->category_id = $request->validated('category_id');
        $ticket->status = 'open';

        $ticket->save();

        $ticket->load(['category', 'assignee']);

        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        $ticket->load(['category', 'assignee']);

        return new TicketResource($ticket);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        //
    }
}