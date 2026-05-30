<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface Exam {
    id: number;
    name: string;
    exam_id: string;
    uuid: string;
    description?: string;
    created_at: string;
    updated_at: string;
    results?: any[];
}

interface Props {
    exam: Exam;
}

defineProps<Props>();
</script>

<template>
    <Head :title="`Admin - ${exam.name}`" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ exam.name }}</h1>
                        <p class="mt-1 text-sm text-gray-500">Exam ID: {{ exam.exam_id }}</p>
                    </div>
                    <div class="flex space-x-3">
                        <Link
                            :href="`/admin/exams/${exam.id}/edit`"
                            class="rounded-md bg-yellow-600 px-4 py-2 text-white transition-colors hover:bg-yellow-700"
                        >
                            Edit Exam
                        </Link>
                        <Link href="/admin/exams" class="rounded-md bg-gray-600 px-4 py-2 text-white transition-colors hover:bg-gray-700">
                            Back to Exams
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Exam Details -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="mb-4 text-lg leading-6 font-medium text-gray-900">Exam Details</h3>

                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Exam Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ exam.name }}</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Exam ID</dt>
                                    <dd class="mt-1">
                                        <span
                                            class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800"
                                        >
                                            {{ exam.exam_id }}
                                        </span>
                                    </dd>
                                </div>

                                <div class="sm:col-span-2" v-if="exam.description">
                                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ exam.description }}</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ new Date(exam.created_at).toLocaleString() }}</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ new Date(exam.updated_at).toLocaleString() }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Exam URL -->
                    <div class="mt-8 bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="mb-4 text-lg leading-6 font-medium text-gray-900">Exam Access</h3>

                            <div class="rounded-md bg-gray-50 p-4">
                                <label class="mb-2 block text-sm font-medium text-gray-700">Exam URL</label>
                                <div class="flex">
                                    <input
                                        type="text"
                                        :value="`${$page.props.app.url}/exam/${exam.uuid}`"
                                        readonly
                                        class="block w-full flex-1 rounded-l-md border-gray-300 bg-gray-50 text-gray-500 sm:text-sm"
                                    />
                                    <button
                                        @click="navigator.clipboard.writeText(`${$page.props.app.url}/exam/${exam.uuid}`)"
                                        class="relative -ml-px inline-flex items-center rounded-r-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                    >
                                        Copy
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">Share this URL with students to access the exam</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exam Statistics -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow sm:rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="mb-4 text-lg leading-6 font-medium text-gray-900">Statistics</h3>

                            <dl class="space-y-4">
                                <div class="rounded-md bg-gray-50 p-4">
                                    <dt class="text-sm font-medium text-gray-500">Total Attempts</dt>
                                    <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                        {{ exam.results?.length || 0 }}
                                    </dd>
                                </div>

                                <div class="rounded-md bg-gray-50 p-4">
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        <span
                                            class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                                        >
                                            Active
                                        </span>
                                    </dd>
                                </div>
                            </dl>

                            <!-- Quick Actions -->
                            <div class="mt-6 border-t border-gray-200 pt-6">
                                <h4 class="mb-3 text-sm font-medium text-gray-900">Quick Actions</h4>
                                <div class="space-y-2">
                                    <Link
                                        :href="`/admin/exams/${exam.id}/results`"
                                        class="block w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-center text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                    >
                                        View Results
                                    </Link>
                                    <button
                                        @click="window.open(`/exam/${exam.uuid}`, '_blank')"
                                        class="block w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-center text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                    >
                                        Preview Exam
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
