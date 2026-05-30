<script setup lang="ts">
import C20Test4ReadingPart2 from '@/components/Exam/Practice/C20/Test4/Reading/C20Test4ReadingPart2.vue';
import ReadingPracticeHeader from '@/components/Reading/ReadingPracticeHeader.vue';
import { useIeltsScoring } from '@/composables/useIeltsScoring';
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const testNumber = 4;
const partNumber = 2;

const correctAnswers: Record<number, string> = {
    14: 'C',
    15: 'A',
    16: 'D',
    17: 'F',
    18: 'Pumps',
    19: 'Dams',
    20: 'Float',
    21: 'Crops',
    22: 'Trees',
    23: 'B',
    24: 'E',
    25: 'A',
    26: 'C',
};

const readingPartRef = ref<any>();
const { calculateBandScore } = useIeltsScoring();

// State
const showSubmitModal = ref(false);
const answeredQuestions = ref<Set<number>>(new Set());
const flaggedQuestions = ref<Set<number>>(new Set());
const isTimeUp = ref(false);
const selectedQuestion = ref<number | null>(null);
const isLeavingPage = ref(false);

// Font size state
const fontSize = ref(16);
const handleFontSizeChange = (size: number) => {
    fontSize.value = size;
};

// Background color state
const bgColor = ref<'white' | 'gray'>('white');
const handleBgColorChange = (color: 'white' | 'gray') => {
    bgColor.value = color;
};

// Contrast theme state
const contrastTheme = ref<'black-on-white' | 'white-on-black' | 'yellow-on-black'>('black-on-white');
const handleContrastChange = (theme: 'black-on-white' | 'white-on-black' | 'yellow-on-black') => {
    contrastTheme.value = theme;
};

// Computed classes for contrast theme
const contrastClasses = computed(() => {
    switch (contrastTheme.value) {
        case 'white-on-black':
            return 'bg-gray-900 text-white';
        case 'yellow-on-black':
            return 'bg-gray-900 text-yellow-400';
        case 'black-on-white':
        default:
            return 'bg-white text-gray-900';
    }
});

// Notes
const notes = computed(() => readingPartRef.value?.notes || []);

const handleDeleteNote = (data: { id: number; part: string }) => {
    readingPartRef.value?.deleteNote?.(data.id);
};

const handleFocusNote = async (note: { part: string; selectedText: string; start: number; end: number }) => {
    await nextTick();

    requestAnimationFrame(() => {
        const marks = document.querySelectorAll('mark[data-highlight-id]');
        let foundMark: Element | null = null;

        for (const mark of marks) {
            if (mark.textContent?.trim() === note.selectedText.trim()) {
                foundMark = mark;
                break;
            }
        }

        if (foundMark) {
            foundMark.scrollIntoView({ behavior: 'smooth', block: 'center' });
            foundMark.classList.add('ring-4', 'ring-blue-400', 'ring-offset-2', 'transition-all');
            requestAnimationFrame(() => {
                setTimeout(() => {
                    foundMark?.classList.remove('ring-4', 'ring-blue-400', 'ring-offset-2');
                }, 2000);
            });
        }
    });
};

// Flag Questions
const handleToggleFlag = (questionNumber: number) => {
    const newFlagged = new Set(flaggedQuestions.value);
    if (newFlagged.has(questionNumber)) {
        newFlagged.delete(questionNumber);
    } else {
        newFlagged.add(questionNumber);
    }
    flaggedQuestions.value = newFlagged;
};

// Question navigation
const handleScrollToQuestion = async (questionNumber: number) => {
    selectedQuestion.value = questionNumber;
    await nextTick();
    readingPartRef.value?.scrollToQuestion?.(questionNumber);
};

// Modal handlers
const handleShowSubmitModal = () => (showSubmitModal.value = true);
const handleCancelSubmit = () => {
    if (isTimeUp.value) return;
    showSubmitModal.value = false;
};
const handleTimeUp = () => {
    isTimeUp.value = true;
    showSubmitModal.value = true;
};

// Exit handler
const handleExit = () => {
    showSubmitModal.value = true;
};

// Track answered questions
const updateAnsweredQuestions = () => {
    const userAnswers = readingPartRef.value?.getAnswers?.() || {};
    const newAnsweredQuestions = new Set<number>();

    for (let i = 14; i <= 26; i++) {
        const answer = userAnswers[`q${i}`];
        if (answer && answer.toString().trim() !== '') {
            newAnsweredQuestions.add(i);
        }
    }

    const oldSet = answeredQuestions.value;
    if (oldSet.size !== newAnsweredQuestions.size || ![...newAnsweredQuestions].every((q) => oldSet.has(q))) {
        answeredQuestions.value = newAnsweredQuestions;
    }
};

let answerCheckInterval: any = null;

