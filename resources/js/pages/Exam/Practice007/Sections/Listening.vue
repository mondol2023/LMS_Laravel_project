<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

import C19Test3ListeningFooter from '@/components/Exam/Practice/C19/Test3/Listening/C19Test3ListeningFooter.vue';
import C19Test3ListeningHeader from '@/components/Exam/Practice/C19/Test3/Listening/C19Test3ListeningHeader.vue';
import C19Test3ListeningPart1 from '@/components/Exam/Practice/C19/Test3/Listening/C19Test3ListeningPart1.vue';
import C19Test3ListeningPart2 from '@/components/Exam/Practice/C19/Test3/Listening/C19Test3ListeningPart2.vue';
import C19Test3ListeningPart3 from '@/components/Exam/Practice/C19/Test3/Listening/C19Test3ListeningPart3.vue';
import C19Test3ListeningPart4 from '@/components/Exam/Practice/C19/Test3/Listening/C19Test3ListeningPart4.vue';
import C19Test3ListeningReviewModal from '@/components/Exam/Practice/C19/Test3/Listening/C19Test3ListeningReviewModal.vue';

interface Props {
    slug: string;
    section: string;
    userPhone?: string;
    examId?: string;
}

const props = defineProps<Props>();

const activePart = ref<'part1' | 'part2' | 'part3' | 'part4'>('part1');
const showSubmitModal = ref(false);
const showReviewModal = ref(false);
const answeredQuestions = ref<Set<number>>(new Set());
const showForceSubmitModal = ref(false);
const isLeavingPage = ref(false);
const isTimeUp = ref(false);

const listeningPart1Ref = ref<any>();
const listeningPart2Ref = ref<any>();
const listeningPart3Ref = ref<any>();
const listeningPart4Ref = ref<any>();

const userPhone = ref(props.userPhone || localStorage.getItem('userPhone'));
const examId = computed(() => props.examId || props.slug);

const part1Notes = computed(() => listeningPart1Ref.value?.notes || []);
const part2Notes = computed(() => listeningPart2Ref.value?.notes || []);
const part3Notes = computed(() => listeningPart3Ref.value?.notes || []);
const part4Notes = computed(() => listeningPart4Ref.value?.notes || []);

// Correct answers for Cambridge 19 Test 3 Listening
const correctAnswers: Record<number, string> = {
    // Part 1: Local food shops (Q1-10)
    1: 'station',
    2: 'bridge',
    3: '4',
    4: 'Harvest',
    5: 'sign',
    6: 'free',
    7: 'kelp',
    8: 'pie',
    9: 'honey',
    10: 'lemon',

    // Part 2: Festival workshops (Q11-16)
    11: 'F',
    12: 'D',
    13: 'E',
    14: 'C',
    15: 'A',
    16: 'G',
    // Part 2: Choose TWO (Q17-20)
    17: 'A/E',
    18: 'A/E',
    19: 'B/D',
    20: 'B/D',

    // Part 3: Science experiment (Q21-25)
    21: 'C',
    22: 'B',
    23: 'B',
    24: 'B',
    25: 'C',
    // Part 3: Flowchart (Q26-30)
    26: 'C',
    27: 'H',
    28: 'E',
    29: 'B',
    30: 'F',

    // Part 4: Microplastics (Q31-40)
    31: 'clothing',
    32: 'skin',
    33: 'salt',
    34: 'toothpaste',
    35: 'flooding',
    36: 'nutrients',
    37: 'growth',
    38: 'weight',
    39: 'pH',
    40: 'agriculture',
};

