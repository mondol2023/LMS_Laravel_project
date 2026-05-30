<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

interface ExamForm {
    name: string;
    exam_id: string;
    description: string;
}

const form = useForm<ExamForm>({
    name: '',
    exam_id: '',
    description: '',
});

const generateExamId = () => {
    const lengths = [5, 6, 7];
    const length = lengths[Math.floor(Math.random() * lengths.length)];
    const min = Math.pow(10, length - 1);
    const max = Math.pow(10, length) - 1;
    form.exam_id = (Math.floor(Math.random() * (max - min + 1)) + min).toString();
};

const submit = () => {
    form.post('/admin/exams', {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Admin - Create Exam" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-6">
                    <h1 class="text-3xl font-bold text-gray-900">Create New Exam</h1>
                    <a href="/admin/exams" class="rounded-md bg-gray-600 px-4 py-2 text-white transition-colors hover:bg-gray-700"> Back to Exams </a>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Exam Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700"> Exam Name <span class="text-red-500">*</span> </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                :class="{ 'border-red-500': form.errors.name }"
                                placeholder="Enter exam name (e.g., IELTS Academic Mock Test #1)"
                                required
                            />
                            <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- Exam ID -->
                        <div>
                            <label for="exam_id" class="block text-sm font-medium text-gray-700"> Exam ID <span class="text-red-500">*</span> </label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input
                                    id="exam_id"
                                    v-model="form.exam_id"
                                    type="text"
                                    class="flex-1 rounded-l-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    :class="{ 'border-red-500': form.errors.exam_id }"
                                    placeholder="12345, 123456, or 1234567"
                                    required
                                />
                                <button
                                    type="button"
                                    @click="generateExamId"
                                    class="relative -ml-px inline-flex items-center rounded-r-md border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                >
                                    Generate
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Must be 5, 6, or 7 digits long</p>
                            <p v-if="form.errors.exam_id" class="mt-2 text-sm text-red-600">{{ form.errors.exam_id }}</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700"> Description </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                :class="{ 'border-red-500': form.errors.description }"
                                placeholder="Enter a brief description of the exam (optional)"
                            />
                            <p v-if="form.errors.description" class="mt-2 text-sm text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 pt-6">
                            <a
                                href="/admin/exams"
                                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                            >
                                Cancel
                            </a>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="mr-3 -ml-1 h-5 w-5 animate-spin text-white"
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
                                {{ form.processing ? 'Creating...' : 'Create Exam' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
