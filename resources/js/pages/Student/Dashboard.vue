<script setup lang="ts">
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Award, BarChart3, BookOpen, Calendar, CheckCircle, Clock, Crown, Headphones, MessageSquare, PenTool, PlayCircle, Target, TrendingUp, XCircle } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
    roll_number?: string;
    phone?: string;
    target_band?: number;
}

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

interface Result {
    id: number;
    exam?: { name: string; exam_id: string };
    created_at: string;
    listening?: { band_score: number };
    reading?: { band_score: number };
    writing?: { band_score: number };
    speaking?: { band_score: number };
}

interface LimitData {
    enabled: boolean;
    limit: number | null;
    used: number;
    remaining: number | null;
    unlimited?: boolean;
}

interface Subscription {
    package_name: string;
    price: number;
    duration: number;
    subscribed_at: string;
    expires_at: string;
    days_remaining: number;
    is_active: boolean;
    is_expired: boolean;
    full_mock_test: LimitData;
    partial_tests: {
        reading: LimitData;
        writing: LimitData;
        listening: LimitData;
        speaking: LimitData;
    };
    practice_modules: {
        reading: LimitData;
        listening: LimitData;
        writing: LimitData;
        speaking: LimitData;
    };
}

interface Props {
    user: User;
    stats: Stats;
    recentResults: Result[];
    subscription: Subscription | null;
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
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const getProgressPercentage = (used: number, limit: number | null): number => {
    if (limit === null) return 0;
    return Math.min((used / limit) * 100, 100);
};

const getProgressColor = (used: number, limit: number | null): string => {
    if (limit === null) return 'bg-gray-900';
    const percentage = (used / limit) * 100;
    if (percentage >= 90) return 'bg-red-500';
    if (percentage >= 70) return 'bg-orange-500';
    return 'bg-gray-900';
};

const getRemainingText = (remaining: number | null, unlimited: boolean = false): string => {
    if (unlimited) return 'Unlimited';
    if (remaining === null) return 'Unlimited';
    return `${remaining} remaining`;
};
</script>

<template>
    <Head title="Student Dashboard" />

