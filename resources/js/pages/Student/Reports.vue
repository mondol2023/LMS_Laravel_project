<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Award, BarChart3, BookOpen, Calendar, ChevronRight, Clock, Headphones, Lightbulb, MessageSquare, Minus, PenTool, Target, TrendingDown, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

interface Stats {
    total_exams: number;
    avg_listening: number;
    avg_reading: number;
    avg_writing: number;
    avg_speaking: number;
    overall_avg: number;
    best_listening: number;
    best_reading: number;
    best_writing: number;
    best_speaking: number;
}

interface ProgressData {
    date: string;
    overall: number;
    listening: number;
    reading: number;
    writing: number;
    speaking: number;
}

interface PracticeTest {
    exam_name: string;
    score: number;
    total: number;
    band_score: number;
    date: string;
}

interface PracticeStats {
    total_attempts: number;
    avg_score: number;
    avg_band: number;
    best_band: number;
    recent_tests: PracticeTest[];
}

interface Props {
    stats: Stats;
    progressData: ProgressData[];
    totalResults: number;
    practiceStats?: {
        listening: PracticeStats;
        reading: PracticeStats;
        writing: PracticeStats;
        speaking: PracticeStats;
    };
}

const props = defineProps<Props>();

const skillCards = [
    {
        name: 'Listening',
        icon: Headphones,
        avg: props.stats.avg_listening,
        best: props.stats.best_listening,
        color: 'blue',
    },
    {
        name: 'Reading',
        icon: BookOpen,
        avg: props.stats.avg_reading,
        best: props.stats.best_reading,
        color: 'green',
    },
    {
        name: 'Writing',
        icon: PenTool,
        avg: props.stats.avg_writing,
        best: props.stats.best_writing,
        color: 'purple',
    },
    {
        name: 'Speaking',
        icon: MessageSquare,
        avg: props.stats.avg_speaking,
        best: props.stats.best_speaking,
        color: 'orange',
    },
];

const getBandLevel = (band: number): { label: string; color: string } => {
    if (band >= 8) return { label: 'Expert', color: 'text-green-600' };
    if (band >= 7) return { label: 'Good', color: 'text-blue-600' };
    if (band >= 6) return { label: 'Competent', color: 'text-indigo-600' };
    if (band >= 5) return { label: 'Modest', color: 'text-yellow-600' };
    if (band >= 4) return { label: 'Limited', color: 'text-orange-600' };
    return { label: 'Basic', color: 'text-red-600' };
};

const getProgress = (): { trend: 'up' | 'down' | 'stable'; value: number } => {
    if (props.progressData.length < 2) return { trend: 'stable', value: 0 };
    const recent = props.progressData.slice(-2);
    const diff = recent[1].overall - recent[0].overall;
    if (diff > 0.2) return { trend: 'up', value: diff };
    if (diff < -0.2) return { trend: 'down', value: Math.abs(diff) };
    return { trend: 'stable', value: 0 };
};

const progress = getProgress();