const normalize = (str: string): string => {
    let normalized = str.toString().trim().toLowerCase();
    normalized = normalized.replace(/\b(\d+)(st|nd|rd|th)\b/g, '$1');
    normalized = normalized
        .replace(/[.,!?;:'"()-]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    return normalized.trim();
};

const calculateBandScore = (score: number): number =>
    score >= 39
        ? 9
        : score >= 37
          ? 8.5
          : score >= 35
            ? 8
            : score >= 33
              ? 7.5
              : score >= 30
                ? 7
                : score >= 27
                  ? 6.5
                  : score >= 23
                    ? 6
                    : score >= 19
                      ? 5.5
                      : score >= 15
                        ? 5
                        : score >= 13
                          ? 4.5
                          : score >= 10
                            ? 4
                            : score >= 8
                              ? 3.5
                              : score >= 6
                                ? 3
                                : score >= 4
                                  ? 2.5
                                  : 0;

const compareAnswers = (userAnswers: Record<string, string>) => {
    let correct = 0;
    for (let i = 1; i <= 40; i++) {
        const userAnswer = normalize(userAnswers[`q${i}`] || '');
        const correctAnswer = normalize(correctAnswers[i]);
        const possibleAnswers = correctAnswer.split('/').map((a) => normalize(a.trim()));
        const isCorrect = possibleAnswers.some((possibleAnswer) => {
            if (possibleAnswer === userAnswer) return true;
            if (possibleAnswer.includes(' ') && userAnswer.includes(' ')) {
                const possibleWords = possibleAnswer.split(' ');
                const userWords = userAnswer.split(' ');
                return possibleWords.every((word) => userWords.includes(word)) || userWords.every((word) => possibleWords.includes(word));
            }
            return false;
        });
        if (isCorrect) correct++;
    }
    return { correct, incorrect: 40 - correct };
};

const handleNavigate = (part: 'part1' | 'part2' | 'part3' | 'part4') => (activePart.value = part);

const handleScrollToQuestion = async (questionNumber: number) => {
    if (questionNumber <= 10) activePart.value = 'part1';
    else if (questionNumber <= 20) activePart.value = 'part2';
    else if (questionNumber <= 30) activePart.value = 'part3';
    else activePart.value = 'part4';

    await nextTick();

    if (questionNumber <= 10) listeningPart1Ref.value?.scrollToQuestion(questionNumber);
    else if (questionNumber <= 20) listeningPart2Ref.value?.scrollToQuestion(questionNumber);
    else if (questionNumber <= 30) listeningPart3Ref.value?.scrollToQuestion(questionNumber);
    else listeningPart4Ref.value?.scrollToQuestion(questionNumber);
};

const handleFocusNote = async (note: { part: string; selectedText: string; start: number; end: number }) => {
    if (note.part === 'Part 1') activePart.value = 'part1';
    else if (note.part === 'Part 2') activePart.value = 'part2';
    else if (note.part === 'Part 3') activePart.value = 'part3';
    else activePart.value = 'part4';

    await nextTick();

    setTimeout(() => {
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
            foundMark.classList.add('ring-4', 'ring-rose-400', 'ring-offset-2', 'transition-all');
            setTimeout(() => {
                foundMark?.classList.remove('ring-4', 'ring-rose-400', 'ring-offset-2');
            }, 2500);
        }
    }, 100);
};

const handleDeleteNote = (data: { id: number; part: string }) => {
    if (data.part === 'Part 1') listeningPart1Ref.value?.deleteNote(data.id);
    else if (data.part === 'Part 2') listeningPart2Ref.value?.deleteNote(data.id);
    else if (data.part === 'Part 3') listeningPart3Ref.value?.deleteNote(data.id);
    else listeningPart4Ref.value?.deleteNote(data.id);
};

const handleShowSubmitModal = () => (showSubmitModal.value = true);
const handleCancelSubmit = () => {
    if (!isTimeUp.value) showSubmitModal.value = false;
};
const handleShowReviewModal = () => {
    if (!isTimeUp.value) showReviewModal.value = true;
};
const handleCloseReviewModal = () => (showReviewModal.value = false);
const handleTimeUp = () => {
    isTimeUp.value = true;
    showSubmitModal.value = true;
};

const handleConfirmSubmit = async () => {
    showSubmitModal.value = false;
    isLeavingPage.value = true;

    try {
        const allAnswers = {
            ...listeningPart1Ref.value?.getAnswers?.(),
            ...listeningPart2Ref.value?.getAnswers?.(),
            ...listeningPart3Ref.value?.getAnswers?.(),
            ...listeningPart4Ref.value?.getAnswers?.(),
        };
        const { correct, incorrect } = compareAnswers(allAnswers);
        const bandScore = calculateBandScore(correct);

        sessionStorage.removeItem(`listening_visited_${examId.value}`);

        const formattedUserAnswers: Record<number, string> = {};
        for (let i = 1; i <= 40; i++) {
            formattedUserAnswers[i] = allAnswers[`q${i}`] || '';
        }

        router.post('/practice/results/listening/7', {
            userAnswers: formattedUserAnswers,
            correctAnswers: correctAnswers,
            score: { correct, incorrect, bandScore },
        });
    } catch (err) {
        console.error('Submission error:', err);
        router.visit(`/exam/${props.slug}`);
    }
};

const handleForceSubmit = async () => {
    showForceSubmitModal.value = false;
    isLeavingPage.value = true;

    try {
        const allAnswers = {
            ...listeningPart1Ref.value?.getAnswers?.(),
            ...listeningPart2Ref.value?.getAnswers?.(),
            ...listeningPart3Ref.value?.getAnswers?.(),
            ...listeningPart4Ref.value?.getAnswers?.(),
        };
        const { correct, incorrect } = compareAnswers(allAnswers);
        const bandScore = calculateBandScore(correct);

        sessionStorage.removeItem(`listening_visited_${examId.value}`);

        const formattedUserAnswers: Record<number, string> = {};
        for (let i = 1; i <= 40; i++) {
            formattedUserAnswers[i] = allAnswers[`q${i}`] || '';
        }

        router.post('/practice/results/listening/7', {
            userAnswers: formattedUserAnswers,
            correctAnswers: correctAnswers,
            score: { correct, incorrect, bandScore },
        });
    } catch (err) {
        console.error('Submission error:', err);
        router.visit(`/exam/${props.slug}`);
    }
};

const getAllUserAnswers = () => {
    return {
        ...(listeningPart1Ref.value?.getAnswers?.() || {}),
        ...(listeningPart2Ref.value?.getAnswers?.() || {}),
        ...(listeningPart3Ref.value?.getAnswers?.() || {}),
        ...(listeningPart4Ref.value?.getAnswers?.() || {}),
    };
};

const updateAnsweredQuestions = () => {
    const allAnswers = getAllUserAnswers();
    const newAnsweredQuestions = new Set<number>();

    for (let i = 1; i <= 40; i++) {
        const answer = allAnswers[`q${i}`];
        if (answer && answer.toString().trim() !== '') {
            newAnsweredQuestions.add(i);
        }
    }

    answeredQuestions.value = newAnsweredQuestions;
};

const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    if (!isLeavingPage.value) {
        event.preventDefault();
        event.returnValue = '';
        return event.returnValue;
    }
};

