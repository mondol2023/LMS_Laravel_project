<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

import ReadingFooterIELTS008 from '@/components/Exam/ReadingIELTS008/ReadingFooterIELTS008.vue';
import ReadingHeaderIELTS008 from '@/components/Exam/ReadingIELTS008/ReadingHeaderIELTS008.vue';
import ReadingPart1IELTS008 from '@/components/Exam/ReadingIELTS008/ReadingPart1IELTS008.vue';
import ReadingPart2IELTS008 from '@/components/Exam/ReadingIELTS008/ReadingPart2IELTS008.vue';
import ReadingPart3IELTS008 from '@/components/Exam/ReadingIELTS008/ReadingPart3IELTS008.vue';
import ReadingSubmitModal from '@/components/Exam/Reading/ReadingSubmitModalIELTS001.vue';

interface Props {
    slug: string;
    section: string;
    resultId: number;
}

const props = defineProps<Props>();

// ================================
// Component state & refs
// ================================
const activePart = ref<'part1' | 'part2' | 'part3'>('part1');
const showSubmitModal = ref(false);
const answeredQuestions = ref<Set<number>>(new Set());
const flaggedQuestions = ref<Set<number>>(new Set());
const isTimeUp = ref(false);
const showForceSubmitModal = ref(false);
const isLeavingPage = ref(false);
const selectedQuestion = ref<number | null>(null);

const readingPart1Ref = ref<any>();
const readingPart2Ref = ref<any>();
const readingPart3Ref = ref<any>();

const resultId = ref(props.resultId);

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

// Contrast theme state (for Part 1-3 content only)
const contrastTheme = ref<'black-on-white' | 'white-on-black' | 'yellow-on-black'>('black-on-white');
const handleContrastChange = (theme: 'black-on-white' | 'white-on-black' | 'yellow-on-black') => {
    contrastTheme.value = theme;
};

// Computed classes for contrast theme (applied to parts content)
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

// Track notes from all parts using computed properties that access child component refs
const part1Notes = computed(() => readingPart1Ref.value?.notes || []);
const part2Notes = computed(() => readingPart2Ref.value?.notes || []);
const part3Notes = computed(() => readingPart3Ref.value?.notes || []);

// ================================
// Correct answers (IELTS008 specific)
// ================================

const correctAnswers: Record<number, string> = {
    // Part 1: Questions 1-13
    1: 'FALSE',
    2: 'NOT GIVEN',
    3: 'FALSE',
    4: 'TRUE',
    5: 'NOT GIVEN',
    6: 'TRUE',
    7: 'NOT GIVEN',
    8: 'rich',
    9: 'commercial',
    10: 'mauve',
    11: 'Robert Pullar',
    12: 'France',
    13: 'malaria',

    // Part 2: Questions 14-26
    14: 'iv',
    15: 'vii',
    16: 'i',
    17: 'ii',
    18: 'several billion years',
    19: 'radio waves/waves',
    20: '1000 stars/1000',
    21: 'TRUE',
    22: 'TRUE',
    23: 'NOT GIVEN',
    24: 'FALSE',
    25: 'NOT GIVEN',
    26: 'FALSE',

    // Part 3: Questions 27-40
    27: 'plants',
    28: 'breathing and reproduction',
    29: 'gills',
    30: 'dolphins',
    31: 'NOT GIVEN',
    32: 'FALSE',
    33: 'TRUE',
    34: '3 measurements',
    35: 'triangular graph/graph',
    36: 'cluster',
    37: 'amphibious',
    38: 'half way',
    39: 'dry land tortoises',
    40: 'D',
};

// ================================
// Navigation
// ================================
const handleNavigate = (part: 'part1' | 'part2' | 'part3') => (activePart.value = part);

const partsList: ('part1' | 'part2' | 'part3')[] = ['part1', 'part2', 'part3'];
const partLabels: Record<string, string> = { part1: 'Part 1', part2: 'Part 2', part3: 'Part 3' };
const currentPartIndex = computed(() => partsList.indexOf(activePart.value));
const hasPreviousPart = computed(() => currentPartIndex.value > 0);
const hasNextPart = computed(() => currentPartIndex.value < partsList.length - 1);

const goToPreviousPart = () => {
    if (hasPreviousPart.value) {
        activePart.value = partsList[currentPartIndex.value - 1];
    }
};

const goToNextPart = () => {
    if (hasNextPart.value) {
        activePart.value = partsList[currentPartIndex.value + 1];
    }
};

