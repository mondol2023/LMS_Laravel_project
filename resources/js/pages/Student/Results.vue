<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Award, Calendar, Eye, FileText } from 'lucide-vue-next';

interface Result {
    id: number;
    exam?: { name: string; exam_id: string };
    exam_type: string;
    created_at: string;
    listening?: { band_score: number; correct: number; total: number };
    reading?: { band_score: number; correct: number; total: number };
    writing?: { band_score: number };
    speaking?: { band_score: number };
}

interface Props {
    results: {
        data: Result[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
}

defineProps<Props>();

const getBandColor = (band: number): string => {
    if (band >= 7) return 'bg-gray-200 text-gray-900 border-gray-300';
    if (band >= 5.5) return 'bg-gray-100 text-gray-700 border-gray-200';
    return 'bg-gray-50 text-gray-500 border-gray-200';
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const calculateOverall = (result: Result): number => {
    const scores = [result.listening?.band_score, result.reading?.band_score, result.writing?.band_score, result.speaking?.band_score].filter(
        (s): s is number => s !== undefined && s > 0,
    );

    if (scores.length === 0) return 0;
    return Math.round((scores.reduce((a, b) => a + b, 0) / scores.length) * 2) / 2;
};
</script>

<template>
    <Head title="My Results" />

    <StudentLayout title="My Results">
        <div class="overflow-hidden border border-gray-100 bg-white shadow-sm">
            <!-- Table Header -->
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                <div>
                    <h3 class="font-semibold text-gray-900">Test Results</h3>
                    <p class="text-sm text-gray-500">View all your IELTS test results</p>
                </div>
                <span class="inline-flex items-center gap-2 bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700">
                    <Award class="h-4 w-4" />
                    {{ results.total }} Total Tests
                </span>
            </div>

            <!-- Results Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-gray-100 bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase">Exam</th>
                            <th class="px-4 py-4 text-center text-xs font-semibold tracking-wider text-gray-500 uppercase">L</th>
                            <th class="px-4 py-4 text-center text-xs font-semibold tracking-wider text-gray-500 uppercase">R</th>
                            <th class="px-4 py-4 text-center text-xs font-semibold tracking-wider text-gray-500 uppercase">W</th>
                            <th class="px-4 py-4 text-center text-xs font-semibold tracking-wider text-gray-500 uppercase">S</th>
                            <th class="px-4 py-4 text-center text-xs font-semibold tracking-wider text-gray-500 uppercase">Overall</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold tracking-wider text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="result in results.data" :key="result.id" class="transition-colors hover:bg-gray-50/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center bg-gray-100">
                                        <FileText class="h-5 w-5 text-gray-700" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ result.exam?.name || 'IELTS Mock Test' }}</p>
                                        <p class="text-xs text-gray-500 capitalize">{{ result.exam_type || 'Full' }} Test</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span
                                    v-if="result.listening?.band_score"
                                    :class="getBandColor(result.listening.band_score)"
                                    class="inline-flex min-w-[44px] items-center justify-center border px-2.5 py-1.5 text-sm font-semibold"
                                >
                                    {{ result.listening.band_score }}
                                </span>
                                <span v-else class="text-gray-300">-</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span
                                    v-if="result.reading?.band_score"
                                    :class="getBandColor(result.reading.band_score)"
                                    class="inline-flex min-w-[44px] items-center justify-center border px-2.5 py-1.5 text-sm font-semibold"
                                >
                                    {{ result.reading.band_score }}
                                </span>
                                <span v-else class="text-gray-300">-</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span
                                    v-if="result.writing?.band_score"
                                    :class="getBandColor(result.writing.band_score)"
                                    class="inline-flex min-w-[44px] items-center justify-center border px-2.5 py-1.5 text-sm font-semibold"
                                >
                                    {{ result.writing.band_score }}
                                </span>
                                <span v-else class="text-gray-300">-</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span
                                    v-if="result.speaking?.band_score"
                                    :class="getBandColor(result.speaking.band_score)"
                                    class="inline-flex min-w-[44px] items-center justify-center border px-2.5 py-1.5 text-sm font-semibold"
                                >
                                    {{ result.speaking.band_score }}
                                </span>
                                <span v-else class="text-gray-300">-</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span
                                    v-if="calculateOverall(result) > 0"
                                    class="inline-flex min-w-[44px] items-center justify-center bg-black px-2.5 py-1.5 text-sm font-bold text-white"
                                >
                                    {{ calculateOverall(result) }}
                                </span>
                                <span v-else class="text-gray-300">-</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                    <Calendar class="h-4 w-4" />
                                    {{ formatDate(result.created_at) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link
                                    :href="`/student/my-results/${result.id}`"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-700 hover:text-gray-900 hover:underline"
                                >
                                    <Eye class="h-4 w-4" />
                                    View
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="results.data.length === 0">
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="mb-4 flex h-16 w-16 items-center justify-center bg-gray-100">
                                        <FileText class="h-8 w-8 text-gray-400" />
                                    </div>
                                    <p class="mb-1 font-medium text-gray-900">No results found</p>
                                    <p class="text-sm text-gray-500">Take your first test to see results here!</p>
                                    <Link
                                        href="/"
                                        class="mt-4 inline-flex items-center gap-2 bg-black px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                                    >
                                        Start a Test
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="results.last_page > 1" class="border-t border-gray-100 bg-gray-50/50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500">Showing {{ results.from || 0 }} to {{ results.to || 0 }} of {{ results.total || 0 }} results</p>
                    <div class="flex gap-1">
                        <template v-for="link in results.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    link.active ? 'border-black bg-black text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50',
                                    'border px-3 py-1.5 text-sm font-medium transition-colors',
                                ]"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