// Submit answers
const submitAnswers = () => {
    showSubmitModal.value = false;
    isLeavingPage.value = true;

    const userAnswers = readingPartRef.value?.getAnswers?.() || {};

    const formattedUserAnswers: Record<number, string> = {};
    Object.keys(userAnswers).forEach((key) => {
        const num = parseInt(key.replace('q', ''));
        formattedUserAnswers[num] = userAnswers[key];
    });

    let correctCount = 0;
    Object.keys(correctAnswers).forEach((key) => {
        const qNum = parseInt(key);
        const userAnswer = (formattedUserAnswers[qNum] || '').trim().toLowerCase();
        const correct = correctAnswers[qNum].toLowerCase();
        const possibleAnswers = correct.split('/').map((a) => a.trim().toLowerCase());
        if (possibleAnswers.includes(userAnswer)) {
            correctCount++;
        }
    });

    const totalQuestions = Object.keys(correctAnswers).length;
    const incorrectCount = totalQuestions - correctCount;
    const bandScore = calculateBandScore(correctCount, totalQuestions);

    router.post(
        `/practice/results/reading/${testNumber}`,
        {
            userAnswers: formattedUserAnswers,
            correctAnswers,
            score: { correct: correctCount, incorrect: incorrectCount, bandScore },
            partNumber,
        },
        {
            preserveState: false,
            onSuccess: () => {
                router.visit(`/practice/results/reading/${testNumber}?part=${partNumber}`);
            },
        },
    );
};

// Check if a question is answered
const isQuestionAnswered = (questionNumber: number): boolean => {
    return answeredQuestions.value.has(questionNumber);
};

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return flaggedQuestions.value.has(questionNumber);
};

// Check if a question is selected
const isQuestionSelected = (questionNumber: number): boolean => {
    return selectedQuestion.value === questionNumber;
};

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
    } catch {
        batteryLevel.value = 85;
    }
};

let timeInterval: number | null = null;

onMounted(() => {
    document.documentElement.classList.add('hide-scrollbar');
    document.body.classList.add('hide-scrollbar');

    answerCheckInterval = setInterval(() => {
        updateAnsweredQuestions();
    }, 2000);

    updateTime();
    timeInterval = window.setInterval(updateTime, 10000);
    updateBattery();
});

onBeforeUnmount(() => {
    document.documentElement.classList.remove('hide-scrollbar');
    document.body.classList.remove('hide-scrollbar');

    if (answerCheckInterval) {
        clearInterval(answerCheckInterval);
    }

    if (timeInterval) {
        clearInterval(timeInterval);
    }
});

// Part 2 questions (14-26)
const partQuestions = Array.from({ length: 13 }, (_, i) => i + 14);

// Count answered questions
const answeredCount = computed(() => {
    let count = 0;
    for (let i = 14; i <= 26; i++) {
        if (answeredQuestions.value.has(i)) count++;
    }
    return count;
});
</script>

