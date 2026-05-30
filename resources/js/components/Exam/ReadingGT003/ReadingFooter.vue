<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    activePart: 'part1' | 'part2' | 'part3';
    answeredQuestions: Set<number>;
    flaggedQuestions?: Set<number>;
    selectedQuestion?: number | null;
}

const props = withDefaults(defineProps<Props>(), {
    flaggedQuestions: () => new Set<number>(),
    selectedQuestion: null,
});

// Check if a question is selected
const isQuestionSelected = (questionNumber: number): boolean => {
    return props.selectedQuestion === questionNumber;
};

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const emit = defineEmits<{
    navigate: [part: 'part1' | 'part2' | 'part3'];
    scrollToQuestion: [questionNumber: number];
    exit: [];
}>();

// Real-time clock
const currentTime = ref('');
const updateTime = () => {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    currentTime.value = `${hours}:${minutes}`;
};

// Battery status
const batteryLevel = ref(100);
const isCharging = ref(false);
const updateBattery = async () => {
    try {
        if ('getBattery' in navigator) {
            const battery = await (navigator as any).getBattery();
            batteryLevel.value = Math.round(battery.level * 100);
            isCharging.value = battery.charging;

            battery.addEventListener('levelchange', () => {
                batteryLevel.value = Math.round(battery.level * 100);
            });
            battery.addEventListener('chargingchange', () => {
                isCharging.value = battery.charging;
            });
        }
    } catch (e) {
        batteryLevel.value = 85;
    }
};

let timeInterval: number | null = null;

onMounted(() => {
    updateTime();
    // Update clock every 10 seconds instead of every 1 second - clock shows HH:MM so this is sufficient
    timeInterval = window.setInterval(updateTime, 10000);
    updateBattery();
});

onUnmounted(() => {
    if (timeInterval) clearInterval(timeInterval);
});

const handleNavigate = (part: 'part1' | 'part2' | 'part3') => {
    emit('navigate', part);
};

const handleQuestionClick = (questionNumber: number) => {
    emit('scrollToQuestion', questionNumber);
};

const handleExit = () => {
    emit('exit');
};

// Question ranges for each part (Reading: 13, 13, 14 questions)
const partQuestions = {
    part1: Array.from({ length: 14 }, (_, i) => i + 1),      // 1-13
    part2: Array.from({ length: 13 }, (_, i) => i + 15),     // 14-26
    part3: Array.from({ length: 13 }, (_, i) => i + 28),     // 27-40
};

// Count answered questions for each part
const part1AnsweredCount = computed(() => {
    let count = 0;
    for (let i = 1; i <= 13; i++) {
        if (props.answeredQuestions.has(i)) count++;
    }
    return count;
});

const part2AnsweredCount = computed(() => {
    let count = 0;
    for (let i = 14; i <= 26; i++) {
        if (props.answeredQuestions.has(i)) count++;
    }
    return count;
});

const part3AnsweredCount = computed(() => {
    let count = 0;
    for (let i = 27; i <= 40; i++) {
        if (props.answeredQuestions.has(i)) count++;
    }
    return count;
});

// Check if a question is answered
const isQuestionAnswered = (questionNumber: number): boolean => {
    return props.answeredQuestions.has(questionNumber);
};

// Navigation to previous/next part
const canGoPrevious = computed(() => props.activePart !== 'part1');
const canGoNext = computed(() => props.activePart !== 'part3');

const goToPreviousPart = () => {
    if (props.activePart === 'part2') {
        emit('navigate', 'part1');
    } else if (props.activePart === 'part3') {
        emit('navigate', 'part2');
    }
};

const goToNextPart = () => {
    if (props.activePart === 'part1') {
        emit('navigate', 'part2');
    } else if (props.activePart === 'part2') {
        emit('navigate', 'part3');
    }
};
</script>

