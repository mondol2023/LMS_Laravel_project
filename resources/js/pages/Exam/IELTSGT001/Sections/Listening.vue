<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';


import ListeningGT001Footer from '@/components/Exam/ListeningGT001/ListeningGT001Footer.vue';
import ListeningGT001Header from '@/components/Exam/ListeningGT001/ListeningGT001Header.vue';
import ListeningGT001Part1 from '@/components/Exam/ListeningGT001/ListeningGT001Part1.vue';
import ListeningGT001Part2 from '@/components/Exam/ListeningGT001/ListeningGT001Part2.vue';
import ListeningGT001Part3 from '@/components/Exam/ListeningGT001/ListeningGT001Part3.vue';
import ListeningGT001Part4 from '@/components/Exam/ListeningGT001/ListeningGT001Part4.vue';
import ListeningReviewModal from '@/components/Exam/ListeningGT001/ListeningReviewModal.vue';
import ListeningSubmitModal from '@/components/Exam/ListeningGT001/ListeningSubmitModalIELTS001.vue';

interface Props {
    slug: string;
    section: string;
    resultId: number;
}

const props = defineProps<Props>();

// ================================
// Intro screen state
// ================================
const examStarted = ref(false);
const introAudioRef = ref<HTMLAudioElement | null>(null);

// ================================
// Component state & refs
// ================================
const activePart = ref<'part1' | 'part2' | 'part3' | 'part4'>('part1');
const showSubmitModal = ref(false);
const showReviewModal = ref(false);
const answeredQuestions = ref<Set<number>>(new Set());
const flaggedQuestions = ref<Set<number>>(new Set());
const isTimeUp = ref(false);
const showForceSubmitModal = ref(false);
const isLeavingPage = ref(false);
const selectedQuestion = ref<number | null>(null);

const listeningPart1Ref = ref<any>();
const listeningPart2Ref = ref<any>();
const listeningPart3Ref = ref<any>();
const listeningPart4Ref = ref<any>();

const resultId = ref(props.resultId);

// ================================
// Intro screen handlers
// ================================
const playIntroAudio = () => {
    if (introAudioRef.value) {
        introAudioRef.value.play().catch(() => {
            // If audio fails to play, that's okay - user can still click Play
        });
    }
};

const handleStartExam = () => {
    // Stop intro audio if still playing
    if (introAudioRef.value) {
        introAudioRef.value.pause();
        introAudioRef.value.currentTime = 0;
    }
    examStarted.value = true;
};

// Font size state
const fontSize = ref(16);
const handleFontSizeChange = (size: number) => {
    fontSize.value = size;
};

// Audio volume state (shared between header and footer)
const audioVolume = ref(100);
const handleVolumeChange = (volume: number) => {
    audioVolume.value = volume;
};

// Background color state
const bgColor = ref<'white' | 'gray'>('white');
const handleBgColorChange = (color: 'white' | 'gray') => {
    bgColor.value = color;
};

// Contrast theme state (for Part 1-4 content only)
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

// Track notes from all parts
const part1Notes = computed(() => listeningPart1Ref.value?.notes || []);
const part2Notes = computed(() => listeningPart2Ref.value?.notes || []);
const part3Notes = computed(() => listeningPart3Ref.value?.notes || []);
const part4Notes = computed(() => listeningPart4Ref.value?.notes || []);

