<template>
    <AppLayout>
        <div class="min-h-screen bg-slate-50 p-2">
            <div class="mx-auto max-w-7xl">
                <!-- Header -->
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">{{ exam.name }}</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Exam ID: <code class="bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-700">{{ exam.exam_id }}</code> &middot;
                            {{ results.total || results.data?.length || 0 }} submissions
                        </p>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="overflow-hidden border border-slate-200 bg-white">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Student</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold tracking-wide text-slate-500 uppercase">L</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold tracking-wide text-slate-500 uppercase">R</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold tracking-wide text-slate-500 uppercase">W</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold tracking-wide text-slate-500 uppercase">S</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold tracking-wide text-slate-500 uppercase">Overall</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold tracking-wide text-slate-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(result, resultIndex) in results.data" :key="result.id" class="transition-colors hover:bg-slate-50">
                                    <!-- Row Number -->
                                    <td class="px-4 py-3 text-sm text-slate-400">
                                        {{ (results.current_page - 1) * results.per_page + resultIndex + 1 }}
                                    </td>

                                    <!-- Student Info -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-8 w-8 shrink-0 items-center justify-center bg-indigo-100 text-xs font-bold text-indigo-600"
                                            >
                                                {{ getInitials(result.username) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-semibold text-slate-900">{{ result.username }}</div>
                                                <div class="truncate text-xs text-slate-500">
                                                    <span v-if="result.student?.roll_number">{{ result.student.roll_number }} &middot; </span>
                                                    {{ result.email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Exam Type -->
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center px-2 py-1 text-xs font-medium"
                                            :class="result.exam_type === 'full' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                        >
                                            {{ result.exam_type === 'full' ? 'Full' : 'Partial' }}
                                        </span>
                                    </td>

                                    <!-- Listening -->
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            v-if="result.listening"
                                            @click="openAnswersModal('listening', result)"
                                            class="inline-flex min-w-[2.5rem] items-center justify-center bg-emerald-50 px-2 py-1 text-sm font-bold text-emerald-700 transition-colors hover:bg-emerald-100"
                                            :title="`Listening: ${getSectionRawScore('listening', result)}`"
                                        >
                                            {{ getSectionBandScore('listening', result) }}
                                        </button>
                                        <span v-else class="text-sm text-slate-300">-</span>
                                    </td>

                                    <!-- Reading -->
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            v-if="result.reading"
                                            @click="openAnswersModal('reading', result)"
                                            class="inline-flex min-w-[2.5rem] items-center justify-center bg-blue-50 px-2 py-1 text-sm font-bold text-blue-700 transition-colors hover:bg-blue-100"
                                            :title="`Reading: ${getSectionRawScore('reading', result)}`"
                                        >
                                            {{ getSectionBandScore('reading', result) }}
                                        </button>
                                        <span v-else class="text-sm text-slate-300">-</span>
                                    </td>

                                    <!-- Writing -->
                                    <td class="px-4 py-3 text-center">
                                        <div v-if="result.writing" class="inline-flex items-center gap-1">
                                            <button
                                                @click="openAnswersModal('writing', result)"
                                                class="inline-flex min-w-[2.5rem] items-center justify-center bg-violet-50 px-2 py-1 text-sm font-bold text-violet-700 transition-colors hover:bg-violet-100"
                                            >
                                                {{ getSectionBandScore('writing', result) }}
                                            </button>
                                            <button
                                                v-if="isAdmin"
                                                @click="openWritingEditModal(result)"
                                                class="p-0.5 text-slate-400 hover:text-violet-600"
                                                title="Edit writing score"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                        <span v-else class="text-sm text-slate-300">-</span>
                                    </td>

                                    <!-- Speaking -->
                                    <td class="px-4 py-3 text-center">
                                        <!-- Inline edit mode: shown when admin clicks edit OR when score is missing/0 -->
                                        <div
                                            v-if="isAdmin && (inlineEditingSpeaking === result.id || !hasSpeakingScore(result))"
                                            class="inline-flex items-center gap-1"
                                        >
                                            <input
                                                v-model="inlineSpeakingBand"
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                max="9"
                                                class="w-14 border border-slate-300 px-1 py-0.5 text-center text-sm font-bold focus:border-amber-400 focus:outline-none"
                                                placeholder="-"
                                                @focus="onSpeakingInputFocus(result)"
                                            />
                                            <button
                                                @click="saveInlineSpeaking(result)"
                                                class="p-0.5 text-emerald-600 hover:text-emerald-800"
                                                title="Save"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <button
                                                v-if="hasSpeakingScore(result)"
                                                @click="cancelInlineSpeaking"
                                                class="p-0.5 text-slate-400 hover:text-red-500"
                                                title="Cancel"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <!-- Display mode: only when score exists and > 0 -->
                                        <div v-else-if="hasSpeakingScore(result)" class="inline-flex items-center gap-1">
                                            <span
                                                class="inline-flex min-w-[2.5rem] items-center justify-center bg-amber-50 px-2 py-1 text-sm font-bold text-amber-700"
                                            >
                                                {{ getSectionBandScore('speaking', result) }}
                                            </span>
                                            <button
                                                v-if="isAdmin"
                                                @click="startInlineSpeaking(result)"
                                                class="p-0.5 text-slate-400 hover:text-amber-600"
                                                title="Edit speaking score"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                        <!-- Non-admin, no score -->
                                        <span v-else class="text-sm text-slate-300">-</span>
                                    </td>

                                    <!-- Overall -->
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            v-if="getOverallScore(result)"
                                            class="inline-flex min-w-[2.5rem] items-center justify-center bg-indigo-100 px-2 py-1 text-sm font-bold text-indigo-700"
                                        >
                                            {{ getOverallScore(result) }}
                                        </span>
                                        <span v-else class="text-sm text-slate-300">-</span>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-4 py-3 text-sm whitespace-nowrap text-slate-500">
                                        {{ formatDate(result.created_at) }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link
                                                :href="`/results/${result.id}/details`"
                                                class="inline-flex items-center gap-1 bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-200"
                                            >
                                                Details
                                            </Link>
                                            <button
                                                v-if="isAdmin"
                                                @click="confirmDelete(result)"
                                                class="inline-flex items-center p-1.5 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                                title="Delete"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="results.data.length === 0" class="px-6 py-16 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">No results yet</h3>
                        <p class="mt-1 text-sm text-slate-500">No students have submitted results for this exam.</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="results.last_page > 1" class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="hidden text-sm text-slate-500 sm:block">
                            Showing <span class="font-medium text-slate-700">{{ results.from }}</span> to
                            <span class="font-medium text-slate-700">{{ results.to }}</span> of
                            <span class="font-medium text-slate-700">{{ results.total }}</span>
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-if="results.prev_page_url"
                                :href="results.prev_page_url"
                                class="inline-flex items-center gap-1 border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="results.next_page_url"
                                :href="results.next_page_url"
                                class="inline-flex items-center gap-1 border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
                            >
                                Next
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Speaking Score Edit Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" @click="closeEditModal"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div
                    class="relative inline-block transform overflow-hidden rounded-2xl bg-white px-6 pt-6 pb-6 text-left align-bottom shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-8 sm:align-middle"
                >
                    <div>
                        <div
                            class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-100 to-indigo-100 shadow-sm"
                        >
                            <svg class="h-8 w-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 715 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="mt-4 text-center sm:mt-6">
                            <h3 class="text-xl leading-6 font-bold text-slate-900" id="modal-title">Edit Speaking Score</h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-600">
                                    Update speaking band score for <strong>{{ editingResult?.username }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="mt-6 space-y-6">
                        <!-- Speaking Band Score -->
                        <div>
                            <label for="speaking_band" class="block text-sm font-bold text-slate-700"> Speaking Band Score </label>
                            <input
                                id="speaking_band"
                                v-model="editForm.speaking_band"
                                type="number"
                                step="0.5"
                                min="0"
                                max="9"
                                class="mt-1 block w-full rounded-xl border-2 border-slate-300 px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                placeholder="0.0 - 9.0"
                            />
                        </div>

                        <!-- Teacher Feedback -->
                        <div>
                            <label for="speaking_feedback" class="block text-sm font-bold text-slate-700"> Teacher Feedback </label>
                            <textarea
                                id="speaking_feedback"
                                ref="speakingFeedbackRef"
                                v-model="editForm.teacher_feedback"
                                rows="4"
                                @input="autoResize($event)"
                                class="mt-1 block w-full resize-none overflow-hidden rounded-xl border-2 border-slate-300 px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                placeholder="Enter feedback for the student..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-4">
                        <button
                            type="button"
                            @click="saveSpeakingChanges"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-transparent bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-indigo-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none sm:col-start-2"
                        >
                            Save Changes
                        </button>
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="mt-3 inline-flex w-full items-center justify-center rounded-xl border-2 border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:border-slate-400 hover:bg-slate-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none sm:col-start-1 sm:mt-0"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Writing Score Edit Modal -->
        <div
            v-if="showWritingEditModal"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="writing-modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" @click="closeWritingEditModal"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div
                    class="relative inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-6xl sm:align-middle"
                >
                    <!-- Header with Close Button -->
                    <div class="sticky top-0 z-10 bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20 backdrop-blur-sm">
                                    <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-white" id="writing-modal-title">Edit Writing Score</h3>
                                    <p class="text-sm text-indigo-100">
                                        <strong>{{ editingResult?.username }}</strong>
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="closeWritingEditModal"
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10 text-white transition-all duration-200 hover:rotate-90 hover:bg-white/20"
                                title="Close"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-6">
                        <!-- Loop through writing tasks -->
                        <div v-for="(task, index) in getWritingTasks(editingResult?.writing)" :key="index">
                            <div class="rounded-xl border-2 shadow-lg" :class="getTaskTheme(index).containerClass">
                                <!-- Task Header -->
                                <div class="border-b px-5 py-3" :class="getTaskTheme(index).borderClass">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-base font-bold text-white shadow"
                                            :class="getTaskTheme(index).badgeClass"
                                        >
                                            {{ index + 1 }}
                                        </div>
                                        <h4 class="text-sm font-bold text-slate-900">{{ getTaskTitle(index) }}</h4>
                                    </div>
                                </div>

                                <!-- Question -->
                                <div class="border-b bg-white px-5 py-4" :class="getTaskTheme(index).borderClass">
                                    <div class="mb-2 flex items-center gap-2">
                                        <svg class="h-4 w-4" :class="getTaskTheme(index).iconClass" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        <span class="text-xs font-bold tracking-wide uppercase" :class="getTaskTheme(index).labelClass"
                                            >Question</span
                                        >
                                    </div>
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                        <p class="text-base leading-relaxed text-slate-800">{{ task.q }}</p>
                                        <!-- Question Image - Only for Task 1 -->
                                        <div v-if="index === 0 && exam?.exam_id && getWritingImagePath(exam.exam_id)" class="mt-4">
                                            <img
                                                :src="getWritingImagePath(exam.exam_id)"
                                                :alt="`Writing question for ${exam.exam_id}`"
                                                @error="handleImageError"
                                                class="w-full rounded-lg border border-slate-300 shadow-sm"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Answer -->
                                <div class="bg-white px-5 py-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            <span class="text-xs font-bold tracking-wide text-emerald-700 uppercase">Student's Answer</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            <span class="text-xs font-semibold text-slate-600">{{ countWords(task.ans) }} words</span>
                                        </div>
                                    </div>
                                    <div
                                        class="min-h-[200px] rounded-lg border-2 border-emerald-200 bg-gradient-to-br from-emerald-50/50 to-teal-50/50 p-4"
                                    >
                                        <p class="text-base leading-relaxed whitespace-pre-wrap text-slate-800">
                                            {{ task.ans }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Footer with Input and Actions -->
                    <div class="sticky bottom-0 border-t-2 border-indigo-200 bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 shadow-2xl">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col items-end gap-2 py-4">
                                <button
                                    @click="evaluateByAi(getWritingTasks(editingResult?.writing))"
                                    :disabled="isEvaluating"
                                    class="flex items-center gap-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:from-indigo-600 hover:to-purple-700 hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <svg
                                        v-if="isEvaluating"
                                        class="h-4 w-4 animate-spin"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"
                                        />
                                    </svg>
                                    {{ isEvaluating ? 'Evaluating...' : 'Evaluate by AI' }}
                                </button>
                                <p v-if="evaluationError" class="text-sm text-red-600">
                                    {{ evaluationError }}
                                </p>
                            </div>
                            <!-- Task Scores Grid -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <!-- Task 1 Score & Feedback -->
                                <div class="rounded-xl border-2 border-blue-200 bg-blue-50/50 p-4">
                                    <!-- 4 Criteria Fields -->
                                    <div class="mb-3 grid grid-cols-2 gap-2">
                                        <div>
                                            <label for="task1_ta" class="mb-1 block text-xs font-bold text-blue-900">TA (Task Achievement)</label>
                                            <input
                                                id="task1_ta"
                                                v-model="writingEditForm.task1_ta"
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                max="9"
                                                class="block w-full rounded-lg border-2 border-blue-300 bg-white px-3 py-1.5 text-center text-sm font-semibold shadow-sm transition-all duration-200 hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                                                placeholder="0-9"
                                            />
                                        </div>
                                        <div>
                                            <label for="task1_cc" class="mb-1 block text-xs font-bold text-blue-900">CC (Coherence & Cohesion)</label>
                                            <input
                                                id="task1_cc"
                                                v-model="writingEditForm.task1_cc"
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                max="9"
                                                class="block w-full rounded-lg border-2 border-blue-300 bg-white px-3 py-1.5 text-center text-sm font-semibold shadow-sm transition-all duration-200 hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                                                placeholder="0-9"
                                            />
                                        </div>
                                        <div>
                                            <label for="task1_lr" class="mb-1 block text-xs font-bold text-blue-900">LR (Lexical Resource)</label>
                                            <input
                                                id="task1_lr"
                                                v-model="writingEditForm.task1_lr"
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                max="9"
                                                class="block w-full rounded-lg border-2 border-blue-300 bg-white px-3 py-1.5 text-center text-sm font-semibold shadow-sm transition-all duration-200 hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                                                placeholder="0-9"
                                            />
                                        </div>
                                        <div>
                                            <label for="task1_gra" class="mb-1 block text-xs font-bold text-blue-900">GRA (Grammar)</label>
                                            <input
                                                id="task1_gra"
                                                v-model="writingEditForm.task1_gra"
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                max="9"
                                                class="block w-full rounded-lg border-2 border-blue-300 bg-white px-3 py-1.5 text-center text-sm font-semibold shadow-sm transition-all duration-200 hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                                                placeholder="0-9"
                                            />
                                        </div>
                                    </div>

                                    <!-- Calculated Band Score (Editable) -->
                                    <div class="mb-3">
                                        <label for="task1_band" class="mb-2 flex items-center gap-2 text-sm font-bold text-blue-900">
                                            <div class="flex h-6 w-6 items-center justify-center rounded bg-blue-600 text-xs font-bold text-white">
                                                1
                                            </div>
                                            Task 1 Band Score
                                            <span class="text-xs font-normal text-blue-700">(Auto-calculated)</span>
                                        </label>
                                        <input
                                            id="task1_band"
                                            v-model="writingEditForm.task1_band"
                                            type="number"
                                            step="0.5"
                                            min="0"
                                            max="9"
                                            readonly
                                            class="block w-full rounded-lg border-2 border-blue-300 bg-white px-4 py-2 text-center text-lg font-bold shadow-sm transition-all duration-200 hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                                            placeholder="0.0 - 9.0"
                                        />
                                    </div>
                                    <div>
                                        <label for="task1_feedback" class="mb-2 flex items-center gap-2 text-xs font-bold text-blue-900">
                                            <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                                            </svg>
                                            Task 1 Feedback
                                        </label>
                                        <textarea
                                            id="task1_feedback"
                                            ref="task1FeedbackRef"
                                            v-model="writingEditForm.task1_feedback"
                                            rows="2"
                                            @input="autoResize($event)"
                                            class="block w-full resize-none overflow-hidden rounded-lg border-2 border-blue-300 bg-white px-3 py-2 text-xs shadow-sm transition-all duration-200 hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                                            placeholder="Feedback for Task 1..."
                                        ></textarea>
                                    </div>
                                </div>

                                <!-- Task 2 Score & Feedback -->
                                <div class="rounded-xl border-2 border-purple-200 bg-purple-50/50 p-4">
                                    <!-- 4 Criteria Fields -->
                                    <div class="mb-3 grid grid-cols-2 gap-2">
                                        <div>
                                            <label for="task2_ta" class="mb-1 block text-xs font-bold text-purple-900">TR (Task Response)</label>
                                            <input
                                                id="task2_ta"
                                                v-model="writingEditForm.task2_ta"
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                max="9"
                                                class="block w-full rounded-lg border-2 border-purple-300 bg-white px-3 py-1.5 text-center text-sm font-semibold shadow-sm transition-all duration-200 hover:border-purple-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                                                placeholder="0-9"
                                            />
                                        </div>
                                        <div>
                                            <label for="task2_cc" class="mb-1 block text-xs font-bold text-purple-900"
                                                >CC (Coherence & Cohesion)</label
                                            >
                                            <input
                                                id="task2_cc"
                                                v-model="writingEditForm.task2_cc"
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                max="9"
                                                class="block w-full rounded-lg border-2 border-purple-300 bg-white px-3 py-1.5 text-center text-sm font-semibold shadow-sm transition-all duration-200 hover:border-purple-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                                                placeholder="0-9"
                                            />
                                        </div>
                                        <div>
                                            <label for="task2_lr" class="mb-1 block text-xs font-bold text-purple-900">LR (Lexical Resource)</label>
                                            <input
                                                id="task2_lr"
                                                v-model="writingEditForm.task2_lr"
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                max="9"
                                                class="block w-full rounded-lg border-2 border-purple-300 bg-white px-3 py-1.5 text-center text-sm font-semibold shadow-sm transition-all duration-200 hover:border-purple-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                                                placeholder="0-9"
                                            />
                                        </div>
                                        <div>
                                            <label for="task2_gra" class="mb-1 block text-xs font-bold text-purple-900">GRA (Grammar)</label>
                                            <input
                                                id="task2_gra"
                                                v-model="writingEditForm.task2_gra"
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                max="9"
                                                class="block w-full rounded-lg border-2 border-purple-300 bg-white px-3 py-1.5 text-center text-sm font-semibold shadow-sm transition-all duration-200 hover:border-purple-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                                                placeholder="0-9"
                                            />
                                        </div>
                                    </div>

                                    <!-- Calculated Band Score (Editable) -->
                                    <div class="mb-3">
                                        <label for="task2_band" class="mb-2 flex items-center gap-2 text-sm font-bold text-purple-900">
                                            <div class="flex h-6 w-6 items-center justify-center rounded bg-purple-600 text-xs font-bold text-white">
                                                2
                                            </div>
                                            Task 2 Band Score
                                            <span class="text-xs font-normal text-purple-700">(Auto-calculated)</span>
                                        </label>
                                        <input
                                            id="task2_band"
                                            v-model="writingEditForm.task2_band"
                                            type="number"
                                            step="0.5"
                                            readonly
                                            min="0"
                                            max="9"
                                            class="block w-full rounded-lg border-2 border-purple-300 bg-white px-4 py-2 text-center text-lg font-bold shadow-sm transition-all duration-200 hover:border-purple-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                                            placeholder="0.0 - 9.0"
                                        />
                                    </div>
                                    <div>
                                        <label for="task2_feedback" class="mb-2 flex items-center gap-2 text-xs font-bold text-purple-900">
                                            <svg class="h-4 w-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                                            </svg>
                                            Task 2 Feedback
                                        </label>
                                        <textarea
                                            id="task2_feedback"
                                            ref="task2FeedbackRef"
                                            v-model="writingEditForm.task2_feedback"
                                            rows="2"
                                            @input="autoResize($event)"
                                            class="block w-full resize-none overflow-hidden rounded-lg border-2 border-purple-300 bg-white px-3 py-2 text-xs shadow-sm transition-all duration-200 hover:border-purple-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30"
                                            placeholder="Feedback for Task 2..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Calculated Band Score Display -->
                            <div
                                v-if="writingEditForm.task1_band && writingEditForm.task2_band"
                                class="rounded-xl border-2 border-emerald-200 bg-emerald-50 p-4"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-5 w-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        <span class="text-sm font-bold text-emerald-900">Overall Writing Band Score</span>
                                        <span class="ml-2 text-xs text-emerald-700">(Task1 + Task2×2) / 3</span>
                                    </div>
                                    <div class="text-3xl font-bold text-emerald-600">
                                        {{
                                            calculateWritingBandScore(
                                                parseFloat(writingEditForm.task1_band) || 0,
                                                parseFloat(writingEditForm.task2_band) || 0,
                                            ).toFixed(1)
                                        }}
                                    </div>
                                </div>
                            </div>

                            <!-- General Teacher Feedback (Optional) -->
                            <div>
                                <label for="writing_feedback" class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-900">
                                    <svg class="h-5 w-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                                        <path
                                            d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"
                                        ></path>
                                    </svg>
                                    General Feedback (Optional)
                                </label>
                                <textarea
                                    id="writing_feedback"
                                    ref="generalFeedbackRef"
                                    v-model="writingEditForm.teacher_feedback"
                                    rows="2"
                                    @input="autoResize($event)"
                                    class="block w-full resize-none overflow-hidden rounded-xl border-2 border-indigo-300 bg-white px-4 py-3 text-sm shadow-sm transition-all duration-200 hover:border-indigo-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/30"
                                    placeholder="Optional: Overall feedback for both tasks..."
                                ></textarea>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="saveWritingChanges"
                                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-8 py-3 text-sm font-bold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-emerald-700 hover:to-teal-700 focus:ring-4 focus:ring-emerald-500/50 focus:outline-none"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Save Scores
                                </button>
                                <button
                                    type="button"
                                    @click="closeWritingEditModal"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm transition-all duration-200 hover:border-slate-400 hover:bg-slate-50 focus:ring-4 focus:ring-slate-500/20 focus:outline-none"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Answers Modal -->
        <div v-if="showAnswersModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="answers-modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" @click="closeAnswersModal"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div
                    class="relative inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-6xl sm:align-middle"
                >
                    <!-- Header with Close Button -->
                    <div class="sticky top-0 z-10 bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20 backdrop-blur-sm">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        ></path>
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-white capitalize" id="answers-modal-title">{{ viewingSection }} Answers</h3>
                                    <p class="text-sm text-blue-100">
                                        <strong>{{ viewingResult?.username }}</strong>
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="closeAnswersModal"
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10 text-white transition-all duration-200 hover:rotate-90 hover:bg-white/20"
                                title="Close"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-6">
                        <!-- Writing Section -->
                        <div v-if="viewingSection === 'writing' && viewingResult?.writing">
                            <!-- Task Scores Display -->
                            <div v-if="viewingResult.writing.task1_band || viewingResult.writing.task2_band" class="mb-6">
                                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <!-- Task 1 Score Card -->
                                    <div
                                        v-if="viewingResult.writing.task1_band"
                                        class="rounded-xl border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50 p-4"
                                    >
                                        <div class="mb-3 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="flex h-6 w-6 items-center justify-center rounded bg-blue-600 text-xs font-bold text-white"
                                                >
                                                    1
                                                </div>
                                                <span class="text-sm font-bold text-blue-900">Task 1</span>
                                            </div>
                                            <div class="text-2xl font-bold text-blue-600">{{ viewingResult.writing.task1_band }}</div>
                                        </div>
                                        <!-- Criteria Scores -->
                                        <div
                                            v-if="
                                                viewingResult.writing.task1_ta ||
                                                viewingResult.writing.task1_cc ||
                                                viewingResult.writing.task1_lr ||
                                                viewingResult.writing.task1_gra
                                            "
                                            class="grid grid-cols-4 gap-2"
                                        >
                                            <div class="rounded-lg bg-white/70 p-2 text-center">
                                                <div class="text-xs font-semibold text-blue-700">TA</div>
                                                <div class="text-lg font-bold text-blue-900">{{ viewingResult.writing.task1_ta || '-' }}</div>
                                            </div>
                                            <div class="rounded-lg bg-white/70 p-2 text-center">
                                                <div class="text-xs font-semibold text-blue-700">CC</div>
                                                <div class="text-lg font-bold text-blue-900">{{ viewingResult.writing.task1_cc || '-' }}</div>
                                            </div>
                                            <div class="rounded-lg bg-white/70 p-2 text-center">
                                                <div class="text-xs font-semibold text-blue-700">LR</div>
                                                <div class="text-lg font-bold text-blue-900">{{ viewingResult.writing.task1_lr || '-' }}</div>
                                            </div>
                                            <div class="rounded-lg bg-white/70 p-2 text-center">
                                                <div class="text-xs font-semibold text-blue-700">GRA</div>
                                                <div class="text-lg font-bold text-blue-900">{{ viewingResult.writing.task1_gra || '-' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Task 2 Score Card -->
                                    <div
                                        v-if="viewingResult.writing.task2_band"
                                        class="rounded-xl border-2 border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50 p-4"
                                    >
                                        <div class="mb-3 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="flex h-6 w-6 items-center justify-center rounded bg-purple-600 text-xs font-bold text-white"
                                                >
                                                    2
                                                </div>
                                                <span class="text-sm font-bold text-purple-900">Task 2</span>
                                            </div>
                                            <div class="text-2xl font-bold text-purple-600">{{ viewingResult.writing.task2_band }}</div>
                                        </div>
                                        <!-- Criteria Scores -->
                                        <div
                                            v-if="
                                                viewingResult.writing.task2_ta ||
                                                viewingResult.writing.task2_cc ||
                                                viewingResult.writing.task2_lr ||
                                                viewingResult.writing.task2_gra
                                            "
                                            class="grid grid-cols-4 gap-2"
                                        >
                                            <div class="rounded-lg bg-white/70 p-2 text-center">
                                                <div class="text-xs font-semibold text-purple-700">TR</div>
                                                <div class="text-lg font-bold text-purple-900">{{ viewingResult.writing.task2_ta || '-' }}</div>
                                            </div>
                                            <div class="rounded-lg bg-white/70 p-2 text-center">
                                                <div class="text-xs font-semibold text-purple-700">CC</div>
                                                <div class="text-lg font-bold text-purple-900">{{ viewingResult.writing.task2_cc || '-' }}</div>
                                            </div>
                                            <div class="rounded-lg bg-white/70 p-2 text-center">
                                                <div class="text-xs font-semibold text-purple-700">LR</div>
                                                <div class="text-lg font-bold text-purple-900">{{ viewingResult.writing.task2_lr || '-' }}</div>
                                            </div>
                                            <div class="rounded-lg bg-white/70 p-2 text-center">
                                                <div class="text-xs font-semibold text-purple-700">GRA</div>
                                                <div class="text-lg font-bold text-purple-900">{{ viewingResult.writing.task2_gra || '-' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Task 1 Feedback -->
                                <div v-if="viewingResult.writing.task1_feedback" class="mb-3 rounded-lg border-2 border-blue-200 bg-blue-50/50 p-4">
                                    <div class="mb-2 flex items-center gap-2">
                                        <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                                        </svg>
                                        <span class="text-xs font-bold text-blue-900">Task 1 Feedback</span>
                                    </div>
                                    <p class="text-sm text-blue-900">{{ viewingResult.writing.task1_feedback }}</p>
                                </div>

                                <!-- Task 2 Feedback -->
                                <div
                                    v-if="viewingResult.writing.task2_feedback"
                                    class="mb-3 rounded-lg border-2 border-purple-200 bg-purple-50/50 p-4"
                                >
                                    <div class="mb-2 flex items-center gap-2">
                                        <svg class="h-4 w-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                                        </svg>
                                        <span class="text-xs font-bold text-purple-900">Task 2 Feedback</span>
                                    </div>
                                    <p class="text-sm text-purple-900">{{ viewingResult.writing.task2_feedback }}</p>
                                </div>

                                <!-- General Feedback -->
                                <div
                                    v-if="viewingResult.writing.teacher_feedback"
                                    class="mb-3 rounded-lg border-2 border-indigo-200 bg-indigo-50/50 p-4"
                                >
                                    <div class="mb-2 flex items-center gap-2">
                                        <svg class="h-4 w-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                                        </svg>
                                        <span class="text-xs font-bold text-indigo-900">General Feedback</span>
                                    </div>
                                    <p class="text-sm text-indigo-900">{{ viewingResult.writing.teacher_feedback }}</p>
                                </div>
                            </div>

                            <div v-for="(task, index) in getWritingTasks(viewingResult.writing)" :key="index" class="mb-4">
                                <div class="rounded-xl border-2 shadow-lg" :class="getTaskTheme(index).containerClass">
                                    <!-- Task Header -->
                                    <div class="border-b px-5 py-3" :class="getTaskTheme(index).borderClass">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-base font-bold text-white shadow"
                                                :class="getTaskTheme(index).badgeClass"
                                            >
                                                {{ index + 1 }}
                                            </div>
                                            <h4 class="text-sm font-bold text-slate-900">{{ getTaskTitle(index) }}</h4>
                                        </div>
                                    </div>

                                    <!-- Question -->
                                    <div class="border-b bg-white px-5 py-4" :class="getTaskTheme(index).borderClass">
                                        <div class="mb-2 flex items-center gap-2">
                                            <svg class="h-4 w-4" :class="getTaskTheme(index).iconClass" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            <span class="text-xs font-bold tracking-wide uppercase" :class="getTaskTheme(index).labelClass"
                                                >Question</span
                                            >
                                        </div>
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                            <p class="text-base leading-relaxed text-slate-800">{{ task.q }}</p>
                                            <!-- Question Image - Only for Task 1 -->
                                            <div v-if="index === 0 && exam?.exam_id && getWritingImagePath(exam.exam_id)" class="mt-4">
                                                <img
                                                    :src="getWritingImagePath(exam.exam_id)"
                                                    :alt="`Writing question for ${exam.exam_id}`"
                                                    @error="handleImageError"
                                                    class="w-full rounded-lg border border-slate-300 shadow-sm"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Answer -->
                                    <div class="bg-white px-5 py-4">
                                        <div class="mb-2 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <svg
                                                    class="h-4 w-4"
                                                    :class="task.ans ? 'text-emerald-600' : 'text-slate-400'"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                                <span
                                                    class="text-xs font-bold tracking-wide uppercase"
                                                    :class="task.ans ? 'text-emerald-700' : 'text-slate-500'"
                                                    >Student's Answer</span
                                                >
                                            </div>
                                            <div class="flex items-center gap-2" v-if="task.ans">
                                                <svg class="h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                                <span class="text-xs font-semibold text-slate-600">{{ countWords(task.ans) }} words</span>
                                            </div>
                                        </div>
                                        <div
                                            v-if="task.ans"
                                            class="min-h-[200px] rounded-lg border-2 border-emerald-200 bg-gradient-to-br from-emerald-50/50 to-teal-50/50 p-4"
                                        >
                                            <p class="text-base leading-relaxed whitespace-pre-wrap text-slate-800">
                                                {{ task.ans }}
                                            </p>
                                        </div>
                                        <div
                                            v-else
                                            class="flex min-h-[200px] items-center justify-center rounded-lg border-2 border-slate-200 bg-slate-50 p-4"
                                        >
                                            <div class="text-center">
                                                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                    ></path>
                                                </svg>
                                                <p class="mt-2 text-sm font-semibold text-slate-500">No answer provided</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reading/Listening Section -->
                        <div
                            v-else-if="
                                (viewingSection === 'reading' || viewingSection === 'listening') && viewingResult?.[viewingSection]?.user_answers
                            "
                        >
                            <div class="rounded-xl border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50 p-6">
                                <h4 class="mb-4 text-lg font-bold text-slate-900">Answers</h4>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                                    <div
                                        v-for="(answer, key) in viewingResult[viewingSection].user_answers"
                                        :key="key"
                                        class="rounded-lg border-2 border-slate-200 bg-white p-3 shadow-sm"
                                    >
                                        <div class="mb-1 text-xs font-bold text-slate-600 uppercase">{{ key }}</div>
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ answer || 'No answer' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="rounded-xl border-2 border-slate-200 bg-slate-50 p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                ></path>
                            </svg>
                            <h3 class="mt-4 text-lg font-bold text-slate-900">No answers available</h3>
                            <p class="mt-2 text-sm text-slate-600">This section has no recorded answers.</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="sticky bottom-0 border-t-2 border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4">
                        <button
                            type="button"
                            @click="closeAnswersModal"
                            class="w-full rounded-xl border-2 border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm transition-all duration-200 hover:border-slate-400 hover:bg-slate-50 focus:ring-4 focus:ring-slate-500/20 focus:outline-none"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="delete-modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" @click="closeDeleteModal"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div
                    class="relative inline-block transform overflow-hidden rounded-2xl bg-white px-6 pt-6 pb-6 text-left align-bottom shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-8 sm:align-middle"
                >
                    <div>
                        <div
                            class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-red-100 to-rose-100 shadow-sm"
                        >
                            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </div>
                        <div class="mt-4 text-center sm:mt-6">
                            <h3 class="text-xl leading-6 font-bold text-slate-900" id="delete-modal-title">Delete Result</h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-600">
                                    Are you sure you want to delete the result for <strong>{{ deletingResult?.username }}</strong
                                    >?
                                </p>
                                <p class="mt-2 text-sm font-semibold text-red-600">This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-4">
                        <button
                            type="button"
                            @click="deleteResult"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-transparent bg-gradient-to-r from-red-600 to-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-red-700 hover:to-rose-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none sm:col-start-2"
                        >
                            Delete
                        </button>
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            class="mt-3 inline-flex w-full items-center justify-center rounded-xl border-2 border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:border-slate-400 hover:bg-slate-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none sm:col-start-1 sm:mt-0"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const props = defineProps({
    exam: Object,
    results: Object,
});

const page = usePage();
ref([]);
const isAdmin = computed(() => {
    return page.props?.auth?.user?.role === 'admin' || page.props?.auth?.user?.is_admin === true || page.props?.auth?.user?.email?.includes('admin');
});

const showEditModal = ref(false);
const showWritingEditModal = ref(false);
const editingResult = ref(null);
const editForm = ref({
    speaking_band: '',
    teacher_feedback: '',
});

const writingEditForm = ref({
    // Task 1 Criteria
    task1_ta: '', // Task Achievement
    task1_cc: '', // Coherence and Cohesion
    task1_lr: '', // Lexical Resource
    task1_gra: '', // Grammatical Range and Accuracy
    task1_band: '', // Calculated band score
    task1_feedback: '',
    // Task 2 Criteria
    task2_ta: '', // Task Achievement (Task Response for Task 2)
    task2_cc: '', // Coherence and Cohesion
    task2_lr: '', // Lexical Resource
    task2_gra: '', // Grammatical Range and Accuracy
    task2_band: '', // Calculated band score
    task2_feedback: '',
    // Overall
    band_score: '',
    teacher_feedback: '',
});

// Textarea refs for auto-resize
const speakingFeedbackRef = ref(null);
const task1FeedbackRef = ref(null);
const task2FeedbackRef = ref(null);
const generalFeedbackRef = ref(null);

// Auto-resize textarea to fit content
const autoResize = (event) => {
    const el = event.target;
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
};

const autoResizeElement = (el) => {
    if (el) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }
};

// AI Evaluation state
const isEvaluating = ref(false);
const evaluationError = ref<string | null>(null);

// Watch for changes in Task 1 criteria and auto-calculate band score
watch(
    () => [writingEditForm.value.task1_ta, writingEditForm.value.task1_cc, writingEditForm.value.task1_lr, writingEditForm.value.task1_gra],
    () => {
        const ta = parseFloat(writingEditForm.value.task1_ta);
        const cc = parseFloat(writingEditForm.value.task1_cc);
        const lr = parseFloat(writingEditForm.value.task1_lr);
        const gra = parseFloat(writingEditForm.value.task1_gra);

        if (!isNaN(ta) && !isNaN(cc) && !isNaN(lr) && !isNaN(gra)) {
            const average = (ta + cc + lr + gra) / 4;
            writingEditForm.value.task1_band = roundToIELTS(average).toFixed(1);
        }
    },
);

// Watch for changes in Task 2 criteria and auto-calculate band score
watch(
    () => [writingEditForm.value.task2_ta, writingEditForm.value.task2_cc, writingEditForm.value.task2_lr, writingEditForm.value.task2_gra],
    () => {
        const ta = parseFloat(writingEditForm.value.task2_ta);
        const cc = parseFloat(writingEditForm.value.task2_cc);
        const lr = parseFloat(writingEditForm.value.task2_lr);
        const gra = parseFloat(writingEditForm.value.task2_gra);

        if (!isNaN(ta) && !isNaN(cc) && !isNaN(lr) && !isNaN(gra)) {
            const average = (ta + cc + lr + gra) / 4;
            writingEditForm.value.task2_band = roundToIELTS(average).toFixed(1);
        }
    },
);

const showAnswersModal = ref(false);
const viewingSection = ref(null);
const viewingResult = ref(null);

const showDeleteModal = ref(false);
const deletingResult = ref(null);

// Inline speaking edit
const inlineEditingSpeaking = ref<number | null>(null);
const inlineSpeakingBand = ref('');

const hasSpeakingScore = (result: any): boolean => {
    const score = result.speaking?.band_score;
    return score !== null && score !== undefined && score !== 0 && score !== '0' && score !== '';
};

const onSpeakingInputFocus = (result: any) => {
    if (inlineEditingSpeaking.value !== result.id) {
        inlineEditingSpeaking.value = result.id;
        inlineSpeakingBand.value =
            result.speaking?.band_score !== null && result.speaking?.band_score !== undefined && result.speaking?.band_score !== 0
                ? String(result.speaking.band_score)
                : '';
    }
};

const startInlineSpeaking = (result: any) => {
    inlineEditingSpeaking.value = result.id;
    inlineSpeakingBand.value =
        result.speaking?.band_score !== null && result.speaking?.band_score !== undefined ? String(result.speaking.band_score) : '';
};

const cancelInlineSpeaking = () => {
    inlineEditingSpeaking.value = null;
    inlineSpeakingBand.value = '';
};

const saveInlineSpeaking = (result: any) => {
    const speakingBand = inlineSpeakingBand.value;
    const updatedSpeaking = {
        ...result.speaking,
        band_score: speakingBand !== '' && speakingBand !== null ? parseFloat(speakingBand) : null,
    };

    router.put(
        `/admin/results/${result.id}/speaking`,
        { speaking: updatedSpeaking },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                cancelInlineSpeaking();
            },
            onError: (errors) => {
                console.error('Error updating speaking result:', errors);
            },
        },
    );
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const countWords = (text) => {
    if (!text) return 0;
    return text
        .trim()
        .split(/\s+/)
        .filter((word) => word.length > 0).length;
};

const getTaskTheme = (index) => {
    const themes = [
        {
            containerClass: 'border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50',
            badgeClass: 'bg-blue-600',
            borderClass: 'border-blue-200',
            iconClass: 'text-blue-600',
            labelClass: 'text-blue-700',
        },
        {
            containerClass: 'border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50',
            badgeClass: 'bg-purple-600',
            borderClass: 'border-purple-200',
            iconClass: 'text-purple-600',
            labelClass: 'text-purple-700',
        },
        {
            containerClass: 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-teal-50',
            badgeClass: 'bg-emerald-600',
            borderClass: 'border-emerald-200',
            iconClass: 'text-emerald-600',
            labelClass: 'text-emerald-700',
        },
        {
            containerClass: 'border-orange-200 bg-gradient-to-br from-orange-50 to-amber-50',
            badgeClass: 'bg-orange-600',
            borderClass: 'border-orange-200',
            iconClass: 'text-orange-600',
            labelClass: 'text-orange-700',
        },
    ];

    return themes[index % themes.length];
};

const getTaskTitle = (index) => {
    const titles = ['Task 1 - Academic Writing', 'Task 2 - Essay Writing', 'Task 3', 'Task 4'];

    return titles[index] || `Task ${index + 1}`;
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
const getOverallScore = (result) => {
    const scores = [];

    // Collect all four section scores
    if (result.listening?.band_score !== null && result.listening?.band_score !== undefined) {
        scores.push(parseFloat(result.listening.band_score));
    }

    if (result.reading?.band_score !== null && result.reading?.band_score !== undefined) {
        scores.push(parseFloat(result.reading.band_score));
    }

    // For writing, check band_score in the writing structure
    if (result.writing?.band_score !== null && result.writing?.band_score !== undefined) {
        scores.push(parseFloat(result.writing.band_score));
    }

    if (result.speaking?.band_score !== null && result.speaking?.band_score !== undefined) {
        scores.push(parseFloat(result.speaking.band_score));
    }

    // Need all 4 sections to calculate overall score
    if (scores.length !== 4) return null;

    // Calculate average
    const sum = scores.reduce((a, b) => a + b, 0);
    const average = sum / 4;

    // Apply IELTS rounding rules
    // If average ends in .25, round up to next .5
    // If average ends in .75, round up to next whole number
    // Otherwise, round to nearest .5
    const rounded = roundToIELTS(average);

    return rounded.toFixed(1);
};

// IELTS Rounding Rules:
// .00 - .24 → round down to .0
// .25 - .74 → round to .5
// .75 - .99 → round up to next whole number
const roundToIELTS = (score) => {
    const wholePart = Math.floor(score);
    const decimalPart = score - wholePart;

    if (decimalPart < 0.25) {
        // 6.24 → 6.0
        return wholePart;
    } else if (decimalPart >= 0.25 && decimalPart < 0.75) {
        // 6.25 → 6.5, 6.74 → 6.5
        return wholePart + 0.5;
    } else {
        // 6.75 → 7.0
        return wholePart + 1.0;
    }
};

const getCsrfToken = (): string => {
    const cookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
};

interface AIEvaluationResponse {
    task1_ta: number;
    task1_cc: number;
    task1_lr: number;
    task1_gra: number;
    task1_feedback: string;
    task2_ta: number;
    task2_cc: number;
    task2_lr: number;
    task2_gra: number;
    task2_feedback: string;
    teacher_feedback: string;
}

const evaluateByAi = async (writingTasks: Array<{ q: string; ans: string }>) => {
    if (!writingTasks || writingTasks.length < 2) {
        evaluationError.value = 'At least two writing tasks are required for evaluation.';
        return;
    }

    if (!writingTasks[0]?.ans || !writingTasks[1]?.ans) {
        evaluationError.value = 'Both Task 1 and Task 2 must have answers to evaluate.';
        return;
    }

    isEvaluating.value = true;
    evaluationError.value = null;

    try {
        const response = await fetch('/api/writing/evaluate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                writing: writingTasks.slice(0, 2),
            }),
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to evaluate writing.');
        }

        const evaluation: AIEvaluationResponse = result.data;

        // Auto-fill the form fields
        writingEditForm.value.task1_ta = evaluation.task1_ta.toString();
        writingEditForm.value.task1_cc = evaluation.task1_cc.toString();
        writingEditForm.value.task1_lr = evaluation.task1_lr.toString();
        writingEditForm.value.task1_gra = evaluation.task1_gra.toString();
        writingEditForm.value.task1_feedback = evaluation.task1_feedback;

        writingEditForm.value.task2_ta = evaluation.task2_ta.toString();
        writingEditForm.value.task2_cc = evaluation.task2_cc.toString();
        writingEditForm.value.task2_lr = evaluation.task2_lr.toString();
        writingEditForm.value.task2_gra = evaluation.task2_gra.toString();
        writingEditForm.value.task2_feedback = evaluation.task2_feedback;

        writingEditForm.value.teacher_feedback = evaluation.teacher_feedback;

        // Auto-expand textareas after AI fills in feedback
        nextTick(() => {
            autoResizeElement(task1FeedbackRef.value);
            autoResizeElement(task2FeedbackRef.value);
            autoResizeElement(generalFeedbackRef.value);
        });
    } catch (error) {
        evaluationError.value = error instanceof Error ? error.message : 'An unexpected error occurred.';
        console.error('AI Evaluation Error:', error);
    } finally {
        isEvaluating.value = false;
    }
};

const getSectionsForResult = (result) => {
    const allSections = [
        {
            id: 'reading',
            label: 'Reading',
            subtitle: getSectionRawScore('reading', result) || 'Passages',
            accentClass: 'bg-linear-to-r from-blue-400 to-cyan-400',
            borderClass: 'border-blue-200/60',
            iconBgClass: 'bg-blue-50',
            iconColorClass: 'text-blue-600',
            scoreColorClass: 'text-blue-600',
            scoreBorderClass: 'border-blue-200 bg-blue-50/40',
            viewBtnClass: 'bg-blue-500 hover:bg-blue-600',
            editBtnClass: 'bg-blue-600 hover:bg-blue-700',
        },
        {
            id: 'writing',
            label: 'Writing',
            subtitle: 'Essay Tasks',
            accentClass: 'bg-linear-to-r from-violet-400 to-purple-400',
            borderClass: 'border-violet-200/60',
            iconBgClass: 'bg-violet-50',
            iconColorClass: 'text-violet-600',
            scoreColorClass: 'text-violet-600',
            scoreBorderClass: 'border-violet-200 bg-violet-50/40',
            viewBtnClass: 'bg-violet-500 hover:bg-violet-600',
            editBtnClass: 'bg-violet-600 hover:bg-violet-700',
        },
        {
            id: 'listening',
            label: 'Listening',
            subtitle: getSectionRawScore('listening', result) || 'Audio Test',
            accentClass: 'bg-linear-to-r from-emerald-400 to-teal-400',
            borderClass: 'border-emerald-200/60',
            iconBgClass: 'bg-emerald-50',
            iconColorClass: 'text-emerald-600',
            scoreColorClass: 'text-emerald-600',
            scoreBorderClass: 'border-emerald-200 bg-emerald-50/40',
            viewBtnClass: 'bg-emerald-500 hover:bg-emerald-600',
            editBtnClass: 'bg-emerald-600 hover:bg-emerald-700',
        },
        {
            id: 'speaking',
            label: 'Speaking',
            subtitle: 'Interview',
            accentClass: 'bg-linear-to-r from-amber-400 to-orange-400',
            borderClass: 'border-amber-200/60',
            iconBgClass: 'bg-amber-50',
            iconColorClass: 'text-amber-600',
            scoreColorClass: 'text-amber-600',
            scoreBorderClass: 'border-amber-200 bg-amber-50/40',
            viewBtnClass: 'bg-amber-500 hover:bg-amber-600',
            editBtnClass: 'bg-amber-600 hover:bg-amber-700',
        },
    ];

    if (result.exam_type === 'full') return allSections;
    if (result.modules && result.modules.length > 0) {
        return allSections.filter((s) => result.modules.includes(s.id));
    }
    return allSections;
};

const getSectionBandScore = (section, result) => {
    const sectionData = result[section];
    if (!sectionData) return 'N/A';

    if (section === 'writing') {
        // For writing, check band_score in the writing structure
        // It could be in writing.band_score or writing['band_score'] (for array format)
        const bandScore = sectionData.band_score;
        return bandScore !== null && bandScore !== undefined ? bandScore : 'N/A';
    }

    if (section === 'reading' || section === 'listening' || section === 'speaking') {
        return sectionData.band_score !== null && sectionData.band_score !== undefined ? sectionData.band_score : 'N/A';
    }

    return 'N/A';
};

const getSectionRawScore = (section, result) => {
    const sectionData = result[section];
    if (!sectionData) return '';

    if (section === 'reading' || section === 'listening') {
        return `${sectionData.correct_answers || 0}/${sectionData.total_questions || 40}`;
    }

    return '';
};
const getSectionBorderClass = (section, result) => {
    const sectionData = result[section];
    if (!sectionData) return 'border-slate-200';

    const isCompleted = section === 'speaking' ? sectionData.status !== 'not attempted' : sectionData.completed_at;

    return isCompleted ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50';
};

const getSectionIconClass = (section, result) => {
    const sectionData = result[section];
    if (!sectionData) return 'bg-slate-100 text-slate-400';

    const isCompleted = section === 'speaking' ? sectionData.status !== 'not attempted' : sectionData.completed_at;

    return isCompleted ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600';
};

const getSectionScoreClass = (section, result) => {
    const sectionData = result[section];
    if (!sectionData) return 'text-slate-400';

    const isCompleted = section === 'speaking' ? sectionData.status !== 'not attempted' : sectionData.completed_at;

    return isCompleted ? 'text-emerald-600' : 'text-amber-600';
};
const openEditModal = (result) => {
    editingResult.value = result;

    editForm.value = {
        speaking_band: result.speaking?.band_score !== null && result.speaking?.band_score !== undefined ? result.speaking.band_score : '',
        teacher_feedback: result.speaking?.teacher_feedback || '',
    };

    showEditModal.value = true;

    nextTick(() => {
        autoResizeElement(speakingFeedbackRef.value);
    });
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingResult.value = null;
    editForm.value = {
        speaking_band: '',
        teacher_feedback: '',
    };
};

const saveSpeakingChanges = () => {
    if (!editingResult.value) return;

    // Update band_score and teacher_feedback, keep everything else intact
    const speakingBand = editForm.value.speaking_band;
    const updatedSpeaking = {
        ...editingResult.value.speaking,
        band_score: speakingBand !== '' && speakingBand !== null ? parseFloat(speakingBand) : null,
        teacher_feedback: editForm.value.teacher_feedback || null,
    };

    router.put(
        `/admin/results/${editingResult.value.id}/speaking`,
        {
            speaking: updatedSpeaking,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                closeEditModal();
            },
            onError: (errors) => {
                console.error('Error updating speaking result:', errors);
                alert('Failed to update speaking result. Please try again.');
            },
        },
    );
};

const openWritingEditModal = (result) => {
    editingResult.value = result;

    // Helper to get score value, allowing 0 as valid
    const getScore = (value: any) => (value !== null && value !== undefined ? value : '');

    // Get criteria scores and feedback from writing structure
    writingEditForm.value = {
        // Task 1 Criteria
        task1_ta: getScore(result.writing?.task1_ta),
        task1_cc: getScore(result.writing?.task1_cc),
        task1_lr: getScore(result.writing?.task1_lr),
        task1_gra: getScore(result.writing?.task1_gra),
        task1_band: getScore(result.writing?.task1_band),
        task1_feedback: result.writing?.task1_feedback || '',
        // Task 2 Criteria
        task2_ta: getScore(result.writing?.task2_ta),
        task2_cc: getScore(result.writing?.task2_cc),
        task2_lr: getScore(result.writing?.task2_lr),
        task2_gra: getScore(result.writing?.task2_gra),
        task2_band: getScore(result.writing?.task2_band),
        task2_feedback: result.writing?.task2_feedback || '',
        // Overall
        band_score: getScore(result.writing?.band_score),
        teacher_feedback: result.writing?.teacher_feedback || '',
    };

    showWritingEditModal.value = true;

    // Auto-expand textareas after modal renders with existing data
    nextTick(() => {
        autoResizeElement(task1FeedbackRef.value);
        autoResizeElement(task2FeedbackRef.value);
        autoResizeElement(generalFeedbackRef.value);
    });
};

const closeWritingEditModal = () => {
    showWritingEditModal.value = false;
    editingResult.value = null;
    writingEditForm.value = {
        task1_ta: '',
        task1_cc: '',
        task1_lr: '',
        task1_gra: '',
        task1_band: '',
        task1_feedback: '',
        task2_ta: '',
        task2_cc: '',
        task2_lr: '',
        task2_gra: '',
        task2_band: '',
        task2_feedback: '',
        band_score: '',
        teacher_feedback: '',
    };
};

const saveWritingChanges = () => {
    if (!editingResult.value) return;

    // Helper to parse float, allowing 0 as valid value
    const parseScore = (value: string | number) => {
        const parsed = parseFloat(String(value));
        return isNaN(parsed) ? null : parsed;
    };

    // Calculate overall band score from task1 and task2
    const task1Band = parseScore(writingEditForm.value.task1_band);
    const task2Band = parseScore(writingEditForm.value.task2_band);

    let calculatedBandScore = null;
    if (task1Band !== null && task2Band !== null) {
        calculatedBandScore = calculateWritingBandScore(task1Band, task2Band);
    }

    // Keep the writing data structure intact (array or object)
    // Just add/update the band_score and teacher_feedback fields
    let updatedWriting;

    if (Array.isArray(editingResult.value.writing)) {
        // If writing is an array, keep it as array and add band_score as a property
        updatedWriting = editingResult.value.writing;
    } else if (typeof editingResult.value.writing === 'object') {
        // If writing is an object, spread it and update band_score
        updatedWriting = {
            ...editingResult.value.writing,
        };
    } else {
        updatedWriting = editingResult.value.writing;
    }

    router.put(
        `/admin/results/${editingResult.value.id}/writing`,
        {
            writing: updatedWriting,
            // Task 1 Criteria
            task1_ta: parseScore(writingEditForm.value.task1_ta),
            task1_cc: parseScore(writingEditForm.value.task1_cc),
            task1_lr: parseScore(writingEditForm.value.task1_lr),
            task1_gra: parseScore(writingEditForm.value.task1_gra),
            task1_band: task1Band,
            task1_feedback: writingEditForm.value.task1_feedback || null,
            // Task 2 Criteria
            task2_ta: parseScore(writingEditForm.value.task2_ta),
            task2_cc: parseScore(writingEditForm.value.task2_cc),
            task2_lr: parseScore(writingEditForm.value.task2_lr),
            task2_gra: parseScore(writingEditForm.value.task2_gra),
            task2_band: task2Band,
            task2_feedback: writingEditForm.value.task2_feedback || null,
            // Overall
            band_score: calculatedBandScore,
            teacher_feedback: writingEditForm.value.teacher_feedback || null,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                closeWritingEditModal();
            },
            onError: (errors) => {
                console.error('Error updating writing result:', errors);
                alert('Failed to update writing result. Please try again.');
            },
        },
    );
};

const openAnswersModal = (section, result) => {
    viewingSection.value = section;
    viewingResult.value = result;
    showAnswersModal.value = true;
};

const closeAnswersModal = () => {
    showAnswersModal.value = false;
    viewingSection.value = null;
    viewingResult.value = null;
};

const getWritingTasks = (writingData) => {
    if (!writingData) return [];

    // If it's already an array, return it
    if (Array.isArray(writingData)) {
        return writingData;
    }

    // If it's an object with numeric keys, extract the tasks
    const tasks = [];
    Object.keys(writingData).forEach((key) => {
        // Skip band_score and other non-numeric keys
        if (!isNaN(key)) {
            tasks.push(writingData[key]);
        }
    });

    return tasks;
};

const confirmDelete = (result) => {
    deletingResult.value = result;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deletingResult.value = null;
};

const deleteResult = () => {
    if (!deletingResult.value) return;

    router.delete(`/results/${deletingResult.value.id}`, {
        preserveState: false,
        preserveScroll: false,
        onSuccess: () => {
            closeDeleteModal();
        },
        onError: (errors) => {
            console.error('Error deleting result:', errors);
            alert('Failed to delete result. Please try again.');
        },
    });
};

const getWritingImagePath = (examId) => {
    if (!examId) return null;
    // Try common extensions - .jpg is more common based on the directory listing
    return `/images/writing/${examId}.jpg`;
};

const handleImageError = (event) => {
    // If .jpg fails, try .png
    const img = event.target;
    if (img.src.endsWith('.jpg')) {
        img.src = img.src.replace('.jpg', '.png');
    } else {
        // If both fail, hide the image
        img.style.display = 'none';
    }
};

// IELTS Writing Band Score Calculation
const calculateBandScore = (score: number): number =>
    score >= 39
        ? 9
        : score >= 37
          ? 8.5
          : score >= 35
            ? 8
            : score >= 33
              ? 7.5
              : score >= 30
                ? 7
                : score >= 27
                  ? 6.5
                  : score >= 23
                    ? 6
                    : score >= 19
                      ? 5.5
                      : score >= 15
                        ? 5
                        : score >= 13
                          ? 4.5
                          : score >= 10
                            ? 4
                            : score >= 8
                              ? 3.5
                              : score >= 6
                                ? 3
                                : score >= 4
                                  ? 2.5
                                  : 0;

// Calculate overall writing band score from Task 1 and Task 2
const calculateWritingBandScore = (task1Score: number, task2Score: number): number => {
    // Formula: (Task1 + (Task2 * 2)) / 3
    const rawScore = (task1Score + task2Score * 2) / 3;
    // Round to nearest 0.5
    return Math.round(rawScore * 2) / 2;
};

// Calculate writing band score from marks (out of 40)
const calculateWritingBandScoreFromMarks = (task1Marks: number, task2Marks: number): number => {
    // Convert marks to band scores using the IELTS scale
    const task1Band = calculateBandScore(task1Marks);
    const task2Band = calculateBandScore(task2Marks);
    // Calculate weighted average
    return calculateWritingBandScore(task1Band, task2Band);
};
</script>
