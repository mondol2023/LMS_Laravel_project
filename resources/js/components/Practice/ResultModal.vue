<script setup lang="ts">
import type { IeltsResult } from '@/composables/useIeltsScoring';
import { useIeltsScoring } from '@/composables/useIeltsScoring';
import { AlertCircle, CheckCircle, Target, TrendingUp, Trophy, X, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    show: boolean;
    result: IeltsResult | null;
    title?: string;
    section?: string;
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    result: null,
    title: 'Practice Result',
    section: 'Practice',
});

const emit = defineEmits<{
    close: [];
    retry: [];
    viewAnswers: [];
}>();

const { getPerformanceLevel, getPerformanceColor } = useIeltsScoring();

const performanceLevel = computed(() => {
    if (!props.result) return '';
    return getPerformanceLevel(props.result.bandScore);
});

const performanceColor = computed(() => {
    if (!props.result) return '';
    return getPerformanceColor(props.result.bandScore);
});

const accuracyPercentage = computed(() => {
    if (!props.result || props.result.totalQuestions === 0) return 0;
    return Math.round((props.result.correctAnswers / props.result.totalQuestions) * 100);
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="emit('close')">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <!-- Header -->
            <div class="relative bg-gradient-to-r from-purple-600 to-indigo-600 p-6 text-white">
                <button class="absolute top-4 right-4 rounded-full p-2 transition-colors hover:bg-white/20" @click="emit('close')">
                    <X class="h-5 w-5" />
                </button>
                <div class="flex items-center gap-3">
                    <Trophy class="h-8 w-8" />
                    <div>
                        <h2 class="text-2xl font-bold">{{ title }}</h2>
                        <p class="text-sm text-purple-100">{{ section }} Section</p>
                    </div>
                </div>
            </div>

            <!-- Result Body -->
            <div v-if="result" class="p-6">
                <!-- Band Score Display -->
                <div class="mb-8 text-center">
                    <div class="mb-2 text-sm font-medium text-gray-600">Your Band Score</div>
                    <div class="mb-3 text-6xl font-bold" :class="performanceColor">
                        {{ result.bandScore.toFixed(1) }}
                    </div>
                    <div class="inline-block rounded-full bg-gray-100 px-4 py-2">
                        <span class="text-lg font-semibold" :class="performanceColor">
                            {{ performanceLevel }}
                        </span>
                    </div>
                </div>

                <!-- Statistics Grid -->
                <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
                    <!-- Total Questions -->
                    <div class="rounded-lg bg-blue-50 p-4 text-center">
                        <div class="mb-1 flex items-center justify-center">
                            <Target class="h-5 w-5 text-blue-600" />
                        </div>
                        <div class="text-2xl font-bold text-blue-600">{{ result.totalQuestions }}</div>
                        <div class="text-xs text-gray-600">Total</div>
                    </div>

                    <!-- Correct Answers -->
                    <div class="rounded-lg bg-green-50 p-4 text-center">
                        <div class="mb-1 flex items-center justify-center">
                            <CheckCircle class="h-5 w-5 text-green-600" />
                        </div>
                        <div class="text-2xl font-bold text-green-600">{{ result.correctAnswers }}</div>
                        <div class="text-xs text-gray-600">Correct</div>
                    </div>

                    <!-- Incorrect Answers -->
                    <div class="rounded-lg bg-red-50 p-4 text-center">
                        <div class="mb-1 flex items-center justify-center">
                            <XCircle class="h-5 w-5 text-red-600" />
                        </div>
                        <div class="text-2xl font-bold text-red-600">{{ result.incorrectAnswers }}</div>
                        <div class="text-xs text-gray-600">Incorrect</div>
                    </div>

                    <!-- Unanswered -->
                    <div class="rounded-lg bg-yellow-50 p-4 text-center">
                        <div class="mb-1 flex items-center justify-center">
                            <AlertCircle class="h-5 w-5 text-yellow-600" />
                        </div>
                        <div class="text-2xl font-bold text-yellow-600">{{ result.unanswered }}</div>
                        <div class="text-xs text-gray-600">Unanswered</div>
                    </div>
                </div>

                <!-- Accuracy Bar -->
                <div class="mb-6">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Accuracy</span>
                        <span class="text-sm font-bold" :class="performanceColor"> {{ accuracyPercentage }}% </span>
                    </div>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 transition-all duration-500"
                            :style="{ width: `${accuracyPercentage}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Performance Insights -->
                <div class="mb-6 rounded-lg bg-purple-50 p-4">
                    <div class="mb-2 flex items-center gap-2">
                        <TrendingUp class="h-5 w-5 text-purple-600" />
                        <h3 class="font-semibold text-purple-900">Performance Insights</h3>
                    </div>
                    <ul class="space-y-2 text-sm text-purple-800">
                        <li v-if="result.bandScore >= 7.0">Excellent work! You're performing at a high level.</li>
                        <li v-else-if="result.bandScore >= 6.0">Good performance! Keep practicing to reach the next level.</li>
                        <li v-else-if="result.bandScore >= 5.0">You're making progress. Focus on accuracy and understanding.</li>
                        <li v-else>Keep practicing! Review the correct answers to improve your skills.</li>

                        <li v-if="result.unanswered > 0">Try to answer all questions next time for a better score.</li>
                        <li v-if="result.incorrectAnswers > result.correctAnswers">Review the question types you find challenging.</li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-purple-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-purple-700"
                        @click="emit('viewAnswers')"
                    >
                        View Answers
                    </button>
                    <button
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-purple-600 px-6 py-3 font-semibold text-purple-600 transition-colors hover:bg-purple-50"
                        @click="emit('retry')"
                    >
                        Try Again
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