// ================================
// Correct answers
// ================================
const correctAnswers: Record<number, string> = {
    // Section 1: Form completion (Q1-6), Multiple choice (Q7-8), Short answer (Q9-10)
    1: 'Lynda',
    2: 'Unit 15/Unit fifteen',
    3: '5577',
    4: 'night shift',
    5: 'swipe card',
    6: 'September 1975/Sept 1975',
    7: 'A/C',
    8: 'A/C',
    9: 'fifteen dollars/$15/15 dollars',
    10: '60 minutes/sixty minutes/one hour/1 hour',

    // Section 2: MCQ (Q11-15), Table completion (Q16-20)
    11: 'B',
    12: 'A',
    13: 'A',
    14: 'B',
    15: 'C',
    16: '2 July/2nd July/July 2/July 2nd',
    17: '9.15 a.m./9:15 a.m./9.15/9:15/nine fifteen a.m.',
    18: 'president',
    19: 'observation hut',
    20: '2 p.m./2:00 p.m./2 pm/14:00/two p.m.',

    // Section 3: Sentence completion (Q21-25), MCQ (Q26-28), Multiple choice (Q29-30)
    21: 'safety procedures/directions',
    22: 'eye protection/safety glasses/goggles',
    23: 'clean-up/clean up/cleanup',
    24: 'naked flame',
    25: 'leather',
    26: 'C',
    27: 'B',
    28: 'C',
    29: 'E/C',
    30: 'E/C',

    // Section 4: Note completion (Q31-40)
    31: 'fuel',
    32: 'combustion engine',
    33: '145%/145 per cent/145 percent',
    34: 'agriculture',
    35: 'barrier/a barrier',
    36: '46 million',
    37: '118 million',
    38: 'vegetation zones',
    39: 'disease/diseases',
    40: 'species composition',
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
            correct++;
        }
    }
    return { correct, incorrect: 40 - correct };
};

// ================================
// Navigation
// ================================
const handleNavigate = (part: 'part1' | 'part2' | 'part3' | 'part4') => (activePart.value = part);

const partsList: ('part1' | 'part2' | 'part3' | 'part4')[] = ['part1', 'part2', 'part3', 'part4'];
const partLabels: Record<string, string> = { part1: 'Part 1', part2: 'Part 2', part3: 'Part 3', part4: 'Part 4' };
const currentPartIndex = computed(() => partsList.indexOf(activePart.value));
const hasPreviousPart = computed(() => currentPartIndex.value > 0);
const hasNextPart = computed(() => currentPartIndex.value < partsList.length - 1);
const previousPartLabel = computed(() => (hasPreviousPart.value ? partLabels[partsList[currentPartIndex.value - 1]] : ''));
const nextPartLabel = computed(() => (hasNextPart.value ? partLabels[partsList[currentPartIndex.value + 1]] : ''));

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
        // First, try to find if there's already a highlight for this text
        const marks = document.querySelectorAll('mark[data-highlight-id]');
        let foundMark: Element | null = null;

        for (const mark of marks) {
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
            // If no highlight exists, find the text in the content and add a temporary highlight
            // Search through ALL elements with select-text class
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
                        // Found the text - create a temporary highlight
                        const range = document.createRange();
                        range.setStart(node, index);
                        range.setEnd(node, index + note.selectedText.length);

                        const tempMark = document.createElement('mark');
                        tempMark.className = 'bg-blue-200 ring-4 ring-blue-400 ring-offset-2 transition-all px-1 rounded';

                        try {
                            range.surroundContents(tempMark);

                            // Scroll to the temporary highlight
                            tempMark.scrollIntoView({ behavior: 'smooth', block: 'center' });

                            // Remove the temporary highlight after 2.5 seconds
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
                            // If surroundContents fails (e.g., spans multiple elements), try a different approach
                            continue;
                        }
                    }
                }
            }
        }
    }, 100);
};