const handleScrollToQuestion = async (questionNumber: number) => {
    // Update selected question
    selectedQuestion.value = questionNumber;

    // IELTS008 question ranges: Part1: 1-14, Part2: 15-27, Part3: 28-40
    if (questionNumber <= 14) activePart.value = 'part1';
    else if (questionNumber <= 27) activePart.value = 'part2';
    else activePart.value = 'part3';

    await nextTick();

    if (questionNumber <= 14) readingPart1Ref.value?.scrollToQuestion(questionNumber);
    else if (questionNumber <= 27) readingPart2Ref.value?.scrollToQuestion(questionNumber);
    else readingPart3Ref.value?.scrollToQuestion(questionNumber);
};

// ================================
// Notes handlers
// ================================
const handleFocusNote = async (note: { part: string; selectedText: string; start: number; end: number }) => {
    // Switch to the correct part
    if (note.part === 'Part 1') activePart.value = 'part1';
    else if (note.part === 'Part 2') activePart.value = 'part2';
    else activePart.value = 'part3';

    // Wait for DOM to update
    await nextTick();

    // Use requestAnimationFrame to ensure DOM is fully rendered
    requestAnimationFrame(() => {
        // Find existing highlight with matching text
        const marks = document.querySelectorAll('mark[data-highlight-id]');
        let foundMark: Element | null = null;

        for (const mark of marks) {
            if (mark.textContent?.trim() === note.selectedText.trim()) {
                foundMark = mark;
                break;
            }
        }

        if (foundMark) {
            // Scroll to the existing highlight and add a temporary focus effect
            foundMark.scrollIntoView({ behavior: 'smooth', block: 'center' });
            foundMark.classList.add('ring-4', 'ring-blue-400', 'ring-offset-2', 'transition-all');
            requestAnimationFrame(() => {
                setTimeout(() => {
                    foundMark?.classList.remove('ring-4', 'ring-blue-400', 'ring-offset-2');
                }, 2000);
            });
        } else {
            // Search through all .select-text elements to find the text
            const contentAreas = document.querySelectorAll('.select-text');
            let foundText = false;

            for (const contentArea of contentAreas) {
                if (foundText) break;

                const walker = document.createTreeWalker(contentArea, NodeFilter.SHOW_TEXT, null);
                let node;

                while ((node = walker.nextNode())) {
                    const text = node.textContent || '';
                    const index = text.indexOf(note.selectedText);

                    if (index !== -1 && node.parentElement) {
                        try {
                            const range = document.createRange();
                            range.setStart(node, index);
                            range.setEnd(node, index + note.selectedText.length);

                            // Create temporary highlight
                            const span = document.createElement('span');
                            span.className = 'bg-blue-200 ring-4 ring-blue-400 ring-offset-2 transition-all rounded px-0.5';
                            range.surroundContents(span);

                            // Scroll to it
                            span.scrollIntoView({ behavior: 'smooth', block: 'center' });

                            // Remove temporary highlight after delay
                            requestAnimationFrame(() => {
                                setTimeout(() => {
                                    const parent = span.parentNode;
                                    if (parent) {
                                        parent.replaceChild(document.createTextNode(note.selectedText), span);
                                        parent.normalize();
                                    }
                                }, 2000);
                            });

                            foundText = true;
                            break;
                        } catch {
                            // If surroundContents fails, continue to next node
                            continue;
                        }
                    }
                }
            }
        }
    });
};

const handleDeleteNote = (data: { id: number; part: string }) => {
    if (data.part === 'Part 1') readingPart1Ref.value?.deleteNote(data.id);
    else if (data.part === 'Part 2') readingPart2Ref.value?.deleteNote(data.id);
    else readingPart3Ref.value?.deleteNote(data.id);
};

// ================================
// Flag Questions
// ================================
const handleToggleFlag = (questionNumber: number) => {
    const newFlagged = new Set(flaggedQuestions.value);
    if (newFlagged.has(questionNumber)) {
        newFlagged.delete(questionNumber);
    } else {
        newFlagged.add(questionNumber);
    }
    flaggedQuestions.value = newFlagged;
};

// ================================
// Exit Handler
// ================================
const handleExit = () => {
    // Show submit modal when user clicks exit
    showSubmitModal.value = true;
};

// ================================
// Modal
// ================================
const handleShowSubmitModal = () => (showSubmitModal.value = true);
const handleCancelSubmit = () => {
    // Prevent closing modal when time is up
    if (isTimeUp.value) return;
    showSubmitModal.value = false;
};
const handleTimeUp = () => {
    isTimeUp.value = true;
    // Auto-show submit modal when time is up
    showSubmitModal.value = true;
};

