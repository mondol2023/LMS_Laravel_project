<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

import C19Test1ListeningFooter from '@/components/Exam/Practice/C19/Test1/Listening/C19Test1ListeningFooter.vue';
import C19Test1ListeningHeader from '@/components/Exam/Practice/C19/Test1/Listening/C19Test1ListeningHeader.vue';
import C19Test1ListeningPart1 from '@/components/Exam/Practice/C19/Test1/Listening/C19Test1ListeningPart1.vue';
import C19Test1ListeningPart2 from '@/components/Exam/Practice/C19/Test1/Listening/C19Test1ListeningPart2.vue';
import C19Test1ListeningPart3 from '@/components/Exam/Practice/C19/Test1/Listening/C19Test1ListeningPart3.vue';
import C19Test1ListeningPart4 from '@/components/Exam/Practice/C19/Test1/Listening/C19Test1ListeningPart4.vue';
import C19Test1ListeningReviewModal from '@/components/Exam/Practice/C19/Test1/Listening/C19Test1ListeningReviewModal.vue';

interface Props {
    slug: string;
    section: string;
    userPhone?: string;
    examId?: string;
}

const props = defineProps<Props>();

// ================================
// Component state & refs
// ================================
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

// Track notes from all parts
const part1Notes = computed(() => listeningPart1Ref.value?.notes || []);
const part2Notes = computed(() => listeningPart2Ref.value?.notes || []);
const part3Notes = computed(() => listeningPart3Ref.value?.notes || []);
const part4Notes = computed(() => listeningPart4Ref.value?.notes || []);

const part1CorrectAnswers = computed(() => {
    const answers: Record<string, string> = {};
    for (let i = 1; i <= 10; i++) {
        answers[`q${i}`] = correctAnswers[i];
    }
    return answers;
});

// ================================
// Correct answers for Cambridge 19 Test 1 Listening
// ================================
const correctAnswers: Record<number, string> = {
    // Part 1: Hinchingbrooke Country Park (Questions 1-10)
    1: '170',
    2: 'river',
    3: 'data',
    4: 'map',
    5: 'visitors',
    6: 'instruments',
    7: 'freedom',
    8: 'skills',
    9: '4.50',
    10: 'parents',

    // Part 2: Stanthorpe Twinning Association MCQ (Questions 11-15)
    11: 'B',
    12: 'A',
    13: 'C',
    14: 'B',
    15: 'A',
    // Part 2: Farley House Map (Questions 16-20)
    16: 'G',
    17: 'C',
    18: 'F',
    19: 'A',
    20: 'H',

    // Part 3: Choose TWO (Questions 21-24)
    21: 'B/D',
    22: 'B/D',
    23: 'A/E',
    24: 'A/E',
    // Part 3: Food trends matching (Questions 25-30)
    25: 'E',
    26: 'H',
    27: 'A',
    28: 'C',
    29: 'G',
    30: 'D',

    // Part 4: Ceide Fields (Questions 31-40)
    31: 'bog',
    32: 'walls',
    33: 'climate',
    34: 'cattle',
    35: 'grain',
    36: 'trees',
    37: 'blanket',
    38: 'rainfall',
    39: 'farming',
    40: 'coast',
};

