<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    activePart: 'part1' | 'part2' | 'part3' | 'part4';
    answeredQuestions: Set<number>;
    flaggedQuestions?: Set<number>;
    selectedQuestion?: number | null;
    volume?: number;
}

const props = withDefaults(defineProps<Props>(), {
    volume: 100,
    flaggedQuestions: () => new Set<number>(),
    selectedQuestion: null,
});

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

// Check if a question's group is flagged (for Part 3 and Part 4 grouped questions)
const isGroupFlagged = (questionNumber: number): boolean => {
    // Questions 17-18 share flag with question 17
    if (questionNumber === 17 || questionNumber === 18 ) {
        return props.flaggedQuestions.has(17);
    }
    // Questions 19-20 share flag with question 19
    if (questionNumber === 19 || questionNumber === 20) {
        return props.flaggedQuestions.has(19);
    }

    // Individual questions 35-40
    return props.flaggedQuestions.has(questionNumber);
};

// Check if a question is selected
const isQuestionSelected = (questionNumber: number): boolean => {
    return props.selectedQuestion === questionNumber;
};

const emit = defineEmits<{
    navigate: [part: 'part1' | 'part2' | 'part3' | 'part4'];
    scrollToQuestion: [questionNumber: number];
    exit: [];
    volumeChange: [volume: number];
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

// Volume control
const showVolumeSlider = ref(false);
const localVolume = ref(props.volume);

const handleVolumeChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    localVolume.value = parseInt(target.value);
    emit('volumeChange', localVolume.value);
};

const isMuted = computed(() => localVolume.value === 0);

let timeInterval: number | null = null;
let hideVolumeTimeout: number | null = null;

const showVolumeControl = () => {
    if (hideVolumeTimeout) {
        clearTimeout(hideVolumeTimeout);
        hideVolumeTimeout = null;
    }
    showVolumeSlider.value = true;
};

const hideVolumeControl = () => {
    hideVolumeTimeout = window.setTimeout(() => {
        showVolumeSlider.value = false;
    }, 300);
};

onMounted(() => {
    updateTime();
    // Update clock every 10 seconds instead of every 1 second - clock shows HH:MM so this is sufficient
    timeInterval = window.setInterval(updateTime, 10000);
    updateBattery();
});

onUnmounted(() => {
    if (timeInterval) clearInterval(timeInterval);
    if (hideVolumeTimeout) clearTimeout(hideVolumeTimeout);
});

const handleNavigate = (part: 'part1' | 'part2' | 'part3' | 'part4') => {
    emit('navigate', part);
};

const handleQuestionClick = (questionNumber: number) => {
    emit('scrollToQuestion', questionNumber);
};

const handleExit = () => {
    emit('exit');
};

// Question ranges for each part
const partQuestions = {
    part1: Array.from({ length: 10 }, (_, i) => i + 1),
    part2: Array.from({ length: 10 }, (_, i) => i + 11),
    part3: Array.from({ length: 10 }, (_, i) => i + 21),
    part4: Array.from({ length: 10 }, (_, i) => i + 31),
};

// Count answered questions for each part
const part1AnsweredCount = computed(() => {
    let count = 0;
    for (let i = 1; i <= 10; i++) {
        if (props.answeredQuestions.has(i)) count++;
    }
    return count;
});

const part2AnsweredCount = computed(() => {
    let count = 0;
    for (let i = 11; i <= 20; i++) {
        if (props.answeredQuestions.has(i)) count++;
    }
    return count;
});

const part3AnsweredCount = computed(() => {
    let count = 0;
    for (let i = 21; i <= 30; i++) {
        if (props.answeredQuestions.has(i)) count++;
    }
    return count;
});

const part4AnsweredCount = computed(() => {
    let count = 0;
    for (let i = 31; i <= 40; i++) {
        if (props.answeredQuestions.has(i)) count++;
    }
    return count;
});

// Check if a question is answered
const isQuestionAnswered = (questionNumber: number): boolean => {
    return props.answeredQuestions.has(questionNumber);
};
</script>