// ================================
// Utilities & Scoring
// ================================
const normalize = (str: string): string => {
    let normalized = str.toString().trim().toLowerCase();

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
                                  : score >= 2
                                    ? 2
                                    : score >= 1
                                      ? 1
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
            correct++;
        }
    }
    return { correct, incorrect: 40 - correct };
};

// ================================
// Submit
// ================================
const handleConfirmSubmit = async () => {
    showSubmitModal.value = false;
    isLeavingPage.value = true;

    // Clear sessionStorage flag before leaving
    sessionStorage.removeItem(`reading_visited_${props.slug}`);

    try {
        const allAnswers = {
            ...readingPart1Ref.value?.getAnswers?.(),
            ...readingPart2Ref.value?.getAnswers?.(),
            ...readingPart3Ref.value?.getAnswers?.(),
        };
        const { correct, incorrect } = compareAnswers(allAnswers);
        const bandScore = calculateBandScore(correct);

        await fetch('/api/results/store-section', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                result_id: resultId.value,
                section: 'reading',
                section_data: {
                    total_questions: 40,
                    correct_answers: correct,
                    incorrect_answers: incorrect,
                    raw_score: correct,
                    band_score: bandScore,
                    user_answers: allAnswers,
                },
            }),
        });

        router.visit(`/exam/${props.slug}`);
    } catch (err) {
        router.visit(`/exam/${props.slug}`);
    }
};

// Handle force submit from modal
const handleForceSubmit = async () => {
    showForceSubmitModal.value = false;
    isLeavingPage.value = true;

    // Clear sessionStorage flag before leaving
    sessionStorage.removeItem(`reading_visited_${props.slug}`);

    try {
        const allAnswers = {
            ...readingPart1Ref.value?.getAnswers?.(),
            ...readingPart2Ref.value?.getAnswers?.(),
            ...readingPart3Ref.value?.getAnswers?.(),
        };
        const { correct, incorrect } = compareAnswers(allAnswers);
        const bandScore = calculateBandScore(correct);

        await fetch('/api/results/store-section', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                result_id: resultId.value,
                section: 'reading',
                section_data: {
                    total_questions: 40,
                    correct_answers: correct,
                    incorrect_answers: incorrect,
                    raw_score: correct,
                    band_score: bandScore,
                    user_answers: allAnswers,
                },
            }),
        });

        router.visit(`/exam/${props.slug}`);
    } catch (err) {
        router.visit(`/exam/${props.slug}`);
    }
};

// ================================
// Get all user answers
// ================================
const getAllUserAnswers = () => {
    return {
        ...(readingPart1Ref.value?.getAnswers?.() || {}),
        ...(readingPart2Ref.value?.getAnswers?.() || {}),
        ...(readingPart3Ref.value?.getAnswers?.() || {}),
    };
};

// ================================
// Track answered questions
// ================================
const updateAnsweredQuestions = () => {
    const allAnswers = getAllUserAnswers();
    const newAnsweredQuestions = new Set<number>();

    // Check each question (1-40) for answers
    for (let i = 1; i <= 40; i++) {
        const answer = allAnswers[`q${i}`];
        if (answer && answer.toString().trim() !== '') {
            newAnsweredQuestions.add(i);
        }
    }

    // Only update if there's an actual change to prevent unnecessary re-renders
    const oldSet = answeredQuestions.value;
    if (oldSet.size !== newAnsweredQuestions.size || ![...newAnsweredQuestions].every((q) => oldSet.has(q))) {
        answeredQuestions.value = newAnsweredQuestions;
    }
};

// Check if a question is answered
// Update answered questions periodically
let answerCheckInterval: any = null;

// ================================
// Browser Control Logic
// ================================

// Prevent page close
const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    if (!isLeavingPage.value) {
        event.preventDefault();
        event.returnValue = '';
        return event.returnValue;
    }
};

// Detect tab switching or window blur/minimize
const handleVisibilityChange = () => {
    if (document.hidden && !isLeavingPage.value) {
        // User switched tabs, minimized, or opened new tab
        showForceSubmitModal.value = true;
    }
};

// Prevent back button navigation
const preventBackNavigation = () => {
    if (!isLeavingPage.value) {
        window.history.pushState(null, '', window.location.href);
        showForceSubmitModal.value = true;
    }
};

// Detect mouse back/forward buttons
const handleMouseButton = (event: MouseEvent) => {
    if (isLeavingPage.value) return;

    // Mouse button 3 = back, button 4 = forward
    if (event.button === 3 || event.button === 4) {
        event.preventDefault();
        event.stopPropagation();
        showForceSubmitModal.value = true;
        return false;
    }
};