// Progress Over Time Chart
const progressChartOptions = computed(() => ({
    chart: {
        type: 'line',
        height: 350,
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    colors: ['#1f2937', '#3b82f6', '#10b981', '#8b5cf6', '#f59e0b'],
    stroke: {
        width: [4, 3, 3, 3, 3],
        curve: 'smooth',
    },
    grid: {
        borderColor: '#e5e7eb',
        strokeDashArray: 4,
    },
    markers: {
        size: [6, 4, 4, 4, 4],
        strokeWidth: 2,
        strokeColors: '#fff',
        hover: { size: 7 },
    },
    xaxis: {
        categories: props.progressData.map((d) => d.date),
        labels: {
            style: {
                colors: '#6b7280',
                fontSize: '12px',
            },
        },
    },
    yaxis: {
        min: 0,
        max: 9,
        tickAmount: 9,
        labels: {
            style: {
                colors: '#6b7280',
                fontSize: '12px',
            },
            formatter: (val: number) => val.toFixed(1),
        },
    },
    legend: {
        position: 'top',
        horizontalAlign: 'left',
        fontSize: '13px',
        fontWeight: 500,
        labels: {
            colors: '#374151',
        },
        markers: {
            width: 10,
            height: 10,
            radius: 2,
        },
    },
    tooltip: {
        theme: 'light',
        y: {
            formatter: (val: number) => val.toFixed(1),
        },
    },
}));

const progressChartSeries = computed(() => [
    {
        name: 'Overall',
        data: props.progressData.map((d) => d.overall),
    },
    {
        name: 'Listening',
        data: props.progressData.map((d) => d.listening || 0),
    },
    {
        name: 'Reading',
        data: props.progressData.map((d) => d.reading || 0),
    },
    {
        name: 'Writing',
        data: props.progressData.map((d) => d.writing || 0),
    },
    {
        name: 'Speaking',
        data: props.progressData.map((d) => d.speaking || 0),
    },
]);

// Skills Comparison Chart (Radar)
const skillsRadarOptions = computed(() => ({
    chart: {
        type: 'radar',
        height: 350,
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    colors: ['#3b82f6', '#10b981'],
    fill: {
        opacity: [0.2, 0.15],
    },
    stroke: {
        width: 2,
    },
    markers: {
        size: 4,
    },
    xaxis: {
        categories: ['Listening', 'Reading', 'Writing', 'Speaking'],
        labels: {
            style: {
                colors: ['#6b7280', '#6b7280', '#6b7280', '#6b7280'],
                fontSize: '13px',
                fontWeight: 500,
            },
        },
    },
    yaxis: {
        min: 0,
        max: 9,
        tickAmount: 3,
        labels: {
            style: {
                colors: '#6b7280',
                fontSize: '11px',
            },
        },
    },
    legend: {
        position: 'bottom',
        fontSize: '13px',
        fontWeight: 500,
        labels: {
            colors: '#374151',
        },
    },
    tooltip: {
        theme: 'light',
        y: {
            formatter: (val: number) => val.toFixed(1),
        },
    },
}));

const skillsRadarSeries = computed(() => [
    {
        name: 'Average Score',
        data: [props.stats.avg_listening || 0, props.stats.avg_reading || 0, props.stats.avg_writing || 0, props.stats.avg_speaking || 0],
    },
    {
        name: 'Best Score',
        data: [props.stats.best_listening || 0, props.stats.best_reading || 0, props.stats.best_writing || 0, props.stats.best_speaking || 0],
    },
]);

// Practice Tests Distribution Chart
const practiceDistributionOptions = computed(() => ({
    chart: {
        type: 'donut',
        height: 320,
        fontFamily: 'inherit',
    },
    colors: ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b'],
    labels: ['Listening', 'Reading', 'Writing', 'Speaking'],
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total Tests',
                        fontSize: '14px',
                        fontWeight: 600,
                        color: '#374151',
                    },
                },
            },
        },
    },
    legend: {
        position: 'bottom',
        fontSize: '13px',
        fontWeight: 500,
        labels: {
            colors: '#374151',
        },
    },
    dataLabels: {
        enabled: true,
        style: {
            fontSize: '13px',
            fontWeight: 600,
        },
    },
    tooltip: {
        theme: 'light',
        y: {
            formatter: (val: number) => `${val} tests`,
        },
    },
}));

const practiceDistributionSeries = computed(() => {
    if (!props.practiceStats) return [0, 0, 0, 0];
    return [
        props.practiceStats.listening.total_attempts || 0,
        props.practiceStats.reading.total_attempts || 0,
        props.practiceStats.writing.total_attempts || 0,
        props.practiceStats.speaking.total_attempts || 0,
    ];
});

const totalPracticeTests = computed(() => {
    if (!props.practiceStats) return 0;
    return (
        props.practiceStats.listening.total_attempts +
        props.practiceStats.reading.total_attempts +
        props.practiceStats.writing.total_attempts +
        props.practiceStats.speaking.total_attempts
    );
});
</script>

