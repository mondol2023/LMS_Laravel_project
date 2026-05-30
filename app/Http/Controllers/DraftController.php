<?php

namespace App\Http\Controllers;

use App\Models\Draft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DraftController extends Controller
{
    /**
     * Save a draft answer
     */
    public function save(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
                'exam_id' => 'required|string|max:50',
                'section' => 'required|in:writing,reading,listening',
                'part' => 'required|string|max:20',
                'question_key' => 'required|string|max:20',
                'answer' => 'nullable|string|max:65535',
            ]);

            $draft = Draft::saveDraft(
                $validated['phone'],
                $validated['exam_id'],
                $validated['section'],
                $validated['part'],
                $validated['question_key'],
                $validated['answer']
            );

            return response()->json([
                'success' => true,
                'message' => 'Draft saved successfully',
                'data' => [
                    'id' => $draft->id,
                    'last_saved_at' => $draft->last_saved_at->toISOString(),
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save draft',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save multiple draft answers at once
     */
    public function saveBatch(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
                'exam_id' => 'required|string|max:50',
                'section' => 'required|in:writing,reading,listening',
                'part' => 'required|string|max:20',
                'answers' => 'required|array',
                'answers.*' => 'nullable|string|max:65535',
            ]);

            $savedDrafts = [];
            foreach ($validated['answers'] as $questionKey => $answer) {
                $draft = Draft::saveDraft(
                    $validated['phone'],
                    $validated['exam_id'],
                    $validated['section'],
                    $validated['part'],
                    $questionKey,
                    $answer
                );
                $savedDrafts[] = [
                    'question_key' => $questionKey,
                    'id' => $draft->id,
                    'last_saved_at' => $draft->last_saved_at->toISOString(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Drafts saved successfully',
                'data' => $savedDrafts,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save drafts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all drafts for a specific phone and exam
     */
    public function getDrafts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
                'exam_id' => 'required|string|max:50',
            ]);

            $drafts = Draft::getDrafts($validated['phone'], $validated['exam_id']);

            return response()->json([
                'success' => true,
                'data' => $drafts,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get drafts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get drafts for a specific section
     */
    public function getSectionDrafts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
                'exam_id' => 'required|string|max:50',
                'section' => 'required|in:writing,reading,listening',
            ]);

            $drafts = Draft::getSectionDrafts(
                $validated['phone'],
                $validated['exam_id'],
                $validated['section']
            );

            return response()->json([
                'success' => true,
                'data' => $drafts,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get section drafts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all drafts for a specific phone and exam
     */
    public function clearDrafts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
                'exam_id' => 'required|string|max:50',
            ]);

            $cleared = Draft::clearDrafts($validated['phone'], $validated['exam_id']);

            return response()->json([
                'success' => true,
                'message' => $cleared ? 'Drafts cleared successfully' : 'No drafts found to clear',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear drafts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear drafts for a specific section
     */
    public function clearSectionDrafts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
                'exam_id' => 'required|string|max:50',
                'section' => 'required|in:writing,reading,listening',
            ]);

            $cleared = Draft::clearSectionDrafts(
                $validated['phone'],
                $validated['exam_id'],
                $validated['section']
            );

            return response()->json([
                'success' => true,
                'message' => $cleared ? 'Section drafts cleared successfully' : 'No drafts found to clear',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear section drafts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
