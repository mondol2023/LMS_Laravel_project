<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-slate-900">Results</h1>
                    <p class="mt-2 text-slate-600">View and manage all exam results</p>
                </div>

                <!-- Filters Section -->
                <div class="mb-6 bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="grid gap-4 md:grid-cols-4">
                        <!-- Search Input -->
                        <div class="md:col-span-1">
                            <label for="search" class="mb-2 block text-sm font-medium text-slate-700"> Search </label>
                            <input
                                id="search"
                                v-model="filters.search"
                                type="text"
                                placeholder="Username"
                                class="block w-full border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                @input="debouncedSearch"
                            />
                        </div>

                        <!-- Exam Filter -->
                        <div class="md:col-span-1">
                            <label for="exam" class="mb-2 block text-sm font-medium text-slate-700"> Exam </label>
                            <select
                                id="exam"
                                v-model="filters.exam_id"
                                class="block w-full border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                @change="applyFilters"
                            >
                                <option value="">All Exams</option>
                                <option v-for="exam in exams" :key="exam.id" :value="exam.id">
                                    {{ exam.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Date From Filter -->
                        <div class="md:col-span-1">
                            <label for="date_from" class="mb-2 block text-sm font-medium text-slate-700"> From Date </label>
                            <input
                                id="date_from"
                                v-model="filters.date_from"
                                type="date"
                                placeholder="mm/dd/yyyy"
                                class="block w-full border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                @change="applyFilters"
                            />
                        </div>

                        <!-- Date To Filter -->
                        <div class="md:col-span-1">
                            <label for="date_to" class="mb-2 block text-sm font-medium text-slate-700"> To Date </label>
                            <input
                                id="date_to"
                                v-model="filters.date_to"
                                type="date"
                                placeholder="mm/dd/yyyy"
                                class="block w-full border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                @change="applyFilters"
                            />
                        </div>
                    </div>

                    <!-- Clear Filters Button -->
                    <div v-if="hasActiveFilters" class="mt-4 flex justify-end">
                        <button
                            @click="clearFilters"
                            class="inline-flex items-center border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:bg-slate-50 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 focus:outline-none"
                        >
                            <svg class="mr-2 -ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="overflow-hidden bg-white shadow-sm ring-1 ring-slate-200">
                    <div v-if="results.data.length === 0" class="p-12 text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center bg-slate-100">
                            <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-slate-900">No results found</h3>
                        <p class="text-sm text-slate-500">
                            {{ hasActiveFilters ? 'Try adjusting your filters' : 'No exam results available yet' }}
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase">
                                        User Info
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase">Exam</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase">
                                        Scores
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase">Date</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="result in results.data" :key="result.id" class="transition-colors hover:bg-slate-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center bg-indigo-100">
                                                <span class="text-sm font-semibold text-indigo-700">
                                                    {{ result.username.charAt(0).toUpperCase() }}
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-semibold text-slate-900">{{ result.username }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-slate-900">{{ result.exam?.name || 'N/A' }}</div>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span class="text-sm text-slate-500">ID: {{ result.exam?.exam_id || 'N/A' }}</span>
                                            <span
                                                :class="[
                                                    'inline-flex items-center px-2 py-0.5 text-xs font-semibold',
                                                    result.exam_type === 'full' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700',
                                                ]"
                                            >
                                                {{ result.exam_type === 'full' ? 'Full' : 'Partial' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <div
                                                v-if="result.exam_type === 'full' || (result.modules && result.modules.includes('listening'))"
                                                class="inline-flex items-center bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800"
                                            >
                                                L: {{ getScore(result.listening?.band_score) }}
                                            </div>
                                            <div
                                                v-if="result.exam_type === 'full' || (result.modules && result.modules.includes('reading'))"
                                                class="inline-flex items-center bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                                            >
                                                R: {{ getScore(result.reading?.band_score) }}
                                            </div>
                                            <div
                                                v-if="result.exam_type === 'full' || (result.modules && result.modules.includes('writing'))"
                                                class="inline-flex items-center bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800"
                                            >
                                                W: {{ getScore(result.writing?.band_score) }}
                                            </div>
                                            <div
                                                v-if="result.exam_type === 'full' || (result.modules && result.modules.includes('speaking'))"
                                                class="inline-flex items-center bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800"
                                            >
                                                S: {{ getScore(result.speaking?.band_score) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-500">
                                        {{ formatDate(result.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link
                                                :href="`/results/${result.id}/details`"
                                                class="inline-flex items-center border border-indigo-200 bg-white px-3 py-1.5 text-sm font-semibold text-indigo-700 shadow-sm transition-all duration-200 hover:bg-indigo-50"
                                            >
                                                View Details
                                            </Link>
                                            <button
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

                    <!-- Pagination -->
                    <div v-if="results.links.length > 3" class="border-t border-slate-200 bg-slate-50 px-6 py-4">
                        <div class="flex justify-center gap-1">
                            <template v-for="link in results.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-2 text-sm font-medium transition-colors',
                                        link.active ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-200',
                                    ]"
                                    v-html="link.label"
                                />
                                <span v-else :class="['cursor-not-allowed px-3 py-2 text-sm font-medium', 'text-slate-300']" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click="closeDeleteModal">
            <div class="mx-4 w-full max-w-md bg-white p-6 shadow-2xl" @click.stop>
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Delete Result</h3>
                        <p class="text-sm text-slate-500">This action cannot be undone</p>
                    </div>
                </div>
                <p class="mb-6 text-sm text-slate-600">
                    Are you sure you want to delete the result for <strong>{{ deletingResult?.username }}</strong
                    >?
                </p>
                <div class="flex gap-3">
                    <button
                        @click="closeDeleteModal"
                        class="flex-1 border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="deleteResult"
                        class="flex-1 bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index.js';
import type { BreadcrumbItem } from '@/types/index.js';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    results: Object,
    exams: Array,
    filters: Object,
});

const filters = ref({
    search: props.filters?.search || '',
    exam_id: props.filters?.exam_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const hasActiveFilters = computed(() => {
    return filters.value.search || filters.value.exam_id || filters.value.date_from || filters.value.date_to;
});

let debounceTimeout: number | null = null;

const debouncedSearch = () => {
    if (debounceTimeout) {
        clearTimeout(debounceTimeout);
    }

    debounceTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
};

const applyFilters = () => {
    router.get(
        '/results',
        {
            search: filters.value.search || undefined,
            exam_id: filters.value.exam_id || undefined,
            date_from: filters.value.date_from || undefined,
            date_to: filters.value.date_to || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    filters.value = {
        search: '',
        exam_id: '',
        date_from: '',
        date_to: '',
    };
    router.get(
        '/results',
        {},
        {
            preserveState: true,
        },
    );
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getScore = (score: any) => {
    if (score === null || score === undefined) {
        return 'N/A';
    }
    if (score === 0) {
        return '0';
    }
    return score;
};

// Delete functionality
const showDeleteModal = ref(false);
const deletingResult = ref<any>(null);

const confirmDelete = (result: any) => {
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
            closeDeleteModal();
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Results',
        href: '/results',
    },
];
</script>
