<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
            <div class="mx-auto">
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="mb-4 text-4xl font-bold text-slate-900">Edit Exam</h1>
                    <p class="text-lg text-slate-600">Update exam details and configuration</p>
                </div>

                <!-- Main Form Card -->
                <div class="rounded-2xl bg-white p-8 shadow-lg ring-1 ring-slate-200 backdrop-blur-sm">
                    <form @submit.prevent="submit" class="space-y-8">
                        <!-- Current Exam Info Header -->
                        <div class="rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-blue-900">Editing: {{ exam.name }}</h3>
                                    <p class="text-sm text-blue-700">
                                        Exam ID: <span class="font-mono font-semibold">{{ exam.exam_id }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="space-y-8">
                            <!-- Exam Name -->
                            <div class="space-y-3">
                                <label for="name" class="block text-sm font-semibold text-slate-700">
                                    Exam Title <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-4 text-slate-900 placeholder-slate-400 shadow-sm transition-all duration-200 hover:border-slate-400 focus:border-blue-500 focus:shadow-md focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                                    placeholder="e.g., IELTS Academic Mock Test #1"
                                />
                                <div v-if="form.errors.name" class="flex items-center gap-2 text-sm text-red-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <!-- Exam IDs Row - 2 Columns -->
                            <div class="grid grid-cols-2 gap-6">
                                <!-- Exam ID (Read-only) -->
                                <div class="space-y-3">
                                    <label for="exam_id" class="block text-sm font-semibold text-slate-700">
                                        Exam ID
                                        <span class="ml-1 text-xs font-normal text-slate-500">(Read-only)</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            id="exam_id"
                                            type="text"
                                            :value="exam.exam_id"
                                            readonly
                                            class="block w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 font-mono text-slate-600 shadow-sm"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="text-sm text-slate-500">Cannot be modified after creation</p>
                                </div>

                                <!-- User Exam ID -->
                                <div class="space-y-3">
                                    <label for="user_exam_id" class="block text-sm font-semibold text-slate-700">
                                        User Exam ID <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="user_exam_id"
                                        v-model="form.user_exam_id"
                                        type="text"
                                        required
                                        class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-4 font-mono text-slate-900 placeholder-slate-400 shadow-sm transition-all duration-200 hover:border-slate-400 focus:border-blue-500 focus:shadow-md focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                                        placeholder="e.g., EXAM2024001"
                                    />
                                    <div v-if="form.errors.user_exam_id" class="flex items-center gap-2 text-sm text-red-600">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        {{ form.errors.user_exam_id }}
                                    </div>
                                    <p class="text-sm text-slate-500">Custom unique identifier</p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-10 flex justify-end gap-4 border-t border-slate-200 pt-8">
                            <Link
                                href="/exams"
                                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:scale-105 hover:border-slate-400 hover:bg-slate-50 hover:shadow-md focus:ring-2 focus:ring-slate-500/20 focus:outline-none"
                            >
                                <svg class="mr-2 -ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-2 focus:outline-none disabled:transform-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="mr-3 -ml-1 h-4 w-4 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 718-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <svg v-else class="mr-2 -ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span v-if="form.processing">Updating Exam...</span>
                                <span v-else>Update Exam</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    exam: Object,
});

const form = useForm({
    name: props.exam.name,
    description: props.exam.description || '',
    user_exam_id: props.exam.user_exam_id || '',
});

const submit = () => {
    form.put(`/exams/${props.exam.id}`);
};
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Edit Exam',
        href: dashboard().url,
    },
];
</script>