const handleVisibilityChange = () => {
    if (document.hidden && !isLeavingPage.value) {
        showForceSubmitModal.value = true;
    }
};

const preventBackNavigation = () => {
    if (!isLeavingPage.value) {
        window.history.pushState(null, '', window.location.href);
        showForceSubmitModal.value = true;
    }
};

let answerCheckInterval: any = null;

onMounted(() => {
    const hasVisitedListening = sessionStorage.getItem(`listening_visited_${examId.value}`);
    if (hasVisitedListening === 'true') {
        showForceSubmitModal.value = true;
    } else {
        sessionStorage.setItem(`listening_visited_${examId.value}`, 'true');
    }

    answerCheckInterval = setInterval(() => updateAnsweredQuestions(), 500);

    window.addEventListener('beforeunload', handleBeforeUnload);
    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('popstate', preventBackNavigation);
    window.history.pushState(null, '', window.location.href);

    (window as any).__historyInterval = setInterval(() => {
        if (!isLeavingPage.value) window.history.pushState(null, '', window.location.href);
    }, 100);
});

onBeforeUnmount(() => {
    if (answerCheckInterval) clearInterval(answerCheckInterval);
    window.removeEventListener('beforeunload', handleBeforeUnload);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    window.removeEventListener('popstate', preventBackNavigation);
    if ((window as any).__historyInterval) clearInterval((window as any).__historyInterval);
});
</script>