// ================================
// Utilities & Scoring
// ================================
const normalize = (str: string): string => {
    let normalized = str.toString().trim().toLowerCase();

    // Remove ordinal suffixes (st, nd, rd, th) from numbers
    normalized = normalized.replace(/\b(\d+)(st|nd|rd|th)\b/g, '$1');

    // Remove punctuation and extra spaces (including hyphens)
    normalized = normalized
        .replace(/[.,!?;:'"()-]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    // Handle common unit variations
    normalized = normalized
        .replace(/\b(\d+)\s*cm\b/g, '$1 centimeter')
        .replace(/\b(\d+)\s*centimeters?\b/g, '$1 centimeter')
        .replace(/\b(\d+)\s*mm\b/g, '$1 millimeter')
        .replace(/\b(\d+)\s*millimeters?\b/g, '$1 millimeter')
        .replace(/\b(\d+)\s*m\b/g, '$1 meter')
        .replace(/\b(\d+)\s*meters?\b/g, '$1 meter')
        .replace(/\b(\d+)\s*%\b/g, '$1 percent');

    // Handle common word variations
    normalized = normalized
        .replace(/\bfemales?\b/g, 'women')
        .replace(/\bmales?\b/g, 'men')
        .replace(/\bchildren?\b/g, 'child')
        .replace(/\bpeoples?\b/g, 'people');

    // Handle boolean variations
    normalized = normalized
        .replace(/\b(yes|y|true|correct)\b/g, 'yes')
        .replace(/\b(no|n|false|incorrect)\b/g, 'no')
        .replace(/\b(not given|not stated|not mentioned|ng)\b/g, 'not given');

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

        // Handle multiple correct answers separated by "/"
        const possibleAnswers = correctAnswer.split('/').map((a) => normalize(a.trim()));

        // Check if user answer matches any of the possible correct answers
        const isCorrect = possibleAnswers.some((possibleAnswer) => {
            // Direct match
            if (possibleAnswer === userAnswer) return true;

            // Check for partial matches in compound answers
            if (possibleAnswer.includes(' ') && userAnswer.includes(' ')) {
                const possibleWords = possibleAnswer.split(' ');
                const userWords = userAnswer.split(' ');
                return possibleWords.every((word) => userWords.includes(word)) || userWords.every((word) => possibleWords.includes(word));
            }

            return false;
        });

        if (isCorrect) {
            console.log(`Q${i}: "${userAnswers[`q${i}`]}" = "${correctAnswers[i]}"`);
            correct++;
        } else {
            console.log(`Q${i}: "${userAnswers[`q${i}`]}" != "${correctAnswers[i]}"`);
        }
    }
    return { correct, incorrect: 40 - correct };
};

// ================================
// Navigation
// ================================
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

// ================================
// Focus Note
// ================================
const handleFocusNote = async (note: { part: string; selectedText: string; start: number; end: number }) => {
    // Switch to the correct part
    if (note.part === 'Part 1') activePart.value = 'part1';
    else if (note.part === 'Part 2') activePart.value = 'part2';
    else if (note.part === 'Part 3') activePart.value = 'part3';
    else activePart.value = 'part4';

    // Wait for the DOM to update
    await nextTick();

    // Find and highlight the text in the passage
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
            foundMark.classList.add('ring-4', 'ring-blue-400', 'ring-offset-2', 'transition-all');
            setTimeout(() => {
                foundMark?.classList.remove('ring-4', 'ring-blue-400', 'ring-offset-2');
            }, 2500);
        } else {
            const contentAreas = document.querySelectorAll('.select-text, .text-gray-700, .text-gray-800, .text-black, .text-zinc-900');
            let foundText = false;

            for (const contentArea of contentAreas) {
                if (foundText) break;

                const elementText = contentArea.textContent || '';
                if (!elementText.includes(note.selectedText)) {
                    continue;
                }

                const walker = document.createTreeWalker(contentArea, NodeFilter.SHOW_TEXT, null);

                let node;
                while ((node = walker.nextNode())) {
                    const text = node.textContent || '';
                    const index = text.indexOf(note.selectedText);

                    if (index !== -1 && node.parentElement) {
                        const range = document.createRange();
                        range.setStart(node, index);
                        range.setEnd(node, index + note.selectedText.length);

                        const tempMark = document.createElement('mark');
                        tempMark.className = 'bg-blue-200 ring-4 ring-blue-400 ring-offset-2 transition-all px-1 rounded';

                        try {
                            range.surroundContents(tempMark);
                            tempMark.scrollIntoView({ behavior: 'smooth', block: 'center' });

                            setTimeout(() => {
                                const parent = tempMark.parentNode;
                                if (parent) {
                                    parent.replaceChild(document.createTextNode(note.selectedText), tempMark);
                                    parent.normalize();
                                }
                            }, 2500);

                            foundText = true;
                            break;
                        } catch (e) {
                            console.warn('Failed to surround contents, trying alternative approach');
                            continue;
                        }
                    }
                }
            }

            if (!foundText) {
                console.warn('Note text not found in DOM:', note.selectedText);
            }
        }
    }, 100);
};

// ================================
// Delete Note
// ================================
const handleDeleteNote = (data: { id: number; part: string }) => {
    if (data.part === 'Part 1') listeningPart1Ref.value?.deleteNote(data.id);
    else if (data.part === 'Part 2') listeningPart2Ref.value?.deleteNote(data.id);
    else if (data.part === 'Part 3') listeningPart3Ref.value?.deleteNote(data.id);
    else listeningPart4Ref.value?.deleteNote(data.id);
};

// ================================
// Modal
// ================================
const handleShowSubmitModal = () => (showSubmitModal.value = true);
const handleCancelSubmit = () => {
    if (isTimeUp.value) return;
    showSubmitModal.value = false;
};
const handleShowReviewModal = () => {
    if (isTimeUp.value) return;
    showReviewModal.value = true;
};
const handleCloseReviewModal = () => (showReviewModal.value = false);
const handleTimeUp = () => {
    isTimeUp.value = true;
    showSubmitModal.value = true;
};

// ================================
// Submit
// ================================
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

        router.post('/practice/results/listening/5', {
            userAnswers: formattedUserAnswers,
            correctAnswers: correctAnswers,
            score: {
                correct,
                incorrect,
                bandScore,
            },
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

        router.post('/practice/results/listening/5', {
            userAnswers: formattedUserAnswers,
            correctAnswers: correctAnswers,
            score: {
                correct,
                incorrect,
                bandScore,
            },
        });
    } catch (err) {
        console.error('Submission error:', err);
        router.visit(`/exam/${props.slug}`);
    }
};