// Additional context menu disable (right-click)
const handleContextMenu = (event: MouseEvent) => {
    if (!isLeavingPage.value) {
        event.preventDefault();
        return false;
    }
};

// // Setup event listeners
onMounted(() => {
    // Hide browser scrollbar for Reading section
    document.documentElement.classList.add('hide-scrollbar');
    document.body.classList.add('hide-scrollbar');

    // Auto-submit on page load (handles reload scenario)
    const hasVisitedReading = sessionStorage.getItem(`reading_visited_${props.slug}`);

    if (hasVisitedReading === 'true') {
        // User has reloaded the page - auto submit
        showForceSubmitModal.value = true;
    } else {
        // First visit - mark as visited
        sessionStorage.setItem(`reading_visited_${props.slug}`, 'true');
    }

    // Setup interval to check answered questions (reduced frequency for better performance)
    answerCheckInterval = setInterval(() => {
        updateAnsweredQuestions();
    }, 2000); // Check every 2 seconds instead of 500ms for better performance on low-spec devices

    // Prevent page close
    window.addEventListener('beforeunload', handleBeforeUnload);

    // Detect tab switching, minimize, new tab
    document.addEventListener('visibilitychange', handleVisibilityChange);

    // Prevent back button navigation
    window.addEventListener('popstate', preventBackNavigation);

    // Detect mouse back/forward buttons
    document.addEventListener('mousedown', handleMouseButton, true);
    document.addEventListener('mouseup', handleMouseButton, true);

    // Disable right-click context menu
    document.addEventListener('contextmenu', handleContextMenu);

    // Push initial state to prevent back navigation
    window.history.pushState(null, '', window.location.href);
});

// // Cleanup event listeners
onBeforeUnmount(() => {
    // Restore browser scrollbar
    document.documentElement.classList.remove('hide-scrollbar');
    document.body.classList.remove('hide-scrollbar');

    // Clear answer check interval
    if (answerCheckInterval) {
        clearInterval(answerCheckInterval);
    }

    // Remove all event listeners
    window.removeEventListener('beforeunload', handleBeforeUnload);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    window.removeEventListener('popstate', preventBackNavigation);
    document.removeEventListener('mousedown', handleMouseButton, true);
    document.removeEventListener('mouseup', handleMouseButton, true);
    document.removeEventListener('contextmenu', handleContextMenu);
});
</script>

<template>
    <Head title="Reading Section - IELTS Mock Test" />
    <div class="hide-scrollbar min-h-screen text-black" :class="bgColor === 'gray' ? 'bg-gray-100' : 'bg-white'">
        <ReadingHeaderIELTS008
            test-title="IELTS Academic Reading Test"
            current-section="Reading"
            :time-limit="60"
            :is-time-up="isTimeUp"
            :part1-notes="part1Notes"
            :part2-notes="part2Notes"
            :part3-notes="part3Notes"
            @show-submit-modal="handleShowSubmitModal"
            @time-up="handleTimeUp"
            @submit-test="handleConfirmSubmit"
            @focus-note="handleFocusNote"
            @delete-note="handleDeleteNote"
            @font-size-change="handleFontSizeChange"
            @bg-color-change="handleBgColorChange"
            @contrast-change="handleContrastChange"
        />

        <!-- Parts Container with Contrast Theme -->
        <div class="parts-container" :class="contrastClasses" :data-theme="contrastTheme">
            <div :class="{ hidden: activePart !== 'part1' }">
                <ReadingPart1IELTS008 ref="readingPart1Ref" :font-size="fontSize" :flagged-questions="flaggedQuestions" @toggle-flag="handleToggleFlag" />
            </div>
            <div :class="{ hidden: activePart !== 'part2' }">
                <ReadingPart2IELTS008 ref="readingPart2Ref" :font-size="fontSize" :flagged-questions="flaggedQuestions" @toggle-flag="handleToggleFlag" />
            </div>
            <div :class="{ hidden: activePart !== 'part3' }">
                <ReadingPart3IELTS008 ref="readingPart3Ref" :font-size="fontSize" :flagged-questions="flaggedQuestions" @toggle-flag="handleToggleFlag" />
            </div>
        </div>

        <ReadingFooterIELTS008
            :active-part="activePart"
            :answered-questions="answeredQuestions"
            :flagged-questions="flaggedQuestions"
            :selected-question="selectedQuestion"
            @navigate="handleNavigate"
            @scroll-to-question="handleScrollToQuestion"
            @exit="handleExit"
        />

        <!-- Force Submit Modal (Reload/Back/Tab Switch Protection) -->
        <Teleport to="body">
            <div
                v-if="showForceSubmitModal"
                class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-md rounded border border-black bg-white p-6 text-center shadow">
                    <!-- Icon -->
                    <div class="mb-4 flex justify-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded bg-black">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                                ></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Title -->
                    <h2 class="mb-2 text-lg font-bold text-black">Must Submit to Continue</h2>

                    <!-- Message -->
                    <p class="mb-2 text-sm text-gray-600">
                        You cannot reload, go back, switch tabs, or minimize during the exam.
                    </p>
                    <p class="mb-4 text-sm font-semibold text-black">
                        You must submit your exam answers to proceed.
                    </p>

                    <!-- Button -->
                    <button
                        @click="handleForceSubmit"
                        class="w-full rounded bg-black py-2 text-sm font-semibold text-white transition-all duration-200 hover:bg-gray-800 hover:scale-[1.02] active:scale-100"
                    >
                        Submit Exam Now
                    </button>
                </div>
            </div>
        </Teleport>

        <!-- Submit Modal -->
        <ReadingSubmitModal
            :is-visible="showSubmitModal"
            :answered-count="answeredQuestions.size"
            :total-questions="40"
            :is-time-up="isTimeUp"
            @close="handleCancelSubmit"
            @submit="handleConfirmSubmit"
        />
    </div>
