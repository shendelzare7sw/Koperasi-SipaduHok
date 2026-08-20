<?php

namespace App\Http\Controllers;

use App\Models\IndonesiaRegion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndonesiaRegionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent' => ['nullable', 'string', 'max:13', 'regex:/^\d{2}(?:\.\d{2}){0,2}$/'],
            'level' => ['nullable', 'integer', Rule::in([IndonesiaRegion::PROVINCE])],
        ]);

        $query = IndonesiaRegion::query()
            ->select(['code', 'name', 'postal_code'])
            ->orderBy('name');

        if (filled($validated['parent'] ?? null)) {
            $query->where('parent_code', $validated['parent']);
        } else {
            $query->where('level', IndonesiaRegion::PROVINCE);
        }

        return response()->json([
            'data' => $query->get(),
        ])->header('Cache-Control', 'private, max-age=86400');
    }
}