// ================================
// Delete Note
// ================================
const handleDeleteNote = (data: { id: number; part: string }) => {
    // Delegate to the appropriate part component
    if (data.part === 'Part 1') listeningPart1Ref.value?.deleteNote(data.id);
    else if (data.part === 'Part 2') listeningPart2Ref.value?.deleteNote(data.id);
    else if (data.part === 'Part 3') listeningPart3Ref.value?.deleteNote(data.id);
    else listeningPart4Ref.value?.deleteNote(data.id);
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
// Modal
// ================================
const handleShowSubmitModal = () => (showSubmitModal.value = true);
const handleCancelSubmit = () => {
    // Prevent closing modal when time is up
    if (isTimeUp.value) return;
    showSubmitModal.value = false;
};
const handleShowReviewModal = () => {
    if (isTimeUp.value) return; // Prevent opening review modal when time is up
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
    sessionStorage.removeItem(`listening_visited_${props.slug}`);

    try {
        const allAnswers = {
            ...listeningPart1Ref.value?.getAnswers?.(),
            ...listeningPart2Ref.value?.getAnswers?.(),
            ...listeningPart3Ref.value?.getAnswers?.(),
            ...listeningPart4Ref.value?.getAnswers?.(),
        };
        const { correct, incorrect } = compareAnswers(allAnswers);
        const bandScore = calculateBandScore(correct);

        await fetch('/api/results/store-section', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                result_id: resultId.value,
                section: 'listening',
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

const handleForceSubmit = async () => {
    showForceSubmitModal.value = false;
    isLeavingPage.value = true;

    // Clear sessionStorage flag before leaving
    sessionStorage.removeItem(`listening_visited_${props.slug}`);

    try {
        const allAnswers = {
            ...listeningPart1Ref.value?.getAnswers?.(),
            ...listeningPart2Ref.value?.getAnswers?.(),
            ...listeningPart3Ref.value?.getAnswers?.(),
            ...listeningPart4Ref.value?.getAnswers?.(),
        };
        const { correct, incorrect } = compareAnswers(allAnswers);
        const bandScore = calculateBandScore(correct);

        await fetch('/api/results/store-section', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                result_id: resultId.value,
                section: 'listening',
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
    if (!isLeavingPage.value && examStarted.value) {
        event.preventDefault();
        event.returnValue = '';
        return event.returnValue;
    }
};

// Detect tab switching or window blur/minimize
const handleVisibilityChange = () => {
    if (document.hidden && !isLeavingPage.value && examStarted.value) {
        // User switched tabs, minimized, or opened new tab (only after exam started)
        showForceSubmitModal.value = true;
    }
};

// Prevent back button navigation
const preventBackNavigation = () => {
    if (!isLeavingPage.value && examStarted.value) {
        window.history.pushState(null, '', window.location.href);
        showForceSubmitModal.value = true;
    }
};

// Detect mouse back/forward buttons
const handleMouseButton = (event: MouseEvent) => {
    if (isLeavingPage.value || !examStarted.value) return;

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
    if (!isLeavingPage.value && examStarted.value) {
        event.preventDefault();
        return false;
    }
};

// Watch for exam start to set session storage
watch(examStarted, (started) => {
    if (started) {
        // Check if user has already visited (handles reload scenario)
        const hasVisitedListening = sessionStorage.getItem(`listening_visited_${props.slug}`);
        if (hasVisitedListening === 'true') {
            // User has reloaded the page - auto submit
            showForceSubmitModal.value = true;
        } else {
            // First visit - mark as visited
            sessionStorage.setItem(`listening_visited_${props.slug}`, 'true');
        }
    }
});

// Setup event listeners
onMounted(() => {
    // Auto-play intro audio when page loads
    setTimeout(() => {
        playIntroAudio();
    }, 500);

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

    // Push the initial state to prevent back navigation
    window.history.pushState(null, '', window.location.href);
});

// Cleanup event listeners
onBeforeUnmount(() => {
    // Clear answer check an interval
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
    <Head title="Listening Section - IELTS Mock Test" />

    <div class="relative min-h-screen text-black" :class="bgColor === 'gray' ? 'bg-gray-100' : 'bg-white'">
        <ListeningGT001Header
            :is-time-up="isTimeUp"
            :audio-volume="audioVolume"
            :exam-started="examStarted"
            :part1-notes="part1Notes"
            :part2-notes="part2Notes"
            :part3-notes="part3Notes"
            :part4-notes="part4Notes"
            @show-submit-modal="handleShowSubmitModal"
            @show-review-modal="handleShowReviewModal"
            @time-up="handleTimeUp"
            @focus-note="handleFocusNote"
            @delete-note="handleDeleteNote"
            @font-size-change="handleFontSizeChange"
            @bg-color-change="handleBgColorChange"
            @contrast-change="handleContrastChange"
        />
        <!-- Parts Container with Contrast Theme -->
        <div :class="[contrastClasses, 'parts-container min-h-screen transition-colors duration-300']" :data-theme="contrastTheme">
            <div :class="{ hidden: activePart !== 'part1' }">
                <ListeningGT001Part1 ref="listeningPart1Ref" :font-size="fontSize" :flagged-questions="flaggedQuestions" @toggle-flag="handleToggleFlag" />
            </div>
            <div :class="{ hidden: activePart !== 'part2' }">
                <ListeningGT001Part2 ref="listeningPart2Ref" :font-size="fontSize" :flagged-questions="flaggedQuestions" @toggle-flag="handleToggleFlag" />
            </div>
            <div :class="{ hidden: activePart !== 'part3' }">
                <ListeningGT001Part3 ref="listeningPart3Ref" :font-size="fontSize" :flagged-questions="flaggedQuestions" @toggle-flag="handleToggleFlag" />
            </div>
            <div :class="{ hidden: activePart !== 'part4' }">
                <ListeningGT001Part4 ref="listeningPart4Ref" :font-size="fontSize" :flagged-questions="flaggedQuestions" @toggle-flag="handleToggleFlag" />
            </div>
        </div>

        <!-- Previous / Next Part Navigation -->
        <div class="fixed right-10 bottom-40 z-40 flex gap-1">
            <button
                @click="goToPreviousPart"
                :disabled="!hasPreviousPart"
                class="flex h-10 w-11 items-center justify-center rounded transition-all duration-200"
                :class="
                    hasPreviousPart
                        ? 'bg-gray-600 font-extrabold text-white hover:bg-gray-700'
                        : 'cursor-not-allowed bg-gray-400 font-extrabold text-gray-200'
                "
                :title="hasPreviousPart ? previousPartLabel : ''"
            >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
            </button>
            <button
                @click="goToNextPart"
                :disabled="!hasNextPart"
                class="flex h-10 w-11 items-center justify-center rounded transition-all duration-200"
                :class="hasNextPart ? 'bg-gray-800 text-white hover:bg-gray-900' : 'cursor-not-allowed bg-gray-400 text-gray-200'"
                :title="hasNextPart ? nextPartLabel : ''"
            >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8z" />
                </svg>
            </button>
        </div>

        <ListeningGT001Footer
            :active-part="activePart"
            :answered-questions="answeredQuestions"
            :flagged-questions="flaggedQuestions"
            :selected-question="selectedQuestion"
            :volume="audioVolume"
            @navigate="handleNavigate"
            @scroll-to-question="handleScrollToQuestion"
            @exit="handleShowSubmitModal"
            @volume-change="handleVolumeChange"
        />

        <!-- Review Modal -->
        <ListeningReviewModal
            :is-visible="showReviewModal"
            :user-answers="getAllUserAnswers()"
            :answered-questions="answeredQuestions"
            @close="handleCloseReviewModal"
        />

        <!-- Force Submit Modal (Reload/Back/Tab Switch Protection) -->
        <Teleport to="body">
            <div
                v-if="showForceSubmitModal"
                class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-md shadow rounded border border-black bg-white p-6 text-center">
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
        <ListeningSubmitModal
            :is-visible="showSubmitModal"
            :answered-count="answeredQuestions.size"
            :total-questions="40"
            :is-time-up="isTimeUp"
            @close="handleCancelSubmit"
            @submit="handleConfirmSubmit"
        />

        <!-- Intro Overlay Screen -->
        <div
            v-if="!examStarted"
            class="fixed inset-0 z-[11000] flex items-center justify-center bg-black/20 backdrop-blur-[3px]"
        >
            <!-- Hidden Intro Audio Element (loops until user clicks Play) -->
            <audio
                ref="introAudioRef"
                src="/audio/StartListening.mpeg"
                preload="auto"
                loop
            />

            <div class="flex flex-col items-center justify-center px-4 text-center">
                <!-- Headphone Icon -->
                <div class="mb-4 text-6xl sm:text-7xl" style="filter: grayscale(100%) brightness(200%);">
                    🎧
                </div>

                <!-- Text Content -->
                <p class="mb-4 max-w-5xl font-bold text-sm text-white sm:text-base">
                    You will be listening to an audio clip during this test. You will not be permitted to pause or rewind the audio while answering the questions.
                </p>

                <p class="mb-8 font-bold text-sm text-white sm:text-base">
                    To continue, click Play.
                </p>

                <!-- Play Button -->
                <button
                    @click="handleStartExam"
                    class="flex items-center gap-3 rounded bg-black px-6 py-3 text-white transition-all duration-200 hover:bg-gray-800 hover:scale-105 active:scale-95"
                >
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-white">
                        <svg class="ml-0.5 h-5 w-5 text-black" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                    </div>
                    <span class="text-base font-medium">Play</span>
                </button>
            </div>
        </div>
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