</template>

<style scoped>
/* ===== WHITE ON BLACK THEME ===== */
/* Override all Tailwind text color classes */
.parts-container[data-theme='white-on-black'] :deep(.text-gray-900),
.parts-container[data-theme='white-on-black'] :deep(.text-gray-800),
.parts-container[data-theme='white-on-black'] :deep(.text-gray-700),
.parts-container[data-theme='white-on-black'] :deep(.text-gray-600),
.parts-container[data-theme='white-on-black'] :deep(.text-gray-500),
.parts-container[data-theme='white-on-black'] :deep(.text-black) {
    color: white !important;
}

/* Override all text elements */
.parts-container[data-theme='white-on-black'] :deep(span:not(.bg-black span)),
.parts-container[data-theme='white-on-black'] :deep(p),
.parts-container[data-theme='white-on-black'] :deep(h1),
.parts-container[data-theme='white-on-black'] :deep(h2),
.parts-container[data-theme='white-on-black'] :deep(h3),
.parts-container[data-theme='white-on-black'] :deep(h4),
.parts-container[data-theme='white-on-black'] :deep(label) {
    color: white !important;
}

/* Input fields */
.parts-container[data-theme='white-on-black'] :deep(input) {
    background-color: transparent !important;
    color: white !important;
    border-color: rgba(255, 255, 255, 0.5) !important;
}

.parts-container[data-theme='white-on-black'] :deep(input::placeholder) {
    color: rgba(255, 255, 255, 0.5) !important;
}

/* SVG icons */
.parts-container[data-theme='white-on-black'] :deep(svg:not(.bg-black svg)) {
    color: white !important;
}

/* Borders */
.parts-container[data-theme='white-on-black'] :deep(.border-gray-200),
.parts-container[data-theme='white-on-black'] :deep(.border-gray-300),
.parts-container[data-theme='white-on-black'] :deep(.border-gray-900),
.parts-container[data-theme='white-on-black'] :deep([class*='border-gray']) {
    border-color: rgba(255, 255, 255, 0.3) !important;
}

/* Number badges - invert to white bg with black text */
.parts-container[data-theme='white-on-black'] :deep(.bg-black) {
    background-color: white !important;
}

.parts-container[data-theme='white-on-black'] :deep(.bg-black span),
.parts-container[data-theme='white-on-black'] :deep(.bg-black .text-white) {
    color: black !important;
}

.parts-container[data-theme='white-on-black'] :deep(.bg-black svg) {
    color: black !important;
}

/* ===== YELLOW ON BLACK THEME ===== */
/* Override all Tailwind text color classes */
.parts-container[data-theme='yellow-on-black'] :deep(.text-gray-900),
.parts-container[data-theme='yellow-on-black'] :deep(.text-gray-800),
.parts-container[data-theme='yellow-on-black'] :deep(.text-gray-700),
.parts-container[data-theme='yellow-on-black'] :deep(.text-gray-600),
.parts-container[data-theme='yellow-on-black'] :deep(.text-gray-500),
.parts-container[data-theme='yellow-on-black'] :deep(.text-black) {
    color: #facc15 !important;
}

