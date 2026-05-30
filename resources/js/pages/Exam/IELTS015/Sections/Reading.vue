<script setup lang="ts">
import ReadingFooterIELTS015 from '@/components/Exam/ReadingIELTS015/ReadingFooterIELTS015.vue';
import ReadingHeaderIELTS015 from '@/components/Exam/ReadingIELTS015/ReadingHeaderIELTS015.vue';
import ReadingPart1IELTS015 from '@/components/Exam/ReadingIELTS015/ReadingPart1IELTS015.vue';
import ReadingPart2IELTS015 from '@/components/Exam/ReadingIELTS015/ReadingPart2IELTS015.vue';
import ReadingPart3IELTS015 from '@/components/Exam/ReadingIELTS015/ReadingPart3IELTS015.vue';
import ReadingReviewModalIELTS015 from '@/components/Exam/ReadingIELTS015/ReadingReviewModalIELTS015.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

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
const showReviewModal = ref(false);
const answeredQuestions = ref<Set<number>>(new Set());
const isTimeUp = ref(false);
const showForceSubmitModal = ref(false);
const isLeavingPage = ref(false);

const readingPart1Ref = ref<any>();
const readingPart2Ref = ref<any>();
const readingPart3Ref = ref<any>();

const resultId = ref(props.resultId);

// Notes from each part
const part1Notes = computed(() => readingPart1Ref.value?.notes || []);
const part2Notes = computed(() => readingPart2Ref.value?.notes || []);
const part3Notes = computed(() => readingPart3Ref.value?.notes || []);

// ================================
// Correct answers for IELTS002
// ================================
const correctAnswers: Record<number, string> = {
    1: 'D/E/G',
    2: 'D/E/G',
    3: 'D/E/G',

    4: 'clerks/copying clerks',
    5: 'library',
    6: 'stability',
    7: 'pension',

    8: 'TRUE',
    9: 'FALSE',
    10: 'NOT GIVEN',
    11: 'FALSE',
    12: 'FALSE',
    13: 'TRUE',

    14: 'F',
    15: 'A',
    16: 'B',
    17: 'D',
    18: 'I',
    19: 'C',
    20: 'B',

    21: 'D',
    22: 'C',
    23: 'NOT GIVEN',
    24: 'TRUE',
    25: 'FALSE',
    26: 'FALSE',

    27: 'YES',
    28: 'NOT GIVEN',
    29: 'NO',
    30: 'NOT GIVEN',
    31: 'YES',
    32: 'NO',

    33: 'C',
    34: 'D',
    35: 'C',
    36: 'B',
    37: 'B',
    38: 'E',
    39: 'D',
    40: 'I',
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
            console.log(`✅ Q${i}: "${userAnswers[`q${i}`]}" ≈ "${correctAnswers[i]}"`);
            correct++;
        } else {
            console.log(`❌ Q${i}: "${userAnswers[`q${i}`]}" ≠ "${correctAnswers[i]}"`);
        }
    }
    return { correct, incorrect: 40 - correct };
};

// ================================
// Navigation
// ================================
const handleNavigate = (part: 'part1' | 'part2' | 'part3') => (activePart.value = part);

