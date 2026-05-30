<template>
    <AppLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
            <div class="mx-auto">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <Link
                                :href="`/exams/${result.exam_id}/results`"
                                class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition-colors duration-200 hover:text-blue-700"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to Results
                            </Link>
                            <h1 class="mb-2 text-4xl font-bold text-slate-900">Result Details</h1>
                            <p class="text-lg text-slate-600">
                                Exam results for <strong>{{ result.username }}</strong>
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button
                                @click="handleViewPdfClick"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:bg-blue-700"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                    />
                                </svg>
                                View PDF
                            </button>
                            <button
                                @click="handleSendEmailClick"
                                :disabled="sending"
                                class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                    />
                                </svg>
                                {{ sending ? 'Sending...' : 'Send Email' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Exam Type and Modules Badge -->
                <div class="mb-6 flex items-center gap-3">
                    <div
                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold shadow-sm"
                        :class="
                            result.exam_type === 'full'
                                ? 'bg-gradient-to-r from-emerald-100 to-teal-100 text-emerald-800'
                                : 'bg-gradient-to-r from-amber-100 to-orange-100 text-amber-800'
                        "
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                v-if="result.exam_type === 'full'"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                            <path
                                v-else
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span>{{ result.exam_type === 'full' ? 'Full Mock Test' : 'Partial Mock Test' }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">Modules:</span>
                        <template v-if="result.exam_type === 'full'">
                            <span
                                v-for="module in ['listening', 'reading', 'writing', 'speaking']"
                                :key="module"
                                class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800"
                            >
                                {{ module.charAt(0).toUpperCase() + module.slice(1) }}
                            </span>
                        </template>
                        <template v-else-if="result.modules && result.modules.length > 0">
                            <span
                                v-for="module in result.modules"
                                :key="module"
                                class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800"
                            >
                                {{ module.charAt(0).toUpperCase() + module.slice(1) }}
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Result Info Card -->
                <div class="mb-6 rounded-2xl bg-white p-6 shadow-lg ring-1 ring-slate-200">
                    <div :class="result.exam_type === 'full' ? 'grid grid-cols-1 gap-6 md:grid-cols-3' : 'grid grid-cols-1 gap-6 md:grid-cols-2'">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100">
                                <svg class="h-6 w-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-500 uppercase">User</div>
                                <div class="text-sm font-bold text-slate-900">{{ result.username }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100">
                                <svg class="h-6 w-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-500 uppercase">Email</div>
                                <div class="text-sm font-bold text-slate-900">{{ result.email }}</div>
                            </div>
                        </div>
                        <div v-if="result.exam_type === 'full'" class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100">
                                <svg class="h-6 w-6 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-500 uppercase">Overall Score</div>
                                <div class="text-2xl font-black text-emerald-600">{{ overallScore || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs Section -->
                <div class="rounded-2xl bg-white shadow-lg ring-1 ring-slate-200">
                    <!-- Tab Navigation -->
                    <div class="border-b border-slate-200">
                        <nav class="flex gap-4 px-6" aria-label="Tabs">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    'flex items-center gap-2 border-b-2 px-4 py-4 text-sm font-semibold transition-colors duration-200',
                                    activeTab === tab.id
                                        ? 'border-blue-600 text-blue-600'
                                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                                ]"
                            >
                                <component :is="tab.icon" class="h-5 w-5" />
                                {{ tab.label }}
                                <span
                                    v-if="tab.score !== null"
                                    :class="[
                                        'ml-2 rounded-full px-2.5 py-0.5 text-xs font-bold',
                                        activeTab === tab.id ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600',
                                    ]"
                                >
                                    {{ tab.score }}
                                </span>
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="p-6">
                        <!-- Listening Tab -->
                        <div v-if="activeTab === 'listening'" class="space-y-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-2xl font-bold text-slate-900">Listening Results</h3>
                                <div class="flex items-center gap-4">
                                    <div class="text-sm">
                                        <span class="font-semibold text-emerald-600">{{ listeningStats.correct }}</span>
                                        <span class="text-slate-500"> correct</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="font-semibold text-red-600">{{ listeningStats.wrong }}</span>
                                        <span class="text-slate-500"> wrong</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="font-semibold text-slate-600">{{ listeningStats.total }}</span>
                                        <span class="text-slate-500"> total</span>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                <table class="w-full">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">Question</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                                Your Answer
                                            </th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                                Correct Answer
                                            </th>
                                            <th class="px-6 py-4 text-center text-xs font-semibold tracking-wide text-slate-600 uppercase">Result</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white">
                                        <tr
                                            v-for="(answer, index) in listeningAnswers"
                                            :key="index"
                                            :class="answer.isCorrect ? 'bg-emerald-50/50' : 'bg-red-50/50'"
                                        >
                                            <td class="px-6 py-4 text-sm font-semibold text-slate-900">{{ index + 1 }}</td>
                                            <td class="px-6 py-4 text-sm text-slate-700">
                                                <span :class="answer.isCorrect ? 'font-semibold text-emerald-700' : 'text-red-700'">
                                                    {{ answer.userAnswer || '-' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-medium text-slate-700">{{ answer.correctAnswer }}</td>
                                            <td class="px-6 py-4 text-center">
                                                <span
                                                    v-if="answer.isCorrect"
                                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                    Correct
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                    Wrong
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Reading Tab -->
                        <div v-if="activeTab === 'reading'" class="space-y-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-2xl font-bold text-slate-900">Reading Results</h3>
                                <div class="flex items-center gap-4">
                                    <div class="text-sm">
                                        <span class="font-semibold text-emerald-600">{{ readingStats.correct }}</span>
                                        <span class="text-slate-500"> correct</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="font-semibold text-red-600">{{ readingStats.wrong }}</span>
                                        <span class="text-slate-500"> wrong</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="font-semibold text-slate-600">{{ readingStats.total }}</span>
                                        <span class="text-slate-500"> total</span>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                <table class="w-full">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">Question</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                                Your Answer
                                            </th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-slate-600 uppercase">
                                                Correct Answer
                                            </th>
                                            <th class="px-6 py-4 text-center text-xs font-semibold tracking-wide text-slate-600 uppercase">Result</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white">
                                        <tr
                                            v-for="(answer, index) in readingAnswers"
                                            :key="index"
                                            :class="answer.isCorrect ? 'bg-emerald-50/50' : 'bg-red-50/50'"
                                        >
                                            <td class="px-6 py-4 text-sm font-semibold text-slate-900">{{ index + 1 }}</td>
                                            <td class="px-6 py-4 text-sm text-slate-700">
                                                <span :class="answer.isCorrect ? 'font-semibold text-emerald-700' : 'text-red-700'">
                                                    {{ answer.userAnswer || '-' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-medium text-slate-700">{{ answer.correctAnswer }}</td>
                                            <td class="px-6 py-4 text-center">
                                                <span
                                                    v-if="answer.isCorrect"
                                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                    Correct
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                    Wrong
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Writing Tab -->
                        <div v-if="activeTab === 'writing'" class="space-y-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-2xl font-bold text-slate-900">Writing Submission</h3>
                                <div v-if="result.writing?.band_score" class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-600">Band Score:</span>
                                    <span class="rounded-lg bg-purple-100 px-4 py-2 text-lg font-bold text-purple-700">
                                        {{ result.writing.band_score }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="result.writing" class="space-y-6">
                                <!-- Writing Tasks (Question + Answer) -->
                                <div v-for="(task, index) in writingTasks" :key="index">
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
                                                <div
                                                    v-if="index === 0 && (task.image || (result?.local_exam_id && getWritingImagePath(result.local_exam_id)))"
                                                    class="mt-4"
                                                >
                                                    <img
                                                        :src="task.image || getWritingImagePath(result.local_exam_id)"
                                                        :alt="`Writing question for ${result?.local_exam_id ?? ''}`"
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
                                                v-if="task.ans"
                                                class="min-h-[200px] rounded-lg border-2 border-emerald-200 bg-gradient-to-br from-emerald-50/50 to-teal-50/50 p-4"
                                            >
                                                <p class="text-base leading-relaxed whitespace-pre-wrap text-slate-800">{{ task.ans }}</p>
                                            </div>
                                            <div v-else class="rounded-lg bg-slate-100 p-4 text-center">
                                                <p class="text-sm text-slate-500 italic">No answer provided</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Writing Evaluation Section -->
                                <div
                                    class="sticky bottom-0 rounded-2xl border-2 border-indigo-200 bg-gradient-to-r from-indigo-50 to-purple-50 p-6 shadow-2xl"
                                >
                                    <div class="flex flex-col gap-4">
                                        <!-- Evaluate by AI Button -->
                                        <div class="flex flex-col items-end gap-2 py-4">
                                            <button
                                                @click="evaluateByAi"
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
                                                <svg
                                                    v-else
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"
                                                    />
                                                </svg>
                                                {{ isEvaluating ? 'Evaluating...' : 'Evaluate by AI' }}
                                            </button>
                                            <p v-if="evaluationError" class="text-sm text-red-600">{{ evaluationError }}</p>
                                        </div>

                                        <!-- Task Scores Grid -->
                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <!-- Task 1 Score & Feedback -->
                                            <div class="rounded-xl border-2 border-blue-200 bg-blue-50/50 p-4">
                                                <div class="mb-3 grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-bold text-blue-900">TA (Task Achievement)</label>
                                                        <input
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
                                                        <label class="mb-1 block text-xs font-bold text-blue-900">CC (Coherence & Cohesion)</label>
                                                        <input
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
                                                        <label class="mb-1 block text-xs font-bold text-blue-900">LR (Lexical Resource)</label>
                                                        <input
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
                                                        <label class="mb-1 block text-xs font-bold text-blue-900">GRA (Grammar)</label>
                                                        <input
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
                                                <div class="mb-3">
                                                    <label class="mb-2 flex items-center gap-2 text-sm font-bold text-blue-900">
                                                        <div
                                                            class="flex h-6 w-6 items-center justify-center rounded bg-blue-600 text-xs font-bold text-white"
                                                        >
                                                            1
                                                        </div>
                                                        Task 1 Band Score
                                                        <span class="text-xs font-normal text-blue-700">(Auto-calculated)</span>
                                                    </label>
                                                    <input
                                                        v-model="writingEditForm.task1_band"
                                                        type="number"
                                                        step="0.5"
                                                        min="0"
                                                        max="9"
                                                        readonly
                                                        class="block w-full rounded-lg border-2 border-blue-300 bg-white px-4 py-2 text-center text-lg font-bold shadow-sm"
                                                        placeholder="0.0 - 9.0"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="mb-2 flex items-center gap-2 text-xs font-bold text-blue-900">
                                                        <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path
                                                                d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"
                                                            ></path>
                                                        </svg>
                                                        Task 1 Feedback
                                                    </label>
                                                    <textarea
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
                                                <div class="mb-3 grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-bold text-purple-900">TR (Task Response)</label>
                                                        <input
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
                                                        <label class="mb-1 block text-xs font-bold text-purple-900">CC (Coherence & Cohesion)</label>
                                                        <input
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
                                                        <label class="mb-1 block text-xs font-bold text-purple-900">LR (Lexical Resource)</label>
                                                        <input
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
                                                        <label class="mb-1 block text-xs font-bold text-purple-900">GRA (Grammar)</label>
                                                        <input
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
                                                <div class="mb-3">
                                                    <label class="mb-2 flex items-center gap-2 text-sm font-bold text-purple-900">
                                                        <div
                                                            class="flex h-6 w-6 items-center justify-center rounded bg-purple-600 text-xs font-bold text-white"
                                                        >
                                                            2
                                                        </div>
                                                        Task 2 Band Score
                                                        <span class="text-xs font-normal text-purple-700">(Auto-calculated)</span>
                                                    </label>
                                                    <input
                                                        v-model="writingEditForm.task2_band"
                                                        type="number"
                                                        step="0.5"
                                                        min="0"
                                                        max="9"
                                                        readonly
                                                        class="block w-full rounded-lg border-2 border-purple-300 bg-white px-4 py-2 text-center text-lg font-bold shadow-sm"
                                                        placeholder="0.0 - 9.0"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="mb-2 flex items-center gap-2 text-xs font-bold text-purple-900">
                                                        <svg class="h-4 w-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path
                                                                d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"
                                                            ></path>
                                                        </svg>
                                                        Task 2 Feedback
                                                    </label>
                                                    <textarea
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

                                        <!-- Overall Writing Band Score -->
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
                                                    <span class="ml-2 text-xs text-emerald-700">(Task1 + Task2x2) / 3</span>
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

                                        <!-- General Teacher Feedback -->
                                        <div>
                                            <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-900">
                                                <svg class="h-5 w-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                                                    <path
                                                        d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"
                                                    ></path>
                                                </svg>
                                                General Feedback (Optional)
                                            </label>
                                            <textarea
                                                ref="generalFeedbackRef"
                                                v-model="writingEditForm.teacher_feedback"
                                                rows="2"
                                                @input="autoResize($event)"
                                                class="block w-full resize-none overflow-hidden rounded-xl border-2 border-indigo-300 bg-white px-4 py-3 text-sm shadow-sm transition-all duration-200 hover:border-indigo-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/30"
                                                placeholder="Optional: Overall feedback for both tasks..."
                                            ></textarea>
                                        </div>

                                        <!-- Save Button -->
                                        <div class="flex gap-3">
                                            <button
                                                type="button"
                                                @click="saveWritingChanges"
                                                :disabled="savingWriting"
                                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-8 py-3 text-sm font-bold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-emerald-700 hover:to-teal-700 focus:ring-4 focus:ring-emerald-500/50 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                <svg v-if="savingWriting" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path
                                                        class="opacity-75"
                                                        fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                    ></path>
                                                </svg>
                                                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                {{ savingWriting ? 'Saving...' : 'Save Scores' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="rounded-xl border border-slate-200 bg-slate-50 p-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                <p class="mt-4 text-sm text-slate-500">No writing submission available</p>
                            </div>
                        </div>

                        <!-- Speaking Tab -->
                        <div v-if="activeTab === 'speaking'" class="space-y-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-2xl font-bold text-slate-900">Speaking Results</h3>
                                <div v-if="result.speaking?.band_score" class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-600">Band Score:</span>
                                    <span class="rounded-lg bg-orange-100 px-4 py-2 text-lg font-bold text-orange-700">
                                        {{ result.speaking.band_score }}
                                    </span>
                                </div>
                            </div>

                            <!-- Speaking Status Card (only if speaking data exists) -->
                            <div
                                v-if="result.speaking"
                                class="rounded-xl border-2 shadow-lg"
                                :class="
                                    result.speaking.status === 'completed'
                                        ? 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-teal-50'
                                        : 'border-orange-200 bg-gradient-to-br from-orange-50 to-amber-50'
                                "
                            >
                                <div
                                    class="border-b px-5 py-3"
                                    :class="result.speaking.status === 'completed' ? 'border-emerald-200' : 'border-orange-200'"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-xl shadow"
                                                :class="result.speaking.status === 'completed' ? 'bg-emerald-600' : 'bg-orange-500'"
                                            >
                                                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-bold text-slate-900">Speaking Test</h4>
                                            </div>
                                        </div>
                                        <div v-if="result.speaking.band_score" class="text-right">
                                            <p class="text-xs font-semibold text-slate-500 uppercase">Current Score</p>
                                            <p
                                                class="text-3xl font-black"
                                                :class="result.speaking.status === 'completed' ? 'text-emerald-600' : 'text-orange-600'"
                                            >
                                                {{ result.speaking.band_score }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Existing Teacher Feedback Display -->
                                <div v-if="result.speaking.teacher_feedback" class="bg-white px-5 py-4">
                                    <div class="mb-2 flex items-center gap-2">
                                        <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                                        </svg>
                                        <span class="text-xs font-bold tracking-wide text-blue-700 uppercase">Current Feedback</span>
                                    </div>
                                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
                                        <p class="text-sm leading-relaxed text-blue-900">{{ result.speaking.teacher_feedback }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Speaking Edit Section (always visible) -->
                            <div class="rounded-2xl border-2 border-orange-200 bg-gradient-to-r from-orange-50 to-amber-50 p-6 shadow-2xl">
                                <div class="mb-5 flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-amber-500 shadow"
                                    >
                                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-slate-900">Update Speaking Score</h4>
                                        <p class="text-xs text-slate-500">Set or update the band score and feedback</p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <!-- Band Score Input -->
                                    <div class="rounded-xl border-2 border-orange-200 bg-white p-4">
                                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-orange-900">
                                            <svg class="h-5 w-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            Speaking Band Score
                                        </label>
                                        <input
                                            v-model="speakingEditForm.band_score"
                                            type="number"
                                            step="0.5"
                                            min="0"
                                            max="9"
                                            class="block w-full rounded-lg border-2 border-orange-300 bg-white px-4 py-3 text-center text-xl font-bold shadow-sm transition-all duration-200 hover:border-orange-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/30"
                                            placeholder="0.0 - 9.0"
                                        />
                                    </div>

                                    <!-- Feedback Textarea -->
                                    <div>
                                        <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-900">
                                            <svg class="h-5 w-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                                                <path
                                                    d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"
                                                ></path>
                                            </svg>
                                            Teacher Feedback
                                        </label>
                                        <textarea
                                            ref="speakingFeedbackRef"
                                            v-model="speakingEditForm.teacher_feedback"
                                            rows="3"
                                            @input="autoResize($event)"
                                            class="block w-full resize-none overflow-hidden rounded-xl border-2 border-orange-300 bg-white px-4 py-3 text-sm shadow-sm transition-all duration-200 hover:border-orange-400 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/30"
                                            placeholder="Enter feedback for the student's speaking performance..."
                                        ></textarea>
                                    </div>

                                    <!-- Save Button -->
                                    <div class="flex gap-3">
                                        <button
                                            type="button"
                                            @click="saveSpeakingChanges"
                                            :disabled="savingSpeaking"
                                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 px-8 py-3 text-sm font-bold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-orange-700 hover:to-amber-700 focus:ring-4 focus:ring-orange-500/50 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <svg v-if="savingSpeaking" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path
                                                    class="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                ></path>
                                            </svg>
                                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            {{ savingSpeaking ? 'Saving...' : 'Save Speaking Score' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PDF Viewer Modal -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showPdfModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click="showPdfModal = false">
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div v-if="showPdfModal" class="relative flex h-[90vh] w-full max-w-7xl flex-col rounded-2xl bg-white shadow-2xl" @click.stop>
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">Result PDF</h3>
                                    <p class="text-sm text-slate-600">{{ result.username }} - Exam #{{ result.exam_id }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <input ref="pdfFileInput" type="file" accept="application/pdf" @change="handlePdfUpload" class="hidden" />
                                <button
                                    @click="$refs.pdfFileInput.click()"
                                    :disabled="uploadingPdf"
                                    class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white transition-all duration-200 hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    title="Upload annotated PDF to replace current"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                        />
                                    </svg>
                                    {{ uploadingPdf ? 'Uploading...' : 'Upload Annotated' }}
                                </button>
                                <button
                                    @click="sendPdfToUser"
                                    :disabled="sendingPdf"
                                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition-all duration-200 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    title="Send PDF to user's email"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    {{ sendingPdf ? 'Sending...' : 'Send to User' }}
                                </button>
                                <button
                                    @click="showPdfModal = false"
                                    class="rounded-lg p-2 text-slate-400 transition-colors duration-200 hover:bg-slate-100 hover:text-slate-600"
                                    title="Close modal"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- PDF Viewer -->
                        <div class="relative flex-1 overflow-hidden bg-slate-50 p-4">
                            <!-- Loading State -->
                            <Transition
                                enter-active-class="transition-opacity duration-200"
                                enter-from-class="opacity-0"
                                enter-to-class="opacity-100"
                                leave-active-class="transition-opacity duration-200"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div v-if="pdfLoading" class="absolute inset-4 z-10 flex flex-col items-center justify-center rounded-lg bg-white">
                                    <div class="relative">
                                        <!-- Animated Spinner -->
                                        <svg
                                            class="h-16 w-16 animate-spin text-blue-600"
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
                                    </div>
                                    <p class="mt-6 text-base font-semibold text-slate-700">Loading PDF...</p>
                                    <p class="mt-2 text-sm text-slate-500">Please wait while we prepare your document</p>
                                </div>
                            </Transition>

                            <!-- PDF iframe -->
                            <iframe
                                v-if="pdfUrl"
                                :src="pdfUrl"
                                @load="pdfLoading = false"
                                class="h-full w-full rounded-lg border border-slate-300 bg-white shadow-inner"
                                frameborder="0"
                            ></iframe>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { examResults } from '@/pages/Results/json/examResults';
import { Link, router } from '@inertiajs/vue3';
import { computed, h, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    result: Object,
    rawJson: String,
});

const activeTab = ref('listening');
const sending = ref(false);
const showPdfModal = ref(false);
const sendingPdf = ref(false);
const pdfLoading = ref(true);
const uploadingPdf = ref(false);
const pdfFileInput = ref(null);
const isEvaluating = ref(false);
const evaluationError = ref(null);
const savingWriting = ref(false);
const task1FeedbackRef = ref(null);
const task2FeedbackRef = ref(null);
const generalFeedbackRef = ref(null);
const speakingFeedbackRef = ref(null);
const savingSpeaking = ref(false);

// Speaking edit form
const speakingEditForm = ref({
    band_score:
        props.result?.speaking?.band_score !== null && props.result?.speaking?.band_score !== undefined ? props.result.speaking.band_score : '',
    teacher_feedback: props.result?.speaking?.teacher_feedback || '',
});

// Helper to get score value, allowing 0 as valid
const getScore = (value) => (value !== null && value !== undefined ? value : '');

// Writing evaluation form - mirrors Results.vue pattern
const writingEditForm = ref({
    task1_ta: getScore(props.result?.writing?.task1_ta),
    task1_cc: getScore(props.result?.writing?.task1_cc),
    task1_lr: getScore(props.result?.writing?.task1_lr),
    task1_gra: getScore(props.result?.writing?.task1_gra),
    task1_band: getScore(props.result?.writing?.task1_band),
    task1_feedback: props.result?.writing?.task1_feedback || '',
    task2_ta: getScore(props.result?.writing?.task2_ta),
    task2_cc: getScore(props.result?.writing?.task2_cc),
    task2_lr: getScore(props.result?.writing?.task2_lr),
    task2_gra: getScore(props.result?.writing?.task2_gra),
    task2_band: getScore(props.result?.writing?.task2_band),
    task2_feedback: props.result?.writing?.task2_feedback || '',
    band_score: getScore(props.result?.writing?.band_score),
    teacher_feedback: props.result?.writing?.teacher_feedback || '',
});

// Auto-calculate Task 1 band score
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

// Auto-calculate Task 2 band score
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

computed(() => {
    const missing = [];
    if (props.result.listening?.band_score === null || props.result.listening?.band_score === undefined) {
        missing.push('Listening');
    }
    if (props.result.reading?.band_score === null || props.result.reading?.band_score === undefined) {
        missing.push('Reading');
    }
    if (props.result.writing?.band_score === null || props.result.writing?.band_score === undefined) {
        missing.push('Writing');
    }
    if (props.result.speaking?.band_score === null || props.result.speaking?.band_score === undefined) {
        missing.push('Speaking');
    }
    return missing;
});
const handleViewPdfClick = () => {
    showPdfModal.value = true;
};

// Icons as render functions
const ListeningIcon = () =>
    h('svg', { class: 'h-5 w-5', fill: 'currentColor', viewBox: '0 0 20 20' }, [
        h('path', {
            'fill-rule': 'evenodd',
            d: 'M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z',
            'clip-rule': 'evenodd',
        }),
    ]);

const ReadingIcon = () =>
    h('svg', { class: 'h-5 w-5', fill: 'currentColor', viewBox: '0 0 20 20' }, [
        h('path', {
            d: 'M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z',
        }),
    ]);

const WritingIcon = () =>
    h('svg', { class: 'h-5 w-5', fill: 'currentColor', viewBox: '0 0 20 20' }, [
        h('path', { d: 'M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z' }),
    ]);
// Get correct answers from examResults
const correctAnswers = computed(() => {
    if (!props.result?.local_exam_id) {
        console.error('No local_exam_id found in result');
        return null;
    }

    const localExamId = String(props.result.local_exam_id).trim();
    return examResults[localExamId] || null;
});

// Process listening answers
const listeningAnswers = computed(() => {
    if (!props.result.listening || !correctAnswers.value?.listening) {
        return [];
    }

    const answers = [];
    const userAnswers = props.result.listening.user_answers ?? {};
    const correct = correctAnswers.value.listening;

    Object.keys(correct).forEach((key) => {
        const userAnswer = userAnswers[`q${key}`];
        const correctAnswer = correct[key];

        const isCorrect = answersMatch(userAnswer, correctAnswer);

        answers.push({
            userAnswer,
            correctAnswer,
            isCorrect,
        });
    });

    return answers;
});

// Process reading answers
const readingAnswers = computed(() => {
    if (!props.result.reading || !correctAnswers.value?.reading) {
        return [];
    }

    const answers = [];
    const userAnswers = props.result.reading.user_answers ?? {};
    const correct = correctAnswers.value.reading;

    Object.keys(correct).forEach((key) => {
        const userAnswer = userAnswers[`q${key}`];
        const correctAnswer = correct[key];

        const isCorrect = answersMatch(userAnswer, correctAnswer);

        answers.push({
            userAnswer,
            correctAnswer,
            isCorrect,
        });
    });

    return answers;
});

// Calculate statistics
const listeningStats = computed(() => {
    const total = listeningAnswers.value.length;
    const correct = listeningAnswers.value.filter((a) => a.isCorrect).length;
    const wrong = total - correct;

    return { correct, wrong, total };
});

const readingStats = computed(() => {
    const total = readingAnswers.value.length;
    const correct = readingAnswers.value.filter((a) => a.isCorrect).length;
    const wrong = total - correct;

    return { correct, wrong, total };
});

// Process writing tasks
const writingTasks = computed(() => {
    if (!props.result.writing) {
        return [];
    }

    const tasks = [];
    const writing = props.result.writing;

    // Extract tasks from indexed object (0, 1, 2, etc.)
    Object.keys(writing).forEach((key) => {
        // Only process numeric keys (tasks)
        if (!isNaN(key)) {
            tasks.push(writing[key]);
        }
    });

    return tasks;
});

// Normalize answer for comparison (case-insensitive, trim whitespace, remove punctuation, etc.)
function normalizeAnswer(answer) {
    if (!answer) return '';

    let normalized = String(answer).toLowerCase().trim();

    // Remove ordinal suffixes (st, nd, rd, th) from numbers
    normalized = normalized.replace(/\b(\d+)(st|nd|rd|th)\b/g, '$1');

    // Remove punctuation and extra spaces
    normalized = normalized
        .replace(/[.,!?;:'"()-]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    return normalized;
}

// Check if two answers match (handles alternative answers separated by /)
function answersMatch(userAnswer, correctAnswer) {
    if (!userAnswer || !correctAnswer) return false;

    const normalizedUser = normalizeAnswer(userAnswer);

    // Handle multiple correct answers separated by "/"
    const possibleAnswers = String(correctAnswer)
        .split('/')
        .map((a) => normalizeAnswer(a.trim()));

    // Check if user answer matches any of the possible correct answers
    return possibleAnswers.some((possibleAnswer) => {
        // Direct match
        if (possibleAnswer === normalizedUser) return true;

        // Check for partial matches in compound answers
        if (possibleAnswer.includes(' ') && normalizedUser.includes(' ')) {
            const possibleWords = possibleAnswer.split(' ');
            const userWords = normalizedUser.split(' ');
            return possibleWords.every((word) => userWords.includes(word)) || userWords.every((word) => possibleWords.includes(word));
        }

        return false;
    });
}

// Calculate overall score using IELTS rounding rules
const overallScore = computed(() => {
    const scores = [];

    // Collect all four section scores
    if (props.result.listening?.band_score !== null && props.result.listening?.band_score !== undefined) {
        scores.push(parseFloat(props.result.listening.band_score));
    }

    if (props.result.reading?.band_score !== null && props.result.reading?.band_score !== undefined) {
        scores.push(parseFloat(props.result.reading.band_score));
    }

    // For writing, check band_score in the writing structure
    if (props.result.writing?.band_score !== null && props.result.writing?.band_score !== undefined) {
        scores.push(parseFloat(props.result.writing.band_score));
    }

    if (props.result.speaking?.band_score !== null && props.result.speaking?.band_score !== undefined) {
        scores.push(parseFloat(props.result.speaking.band_score));
    }

    // Need all 4 sections to calculate overall score
    if (scores.length !== 4) return null;

    // Calculate average
    const sum = scores.reduce((a, b) => a + b, 0);
    const average = sum / 4;

    // Apply IELTS rounding rules
    const rounded = roundToIELTS(average);

    return rounded.toFixed(1);
});

// IELTS Rounding Rules:
// .00 - .24 → round down to .0
// .25 - .74 → round to .5
// .75 - .99 → round up to the next whole number
function roundToIELTS(score) {
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
}

const tabs = computed(() => {
    const allTabs = [
        {
            id: 'listening',
            label: 'Listening',
            icon: ListeningIcon,
            score: listeningStats.value.total > 0 ? `${listeningStats.value.correct}/${listeningStats.value.total}` : null,
        },
        {
            id: 'reading',
            label: 'Reading',
            icon: ReadingIcon,
            score: readingStats.value.total > 0 ? `${readingStats.value.correct}/${readingStats.value.total}` : null,
        },
        {
            id: 'writing',
            label: 'Writing',
            icon: WritingIcon,
            score: null,
        },
        {
            id: 'speaking',
            label: 'Speaking',
            icon: h('svg', { class: 'w-5 h-5', fill: 'currentColor', viewBox: '0 0 20 20' }, [
                h('path', {
                    'fill-rule': 'evenodd',
                    d: 'M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z',
                    'clip-rule': 'evenodd',
                }),
            ]),
            score: props.result.speaking?.band_score || null,
        },
    ];

    // Filter tabs based on exam_type and modules
    if (props.result.exam_type === 'full') {
        // Show all tabs for full exam
        return allTabs;
    } else if (props.result.modules && props.result.modules.length > 0) {
        // Show only tabs for included modules in partial exam
        return allTabs.filter((tab) => props.result.modules.includes(tab.id));
    }

    // Fallback to showing all tabs
    return allTabs;
});

// Computed property for PDF URL
const pdfUrl = computed(() => {
    return `/results/${props.result.id}/pdf`;
});

// Reset loading state when modal opens
watch(showPdfModal, (newValue) => {
    if (newValue) {
        pdfLoading.value = true;
    }
});

const handleSendEmailClick = () => {
    if (sending.value) return;
    sending.value = true;
    router.post(
        `/results/${props.result.id}/send-email`,
        {},
        {
            onSuccess: () => {
                sending.value = false;
            },
            onError: (errors) => {
                console.error(errors);
                sending.value = false;
            },
        },
    );
};

const handlePdfUpload = async (event: any) => {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file type
    if (file.type !== 'application/pdf') {
        alert('Please select a valid PDF file');
        return;
    }

    // Validate file size (max 10MB)
    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
        alert('PDF file size must be less than 10MB');
        return;
    }

    uploadingPdf.value = true;

    // Create FormData to send file
    const formData = new FormData();
    formData.append('pdf', file);

    router.post(`/results/${props.result.id}/upload-pdf`, formData, {
        onSuccess: () => {
            uploadingPdf.value = false;
            // Reset file input
            if (pdfFileInput.value) {
                pdfFileInput.value.value = '';
            }
            // Reload the PDF in iframe
            pdfLoading.value = true;
            setTimeout(() => {
                const iframe = document.querySelector('iframe');
                if (iframe) {
                    iframe.src = `/results/${props.result.id}/pdf?t=${Date.now()}`;
                }
            }, 100);
        },
        onError: (errors) => {
            console.error(errors);
            uploadingPdf.value = false;
            alert('Failed to upload PDF. Please try again.');
            // Reset file input
            if (pdfFileInput.value) {
                pdfFileInput.value.value = '';
            }
        },
    });
};

const sendPdfToUser = () => {
    if (sendingPdf.value) return;

    sendingPdf.value = true;

    router.post(
        `/results/${props.result.id}/send-pdf`,
        {},
        {
            onSuccess: () => {
                sendingPdf.value = false;
            },
            onError: (errors) => {
                console.error(errors);
                sendingPdf.value = false;
            },
        },
    );
};

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

// Auto-expand textareas on mount if they have content
onMounted(() => {
    nextTick(() => {
        autoResizeElement(task1FeedbackRef.value);
        autoResizeElement(task2FeedbackRef.value);
        autoResizeElement(generalFeedbackRef.value);
        autoResizeElement(speakingFeedbackRef.value);
    });
});

// Writing evaluation helpers
const getWritingImagePath = (examId) => {
    if (!examId) return null;
    return `/images/writing/${examId}.jpg`;
};

const handleImageError = (event) => {
    const img = event.target;
    if (img.src.endsWith('.jpg')) {
        img.src = img.src.replace('.jpg', '.png');
    } else {
        img.style.display = 'none';
    }
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
    ];
    return themes[index % themes.length];
};

const getTaskTitle = (index) => {
    const titles = ['Task 1 - Academic Writing', 'Task 2 - Essay Writing'];
    return titles[index] || `Task ${index + 1}`;
};

const calculateWritingBandScore = (task1Score, task2Score) => {
    const rawScore = (task1Score + task2Score * 2) / 3;
    return Math.round(rawScore * 2) / 2;
};

const getCsrfToken = () => {
    const cookie = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
};

const evaluateByAi = async () => {
    const tasks = writingTasks.value;
    if (!tasks || tasks.length < 2) {
        evaluationError.value = 'At least two writing tasks are required for evaluation.';
        return;
    }

    if (!tasks[0]?.ans || !tasks[1]?.ans) {
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
                writing: tasks.slice(0, 2).map((t) => ({ q: t.q || '', ans: t.ans || '' })),
            }),
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to evaluate writing.');
        }

        const evaluation = result.data;

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

const saveWritingChanges = () => {
    if (savingWriting.value) return;
    savingWriting.value = true;

    const parseScore = (value) => {
        const parsed = parseFloat(String(value));
        return isNaN(parsed) ? null : parsed;
    };

    const task1Band = parseScore(writingEditForm.value.task1_band);
    const task2Band = parseScore(writingEditForm.value.task2_band);

    let calculatedBandScore = null;
    if (task1Band !== null && task2Band !== null) {
        calculatedBandScore = calculateWritingBandScore(task1Band, task2Band);
    }

    let updatedWriting;
    if (Array.isArray(props.result.writing)) {
        updatedWriting = props.result.writing;
    } else if (typeof props.result.writing === 'object') {
        updatedWriting = { ...props.result.writing };
    } else {
        updatedWriting = props.result.writing;
    }

    router.put(
        `/results/${props.result.id}/writing`,
        {
            writing: updatedWriting,
            task1_ta: parseScore(writingEditForm.value.task1_ta),
            task1_cc: parseScore(writingEditForm.value.task1_cc),
            task1_lr: parseScore(writingEditForm.value.task1_lr),
            task1_gra: parseScore(writingEditForm.value.task1_gra),
            task1_band: task1Band,
            task1_feedback: writingEditForm.value.task1_feedback || null,
            task2_ta: parseScore(writingEditForm.value.task2_ta),
            task2_cc: parseScore(writingEditForm.value.task2_cc),
            task2_lr: parseScore(writingEditForm.value.task2_lr),
            task2_gra: parseScore(writingEditForm.value.task2_gra),
            task2_band: task2Band,
            task2_feedback: writingEditForm.value.task2_feedback || null,
            band_score: calculatedBandScore,
            teacher_feedback: writingEditForm.value.teacher_feedback || null,
        },
        {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
                savingWriting.value = false;
            },
            onError: (errors) => {
                console.error('Error updating writing result:', errors);
                savingWriting.value = false;
                alert('Failed to update writing result. Please try again.');
            },
        },
    );
};

const saveSpeakingChanges = () => {
    if (savingSpeaking.value) return;
    savingSpeaking.value = true;

    const speakingBand = speakingEditForm.value.band_score;
    const updatedSpeaking = {
        ...(props.result.speaking || {}),
        band_score: speakingBand !== '' && speakingBand !== null ? parseFloat(speakingBand) : null,
        teacher_feedback: speakingEditForm.value.teacher_feedback || null,
    };

    router.put(
        `/results/${props.result.id}/speaking`,
        {
            speaking: updatedSpeaking,
        },
        {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
                savingSpeaking.value = false;
            },
            onError: (errors) => {
                console.error('Error updating speaking result:', errors);
                savingSpeaking.value = false;
                alert('Failed to update speaking result. Please try again.');
            },
        },
    );
};
</script>