<template>
    <!-- Previous / Next Part Navigation - Same as Listening -->
    <div class="fixed right-10 bottom-40 z-40 flex gap-1">
        <button
            @click="goToPreviousPart"
            :disabled="!canGoPrevious"
            class="flex h-10 w-11 items-center justify-center rounded transition-all duration-200"
            :class="
                canGoPrevious
                    ? 'bg-gray-600 font-extrabold text-white hover:bg-gray-700'
                    : 'cursor-not-allowed bg-gray-400 font-extrabold text-gray-200'
            "
            :title="canGoPrevious ? 'Previous Part' : ''"
        >
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
            </svg>
        </button>
        <button
            @click="goToNextPart"
            :disabled="!canGoNext"
            class="flex h-10 w-11 items-center justify-center rounded transition-all duration-200"
            :class="canGoNext ? 'bg-gray-800 text-white hover:bg-gray-900' : 'cursor-not-allowed bg-gray-400 text-gray-200'"
            :title="canGoNext ? 'Next Part' : ''"
        >
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" />
            </svg>
        </button>
    </div>

    <div class="fixed right-0 bottom-0 left-0 z-30 mx-5">
        <!-- Top Section: Question Tracker -->
        <div class="border-t border-gray-200 bg-white px-2 py-3">
            <div class="flex items-center justify-between">
                <!-- Part 1 -->
                <div class="mx-6 flex items-center gap-1">
                    <template v-if="activePart === 'part1'">
                        <button @click="handleNavigate('part1')" class="mr-3 text-base font-bold text-black">Part 1</button>
                        <div class="flex items-center gap-0.5">
                            <button
                                v-for="n in partQuestions.part1"
                                :key="n"
                                @click="handleQuestionClick(n)"
                                class="group relative flex flex-col items-center"
                            >
                                <!-- Fixed dash with optional hanging bookmark -->
                                <div class="flex flex-col items-center" :class="isQuestionFlagged(n) ? 'mb-0' : 'mb-3'">
                                    <!-- Green/gray dash (always visible) -->
                                    <div
                                        class="h-0.5 w-5 rounded-sm transition-colors"
                                        :class="isQuestionAnswered(n) ? 'bg-green-500' : 'bg-gray-300'"
                                    ></div>
                                    <!-- Bookmark hanging below the dash (if flagged) -->
                                    <svg v-if="isQuestionFlagged(n)" class="h-3 w-3 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </div>
                                <span
                                    class="flex h-6 w-6 items-center justify-center text-sm transition-all"
                                    :class="[
                                        isQuestionSelected(n) ? 'border border-teal-500 text-gray-700' : '',
                                        isQuestionAnswered(n) ? 'font-semibold text-gray-900' : 'text-gray-500',
                                    ]"
                                >
                                    {{ n }}
                                </span>
                            </button>
                        </div>
                    </template>
                    <template v-else>
                        <button
                            @click="handleNavigate('part1')"
                            class="flex items-center gap-3 text-base text-gray-600 transition-colors hover:text-black"
                        >
                            <span class="font-medium">Part 1</span>
                            <span class="text-gray-500">{{ part1AnsweredCount }} of 13</span>
                        </button>
                    </template>
                </div>

                <!-- Part 2 -->
                <div class="flex items-center gap-1">
                    <template v-if="activePart === 'part2'">
                        <button @click="handleNavigate('part2')" class="mr-3 text-base font-bold text-black">Part 2</button>
                        <div class="flex items-center gap-0.5">
                            <button
                                v-for="n in partQuestions.part2"
                                :key="n"
                                @click="handleQuestionClick(n)"
                                class="group relative flex flex-col items-center"
                            >
                                <!-- Fixed dash with optional hanging bookmark -->
                                <div class="flex flex-col items-center" :class="isQuestionFlagged(n) ? 'mb-0' : 'mb-3'">
                                    <!-- Green/gray dash (always visible) -->
                                    <div
                                        class="h-0.5 w-5 rounded-sm transition-colors"
                                        :class="isQuestionAnswered(n) ? 'bg-green-500' : 'bg-gray-300'"
                                    ></div>
                                    <!-- Bookmark hanging below the dash (if flagged) -->
                                    <svg v-if="isQuestionFlagged(n)" class="h-3 w-3 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </div>
                                <span
                                    class="flex h-6 w-6 items-center justify-center text-sm transition-all"
                                    :class="[
                                        isQuestionSelected(n) ? 'border border-teal-500 text-gray-700' : '',
                                        isQuestionAnswered(n) ? 'font-semibold text-gray-900' : 'text-gray-500',
                                    ]"
                                >
                                    {{ n }}
                                </span>
                            </button>
                        </div>
                    </template>
                    <template v-else>
                        <button
                            @click="handleNavigate('part2')"
                            class="flex items-center gap-3 text-base text-gray-600 transition-colors hover:text-black"
                        >
                            <span class="font-medium">Part 2</span>
                            <span class="text-gray-500">{{ part2AnsweredCount }} of 13</span>
                        </button>
                    </template>
                </div>

                <!-- Part 3 -->
                <div class="mr-6 flex items-center gap-1">
                    <template v-if="activePart === 'part3'">
                        <button @click="handleNavigate('part3')" class="mr-3 text-base font-bold text-black">Part 3</button>
                        <div class="flex items-center gap-0.5">
                            <button
                                v-for="n in partQuestions.part3"
                                :key="n"
                                @click="handleQuestionClick(n)"
                                class="group relative flex flex-col items-center"
                            >
                                <!-- Fixed dash with optional hanging bookmark -->
                                <div class="flex flex-col items-center" :class="isQuestionFlagged(n) ? 'mb-0' : 'mb-3'">
                                    <!-- Green/gray dash (always visible) -->
                                    <div
                                        class="h-0.5 w-5 rounded-sm transition-colors"
                                        :class="isQuestionAnswered(n) ? 'bg-green-500' : 'bg-gray-300'"
                                    ></div>
                                    <!-- Bookmark hanging below the dash (if flagged) -->
                                    <svg v-if="isQuestionFlagged(n)" class="h-3 w-3 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </div>
                                <span
                                    class="flex h-6 w-6 items-center justify-center text-sm transition-all"
                                    :class="[
                                        isQuestionSelected(n) ? 'border border-teal-500 text-gray-700' : '',
                                        isQuestionAnswered(n) ? 'font-semibold text-gray-900' : 'text-gray-500',
                                    ]"
                                >
                                    {{ n }}
                                </span>
                            </button>
                        </div>
                    </template>
                    <template v-else>
                        <button
                            @click="handleNavigate('part3')"
                            class="flex items-center gap-3 text-base text-gray-600 transition-colors hover:text-black"
                        >
                            <span class="font-medium">Part 3</span>
                            <span class="text-gray-500">{{ part3AnsweredCount }} of 14</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Bottom Section: Brand Bar (Gray) -->
        <div class="bg-gray-200 px-4 py-3 text-gray-800">
            <div class="flex items-center justify-between">
                <!-- Brand Name: English (black) Therapy (red) -->
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-semibold">
                            <span class="text-black">English</span>
                            <span class="text-red-600"> Therapy</span>
                        </span>
                    </div>
                </div>

                <!-- Right Section: Time, Battery, Exit -->
                <div class="flex items-center gap-5">
                    <!-- Real Time Clock -->
                    <span class="text-2xl font-bold text-gray-800 tabular-nums">{{ currentTime }}</span>

                    <!-- Battery Icon -->
                    <div class="flex items-center gap-1" :title="`Battery: ${batteryLevel}%${isCharging ? ' (Charging)' : ''}`">
                        <div class="relative">
                            <svg class="h-7 w-7 text-gray-700" fill="currentColor" viewBox="0 0 24 24">
                                <rect x="2" y="7" width="18" height="10" rx="1" stroke="currentColor" stroke-width="1.5" fill="none" />
                                <rect x="20" y="10" width="2" height="4" rx="0.5" fill="currentColor" />
                                <rect
                                    x="3"
                                    y="8"
                                    :width="Math.max(1, (batteryLevel / 100) * 16)"
                                    height="8"
                                    :fill="isCharging ? '#22c55e' : batteryLevel > 20 ? 'currentColor' : '#ef4444'"
                                />
                            </svg>
                            <!-- Charging Lightning Bolt Overlay -->
                            <svg v-if="isCharging" class="absolute inset-0 h-7 w-7" viewBox="0 0 24 24">
                                <path d="M12 6L8 12h3l-1 6 5-7h-3l1-5z" fill="#ffffff" stroke="#22c55e" stroke-width="0.5" />
                            </svg>
                        </div>
                    </div>

                    <!-- Exit Button -->
                    <button
                        @click="handleExit"
                        class="rounded border border-gray-300 bg-white px-5 py-2 text-base font-medium text-gray-800 transition-colors hover:bg-gray-100"
                    >
                        Exit
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
