<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    exams: Object,
    currentType: {
        type: String,
        default: 'exam',
    },
});

const showDeleteModal = ref(false);
const examToDelete = ref(null);

const isPracticeTab = computed(() => props.currentType === 'practice');

const switchTab = (type: string) => {
    router.get(
        '/exams',
        { type },
        {
            preserveState: false,
            preserveScroll: false,
        },
    );
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const deleteExam = (exam) => {
    examToDelete.value = exam;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (examToDelete.value) {
        router.delete(`/exams/${examToDelete.value.id}`, {
            onSuccess: () => {
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    examToDelete.value = null;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Exams',
        href: dashboard().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Exams</h1>
                <Link
                    v-if="!isPracticeTab"
                    href="/exams/create"
                    class="inline-flex items-center gap-2 bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Exam
                </Link>
            </div>

            <!-- Tabs -->
            <div class="mt-6 flex gap-1 border-b border-gray-200">
                <button
                    @click="switchTab('exam')"
                    :class="[
                        'px-4 py-2 text-sm font-medium transition-colors',
                        !isPracticeTab ? 'border-b-2 border-black text-gray-900' : 'text-gray-500 hover:text-gray-700',
                    ]"
                >
                    Exams
                </button>
                <button
                    @click="switchTab('practice')"
                    :class="[
                        'px-4 py-2 text-sm font-medium transition-colors',
                        isPracticeTab ? 'border-b-2 border-black text-gray-900' : 'text-gray-500 hover:text-gray-700',
                    ]"
                >
                    Practice
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="exams.data.length === 0" class="py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">No exams yet</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first exam.</p>
                <Link
                    v-if="!isPracticeTab"
                    href="/exams/create"
                    class="mt-6 inline-flex items-center gap-2 bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Exam
                </Link>
            </div>

            <!-- Table -->
            <div v-else class="mt-6 overflow-hidden border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">User Exam ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="exam in exams.data" :key="exam.id" class="transition-colors hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ exam.name }}</div>
                                <div v-if="exam.description" class="mt-0.5 max-w-xs truncate text-xs text-gray-500">{{ exam.description }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-sm text-gray-600">{{ exam.user_exam_id }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ formatDate(exam.created_at) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <Link
                                        v-if="!isPracticeTab"
                                        :href="`/exams/${exam.id}/results`"
                                        class="text-sm font-medium text-gray-600 hover:text-gray-900"
                                    >
                                        Results
                                    </Link>
                                    <Link
                                        v-if="!isPracticeTab"
                                        :href="`/exams/${exam.id}/edit`"
                                        class="text-sm font-medium text-gray-600 hover:text-gray-900"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        v-if="!isPracticeTab"
                                        @click="deleteExam(exam)"
                                        class="text-sm font-medium text-red-600 hover:text-red-800"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="exams.links.length > 3" class="border-t border-gray-200 bg-gray-50 px-6 py-3">
                    <div class="flex justify-center gap-1">
                        <template v-for="link in exams.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1.5 text-sm font-medium transition-colors',
                                    link.active ? 'bg-black text-white' : 'text-gray-500 hover:bg-gray-200 hover:text-gray-700',
                                ]"
                                v-html="link.label"
                            />
                            <span v-else class="cursor-not-allowed px-3 py-1.5 text-sm font-medium text-gray-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/40" @click="closeDeleteModal"></div>
                <div class="relative w-full max-w-sm border border-gray-200 bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-bold text-gray-900">Delete Exam</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Are you sure you want to delete
                        <span class="font-semibold text-gray-900">{{ examToDelete?.name }}</span
                        >? This cannot be undone.
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            @click="closeDeleteModal"
                            class="border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button @click="confirmDelete" class="bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
