<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\HeaderAnalyzerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HeaderAnalyzerController extends Controller
{
        public function __construct(
        private HeaderAnalyzerService $analyzer
    ) {}

    public function analyze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'url', 'max:500'],
        ]);

        try {
            $result = $this->analyzer->analyze($validated['url']);
            return response()->json($result);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo conectar con la URL proporcionada.'
            ], 500);
        }
    }
}
