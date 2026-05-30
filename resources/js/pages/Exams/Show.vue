<template>
    <AppLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
            <div class="mx-auto max-w-4xl">
                <!-- Header Section -->
                <div class="mb-8 text-center">
                    <h1 class="mb-4 text-4xl font-bold text-slate-900">{{ exam.name }}</h1>
                    <div class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-4 py-2">
                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                        </svg>
                        <span class="text-sm font-semibold text-blue-800">
                            Exam ID: <code class="font-mono">{{ exam.exam_id }}</code>
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mb-8 flex justify-center gap-4">
                    <Link
                        :href="`/exams/${exam.id}/edit`"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:scale-105 hover:border-slate-400 hover:bg-slate-50 hover:shadow-md focus:ring-2 focus:ring-slate-500/20 focus:outline-none"
                    >
                        <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                        Edit Exam
                    </Link>
                    <Link
                        href="/exams"
                        class="inline-flex items-center rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-2 focus:outline-none"
                    >
                        <svg class="mr-2 -ml-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Exams
                    </Link>
                </div>

                <!-- Main Content Card -->
                <div class="rounded-2xl bg-white p-8 shadow-lg ring-1 ring-slate-200 backdrop-blur-sm">
                    <!-- Card Header -->
                    <div class="mb-8 flex items-center gap-4 border-b border-slate-200 pb-6">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-r from-blue-100 to-indigo-100">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900">Exam Overview</h3>
                            <p class="text-slate-600">Comprehensive exam details and information</p>
                        </div>
                    </div>

                    <!-- Exam Details Grid -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Exam Name -->
                        <div class="rounded-2xl bg-gradient-to-r from-slate-50 to-blue-50 p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a1.994 1.994 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                                        />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-slate-900">Exam Name</h4>
                            </div>
                            <p class="text-xl leading-tight font-bold text-slate-900">{{ exam.name }}</p>
                            <p class="mt-2 text-sm text-slate-600">Full examination title and identifier</p>
                        </div>

                        <!-- Exam ID -->
                        <div class="rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100">
                                    <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
                                        />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-slate-900">Exam ID</h4>
                            </div>
                            <p class="font-mono text-xl font-bold text-slate-900">{{ exam.exam_id }}</p>
                            <p class="mt-2 text-sm text-slate-600">Unique examination identifier</p>
                        </div>

                        <!-- Status -->
                        <div class="rounded-2xl bg-gradient-to-r from-emerald-50 to-green-50 p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100">
                                    <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-slate-900">Status</h4>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-3 w-3 rounded-full bg-emerald-400"></div>
                                <span class="text-xl font-bold text-emerald-800">Active</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">Ready for student participation</p>
                        </div>

                        <!-- Created Date -->
                        <div class="rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 p-6 shadow-sm ring-1 ring-slate-200">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100">
                                    <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-slate-900">Created</h4>
                            </div>
                            <p class="text-lg font-bold text-slate-900">{{ formatDate(exam.created_at) }}</p>
                            <p class="mt-2 text-sm text-slate-600">Exam creation timestamp</p>
                        </div>
                    </div>

                    <!-- Additional Info Section -->
                    <div class="mt-8 rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                                <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h4 class="mb-2 text-base font-semibold text-blue-900">Exam Information</h4>
                                <div class="space-y-1 text-sm text-blue-800">
                                    <p>• This IELTS mock examination is configured and ready for use</p>
                                    <p>• Students can access this exam using the provided exam ID</p>
                                    <p>• Use the Edit button above to modify exam settings if needed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    exam: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