// ================================
// Get all user answers for review modal
// ================================
const getAllUserAnswers = () => {
    return {
        ...(listeningPart1Ref.value?.getAnswers?.() || {}),
        ...(listeningPart2Ref.value?.getAnswers?.() || {}),
        ...(listeningPart3Ref.value?.getAnswers?.() || {}),
        ...(listeningPart4Ref.value?.getAnswers?.() || {}),
    };
};

// ================================
// Track answered questions
// ================================
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

// ================================
// Browser Control Logic
// ================================
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

const handleMouseButton = (event: MouseEvent) => {
    if (isLeavingPage.value) return;

    if (event.button === 3 || event.button === 4) {
        event.preventDefault();
        event.stopPropagation();
        showForceSubmitModal.value = true;
        return false;
    }
};

const handleContextMenu = (event: MouseEvent) => {
    if (!isLeavingPage.value) {
        event.preventDefault();
        return false;
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

    answerCheckInterval = setInterval(() => {
        updateAnsweredQuestions();
    }, 500);

    window.addEventListener('beforeunload', handleBeforeUnload);
    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('popstate', preventBackNavigation);
    document.addEventListener('mousedown', handleMouseButton, true);
    document.addEventListener('mouseup', handleMouseButton, true);
    document.addEventListener('contextmenu', handleContextMenu);

    window.history.pushState(null, '', window.location.href);

    (window as any).__historyInterval = setInterval(() => {
        if (!isLeavingPage.value) {
            window.history.pushState(null, '', window.location.href);
        }
    }, 100);
});

onBeforeUnmount(() => {
    if (answerCheckInterval) {
        clearInterval(answerCheckInterval);
    }

    window.removeEventListener('beforeunload', handleBeforeUnload);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    window.removeEventListener('popstate', preventBackNavigation);
    document.removeEventListener('mousedown', handleMouseButton, true);
    document.removeEventListener('mouseup', handleMouseButton, true);
    document.removeEventListener('contextmenu', handleContextMenu);

    if ((window as any).__historyInterval) {
        clearInterval((window as any).__historyInterval);
    }
});
</script>

<template>
    <Head title="C19 Listening Section - Nextive Solution" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 text-black">
        <C19Test1ListeningHeader
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
            <C19Test1ListeningPart1 ref="listeningPart1Ref" :phone="userPhone" :exam-id="examId" :correct-answers="part1CorrectAnswers" />
        </div>
        <div :class="{ hidden: activePart !== 'part2' }">
            <C19Test1ListeningPart2 ref="listeningPart2Ref" :phone="userPhone" :exam-id="examId" />
        </div>
        <div :class="{ hidden: activePart !== 'part3' }">
            <C19Test1ListeningPart3 ref="listeningPart3Ref" :phone="userPhone" :exam-id="examId" />
        </div>
        <div :class="{ hidden: activePart !== 'part4' }">
            <C19Test1ListeningPart4 ref="listeningPart4Ref" :phone="userPhone" :exam-id="examId" />
        </div>
        <C19Test1ListeningFooter
            :active-part="activePart"
            :answered-questions="answeredQuestions"
            @navigate="handleNavigate"
            @scroll-to-question="handleScrollToQuestion"
        />

        <!-- Review Modal -->
        <C19Test1ListeningReviewModal
            :is-visible="showReviewModal"
            :user-answers="getAllUserAnswers()"
            :answered-questions="answeredQuestions"
            @close="handleCloseReviewModal"
        />

        <!-- Submit Modal -->
        <div
            v-if="showSubmitModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4"
            style="position: fixed; top: 0; left: 0; right: 0; bottom: 0"
            @click="isTimeUp ? null : handleCancelSubmit"
        >
            <div class="relative z-[10000] w-full max-w-md rounded-lg bg-white p-4 shadow-xl sm:p-6" @click.stop>
                <div class="mb-4 flex items-center gap-3">
                    <div
                        :class="['flex h-8 w-8 items-center justify-center rounded-full sm:h-10 sm:w-10', isTimeUp ? 'bg-orange-100' : 'bg-red-100']"
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
                    <h3 class="text-base font-semibold text-gray-900 sm:text-lg">
                        {{ isTimeUp ? 'Time is Up!' : 'Submit Test' }}
                    </h3>
                </div>
                <p class="mb-6 text-sm text-gray-600 sm:text-base">
                    <span v-if="isTimeUp" class="font-semibold text-orange-600"> Your time has expired. You must submit your test now. </span>
                    <span v-else> Are you sure you want to submit your listening test? This action cannot be undone. </span>
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
    </div>
</template>
