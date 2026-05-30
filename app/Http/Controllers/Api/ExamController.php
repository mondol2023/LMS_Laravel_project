<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Exam::query();

        // Search by name or exam_id
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('exam_id', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = min($request->get('per_page', 10), 50);
        $exams = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ExamResource::collection($exams),
            'meta' => [
                'current_page' => $exams->currentPage(),
                'last_page' => $exams->lastPage(),
                'per_page' => $exams->perPage(),
                'total' => $exams->total(),
            ],
        ]);
    }

    public function show(string $examId): JsonResponse
    {
        $exam = Exam::where('exam_id', $examId)->first();

        if (! $exam) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ExamResource($exam),
        ]);
    }

    public function checkExists(string $examId): JsonResponse
    {
        $exists = Exam::where('exam_id', $examId)->exists();

        return response()->json([
            'success' => true,
            'exists' => $exists,
        ]);
    }

    public function getResults(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'exam_id' => 'required|integer',
        ]);

        $phone = $request->get('phone');
        $examId = $request->get('exam_id');

        // Get results for this phone and exam database ID
        $results = Result::query()->where('phone', $phone)
            ->where('exam_id', $examId)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'exam_id' => $examId,
                'phone' => $phone,
                'results' => $results,
            ],
        ]);
    }
}
