<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Award, BookOpen, Calendar, FileText, Headphones, MessageSquare, PenTool } from 'lucide-vue-next';

interface Result {
    id: number;
    exam?: { name: string; exam_id: string };
    exam_type: string;
    created_at: string;
    username?: string;
    listening?: { band_score: number; correct: number; total: number };
    reading?: { band_score: number; correct: number; total: number };
    writing?: { band_score: number; task1?: any; task2?: any };
    speaking?: { band_score: number };
}

interface Props {
    result: Result;
}

const props = defineProps<Props>();

const getBandColor = (band: number): string => {
    if (band >= 7) return 'text-gray-900';
    if (band >= 5.5) return 'text-gray-700';
    return 'text-gray-500';
};

const getBandBg = (band: number): string => {
    if (band >= 7) return 'bg-gray-100 border-gray-300';
    if (band >= 5.5) return 'bg-gray-50 border-gray-200';
    return 'bg-gray-50 border-gray-200';
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
};

const calculateOverall = (): number => {
    const scores = [
        props.result.listening?.band_score,
        props.result.reading?.band_score,
        props.result.writing?.band_score,
        props.result.speaking?.band_score,
    ].filter((s): s is number => s !== undefined && s > 0);

    if (scores.length === 0) return 0;
    return Math.round((scores.reduce((a, b) => a + b, 0) / scores.length) * 2) / 2;
};

const sections = [
    {
        name: 'Listening',
        icon: Headphones,
        data: props.result.listening,
        color: 'green',
        gradient: '',
    },
    {
        name: 'Reading',
        icon: BookOpen,
        data: props.result.reading,
        color: 'purple',
        gradient: '',
    },
    {
        name: 'Writing',
        icon: PenTool,
        data: props.result.writing,
        color: 'orange',
        gradient: '',
    },
    {
        name: 'Speaking',
        icon: MessageSquare,
        data: props.result.speaking,
        color: 'pink',
        gradient: '',
    },
];
</script>

<template>
    <Head :title="`Result - ${result.exam?.name || 'IELTS Test'}`" />

    <StudentLayout>
        <!-- Back Button -->
        <Link href="/student/my-results" class="group mb-6 inline-flex items-center gap-2 text-gray-600 hover:text-gray-900">
            <ArrowLeft class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
            Back to Results
        </Link>

        <!-- Result Header -->
        <div class="mb-8 overflow-hidden border border-gray-100 bg-white shadow-sm">
            <div class="bg-black px-8 py-8 text-white">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <FileText class="h-5 w-5 text-gray-300" />
                            <span class="text-sm text-gray-300">Test Result</span>
                        </div>
                        <h1 class="mb-2 text-2xl font-bold md:text-3xl">{{ result.exam?.name || 'IELTS Mock Test' }}</h1>
                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-300">
                            <span class="flex items-center gap-1.5">
                                <Calendar class="h-4 w-4" />
                                {{ formatDate(result.created_at) }}
                            </span>
                            <span class="bg-white/20 px-2 py-0.5 capitalize">{{ result.exam_type || 'Full' }} Test</span>
                        </div>
                    </div>
                    <div class="text-center md:text-right">
                        <p class="mb-1 text-sm text-gray-300">Overall Band Score</p>
                        <div class="flex items-center gap-2 md:justify-end">
                            <Award class="h-8 w-8 text-white" />
                            <span class="text-5xl font-bold">{{ calculateOverall() || '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Scores -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div v-for="section in sections" :key="section.name" class="overflow-hidden border border-gray-100 bg-white shadow-sm">
                <div class="bg-gray-800 px-5 py-4 text-white">
                    <div class="flex items-center gap-3">
                        <component :is="section.icon" class="h-6 w-6" />
                        <span class="text-lg font-semibold">{{ section.name }}</span>
                    </div>
                </div>
                <div class="p-5">
                    <div v-if="section.data?.band_score" class="text-center">
                        <p class="mb-2 text-5xl font-bold" :class="getBandColor(section.data.band_score)">
                            {{ section.data.band_score }}
                        </p>
                        <p class="text-sm text-gray-500">Band Score</p>
                        <div v-if="section.data.correct !== undefined" class="mt-4 border-t border-gray-100 pt-4">
                            <p class="text-sm text-gray-500">
                                <span class="font-semibold text-gray-900">{{ section.data.correct }}</span>
                                / {{ section.data.total }} correct
                            </p>
                        </div>
                    </div>
                    <div v-else class="py-4 text-center">
                        <p class="text-gray-400">Not attempted</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Writing Details (if available) -->
        <div v-if="result.writing?.task1 || result.writing?.task2" class="overflow-hidden border border-gray-100 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="font-semibold text-gray-900">Writing Details</h3>
            </div>
            <div class="space-y-6 p-6">
                <div v-if="result.writing?.task1" class="bg-gray-50 p-4">
                    <h4 class="mb-2 font-medium text-gray-900">Task 1</h4>
                    <p v-if="result.writing.task1.band_score" class="text-lg font-semibold" :class="getBandColor(result.writing.task1.band_score)">
                        Band {{ result.writing.task1.band_score }}
                    </p>
                    <p v-if="result.writing.task1.feedback" class="mt-2 text-sm text-gray-600">
                        {{ result.writing.task1.feedback }}
                    </p>
                </div>
                <div v-if="result.writing?.task2" class="bg-gray-50 p-4">
                    <h4 class="mb-2 font-medium text-gray-900">Task 2</h4>
                    <p v-if="result.writing.task2.band_score" class="text-lg font-semibold" :class="getBandColor(result.writing.task2.band_score)">
                        Band {{ result.writing.task2.band_score }}
                    </p>
                    <p v-if="result.writing.task2.feedback" class="mt-2 text-sm text-gray-600">
                        {{ result.writing.task2.feedback }}
                    </p>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