/* Override all text elements */
.parts-container[data-theme='yellow-on-black'] :deep(span:not(.bg-black span)),
.parts-container[data-theme='yellow-on-black'] :deep(p),
.parts-container[data-theme='yellow-on-black'] :deep(h1),
.parts-container[data-theme='yellow-on-black'] :deep(h2),
.parts-container[data-theme='yellow-on-black'] :deep(h3),
.parts-container[data-theme='yellow-on-black'] :deep(h4),
.parts-container[data-theme='yellow-on-black'] :deep(label) {
    color: #facc15 !important;
}

/* Input fields */
.parts-container[data-theme='yellow-on-black'] :deep(input) {
    background-color: transparent !important;
    color: #facc15 !important;
    border-color: rgba(250, 204, 21, 0.5) !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(input::placeholder) {
    color: rgba(250, 204, 21, 0.5) !important;
}

/* SVG icons */
.parts-container[data-theme='yellow-on-black'] :deep(svg:not(.bg-black svg)) {
    color: #facc15 !important;
}

/* Borders */
.parts-container[data-theme='yellow-on-black'] :deep(.border-gray-200),
.parts-container[data-theme='yellow-on-black'] :deep(.border-gray-300),
.parts-container[data-theme='yellow-on-black'] :deep(.border-gray-900),
.parts-container[data-theme='yellow-on-black'] :deep([class*='border-gray']) {
    border-color: rgba(250, 204, 21, 0.3) !important;
}

/* Number badges - invert to white bg with black text */
.parts-container[data-theme='yellow-on-black'] :deep(.bg-black) {
    background-color: white !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(.bg-black span),
.parts-container[data-theme='yellow-on-black'] :deep(.bg-black .text-white) {
    color: black !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(.bg-black svg) {
    color: black !important;
}

/* ===== ADDITIONAL OVERRIDES FOR DARK THEMES ===== */
/* Override white/light backgrounds for White on Black */
.parts-container[data-theme='white-on-black'] :deep(.bg-white),
.parts-container[data-theme='white-on-black'] :deep(.bg-gray-50),
.parts-container[data-theme='white-on-black'] :deep(.bg-gray-100) {
    background-color: transparent !important;
}

/* Hover states for White on Black */
.parts-container[data-theme='white-on-black'] :deep(.hover\:bg-gray-50:hover),
.parts-container[data-theme='white-on-black'] :deep(.hover\:bg-gray-100:hover) {
    background-color: transparent !important;
}

/* Checkboxes and radio buttons */
.parts-container[data-theme='white-on-black'] :deep(input[type='checkbox']),
.parts-container[data-theme='white-on-black'] :deep(input[type='radio']) {
    background-color: transparent !important;
    border-color: white !important;
}

.parts-container[data-theme='white-on-black'] :deep(input[type='checkbox']:checked),
.parts-container[data-theme='white-on-black'] :deep(input[type='radio']:checked) {
    background-color: white !important;
    border-color: white !important;
}

/* Override white/light backgrounds for Yellow on Black */
.parts-container[data-theme='yellow-on-black'] :deep(.bg-white),
.parts-container[data-theme='yellow-on-black'] :deep(.bg-gray-50),
.parts-container[data-theme='yellow-on-black'] :deep(.bg-gray-100) {
    background-color: transparent !important;
}

/* Hover states for Yellow on Black */
.parts-container[data-theme='yellow-on-black'] :deep(.hover\:bg-gray-50:hover),
.parts-container[data-theme='yellow-on-black'] :deep(.hover\:bg-gray-100:hover) {
    background-color: transparent !important;
}

/* Checkboxes and radio buttons */
.parts-container[data-theme='yellow-on-black'] :deep(input[type='checkbox']),
.parts-container[data-theme='yellow-on-black'] :deep(input[type='radio']) {
    background-color: transparent !important;
    border-color: #facc15 !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(input[type='checkbox']:checked),
.parts-container[data-theme='yellow-on-black'] :deep(input[type='radio']:checked) {
    background-color: #facc15 !important;
    border-color: #facc15 !important;
}

/* Selection/selected state boxes */
.parts-container[data-theme='white-on-black'] :deep([class*='selected']),
.parts-container[data-theme='white-on-black'] :deep([class*='Selected']) {
    background-color: transparent !important;
    color: white !important;
}

.parts-container[data-theme='yellow-on-black'] :deep([class*='selected']),
.parts-container[data-theme='yellow-on-black'] :deep([class*='Selected']) {
    background-color: transparent !important;
    color: #facc15 !important;
}

/* ===== HIGHLIGHT MARKS - WHITE ON BLACK ===== */
.parts-container[data-theme='white-on-black'] :deep(.highlight-yellow) {
    background-color: rgba(254, 240, 138, 0.7) !important;
    color: #1f2937 !important;
}

.parts-container[data-theme='white-on-black'] :deep(.highlight-green) {
    background-color: rgba(187, 247, 208, 0.7) !important;
    color: #1f2937 !important;
}

.parts-container[data-theme='white-on-black'] :deep(.highlight-blue) {
    background-color: rgba(147, 197, 253, 0.7) !important;
    color: #1f2937 !important;
}

.parts-container[data-theme='white-on-black'] :deep(.highlight-pink) {
    background-color: rgba(251, 207, 232, 0.7) !important;
    color: #1f2937 !important;
}

.parts-container[data-theme='white-on-black'] :deep(.highlight-orange) {
    background-color: rgba(254, 215, 170, 0.7) !important;
    color: #1f2937 !important;
}

/* Note highlight for White on Black */
.parts-container[data-theme='white-on-black'] :deep(.note-highlight) {
    border-bottom-color: rgba(255, 255, 255, 0.6) !important;
}

.parts-container[data-theme='white-on-black'] :deep(.note-highlight:hover) {
    border-bottom-color: white !important;
}

/* ===== HIGHLIGHT MARKS - YELLOW ON BLACK ===== */
.parts-container[data-theme='yellow-on-black'] :deep(.highlight-yellow) {
    background-color: rgba(254, 240, 138, 0.8) !important;
    color: #1f2937 !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(.highlight-green) {
    background-color: rgba(187, 247, 208, 0.8) !important;
    color: #1f2937 !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(.highlight-blue) {
    background-color: rgba(147, 197, 253, 0.8) !important;
    color: #1f2937 !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(.highlight-pink) {
    background-color: rgba(251, 207, 232, 0.8) !important;
    color: #1f2937 !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(.highlight-orange) {
    background-color: rgba(254, 215, 170, 0.8) !important;
    color: #1f2937 !important;
}

/* Note highlight for Yellow on Black */
.parts-container[data-theme='yellow-on-black'] :deep(.note-highlight) {
    border-bottom-color: rgba(250, 204, 21, 0.6) !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(.note-highlight:hover) {
    border-bottom-color: #facc15 !important;
}

/* ===== SCROLLBAR STYLING FOR DARK THEMES ===== */
.parts-container[data-theme='white-on-black'] :deep(::-webkit-scrollbar-track) {
    background: #1f2937 !important;
}

.parts-container[data-theme='white-on-black'] :deep(::-webkit-scrollbar-thumb) {
    background: rgba(255, 255, 255, 0.4) !important;
}

.parts-container[data-theme='white-on-black'] :deep(::-webkit-scrollbar-thumb:hover) {
    background: rgba(255, 255, 255, 0.6) !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(::-webkit-scrollbar-track) {
    background: #1f2937 !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(::-webkit-scrollbar-thumb) {
    background: rgba(250, 204, 21, 0.4) !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(::-webkit-scrollbar-thumb:hover) {
    background: rgba(250, 204, 21, 0.6) !important;
}

/* ===== FOCUS RINGS FOR ACCESSIBILITY ===== */
.parts-container[data-theme='white-on-black'] :deep(input:focus),
.parts-container[data-theme='white-on-black'] :deep(button:focus) {
    outline-color: white !important;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3) !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(input:focus),
.parts-container[data-theme='yellow-on-black'] :deep(button:focus) {
    outline-color: #facc15 !important;
    box-shadow: 0 0 0 2px rgba(250, 204, 21, 0.3) !important;
}

/* ===== PURPLE/SPECIAL BORDERS ===== */
.parts-container[data-theme='white-on-black'] :deep(.border-purple-200),
.parts-container[data-theme='white-on-black'] :deep([class*='border-purple']) {
    border-color: rgba(255, 255, 255, 0.4) !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(.border-purple-200),
.parts-container[data-theme='yellow-on-black'] :deep([class*='border-purple']) {
    border-color: rgba(250, 204, 21, 0.4) !important;
}

/* ===== BUTTON STYLING FOR DARK THEMES ===== */
.parts-container[data-theme='white-on-black'] :deep(button:not(.bg-black)) {
    border-color: rgba(255, 255, 255, 0.4) !important;
    background-color: transparent !important;
}

.parts-container[data-theme='white-on-black'] :deep(button:not(.bg-black):hover) {
    background-color: transparent !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(button:not(.bg-black)) {
    border-color: rgba(250, 204, 21, 0.4) !important;
    background-color: transparent !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(button:not(.bg-black):hover) {
    background-color: transparent !important;
}

/* ===== TEXT INPUT SPECIFIC STYLING ===== */
.parts-container[data-theme='white-on-black'] :deep(input[type='text']),
.parts-container[data-theme='white-on-black'] :deep(input[type='number']) {
    background-color: transparent !important;
    color: white !important;
    border-color: rgba(255, 255, 255, 0.5) !important;
    caret-color: white !important;
}

.parts-container[data-theme='white-on-black'] :deep(input[type='text']:focus),
.parts-container[data-theme='white-on-black'] :deep(input[type='number']:focus) {
    border-color: white !important;
    background-color: transparent !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(input[type='text']),
.parts-container[data-theme='yellow-on-black'] :deep(input[type='number']) {
    background-color: transparent !important;
    color: #facc15 !important;
    border-color: rgba(250, 204, 21, 0.5) !important;
    caret-color: #facc15 !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(input[type='text']:focus),
.parts-container[data-theme='yellow-on-black'] :deep(input[type='number']:focus) {
    border-color: #facc15 !important;
    background-color: transparent !important;
}

/* ===== QUESTION HIGHLIGHT ANIMATION FOR DARK THEMES ===== */
.parts-container[data-theme='white-on-black'] :deep(.highlight-question) {
    animation: highlightPulseWhite 2s ease-in-out !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(.highlight-question) {
    animation: highlightPulseYellow 2s ease-in-out !important;
}

@keyframes highlightPulseWhite {
    0% {
        background-color: rgba(255, 255, 255, 0.1);
    }
    50% {
        background-color: rgba(255, 255, 255, 0.25);
    }
    100% {
        background-color: rgba(255, 255, 255, 0.1);
    }
}

@keyframes highlightPulseYellow {
    0% {
        background-color: rgba(250, 204, 21, 0.1);
    }
    50% {
        background-color: rgba(250, 204, 21, 0.25);
    }
    100% {
        background-color: rgba(250, 204, 21, 0.1);
    }
}

/* ===== BOOKMARK/FLAG BUTTON STYLING ===== */
/* Active bookmark - RED color for all themes */
.parts-container[data-theme='white-on-black'] :deep(button.text-red-500),
.parts-container[data-theme='white-on-black'] :deep(button.text-red-500 svg) {
    color: #ef4444 !important;
}

/* Inactive bookmark - white for dark theme */
.parts-container[data-theme='white-on-black'] :deep(button.text-gray-400) {
    color: rgba(255, 255, 255, 0.5) !important;
}
.parts-container[data-theme='white-on-black'] :deep(button.text-gray-400 svg) {
    color: rgba(255, 255, 255, 0.5) !important;
}

/* Active bookmark - RED color for yellow theme */
.parts-container[data-theme='yellow-on-black'] :deep(button.text-red-500),
.parts-container[data-theme='yellow-on-black'] :deep(button.text-red-500 svg) {
    color: #ef4444 !important;
}

/* Inactive bookmark - yellow for yellow theme */
.parts-container[data-theme='yellow-on-black'] :deep(button.text-gray-400) {
    color: rgba(250, 204, 21, 0.5) !important;
}
.parts-container[data-theme='yellow-on-black'] :deep(button.text-gray-400 svg) {
    color: rgba(250, 204, 21, 0.5) !important;
}

/* Black on white theme - ensure bookmark red is visible when active */
.parts-container[data-theme='black-on-white'] :deep(button.text-red-500),
.parts-container[data-theme='black-on-white'] :deep(button.text-red-500 svg) {
    color: #ef4444 !important;
}

/* ===== ROUNDED BORDERS AND CARDS ===== */
.parts-container[data-theme='white-on-black'] :deep(.rounded-lg),
.parts-container[data-theme='white-on-black'] :deep(.rounded) {
    border-color: rgba(255, 255, 255, 0.2) !important;
}

.parts-container[data-theme='yellow-on-black'] :deep(.rounded-lg),
.parts-container[data-theme='yellow-on-black'] :deep(.rounded) {
    border-color: rgba(250, 204, 21, 0.2) !important;
}

/* ===== TRANSITION SMOOTHING ===== */
.parts-container {
    transition: background-color 0.3s ease, color 0.3s ease;
}

.parts-container :deep(*) {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}
</style>