const handleScrollToQuestion = async (questionNumber: number) => {
    if (questionNumber <= 13) activePart.value = 'part1';
    else if (questionNumber <= 26) activePart.value = 'part2';
    else activePart.value = 'part3';

    await nextTick();

    if (questionNumber <= 13) readingPart1Ref.value?.scrollToQuestion(questionNumber);
    else if (questionNumber <= 26) readingPart2Ref.value?.scrollToQuestion(questionNumber);
    else readingPart3Ref.value?.scrollToQuestion(questionNumber);
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
const handleShowReviewModal = () => {
    // Prevent opening review modal when time is up
    if (isTimeUp.value) return;
    showReviewModal.value = true;
};
const handleCloseReviewModal = () => (showReviewModal.value = false);
const handleTimeUp = () => {
    isTimeUp.value = true;
    // Auto-show submit modal when time is up
    showSubmitModal.value = true;
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
// Get all user answers for review modal
// ================================
const getAllUserAnswers = () => {
    return {
        ...(readingPart1Ref.value?.getAnswers?.() || {}),
        ...(readingPart2Ref.value?.getAnswers?.() || {}),
        ...(readingPart3Ref.value?.getAnswers?.() || {}),
    };
};

// ================================
// Note Handlers from Header
// ================================
const handleDeleteNote = ({ id, part }: { id: number; part: string }) => {
    if (part === 'Passage 1') {
        readingPart1Ref.value?.deleteNote(id);
    } else if (part === 'Passage 2') {
        readingPart2Ref.value?.deleteNote(id);
    } else if (part === 'Passage 3') {
        readingPart3Ref.value?.deleteNote(id);
    }
};

const handleFocusNote = async (note: { part: string; selectedText: string; start: number; end: number }) => {
    // Switch to the correct part
    if (note.part === 'Passage 1')
        activePart.value = 'part1'; // Note part name is 'Passage 1' not 'Part 1'
    else if (note.part === 'Passage 2') activePart.value = 'part2';
    else if (note.part === 'Passage 3') activePart.value = 'part3';

    // Wait for the DOM to update
    await nextTick();

    // Find and highlight the text in the passage
    setTimeout(() => {
        // First, try to find if there's already a highlight for this text
        // Notes created without highlight will not have this data-highlight-id attribute.
        const marks = document.querySelectorAll('mark[data-highlight-id]');
        let foundMark: Element | null = null;

        // Iterate through existing highlights (if any)
        for (const mark of marks) {
            // Check if the text content of the mark matches the selectedText of the note
            // And if the start/end offsets roughly align (optional, but good for precision)
            // Note: Directly comparing note.id with data-highlight-id might be better if notes
            // are always created with highlights, but this approach is more robust
            // if notes don't always create highlights.
            if (mark.textContent?.trim() === note.selectedText.trim()) {
                foundMark = mark;
                break;
            }
        }

        if (foundMark) {
            // Scroll to existing highlight
            foundMark.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Add a temporary blue ring effect
            foundMark.classList.add('ring-4', 'ring-blue-400', 'ring-offset-2', 'transition-all');
            setTimeout(() => {
                foundMark?.classList.remove('ring-4', 'ring-blue-400', 'ring-offset-2');
            }, 2500);
        } else {
            // If no permanent highlight exists, find the text in the content and add a temporary highlight
            // Search through ALL elements that might contain highlightable text.
            // These classes are derived from the ReadingPart components' templates.
            const contentAreas = document.querySelectorAll('.select-text, .text-gray-700, .text-gray-800, .text-black, .text-zinc-900');
            let foundText = false;

            for (const contentArea of contentAreas) {
                if (foundText) break; // Stop if text is found and highlighted

                // Check if this element or its children contain the text
                const elementText = contentArea.textContent || '';
                if (!elementText.includes(note.selectedText)) {
                    continue; // Skip if text not found in this content area
                }

                // Use TreeWalker to find the exact text node
                const walker = document.createTreeWalker(contentArea, NodeFilter.SHOW_TEXT, null);

                let node;
                while ((node = walker.nextNode())) {
                    const text = node.textContent || '';
                    const index = text.indexOf(note.selectedText);

                    if (index !== -1 && node.parentElement) {
                        // Found the text - create a temporary mark element
                        const range = document.createRange();
                        range.setStart(node, index);
                        range.setEnd(node, index + note.selectedText.length);

                        const tempMark = document.createElement('mark');
                        // Apply the desired blue border animation classes
                        tempMark.className = 'bg-blue-200 ring-4 ring-blue-400 ring-offset-2 transition-all px-1 rounded';

                        try {
                            range.surroundContents(tempMark); // Wrap the text in the temporary mark

                            // Scroll to the temporary highlight
                            tempMark.scrollIntoView({ behavior: 'smooth', block: 'center' });

                            // Remove the temporary highlight after 2.5 seconds
                            setTimeout(() => {
                                const parent = tempMark.parentNode;
                                if (parent) {
                                    // Replace the mark with its original text content
                                    parent.replaceChild(document.createTextNode(note.selectedText), tempMark);
                                    // Normalize the parent to merge adjacent text nodes
                                    parent.normalize();
                                }
                            }, 2500);

                            foundText = true; // Mark as found and break loops
                            break;
                        } catch (e) {
                            // If surroundContents fails (e.g., selection spans multiple elements), try a different approach
                            console.warn('Failed to surround contents, trying alternative approach:', e);
                            continue;
                        }
                    }
                }
            }

            // If still not found after searching all content areas, log a warning
            if (!foundText) {
                console.warn('Note text not found in DOM for temporary highlight:', note.selectedText);
            }
        }
    }, 100); // Small delay to ensure DOM update and smooth scroll
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

    answeredQuestions.value = newAnsweredQuestions;
};

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

// Check if a question is answered
// Update answered questions periodically
let answerCheckInterval: any = null;

// Setup event listeners
onMounted(() => {
    // Auto-submit on page load (handles reload scenario)
    const hasVisitedReading = sessionStorage.getItem(`reading_visited_${props.slug}`);

    if (hasVisitedReading === 'true') {
        // User has reloaded the page - auto submit
        showForceSubmitModal.value = true;
    } else {
        // First visit - mark as visited
        sessionStorage.setItem(`reading_visited_${props.slug}`, 'true');
    }

    // Setup interval to check answered questions
    answerCheckInterval = setInterval(() => {
        updateAnsweredQuestions();
    }, 500); // Check every 500ms

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

    // Store interval ID for cleanup
    (window as any).__historyInterval = setInterval(() => {
        if (!isLeavingPage.value) {
            window.history.pushState(null, '', window.location.href);
        }
    }, 100);
});

// Cleanup event listeners
onBeforeUnmount(() => {
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

    // Clear interval
    if ((window as any).__historyInterval) {
        clearInterval((window as any).__historyInterval);
    }
});
</script>

<template>
    <Head title="Reading Section - IELTS Mock Test" />
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 text-black">
        <ReadingHeaderIELTS015
            :is-time-up="isTimeUp"
            :part1-notes="part1Notes"
            :part2-notes="part2Notes"
            :part3-notes="part3Notes"
            @show-submit-modal="handleShowSubmitModal"
            @show-review-modal="handleShowReviewModal"
            @time-up="handleTimeUp"
            @delete-note="handleDeleteNote"
            @focus-note="handleFocusNote"
        />

        <div :class="{ hidden: activePart !== 'part1' }">
            <ReadingPart1IELTS015 ref="readingPart1Ref" />
        </div>
        <div :class="{ hidden: activePart !== 'part2' }">
            <ReadingPart2IELTS015 ref="readingPart2Ref" />
        </div>
        <div :class="{ hidden: activePart !== 'part3' }">
            <ReadingPart3IELTS015 ref="readingPart3Ref" />
        </div>

        <ReadingFooterIELTS015
            :active-part="activePart"
            :answered-questions="answeredQuestions"
            @navigate="handleNavigate"
            @scroll-to-question="handleScrollToQuestion"
        />

        <!-- Review Modal -->
        <ReadingReviewModalIELTS015
            :is-visible="showReviewModal"
            :user-answers="getAllUserAnswers()"
            :answered-questions="answeredQuestions"
            @close="handleCloseReviewModal"
        />

        <!-- Force Submit Modal (Reload/Back/Tab Switch Protection) -->
        <div
            v-if="showForceSubmitModal"
            class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/70 p-4"
            style="position: fixed; top: 0; left: 0; right: 0; bottom: 0"
        >
            <div class="relative z-[10001] w-full max-w-md rounded-lg bg-white p-4 shadow-2xl sm:p-6" @click.stop>
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100 sm:h-10 sm:w-10">
                        <svg class="h-4 w-4 text-orange-600 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                            ></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 sm:text-lg">Must Submit to Continue</h3>
                </div>
                <p class="mb-6 text-sm text-gray-600 sm:text-base">
                    <span class="font-semibold text-orange-600"> You cannot reload, go back, switch tabs, or minimize during the exam. </span>
                    <br /><br />
                    You must submit your exam answers to proceed.
                </p>
                <button
                    @click="handleForceSubmit"
                    class="w-full rounded-lg bg-orange-600 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-orange-700 sm:px-4 sm:text-sm"
                >
                    Submit Exam Now
                </button>
            </div>
        </div>

        <!-- Submit Modal -->
        <div
            v-if="showSubmitModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4"
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
                    <span v-else> Are you sure you want to submit your reading test? This action cannot be undone. </span>
                </p>
                <div class="flex gap-3">
                    <button
                        v-if="!isTimeUp"
                        @click="handleCancelSubmit"
                        class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 sm:px-4 sm:text-sm"
                    >
                        Continue Test
                    </button>
                    <button
                        @click="handleConfirmSubmit"
                        :class="[
                            'rounded-lg px-3 py-2 text-xs font-medium text-white sm:px-4 sm:text-sm',
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