<template>
    <Head title="C19 Test 3 Listening Section - Nextive Solution" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 via-white to-pink-50 text-black">
        <C19Test3ListeningHeader
            :is-time-up="isTimeUp"
            :part1-notes="part1Notes"
            :part2-notes="part2Notes"
            :part3-notes="part3Notes"
            :part4-notes="part4Notes"
            @show-submit-modal="handleShowSubmitModal"
            @show-review-modal="handleShowReviewModal"
            @time-up="handleTimeUp"
            @focus-note="handleFocusNote"
            @delete-note="handleDeleteNote"
        />
        <div :class="{ hidden: activePart !== 'part1' }">
            <C19Test3ListeningPart1 ref="listeningPart1Ref" :phone="userPhone" :exam-id="examId" />
        </div>
        <div :class="{ hidden: activePart !== 'part2' }">
            <C19Test3ListeningPart2 ref="listeningPart2Ref" :phone="userPhone" :exam-id="examId" />
        </div>
        <div :class="{ hidden: activePart !== 'part3' }">
            <C19Test3ListeningPart3 ref="listeningPart3Ref" :phone="userPhone" :exam-id="examId" />
        </div>
        <div :class="{ hidden: activePart !== 'part4' }">
            <C19Test3ListeningPart4 ref="listeningPart4Ref" :phone="userPhone" :exam-id="examId" />
        </div>
        <C19Test3ListeningFooter
            :active-part="activePart"
            :answered-questions="answeredQuestions"
            @navigate="handleNavigate"
            @scroll-to-question="handleScrollToQuestion"
        />

        <C19Test3ListeningReviewModal
            :is-visible="showReviewModal"
            :user-answers="getAllUserAnswers()"
            :answered-questions="answeredQuestions"
            @close="handleCloseReviewModal"
        />

        <!-- Submit Modal -->
        <Teleport to="body">
            <div
                v-if="showSubmitModal"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4"
                @click="isTimeUp ? null : handleCancelSubmit"
            >
                <div class="relative z-[10000] w-full max-w-md rounded-lg bg-white p-4 shadow-xl sm:p-6" @click.stop>
                    <div class="mb-4 flex items-center gap-3">
                        <div
                            :class="[
                                'flex h-8 w-8 items-center justify-center rounded-full sm:h-10 sm:w-10',
                                isTimeUp ? 'bg-orange-100' : 'bg-red-100',
                            ]"
                        >
                            <svg v-if="isTimeUp" class="h-4 w-4 text-orange-600 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            <svg v-else class="h-4 w-4 text-red-600 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                                />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 sm:text-lg">{{ isTimeUp ? 'Time is Up!' : 'Submit Test' }}</h3>
                    </div>
                    <p class="mb-6 text-sm text-gray-600 sm:text-base">
                        <span v-if="isTimeUp" class="font-semibold text-orange-600">Your time has expired. You must submit your test now.</span>
                        <span v-else>Are you sure you want to submit your listening test? This action cannot be undone.</span>
                    </p>
                    <div class="flex gap-3">
                        <button
                            v-if="!isTimeUp"
                            @click="handleCancelSubmit"
                            class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 sm:px-4 sm:text-sm"
                        >
                            Continue Test
                        </button>
                        <button
                            @click="handleConfirmSubmit"
                            :class="[
                                'rounded-lg px-3 py-2 text-xs font-medium text-white transition-colors sm:px-4 sm:text-sm',
                                isTimeUp ? 'w-full bg-orange-600 hover:bg-orange-700' : 'flex-1 bg-red-600 hover:bg-red-700',
                            ]"
                        >
                            Submit Now
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.min-h-screen {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
</style>