<template>
    <div class="fixed right-0 bottom-0 left-0 z-30 mx-5">
        <!-- Top Section: Question Tracker -->
        <div class="border-t border-gray-200 bg-white px-2 py-3">
            <div class="flex items-center justify-between">
                <!-- Part 1 -->
                <div class="mx-10 flex items-center gap-1">
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
                            <span class="text-gray-500">{{ part1AnsweredCount }} of 10</span>
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
                            <span class="text-gray-500">{{ part2AnsweredCount }} of 10</span>
                        </button>
                    </template>
                </div>

                <!-- Part 3 -->
                <div class="flex items-center gap-1">
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
                                <div class="flex flex-col items-center" :class="isGroupFlagged(n) ? 'mb-0' : 'mb-3'">
                                    <!-- Green/gray dash (always visible) -->
                                    <div
                                        class="h-0.5 w-5 rounded-sm transition-colors"
                                        :class="isQuestionAnswered(n) ? 'bg-green-500' : 'bg-gray-300'"
                                    ></div>
                                    <!-- Bookmark hanging below the dash (if group is flagged) -->
                                    <svg v-if="isGroupFlagged(n)" class="h-3 w-3 text-red-500" fill="currentColor" viewBox="0 0 24 24">
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
                            <span class="text-gray-500">{{ part3AnsweredCount }} of 10</span>
                        </button>
                    </template>
                </div>

                <!-- Part 4 -->
                <div class="mr-10 flex items-center gap-1">
                    <template v-if="activePart === 'part4'">
                        <button @click="handleNavigate('part4')" class="mr-3 text-base font-bold text-black">Part 4</button>
                        <div class="flex items-center gap-0.5">
                            <button
                                v-for="n in partQuestions.part4"
                                :key="n"
                                @click="handleQuestionClick(n)"
                                class="group relative flex flex-col items-center"
                            >
                                <!-- Fixed dash with optional hanging bookmark -->
                                <div class="flex flex-col items-center" :class="isGroupFlagged(n) ? 'mb-0' : 'mb-3'">
                                    <!-- Green/gray dash (always visible) -->
                                    <div
                                        class="h-0.5 w-5 rounded-sm transition-colors"
                                        :class="isQuestionAnswered(n) ? 'bg-green-500' : 'bg-gray-300'"
                                    ></div>
                                    <!-- Bookmark hanging below the dash (if group is flagged) -->
                                    <svg v-if="isGroupFlagged(n)" class="h-3 w-3 text-red-500" fill="currentColor" viewBox="0 0 24 24">
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
                            @click="handleNavigate('part4')"
                            class="flex items-center gap-3 text-base text-gray-600 transition-colors hover:text-black"
                        >
                            <span class="font-medium">Part 4</span>
                            <span class="text-gray-500">{{ part4AnsweredCount }} of 10</span>
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

                <!-- Right Section: Time, Battery, Volume, Exit -->
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

                    <!-- Volume/Sound Control with Slider -->
                    <div class="relative" @mouseenter="showVolumeControl" @mouseleave="hideVolumeControl">
                        <button class="p-1 text-gray-700 transition-colors hover:text-gray-900" :title="`Volume: ${localVolume}%`">
                            <!-- High Volume -->
                            <svg v-if="localVolume > 50" class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"
                                />
                            </svg>
                            <!-- Medium Volume -->
                            <svg v-else-if="localVolume > 0" class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z" />
                            </svg>
                            <!-- Muted -->
                            <svg v-else class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"
                                />
                            </svg>
                        </button>

                        <!-- Volume Slider Popup -->
                        <Transition name="fade">
                            <div
                                v-if="showVolumeSlider"
                                class="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 rounded-lg border border-gray-200 bg-white p-3 shadow-lg"
                                @mouseenter="showVolumeControl"
                                @mouseleave="hideVolumeControl"
                            >
                                <div class="flex flex-col items-center gap-2">
                                    <span class="text-xs font-medium text-gray-600">{{ localVolume }}%</span>
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        :value="localVolume"
                                        @input="handleVolumeChange"
                                        class="volume-slider h-24 w-2 cursor-pointer appearance-none rounded-full bg-gray-200"
                                        orient="vertical"
                                    />
                                </div>
                            </div>
                        </Transition>
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

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Vertical Volume Slider */
.volume-slider {
    writing-mode: vertical-lr;
    direction: rtl;
    -webkit-appearance: none;
    appearance: none;
}

.volume-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 16px;
    height: 16px;
    background: #374151;
    border-radius: 50%;
    cursor: pointer;
}

.volume-slider::-moz-range-thumb {
    width: 16px;
    height: 16px;
    background: #374151;
    border-radius: 50%;
    cursor: pointer;
    border: none;
}

.volume-slider::-webkit-slider-runnable-track {
    background: #e5e7eb;
    border-radius: 9999px;
}

.volume-slider::-moz-range-track {
    background: #e5e7eb;
    border-radius: 9999px;
}
</style>
