<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of users belonging to the authenticated user's company.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return response()->json([
            'data' => $users,
        ]);
    }
}