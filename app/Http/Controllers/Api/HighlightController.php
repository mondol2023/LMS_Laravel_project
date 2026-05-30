<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Highlight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HighlightController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'exam_id' => 'required|string',
            'section' => 'required|string|in:reading,listening,writing',
            'part' => 'required|string',
        ]);

        $highlights = Highlight::getHighlights(
            $request->get('phone'),
            $request->get('exam_id'),
            $request->get('section'),
            $request->get('part')
        );

        return response()->json([
            'success' => true,
            'data' => $highlights,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'exam_id' => 'required|string',
            'section' => 'required|string|in:reading,listening,writing',
            'part' => 'required|string',
            'text' => 'required|string',
            'color' => 'required|string|in:yellow,green,blue,pink,orange',
            'start_offset' => 'required|integer|min:0',
            'end_offset' => 'required|integer|min:0',
        ]);

        $highlight = Highlight::saveHighlight(
            $request->get('phone'),
            $request->get('exam_id'),
            $request->get('section'),
            $request->get('part'),
            $request->get('text'),
            $request->get('color'),
            $request->get('start_offset'),
            $request->get('end_offset')
        );

        return response()->json([
            'success' => true,
            'message' => 'Highlight saved successfully',
            'data' => $highlight,
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $highlight = Highlight::where('id', $id)
            ->where('phone', $request->get('phone'))
            ->first();

        if (! $highlight) {
            return response()->json([
                'success' => false,
                'message' => 'Highlight not found',
            ], 404);
        }

        $highlight->delete();

        return response()->json([
            'success' => true,
            'message' => 'Highlight deleted successfully',
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'exam_id' => 'required|string',
            'section' => 'required|string|in:reading,listening,writing',
            'part' => 'required|string',
        ]);

        Highlight::clearHighlights(
            $request->get('phone'),
            $request->get('exam_id'),
            $request->get('section'),
            $request->get('part')
        );

        return response()->json([
            'success' => true,
            'message' => 'All highlights cleared successfully',
        ]);
    }
}