    <StudentLayout>
        <!-- Welcome Banner -->
        <div class="relative mb-8 overflow-hidden bg-black p-8 text-white shadow-sm">
            <div
                class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23ffffff%22%20fill-opacity%3D%220.05%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-50"
            ></div>
            <div class="relative">
                <div class="mb-4 flex items-center gap-3">
                    <img src="/logo.png" alt="English Therapy" class="h-12 w-12 rounded-lg object-contain shadow-md" />
                    <span class="inline-flex items-center gap-1 bg-white/20 px-3 py-1 text-xs font-medium">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping bg-gray-300 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 bg-gray-300"></span>
                        </span>
                        Student Portal
                    </span>
                </div>
                <h2 class="mb-3 text-3xl font-bold sm:text-4xl">Welcome back, {{ user.name }}!</h2>
                <p class="max-w-xl text-lg text-gray-300">Track your progress, view your results, and continue your IELTS preparation journey.</p>
                <div class="mt-6 flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2 bg-white/10 px-4 py-2">
                        <Award class="h-5 w-5 text-gray-300" />
                        <span class="font-semibold">{{ stats.total_exams }} Tests Taken</span>
                    </div>
                    <div v-if="stats.overall_avg > 0" class="flex items-center gap-2 bg-white/10 px-4 py-2">
                        <TrendingUp class="h-5 w-5 text-gray-300" />
                        <span class="font-semibold">Band {{ stats.overall_avg }} Average</span>
                    </div>
                    <div v-if="user.target_band" class="flex items-center gap-2 bg-white/10 px-4 py-2">
                        <Target class="h-5 w-5 text-gray-300" />
                        <span class="font-semibold">Target: Band {{ user.target_band }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2">
            <!-- Start Practice Button -->
            <Link href="/practice" class="group relative overflow-hidden bg-gradient-to-r from-blue-600 to-blue-700 p-8 text-white shadow-lg transition-all hover:shadow-xl">
                <div class="relative z-10">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                            <PlayCircle class="h-7 w-7" />
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">Start Practice</h3>
                            <p class="text-sm text-blue-100">Listening, Reading, Writing & Speaking</p>
                        </div>
                    </div>
                    <p class="mb-4 text-blue-100">Practice individual modules at your own pace</p>
                    <div class="flex items-center gap-2 font-semibold">
                        <span>Begin Practice</span>
                        <ArrowRight class="h-5 w-5 transition-transform group-hover:translate-x-1" />
                    </div>
                </div>
                <div class="absolute inset-0 bg-gradient-to-br from-blue-400/20 to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
            </Link>

            <!-- Start Mock Test Button -->
            <Link href="/" class="group relative overflow-hidden bg-gradient-to-r from-gray-900 to-gray-800 p-8 text-white shadow-lg transition-all hover:shadow-xl">
                <div class="relative z-10">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                            <Award class="h-7 w-7" />
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold">Start Mock Test</h3>
                            <p class="text-sm text-gray-300">Full & Partial IELTS Tests</p>
                        </div>
                    </div>
                    <p class="mb-4 text-gray-300">Take a complete or partial mock test</p>
                    <div class="flex items-center gap-2 font-semibold">
                        <span>Begin Test</span>
                        <ArrowRight class="h-5 w-5 transition-transform group-hover:translate-x-1" />
                    </div>
                </div>
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
            </Link>
        </div>

        <!-- Stats Grid -->
        <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
            <div class="border border-gray-100 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <div class="bg-gray-100 p-2">
                        <BarChart3 class="h-4 w-4 text-gray-600" />
                    </div>
                    <span class="text-sm font-medium text-gray-500">Total Tests</span>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ stats.total_exams }}</p>
            </div>

            <div class="border border-gray-100 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <div class="bg-gray-100 p-2">
                        <Headphones class="h-4 w-4 text-gray-600" />
                    </div>
                    <span class="text-sm font-medium text-gray-500">Listening</span>
                </div>
                <p class="text-3xl font-bold" :class="getBandColor(stats.avg_listening)">{{ stats.avg_listening || '-' }}</p>
            </div>

            <div class="border border-gray-100 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <div class="bg-gray-100 p-2">
                        <BookOpen class="h-4 w-4 text-gray-600" />
                    </div>
                    <span class="text-sm font-medium text-gray-500">Reading</span>
                </div>
                <p class="text-3xl font-bold" :class="getBandColor(stats.avg_reading)">{{ stats.avg_reading || '-' }}</p>
            </div>

            <div class="border border-gray-100 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <div class="bg-gray-100 p-2">
                        <PenTool class="h-4 w-4 text-gray-600" />
                    </div>
                    <span class="text-sm font-medium text-gray-500">Writing</span>
                </div>
                <p class="text-3xl font-bold" :class="getBandColor(stats.avg_writing)">{{ stats.avg_writing || '-' }}</p>
            </div>

            <div class="border border-gray-100 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <div class="bg-gray-100 p-2">
                        <MessageSquare class="h-4 w-4 text-gray-600" />
                    </div>
                    <span class="text-sm font-medium text-gray-500">Speaking</span>
                </div>
                <p class="text-3xl font-bold" :class="getBandColor(stats.avg_speaking)">{{ stats.avg_speaking || '-' }}</p>
            </div>

            <div class="bg-black p-5 text-white shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <div class="bg-white/20 p-2">
                        <TrendingUp class="h-4 w-4 text-white" />
                    </div>
                    <span class="text-sm font-medium text-gray-300">Overall</span>
                </div>
                <p class="text-3xl font-bold">{{ stats.overall_avg || '-' }}</p>
            </div>
        </div>

        <!-- Subscription Card -->
        <div v-if="subscription" class="mb-8 border border-gray-100 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="bg-black p-2">
                            <Crown class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ subscription.package_name }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ subscription.is_active ? 'Active Subscription' : 'Expired' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div v-if="subscription.is_active" class="flex items-center gap-2">
                            <CheckCircle class="h-5 w-5 text-gray-900" />
                            <span class="font-medium text-gray-900">{{ subscription.days_remaining }} days left</span>
                        </div>
                        <div v-else class="flex items-center gap-2">
                            <XCircle class="h-5 w-5 text-red-500" />
                            <span class="font-medium text-red-600">Expired</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Expires {{ formatDate(subscription.expires_at) }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <!-- Full Mock Test -->
                <div v-if="subscription.full_mock_test.enabled" class="mb-6">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="font-medium text-gray-900">Full Mock Tests</span>
                        <span class="text-sm text-gray-600">
                            {{ getRemainingText(subscription.full_mock_test.remaining) }}
                        </span>
                    </div>
                    <div class="h-2 w-full overflow-hidden bg-gray-100">
                        <div
                            :class="getProgressColor(subscription.full_mock_test.used, subscription.full_mock_test.limit)"
                            :style="{ width: getProgressPercentage(subscription.full_mock_test.used, subscription.full_mock_test.limit) + '%' }"
                            class="h-full transition-all duration-300"
                        ></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ subscription.full_mock_test.used }} / {{ subscription.full_mock_test.limit }} used
                    </p>
                </div>

                <!-- Partial Tests -->
                <div class="mb-6">
                    <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-700">Partial Mock Tests</h4>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <!-- Reading -->
                        <div v-if="subscription.partial_tests.reading.enabled">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="flex items-center gap-2 text-sm font-medium text-gray-900">
                                    <BookOpen class="h-4 w-4" />
                                    Reading
                                </span>
                                <span class="text-xs text-gray-600">
                                    {{ getRemainingText(subscription.partial_tests.reading.remaining) }}
                                </span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden bg-gray-100">
                                <div
                                    :class="getProgressColor(subscription.partial_tests.reading.used, subscription.partial_tests.reading.limit)"
                                    :style="{ width: getProgressPercentage(subscription.partial_tests.reading.used, subscription.partial_tests.reading.limit) + '%' }"
                                    class="h-full transition-all duration-300"
                                ></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ subscription.partial_tests.reading.used }} / {{ subscription.partial_tests.reading.limit }} used
                            </p>
                        </div>

                        <!-- Writing -->
                        <div v-if="subscription.partial_tests.writing.enabled">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="flex items-center gap-2 text-sm font-medium text-gray-900">
                                    <PenTool class="h-4 w-4" />
                                    Writing
                                </span>
                                <span class="text-xs text-gray-600">
                                    {{ getRemainingText(subscription.partial_tests.writing.remaining) }}
                                </span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden bg-gray-100">
                                <div
                                    :class="getProgressColor(subscription.partial_tests.writing.used, subscription.partial_tests.writing.limit)"
                                    :style="{ width: getProgressPercentage(subscription.partial_tests.writing.used, subscription.partial_tests.writing.limit) + '%' }"
                                    class="h-full transition-all duration-300"
                                ></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ subscription.partial_tests.writing.used }} / {{ subscription.partial_tests.writing.limit }} used
                            </p>
                        </div>

                        <!-- Listening -->
                        <div v-if="subscription.partial_tests.listening.enabled">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="flex items-center gap-2 text-sm font-medium text-gray-900">
                                    <Headphones class="h-4 w-4" />
                                    Listening
                                </span>
                                <span class="text-xs text-gray-600">
                                    {{ getRemainingText(subscription.partial_tests.listening.remaining) }}
                                </span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden bg-gray-100">
                                <div
                                    :class="getProgressColor(subscription.partial_tests.listening.used, subscription.partial_tests.listening.limit)"
                                    :style="{ width: getProgressPercentage(subscription.partial_tests.listening.used, subscription.partial_tests.listening.limit) + '%' }"
                                    class="h-full transition-all duration-300"
                                ></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ subscription.partial_tests.listening.used }} / {{ subscription.partial_tests.listening.limit }} used
                            </p>
                        </div>

                        <!-- Speaking -->
                        <div v-if="subscription.partial_tests.speaking.enabled">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="flex items-center gap-2 text-sm font-medium text-gray-900">
                                    <MessageSquare class="h-4 w-4" />
                                    Speaking
                                </span>
                                <span class="text-xs text-gray-600">
                                    {{ getRemainingText(subscription.partial_tests.speaking.remaining) }}
                                </span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden bg-gray-100">
                                <div
                                    :class="getProgressColor(subscription.partial_tests.speaking.used, subscription.partial_tests.speaking.limit)"
                                    :style="{ width: getProgressPercentage(subscription.partial_tests.speaking.used, subscription.partial_tests.speaking.limit) + '%' }"
                                    class="h-full transition-all duration-300"
                                ></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ subscription.partial_tests.speaking.used }} / {{ subscription.partial_tests.speaking.limit }} used
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Practice Modules -->
                <div>
                    <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-700">Practice Modules</h4>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Reading Practice -->
                        <div v-if="subscription.practice_modules.reading.enabled" class="border border-gray-100 bg-gray-50 p-3">
                            <div class="mb-1 flex items-center gap-2">
                                <BookOpen class="h-4 w-4 text-gray-600" />
                                <span class="text-sm font-medium text-gray-900">Reading</span>
                            </div>
                            <p class="text-xs text-gray-600">Unlimited</p>
                        </div>

                        <!-- Listening Practice -->
                        <div v-if="subscription.practice_modules.listening.enabled" class="border border-gray-100 bg-gray-50 p-3">
                            <div class="mb-1 flex items-center gap-2">
                                <Headphones class="h-4 w-4 text-gray-600" />
                                <span class="text-sm font-medium text-gray-900">Listening</span>
                            </div>
                            <p class="text-xs text-gray-600">Unlimited</p>
                        </div>

                        <!-- Writing Practice -->
                        <div v-if="subscription.practice_modules.writing.enabled" class="border border-gray-100 bg-gray-50 p-3">
                            <div class="mb-1 flex items-center gap-2">
                                <PenTool class="h-4 w-4 text-gray-600" />
                                <span class="text-sm font-medium text-gray-900">Writing</span>
                            </div>
                            <p class="text-xs text-gray-600">
                                {{ subscription.practice_modules.writing.used }} / {{ subscription.practice_modules.writing.limit }} used
                            </p>
                        </div>

                        <!-- Speaking Practice -->
                        <div v-if="subscription.practice_modules.speaking.enabled" class="border border-gray-100 bg-gray-50 p-3">
                            <div class="mb-1 flex items-center gap-2">
                                <MessageSquare class="h-4 w-4 text-gray-600" />
                                <span class="text-sm font-medium text-gray-900">Speaking</span>
                            </div>
                            <p class="text-xs text-gray-600">
                                {{ subscription.practice_modules.speaking.used }} / {{ subscription.practice_modules.speaking.limit }} used
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Subscription Message -->
        <div v-else class="mb-8 border border-orange-200 bg-orange-50 p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="bg-orange-100 p-3">
                    <Clock class="h-6 w-6 text-orange-600" />
                </div>
                <div>
                    <h3 class="mb-1 text-lg font-semibold text-gray-900">No Active Subscription</h3>
                    <p class="mb-3 text-gray-600">You don't have an active subscription. Please contact your administrator to get access to tests and practice materials.</p>
                    <p class="text-sm text-gray-500">Contact your administrator for subscription activation.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Recent Results -->
            <div class="border border-gray-100 bg-white shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Results</h3>
                    <Link href="/student/my-results" class="group flex items-center gap-1 text-sm font-medium text-gray-700 hover:text-gray-900">
                        View all
                        <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                    </Link>
                </div>
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="result in recentResults"
                        :key="result.id"
                        class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-gray-50/50"
                    >
                        <div class="flex items-center gap-4">
                            <div class="flex h-10 w-10 items-center justify-center bg-gray-100">
                                <Calendar class="h-5 w-5 text-gray-600" />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ result.exam?.name || 'IELTS Mock Test' }}</p>
                                <p class="text-sm text-gray-500">{{ formatDate(result.created_at) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="hidden items-center gap-3 sm:flex">
                                <div v-if="result.listening" class="min-w-[40px] text-center">
                                    <p class="mb-0.5 text-xs text-gray-400">L</p>
                                    <p :class="[getBandColor(result.listening.band_score), 'font-semibold']">
                                        {{ result.listening.band_score }}
                                    </p>
                                </div>
                                <div v-if="result.reading" class="min-w-[40px] text-center">
                                    <p class="mb-0.5 text-xs text-gray-400">R</p>
                                    <p :class="[getBandColor(result.reading.band_score), 'font-semibold']">
                                        {{ result.reading.band_score }}
                                    </p>
                                </div>
                                <div v-if="result.writing" class="min-w-[40px] text-center">
                                    <p class="mb-0.5 text-xs text-gray-400">W</p>
                                    <p :class="[getBandColor(result.writing.band_score), 'font-semibold']">
                                        {{ result.writing.band_score }}
                                    </p>
                                </div>
                                <div v-if="result.speaking" class="min-w-[40px] text-center">
                                    <p class="mb-0.5 text-xs text-gray-400">S</p>
                                    <p :class="[getBandColor(result.speaking.band_score), 'font-semibold']">
                                        {{ result.speaking.band_score }}
                                    </p>
                                </div>
                            </div>
                            <Link
                                :href="`/student/my-results/${result.id}`"
                                class="text-sm font-medium text-gray-700 hover:text-gray-900 hover:underline"
                            >
                                Details
                            </Link>
                        </div>
                    </div>
                    <div v-if="recentResults.length === 0" class="px-6 py-12 text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center bg-gray-100">
                            <BarChart3 class="h-8 w-8 text-gray-400" />
                        </div>
                        <p class="mb-2 text-gray-500">No results yet</p>
                        <p class="text-sm text-gray-400">Take a test to see your progress here!</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Quick Practice</h3>

                <Link href="/listening" class="group block border border-gray-100 bg-white p-5 shadow-sm transition-all hover:border-gray-300">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center bg-black shadow-sm">
                            <Headphones class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 transition-colors group-hover:text-gray-700">Listening Practice</h4>
                            <p class="text-sm text-gray-500">Improve your listening skills</p>
                        </div>
                    </div>
                </Link>

                <Link href="/reading" class="group block border border-gray-100 bg-white p-5 shadow-sm transition-all hover:border-gray-300">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center bg-black shadow-sm">
                            <BookOpen class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 transition-colors group-hover:text-gray-700">Reading Practice</h4>
                            <p class="text-sm text-gray-500">Enhance your reading comprehension</p>
                        </div>
                    </div>
                </Link>

                <Link href="/student/reports" class="group block bg-black p-5 text-white shadow-sm transition-all">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center bg-white/20">
                            <BarChart3 class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h4 class="font-semibold">View Reports</h4>
                            <p class="text-sm text-gray-300">See your progress analytics</p>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </StudentLayout>
</template>
