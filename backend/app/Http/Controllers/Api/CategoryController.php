<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Category::class);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')
                    ->where('company_id', $request->user()->company_id),
            ],
            'color' => [
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        $category = new Category($data);
        $category->company_id = $request->user()->company_id;
        $category->save();

        return response()->json([
            'data' => $category->only(['id', 'name', 'color']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): JsonResponse
    {
        Gate::authorize('view', $category);

        return response()->json([
            'data' => $category->only(['id', 'name', 'color']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        Gate::authorize('update', $category);

        $data = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')
                    ->where('company_id', $request->user()->company_id)
                    ->ignore($category->id),
            ],
            'color' => [
                'sometimes',
                'required',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        $category->update($data);

        return response()->json([
            'data' => $category->only(['id', 'name', 'color']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        Gate::authorize('delete', $category);

        if ($category->tickets()->exists()) {
            return response()->json([
                'message' => 'Impossibile eliminare una categoria associata a dei ticket.',
            ], 409);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}