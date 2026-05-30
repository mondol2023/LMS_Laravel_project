<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Exam {
    id: number;
    name: string;
    exam_id: string;
    description?: string;
    created_at: string;
    results_count?: number;
}

interface Props {
    exams: {
        data: Exam[];
        meta: any;
        links: any;
    };
}

defineProps<Props>();

const showDeleteModal = ref(false);
const examToDelete = ref<Exam | null>(null);

const confirmDelete = (exam: Exam) => {
    examToDelete.value = exam;
    showDeleteModal.value = true;
};

const deleteExam = () => {
    if (examToDelete.value) {
        router.delete(`/admin/exams/${examToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                examToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Admin - Exams" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-6">
                    <h1 class="text-3xl font-bold text-gray-900">Exams Management</h1>
                    <Link href="/admin/exams/create" class="rounded-md bg-indigo-600 px-4 py-2 text-white transition-colors hover:bg-indigo-700">
                        Create New Exam
                    </Link>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Exams Table -->
            <div class="overflow-hidden bg-white shadow sm:rounded-md">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flow-root">
                        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                Exam Details
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Exam ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Created</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr v-for="exam in exams.data" :key="exam.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ exam.name }}</div>
                                                    <div v-if="exam.description" class="mt-1 text-sm text-gray-500">{{ exam.description }}</div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800"
                                                >
                                                    {{ exam.exam_id }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                                {{ new Date(exam.created_at).toLocaleDateString() }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                                <div class="flex justify-end gap-2">
                                                    <Link :href="`/admin/exams/${exam.id}`" class="text-indigo-600 hover:text-indigo-900">
                                                        View
                                                    </Link>
                                                    <Link :href="`/admin/exams/${exam.id}/edit`" class="text-yellow-600 hover:text-yellow-900">
                                                        Edit
                                                    </Link>
                                                    <button @click="confirmDelete(exam)" class="text-red-600 hover:text-red-900">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="exams.data.length === 0">
                                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                                No exams found.
                                                <Link href="/admin/exams/create" class="text-indigo-600 hover:text-indigo-900"
                                                    >Create your first exam</Link
                                                >
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="exams.links" class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing {{ exams.meta?.from || 0 }} to {{ exams.meta?.to || 0 }} of {{ exams.meta?.total || 0 }} results
                        </div>
                        <div class="flex gap-2">
                            <template v-for="link in exams.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="{
                                        'bg-indigo-600 text-white': link.active,
                                        'bg-white text-gray-900 hover:bg-gray-50': !link.active,
                                    }"
                                    class="rounded-md border px-3 py-2 text-sm"
                                    v-html="link.label"
                                />
                                <span v-else class="cursor-not-allowed rounded-md border px-3 py-2 text-sm text-gray-300" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="bg-opacity-75 fixed inset-0 bg-gray-500 transition-opacity" @click="showDeleteModal = false"></div>
                <div
                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                >
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"
                            >
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.582 16.5c-.77.833.192 2.5 1.732 2.5z"
                                    />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Exam</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Are you sure you want to delete "{{ examToDelete?.name }}"? This action cannot be undone and will also delete
                                        all associated results.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button
                            @click="deleteExam"
                            type="button"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Delete
                        </button>
                        <button
                            @click="showDeleteModal = false"
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