<template>
    <Head title="Reading Test 4 - Part 2" />
    <div class="hide-scrollbar min-h-screen text-black" :class="bgColor === 'gray' ? 'bg-gray-100' : 'bg-white'">
        <ReadingPracticeHeader
            test-title="Cambridge 20 Reading Practice"
            current-section="Reading"
            :time-limit="20"
            :is-time-up="isTimeUp"
            :part1-notes="[]"
            :part2-notes="notes"
            :part3-notes="[]"
            @show-submit-modal="handleShowSubmitModal"
            @time-up="handleTimeUp"
            @submit-test="submitAnswers"
            @focus-note="handleFocusNote"
            @delete-note="handleDeleteNote"
            @font-size-change="handleFontSizeChange"
            @bg-color-change="handleBgColorChange"
            @contrast-change="handleContrastChange"
        />

        <div class="parts-container pb-24" :class="contrastClasses" :data-theme="contrastTheme">
            <C20Test4ReadingPart2
                ref="readingPartRef"
                :font-size="fontSize"
                :flagged-questions="flaggedQuestions"
                @toggle-flag="handleToggleFlag"
            />
        </div>

        <div class="fixed right-0 bottom-0 left-0 z-30 mx-5">
            <div class="border-t border-gray-200 bg-white px-2 py-3">
                <div class="flex items-center justify-center">
                    <div class="mx-6 flex items-center gap-1">
                        <span class="mr-3 text-base font-bold text-black">Part 2</span>
                        <div class="flex items-center gap-0.5">
                            <button
                                v-for="n in partQuestions"
                                :key="n"
                                @click="handleScrollToQuestion(n)"
                                class="group relative flex flex-col items-center"
                            >
                                <div class="flex flex-col items-center" :class="isQuestionFlagged(n) ? 'mb-0' : 'mb-3'">
                                    <div
                                        class="h-0.5 w-5 rounded-sm transition-colors"
                                        :class="isQuestionAnswered(n) ? 'bg-green-500' : 'bg-gray-300'"
                                    ></div>
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
                    </div>
                </div>
            </div>

            <div class="bg-gray-200 px-4 py-3 text-gray-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-semibold">
                            <span class="text-black">English</span>
                            <span class="text-red-600"> Therapy</span>
                        </span>
                    </div>

                    <div class="flex items-center gap-5">
                        <span class="text-2xl font-bold text-gray-800 tabular-nums">{{ currentTime }}</span>

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
                                <svg v-if="isCharging" class="absolute inset-0 h-7 w-7" viewBox="0 0 24 24">
                                    <path d="M12 6L8 12h3l-1 6 5-7h-3l1-5z" fill="#ffffff" stroke="#22c55e" stroke-width="0.5" />
                                </svg>
                            </div>
                        </div>

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

        <Teleport to="body">
            <div v-if="showSubmitModal" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-md rounded border border-black bg-white p-6 text-center shadow">
                    <div class="mb-4 flex justify-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded bg-black">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <h2 class="mb-2 text-lg font-bold text-black">
                        {{ isTimeUp ? 'Time is Up!' : 'Submit Your Answers?' }}
                    </h2>

                    <p class="mb-2 text-sm text-gray-600">
                        {{ isTimeUp ? 'Your time has ended. Your answers will be submitted now.' : 'Are you sure you want to submit your answers?' }}
                    </p>
                    <p class="mb-4 text-sm font-semibold text-black">
                        You have answered {{ answeredCount }} of 13 questions.
                    </p>

                    <div class="flex gap-3">
                        <button
                            v-if="!isTimeUp"
                            @click="handleCancelSubmit"
                            class="flex-1 rounded border border-gray-300 bg-white py-2 text-sm font-semibold text-gray-800 transition-all duration-200 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="submitAnswers"
                            class="flex-1 rounded bg-black py-2 text-sm font-semibold text-white transition-all duration-200 hover:scale-[1.02] hover:bg-gray-800 active:scale-100"
                        >
                            {{ isTimeUp ? 'View Results' : 'Submit Now' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.parts-container[data-theme='white-on-black'] :deep(.text-gray-900),
.parts-container[data-theme='white-on-black'] :deep(.text-gray-800),
.parts-container[data-theme='white-on-black'] :deep(.text-gray-700),
.parts-container[data-theme='white-on-black'] :deep(.text-gray-600),
.parts-container[data-theme='white-on-black'] :deep(.text-gray-500),
.parts-container[data-theme='white-on-black'] :deep(.text-black) { color: white !important; }
.parts-container[data-theme='white-on-black'] :deep(span:not(.bg-black span)),
.parts-container[data-theme='white-on-black'] :deep(p),
.parts-container[data-theme='white-on-black'] :deep(h1),
.parts-container[data-theme='white-on-black'] :deep(h2),
.parts-container[data-theme='white-on-black'] :deep(h3),
.parts-container[data-theme='white-on-black'] :deep(h4),
.parts-container[data-theme='white-on-black'] :deep(label) { color: white !important; }
.parts-container[data-theme='white-on-black'] :deep(input) { background-color: transparent !important; color: white !important; border-color: rgba(255, 255, 255, 0.5) !important; }
.parts-container[data-theme='white-on-black'] :deep(input::placeholder) { color: rgba(255, 255, 255, 0.5) !important; }
.parts-container[data-theme='white-on-black'] :deep(svg:not(.bg-black svg)) { color: white !important; }
.parts-container[data-theme='white-on-black'] :deep(.border-gray-200),
.parts-container[data-theme='white-on-black'] :deep(.border-gray-300),
.parts-container[data-theme='white-on-black'] :deep(.border-gray-900),
.parts-container[data-theme='white-on-black'] :deep([class*='border-gray']) { border-color: rgba(255, 255, 255, 0.3) !important; }
.parts-container[data-theme='white-on-black'] :deep(.bg-black) { background-color: white !important; }
.parts-container[data-theme='white-on-black'] :deep(.bg-black span),
.parts-container[data-theme='white-on-black'] :deep(.bg-black .text-white) { color: black !important; }
.parts-container[data-theme='white-on-black'] :deep(.bg-black svg) { color: black !important; }

.parts-container[data-theme='yellow-on-black'] :deep(.text-gray-900),
.parts-container[data-theme='yellow-on-black'] :deep(.text-gray-800),
.parts-container[data-theme='yellow-on-black'] :deep(.text-gray-700),
.parts-container[data-theme='yellow-on-black'] :deep(.text-gray-600),
.parts-container[data-theme='yellow-on-black'] :deep(.text-gray-500),
.parts-container[data-theme='yellow-on-black'] :deep(.text-black) { color: #facc15 !important; }
.parts-container[data-theme='yellow-on-black'] :deep(span:not(.bg-black span)),
.parts-container[data-theme='yellow-on-black'] :deep(p),
.parts-container[data-theme='yellow-on-black'] :deep(h1),
.parts-container[data-theme='yellow-on-black'] :deep(h2),
.parts-container[data-theme='yellow-on-black'] :deep(h3),
.parts-container[data-theme='yellow-on-black'] :deep(h4),
.parts-container[data-theme='yellow-on-black'] :deep(label) { color: #facc15 !important; }
.parts-container[data-theme='yellow-on-black'] :deep(input) { background-color: transparent !important; color: #facc15 !important; border-color: rgba(250, 204, 21, 0.5) !important; }
.parts-container[data-theme='yellow-on-black'] :deep(input::placeholder) { color: rgba(250, 204, 21, 0.5) !important; }
.parts-container[data-theme='yellow-on-black'] :deep(svg:not(.bg-black svg)) { color: #facc15 !important; }
.parts-container[data-theme='yellow-on-black'] :deep(.border-gray-200),
.parts-container[data-theme='yellow-on-black'] :deep(.border-gray-300),
.parts-container[data-theme='yellow-on-black'] :deep(.border-gray-900),
.parts-container[data-theme='yellow-on-black'] :deep([class*='border-gray']) { border-color: rgba(250, 204, 21, 0.3) !important; }
.parts-container[data-theme='yellow-on-black'] :deep(.bg-black) { background-color: white !important; }
.parts-container[data-theme='yellow-on-black'] :deep(.bg-black span),
.parts-container[data-theme='yellow-on-black'] :deep(.bg-black .text-white) { color: black !important; }
.parts-container[data-theme='yellow-on-black'] :deep(.bg-black svg) { color: black !important; }

.parts-container[data-theme='white-on-black'] :deep(.bg-white),
.parts-container[data-theme='white-on-black'] :deep(.bg-gray-50),
.parts-container[data-theme='white-on-black'] :deep(.bg-gray-100) { background-color: transparent !important; }
.parts-container[data-theme='yellow-on-black'] :deep(.bg-white),
.parts-container[data-theme='yellow-on-black'] :deep(.bg-gray-50),
.parts-container[data-theme='yellow-on-black'] :deep(.bg-gray-100) { background-color: transparent !important; }

.parts-container[data-theme='white-on-black'] :deep(.highlight-yellow) { background-color: rgba(254, 240, 138, 0.7) !important; color: #1f2937 !important; }
.parts-container[data-theme='white-on-black'] :deep(.highlight-green) { background-color: rgba(187, 247, 208, 0.7) !important; color: #1f2937 !important; }
.parts-container[data-theme='white-on-black'] :deep(.highlight-blue) { background-color: rgba(147, 197, 253, 0.7) !important; color: #1f2937 !important; }
.parts-container[data-theme='white-on-black'] :deep(.highlight-pink) { background-color: rgba(251, 207, 232, 0.7) !important; color: #1f2937 !important; }
.parts-container[data-theme='white-on-black'] :deep(.highlight-orange) { background-color: rgba(254, 215, 170, 0.7) !important; color: #1f2937 !important; }
.parts-container[data-theme='yellow-on-black'] :deep(.highlight-yellow) { background-color: rgba(254, 240, 138, 0.8) !important; color: #1f2937 !important; }
.parts-container[data-theme='yellow-on-black'] :deep(.highlight-green) { background-color: rgba(187, 247, 208, 0.8) !important; color: #1f2937 !important; }
.parts-container[data-theme='yellow-on-black'] :deep(.highlight-blue) { background-color: rgba(147, 197, 253, 0.8) !important; color: #1f2937 !important; }
.parts-container[data-theme='yellow-on-black'] :deep(.highlight-pink) { background-color: rgba(251, 207, 232, 0.8) !important; color: #1f2937 !important; }
.parts-container[data-theme='yellow-on-black'] :deep(.highlight-orange) { background-color: rgba(254, 215, 170, 0.8) !important; color: #1f2937 !important; }

.parts-container[data-theme='white-on-black'] :deep(button.text-red-500),
.parts-container[data-theme='white-on-black'] :deep(button.text-red-500 svg) { color: #ef4444 !important; }
.parts-container[data-theme='white-on-black'] :deep(button.text-gray-400) { color: rgba(255, 255, 255, 0.5) !important; }
.parts-container[data-theme='yellow-on-black'] :deep(button.text-red-500),
.parts-container[data-theme='yellow-on-black'] :deep(button.text-red-500 svg) { color: #ef4444 !important; }
.parts-container[data-theme='yellow-on-black'] :deep(button.text-gray-400) { color: rgba(250, 204, 21, 0.5) !important; }

.parts-container { transition: background-color 0.3s ease, color 0.3s ease; }
.parts-container :deep(*) { transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease; }
</style>
