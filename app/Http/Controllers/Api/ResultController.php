<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function storeSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'result_id' => 'required|integer|exists:results,id',
            'section' => 'required|string|in:listening,reading,writing',
            'section_data' => 'required|array',
        ]);

        $result = Result::findOrFail($validated['result_id']);

        // Check if section already submitted
        $existingSectionData = $result->{$validated['section']};
        if (is_array($existingSectionData) && ! empty($existingSectionData)) {
            if (isset($existingSectionData['completed_at']) || isset($existingSectionData['submitted'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This section has already been submitted.',
                ], 422);
            }
        }

        // Store section data
        $sectionData = $validated['section_data'];
        $sectionData['submitted'] = true;
        $sectionData['completed_at'] = now()->toIso8601String();

        $result->{$validated['section']} = $sectionData;
        $result->save();

        // Increment subscription usage counter
        $user = $result->user;
        if ($user) {
            if ($result->exam_type === 'full') {
                // For full mock test, increment only when ALL sections are completed
                $allSectionsCompleted = $this->checkAllSectionsCompleted($result);
                if ($allSectionsCompleted) {
                    $user->incrementUsage('full_mock_test');
                }
            } elseif ($result->exam_type === 'partial') {
                // For partial mock test, increment for each module when submitted
                $usageType = match ($validated['section']) {
                    'reading' => 'partial_reading',
                    'writing' => 'partial_writing',
                    'listening' => 'partial_listening',
                    default => null,
                };

                if ($usageType) {
                    $user->incrementUsage($usageType);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Section submitted successfully',
        ]);
    }

    /**
     * Check if all sections in the exam are completed
     */
    private function checkAllSectionsCompleted(Result $result): bool
    {
        $modules = $result->modules ?? [];

        foreach ($modules as $module) {
            $sectionData = $result->{$module};

            // Check if section is not completed
            if (! is_array($sectionData) || empty($sectionData)) {
                return false;
            }

            if (! isset($sectionData['completed_at']) && ! isset($sectionData['submitted'])) {
                return false;
            }
        }

        return true;
    }

    public function getResult(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'result_id' => 'required|integer|exists:results,id',
        ]);

        $result = Result::findOrFail($validated['result_id']);

        return response()->json([
            'success' => true,
            'result' => [
                'listening' => $result->listening,
                'reading' => $result->reading,
                'writing' => $result->writing,
                'speaking' => $result->speaking,
            ],
        ]);
    }
}
