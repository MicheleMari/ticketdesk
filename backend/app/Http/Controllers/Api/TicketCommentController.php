<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Ticket $ticket): JsonResponse
    {
        Gate::authorize('view', $ticket);

        $comments = $ticket->comments()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $comments->map(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                ],
                'created_at' => $comment->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        Gate::authorize('view', $ticket);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment = $ticket->comments()->make([
            'body' => $data['body'],
        ]);

        $comment->user_id = $request->user()->id;
        $comment->save();

        $comment->load('user:id,name');

        return response()->json([
            'data' => [
                'id' => $comment->id,
                'body' => $comment->body,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                ],
                'created_at' => $comment->created_at->toIso8601String(),
            ],
        ], 201);
    }
}