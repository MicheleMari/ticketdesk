<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Authenticate the user and issue an access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Le credenziali inserite non sono corrette.'],
            ]);
        }

        $token = $user->createToken(
            'ticketdesk-web',
            ['*'],
            now()->addDay()
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user->only([
                'id',
                'name',
                'email',
                'role',
                'company_id',
            ]),
        ]);
    }

    /**
     * Return the authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->only([
                'id',
                'name',
                'email',
                'role',
                'company_id',
            ]),
        ]);
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(null, 204);
    }
}