<template>
    <Head title="Performance Reports" />

    <StudentLayout title="Performance Reports">
        <!-- Hero Overview Section -->
        <div class="mb-8">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Overall Score Card -->
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-8 text-white shadow-lg lg:col-span-2">
                    <div class="mb-6 flex items-start justify-between">
                        <div>
                            <div class="mb-3 inline-flex rounded-lg bg-white/10 p-3 backdrop-blur-sm">
                                <Award class="h-7 w-7" />
                            </div>
                            <p class="mb-2 text-sm font-medium text-gray-300">Overall Band Score</p>
                            <p class="mb-3 text-7xl font-bold tracking-tight">{{ stats.overall_avg || '0.0' }}</p>
                            <div class="flex items-center gap-3">
                                <span v-if="stats.overall_avg" class="inline-flex items-center gap-2 rounded-full bg-white/20 px-4 py-2 text-sm font-semibold backdrop-blur-sm">
                                    <Target class="h-4 w-4" />
                                    {{ getBandLevel(stats.overall_avg).label }} User
                                </span>
                                <span v-if="progress.trend !== 'stable'" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-sm backdrop-blur-sm">
                                    <TrendingUp v-if="progress.trend === 'up'" class="h-4 w-4 text-emerald-400" />
                                    <TrendingDown v-else class="h-4 w-4 text-red-400" />
                                    <span :class="progress.trend === 'up' ? 'text-emerald-400' : 'text-red-400'" class="font-semibold">
                                        {{ progress.trend === 'up' ? '+' : '-' }}{{ progress.value.toFixed(1) }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-4 border-t border-white/10 pt-6">
                        <div>
                            <div class="mb-1 flex items-center gap-1.5">
                                <Headphones class="h-4 w-4 text-blue-400" />
                                <p class="text-xs font-medium text-gray-400">Listening</p>
                            </div>
                            <p class="text-2xl font-bold">{{ stats.avg_listening || '-' }}</p>
                        </div>
                        <div>
                            <div class="mb-1 flex items-center gap-1.5">
                                <BookOpen class="h-4 w-4 text-green-400" />
                                <p class="text-xs font-medium text-gray-400">Reading</p>
                            </div>
                            <p class="text-2xl font-bold">{{ stats.avg_reading || '-' }}</p>
                        </div>
                        <div>
                            <div class="mb-1 flex items-center gap-1.5">
                                <PenTool class="h-4 w-4 text-purple-400" />
                                <p class="text-xs font-medium text-gray-400">Writing</p>
                            </div>
                            <p class="text-2xl font-bold">{{ stats.avg_writing || '-' }}</p>
                        </div>
                        <div>
                            <div class="mb-1 flex items-center gap-1.5">
                                <MessageSquare class="h-4 w-4 text-orange-400" />
                                <p class="text-xs font-medium text-gray-400">Speaking</p>
                            </div>
                            <p class="text-2xl font-bold">{{ stats.avg_speaking || '-' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 gap-6">
                    <div class="border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 inline-flex rounded-lg bg-blue-50 p-3">
                            <BarChart3 class="h-6 w-6 text-blue-600" />
                        </div>
                        <p class="mb-1 text-sm font-medium text-gray-500">Mock Tests</p>
                        <p class="mb-2 text-4xl font-bold text-gray-900">{{ totalResults }}</p>
                        <div class="flex items-center gap-1 text-xs text-gray-500">
                            <Clock class="h-3.5 w-3.5" />
                            Total completed
                        </div>
                    </div>

                    <div class="border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 inline-flex rounded-lg bg-purple-50 p-3">
                            <Target class="h-6 w-6 text-purple-600" />
                        </div>
                        <p class="mb-1 text-sm font-medium text-gray-500">Practice Tests</p>
                        <p class="mb-2 text-4xl font-bold text-gray-900">{{ totalPracticeTests }}</p>
                        <div class="flex items-center gap-1 text-xs text-gray-500">
                            <Clock class="h-3.5 w-3.5" />
                            Total attempts
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Progress Over Time Chart -->
            <div class="overflow-hidden border border-gray-200 bg-white shadow-sm lg:col-span-2">
                <div class="border-b border-gray-100 px-6 py-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Progress Over Time</h2>
                            <p class="mt-1 text-sm text-gray-500">Your band score progression across all modules</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div v-if="progressData.length > 0">
                        <VueApexCharts type="line" height="350" :options="progressChartOptions" :series="progressChartSeries" />
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100">
                            <BarChart3 class="h-10 w-10 text-gray-400" />
                        </div>
                        <p class="mb-2 text-lg font-semibold text-gray-900">No data yet</p>
                        <p class="text-sm text-gray-500">Take mock tests to see your progress</p>
                    </div>
                </div>
            </div>

            <!-- Skills Comparison Radar Chart -->
            <div class="overflow-hidden border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-5">
                    <h2 class="text-lg font-bold text-gray-900">Skills Overview</h2>
                    <p class="mt-1 text-sm text-gray-500">Average vs Best scores</p>
                </div>
                <div class="p-6">
                    <VueApexCharts type="radar" height="320" :options="skillsRadarOptions" :series="skillsRadarSeries" />
                </div>
            </div>
        </div>

        <!-- Detailed Skill Breakdown -->
        <div class="mb-8">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Mock Test Performance</h2>
                    <p class="mt-1 text-sm text-gray-500">Detailed breakdown of your performance in each skill</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div v-for="skill in skillCards" :key="skill.name" class="group overflow-hidden border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
                    <div
                        :class="{
                            'bg-gradient-to-br from-blue-500 to-blue-600': skill.color === 'blue',
                            'bg-gradient-to-br from-green-500 to-green-600': skill.color === 'green',
                            'bg-gradient-to-br from-purple-500 to-purple-600': skill.color === 'purple',
                            'bg-gradient-to-br from-orange-500 to-orange-600': skill.color === 'orange',
                        }"
                        class="p-5 text-white"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <component :is="skill.icon" class="h-8 w-8" />
                            <ChevronRight class="h-5 w-5 opacity-50 transition-transform group-hover:translate-x-1" />
                        </div>
                        <h3 class="text-lg font-bold">{{ skill.name }}</h3>
                    </div>
                    <div class="p-6">
                        <div class="mb-5">
                            <div class="mb-2 flex items-baseline justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Average</span>
                                <span :class="skill.avg >= 7 ? 'text-green-600' : skill.avg >= 5.5 ? 'text-blue-600' : 'text-gray-600'" class="text-xs font-bold">
                                    {{ getBandLevel(skill.avg).label }}
                                </span>
                            </div>
                            <p class="text-5xl font-bold text-gray-900">{{ skill.avg || '0.0' }}</p>
                        </div>
                        <div class="space-y-3 border-t border-gray-100 pt-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Best Score</span>
                                <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-bold text-green-700">{{ skill.best || '0.0' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Progress</span>
                                <span class="flex items-center gap-1.5 text-sm font-medium text-gray-700">
                                    <TrendingUp v-if="skill.avg > 0" class="h-4 w-4 text-green-500" />
                                    <Minus v-else class="h-4 w-4 text-gray-400" />
                                    {{ skill.avg > 0 ? 'On Track' : 'No Data' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Practice Tests Section -->
        <div v-if="practiceStats" class="mb-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Practice Test Analytics</h2>
                    <p class="mt-1 text-sm text-gray-500">Individual module practice performance and distribution</p>
                </div>
            </div>

            <!-- Practice Distribution Chart -->
            <div class="mb-6 overflow-hidden border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-5">
                    <h3 class="font-bold text-gray-900">Test Distribution</h3>
                    <p class="mt-1 text-sm text-gray-500">Practice tests by module</p>
                </div>
                <div class="p-6">
                    <div class="mx-auto max-w-md">
                        <VueApexCharts v-if="totalPracticeTests > 0" type="donut" height="320" :options="practiceDistributionOptions" :series="practiceDistributionSeries" />
                        <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                                <Target class="h-8 w-8 text-gray-400" />
                            </div>
                            <p class="mb-1 text-sm font-semibold text-gray-900">No practice tests yet</p>
                            <p class="text-xs text-gray-500">Start practicing to see stats</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Practice Module Cards - Full Width -->
            <div class="space-y-6">
                <!-- Listening Practice -->
                <div class="overflow-hidden border border-gray-200 bg-white shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3">
                        <!-- Header & Stats -->
                        <div class="border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white p-6 md:border-b-0 md:border-r">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="rounded-lg bg-blue-600 p-3">
                                    <Headphones class="h-6 w-6 text-white" />
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900">Listening</h4>
                                    <p class="text-xs text-gray-500">Practice Tests</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Tests</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ practiceStats.listening.total_attempts }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Avg Band</p>
                                    <p class="mt-1 text-2xl font-bold text-blue-600">{{ practiceStats.listening.avg_band || '0.0' }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Best</p>
                                    <p class="mt-1 text-2xl font-bold text-green-600">{{ practiceStats.listening.best_band || '0.0' }}</p>
                                </div>
                            </div>
                        </div>
                        <!-- Recent Tests -->
                        <div class="md:col-span-2">
                            <div v-if="practiceStats.listening.recent_tests.length > 0" class="divide-y divide-gray-100">
                                <div v-for="(test, index) in practiceStats.listening.recent_tests.slice(0, 3)" :key="index" class="flex items-center justify-between p-4 transition-colors hover:bg-gray-50">
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900">{{ test.exam_name }}</p>
                                        <p class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                                            <Calendar class="h-3 w-3" />
                                            {{ test.date }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-gray-900">{{ test.band_score }}</p>
                                        <p class="text-xs text-gray-500">{{ test.score }}/{{ test.total }} correct</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                                <Headphones class="mb-3 h-12 w-12 text-gray-400" />
                                <p class="font-medium text-gray-500">No tests yet</p>
                                <p class="mt-1 text-xs text-gray-400">Start practicing to see your results</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reading Practice -->
                <div class="overflow-hidden border border-gray-200 bg-white shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3">
                        <!-- Header & Stats -->
                        <div class="border-b border-gray-100 bg-gradient-to-r from-green-50 to-white p-6 md:border-b-0 md:border-r">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="rounded-lg bg-green-600 p-3">
                                    <BookOpen class="h-6 w-6 text-white" />
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900">Reading</h4>
                                    <p class="text-xs text-gray-500">Practice Tests</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Tests</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ practiceStats.reading.total_attempts }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Avg Band</p>
                                    <p class="mt-1 text-2xl font-bold text-green-600">{{ practiceStats.reading.avg_band || '0.0' }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Best</p>
                                    <p class="mt-1 text-2xl font-bold text-green-700">{{ practiceStats.reading.best_band || '0.0' }}</p>
                                </div>
                            </div>
                        </div>
                        <!-- Recent Tests -->
                        <div class="md:col-span-2">
                            <div v-if="practiceStats.reading.recent_tests.length > 0" class="divide-y divide-gray-100">
                                <div v-for="(test, index) in practiceStats.reading.recent_tests.slice(0, 3)" :key="index" class="flex items-center justify-between p-4 transition-colors hover:bg-gray-50">
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900">{{ test.exam_name }}</p>
                                        <p class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                                            <Calendar class="h-3 w-3" />
                                            {{ test.date }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-gray-900">{{ test.band_score }}</p>
                                        <p class="text-xs text-gray-500">{{ test.score }}/{{ test.total }} correct</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                                <BookOpen class="mb-3 h-12 w-12 text-gray-400" />
                                <p class="font-medium text-gray-500">No tests yet</p>
                                <p class="mt-1 text-xs text-gray-400">Start practicing to see your results</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Writing Practice -->
                <div class="overflow-hidden border border-gray-200 bg-white shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3">
                        <!-- Header & Stats -->
                        <div class="border-b border-gray-100 bg-gradient-to-r from-purple-50 to-white p-6 md:border-b-0 md:border-r">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="rounded-lg bg-purple-600 p-3">
                                    <PenTool class="h-6 w-6 text-white" />
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900">Writing</h4>
                                    <p class="text-xs text-gray-500">Practice Tests</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Tests</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ practiceStats.writing.total_attempts }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Avg Band</p>
                                    <p class="mt-1 text-2xl font-bold text-purple-600">{{ practiceStats.writing.avg_band || '0.0' }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Best</p>
                                    <p class="mt-1 text-2xl font-bold text-purple-700">{{ practiceStats.writing.best_band || '0.0' }}</p>
                                </div>
                            </div>
                        </div>
                        <!-- Recent Tests -->
                        <div class="md:col-span-2">
                            <div v-if="practiceStats.writing.recent_tests.length > 0" class="divide-y divide-gray-100">
                                <div v-for="(test, index) in practiceStats.writing.recent_tests.slice(0, 3)" :key="index" class="flex items-center justify-between p-4 transition-colors hover:bg-gray-50">
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900">{{ test.exam_name }}</p>
                                        <p class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                                            <Calendar class="h-3 w-3" />
                                            {{ test.date }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-gray-900">{{ test.band_score }}</p>
                                        <p class="text-xs text-gray-500">Band score</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                                <PenTool class="mb-3 h-12 w-12 text-gray-400" />
                                <p class="font-medium text-gray-500">No tests yet</p>
                                <p class="mt-1 text-xs text-gray-400">Start practicing to see your results</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Speaking Practice -->
                <div class="overflow-hidden border border-gray-200 bg-white shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3">
                        <!-- Header & Stats -->
                        <div class="border-b border-gray-100 bg-gradient-to-r from-orange-50 to-white p-6 md:border-b-0 md:border-r">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="rounded-lg bg-orange-600 p-3">
                                    <MessageSquare class="h-6 w-6 text-white" />
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900">Speaking</h4>
                                    <p class="text-xs text-gray-500">Practice Tests</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Tests</p>
                                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ practiceStats.speaking.total_attempts }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Avg Band</p>
                                    <p class="mt-1 text-2xl font-bold text-orange-600">{{ practiceStats.speaking.avg_band || '0.0' }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-gray-500">Best</p>
                                    <p class="mt-1 text-2xl font-bold text-orange-700">{{ practiceStats.speaking.best_band || '0.0' }}</p>
                                </div>
                            </div>
                        </div>
                        <!-- Recent Tests -->
                        <div class="md:col-span-2">
                            <div v-if="practiceStats.speaking.recent_tests.length > 0" class="divide-y divide-gray-100">
                                <div v-for="(test, index) in practiceStats.speaking.recent_tests.slice(0, 3)" :key="index" class="flex items-center justify-between p-4 transition-colors hover:bg-gray-50">
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900">{{ test.exam_name }}</p>
                                        <p class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                                            <Calendar class="h-3 w-3" />
                                            {{ test.date }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-gray-900">{{ test.band_score }}</p>
                                        <p class="text-xs text-gray-500">Band score</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                                <MessageSquare class="mb-3 h-12 w-12 text-gray-400" />
                                <p class="font-medium text-gray-500">No tests yet</p>
                                <p class="mt-1 text-xs text-gray-400">Start practicing to see your results</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tips & IELTS Band Scale Section -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Tips Section -->
            <div class="overflow-hidden border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white p-6">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-blue-600 p-3">
                            <Lightbulb class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Tips to Improve</h3>
                            <p class="mt-1 text-xs text-gray-500">Strategies for better performance</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex gap-4 rounded-lg bg-blue-50/50 p-4 transition-colors hover:bg-blue-50">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">1</div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Practice Regularly</p>
                                <p class="mt-1 text-sm text-gray-600">Consistency is key to improving your IELTS score. Aim for daily practice sessions.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 rounded-lg bg-green-50/50 p-4 transition-colors hover:bg-green-50">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white">2</div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Focus on Weak Areas</p>
                                <p class="mt-1 text-sm text-gray-600">Prioritize your weakest skill for the biggest overall improvement.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 rounded-lg bg-purple-50/50 p-4 transition-colors hover:bg-purple-50">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-purple-600 text-sm font-bold text-white">3</div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Review Mistakes</p>
                                <p class="mt-1 text-sm text-gray-600">Analyze errors after each test to understand improvement opportunities.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 rounded-lg bg-orange-50/50 p-4 transition-colors hover:bg-orange-50">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-orange-600 text-sm font-bold text-white">4</div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Time Management</p>
                                <p class="mt-1 text-sm text-gray-600">Practice under timed conditions to build confidence and speed.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IELTS Band Scale -->
            <div class="overflow-hidden border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gradient-to-r from-gray-900 to-gray-800 p-6 text-white">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-white/10 p-3 backdrop-blur-sm">
                            <Award class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">IELTS Band Scale</h3>
                            <p class="mt-1 text-xs text-gray-300">Understanding your score levels</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div class="flex items-center gap-4 rounded-lg border border-green-200 bg-green-50 p-4 transition-colors hover:bg-green-100">
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-green-600 text-xl font-bold text-white shadow-lg">9</div>
                            <div class="flex-1">
                                <p class="font-bold text-gray-900">Expert User</p>
                                <p class="mt-0.5 text-xs text-gray-600">Fully operational command of the language</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-lg border border-green-200 bg-green-50/70 p-4 transition-colors hover:bg-green-50">
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-green-500 text-xl font-bold text-white shadow-lg">8</div>
                            <div class="flex-1">
                                <p class="font-bold text-gray-900">Very Good User</p>
                                <p class="mt-0.5 text-xs text-gray-600">Fully operational with occasional inaccuracies</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-lg border border-blue-200 bg-blue-50 p-4 transition-colors hover:bg-blue-100">
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-blue-600 text-xl font-bold text-white shadow-lg">7</div>
                            <div class="flex-1">
                                <p class="font-bold text-gray-900">Good User</p>
                                <p class="mt-0.5 text-xs text-gray-600">Operational command with some inaccuracies</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-lg border border-blue-200 bg-blue-50/70 p-4 transition-colors hover:bg-blue-50">
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-blue-500 text-xl font-bold text-white shadow-lg">6</div>
                            <div class="flex-1">
                                <p class="font-bold text-gray-900">Competent User</p>
                                <p class="mt-0.5 text-xs text-gray-600">Effective command despite inaccuracies</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 transition-colors hover:bg-gray-100">
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-gray-500 text-xl font-bold text-white shadow-lg">5</div>
                            <div class="flex-1">
                                <p class="font-bold text-gray-900">Modest User</p>
                                <p class="mt-0.5 text-xs text-gray-600">Partial command with frequent errors</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
