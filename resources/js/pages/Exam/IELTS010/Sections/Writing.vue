<script setup lang="ts">
import WritingFooterIELTS010 from '@/components/Exam/WritingIELTS010/WritingFooterIELTS010.vue';
import WritingHeaderIELTS010 from '@/components/Exam/WritingIELTS010/WritingHeaderIELTS010.vue';
import WritingPart1IELTS010 from '@/components/Exam/WritingIELTS010/WritingPart1IELTS010.vue';
import WritingPart2IELTS010 from '@/components/Exam/WritingIELTS010/WritingPart2IELTS010.vue';
import WritingSubmitModalIELTS010 from '@/components/Exam/WritingIELTS010/WritingSubmitModalIELTS010.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';


interface Props {
    slug: string;
    section: string;
    resultId: number;
}

const props = defineProps<Props>();

const activePart = ref<'part1' | 'part2'>('part1');
const part1WordCount = ref(0);
const part2WordCount = ref(0);
const showSubmitModal = ref(false);
const isTimeUp = ref(false);
const showForceSubmitModal = ref(false);
const isLeavingPage = ref(false);
const part1Notes = ref<
    Array<{
        id: number;
        text: string;
        selectedText: string;
        note: string;
        start: number;
        end: number;
    }>
>([]);
const part2Notes = ref<
    Array<{
        id: number;
        text: string;
        selectedText: string;
        note: string;
        start: number;
        end: number;
    }>
>([]);

const writingPart1Ref = ref();
const writingPart2Ref = ref();
const fontSize = ref(16);
const bgColor = ref<'white' | 'gray'>('white');

// Contrast theme state (for content styling)
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

const resultId = ref(props.resultId);

const handleNavigate = (part: 'part1' | 'part2') => {
    activePart.value = part;
};

const handlePart1WordCount = (count: number) => {
    part1WordCount.value = count;
};

const handlePart2WordCount = (count: number) => {
    part2WordCount.value = count;
};

const handlePart1NotesChange = (
    notes: Array<{
        id: number;
        text: string;
        selectedText: string;
        note: string;
        start: number;
        end: number;
    }>,
) => {
    part1Notes.value = notes;
};

const handlePart2NotesChange = (
    notes: Array<{
        id: number;
        text: string;
        selectedText: string;
        note: string;
        start: number;
        end: number;
    }>,
) => {
    part2Notes.value = notes;
};

// ================================
// Notes handlers
// ================================
const handleFocusNote = async (note: { part: string; selectedText: string; start: number; end: number }) => {
    // Switch to correct part
    if (note.part === 'Task 1') activePart.value = 'part1';
    else activePart.value = 'part2';

    // Wait for DOM to update
    await nextTick();

    // Use requestAnimationFrame to ensure DOM is fully rendered (avoids blinking)
    requestAnimationFrame(() => {
        if (note.part === 'Task 1') {
            writingPart1Ref.value?.focusNote({
                selectedText: note.selectedText,
                start: note.start,
                end: note.end,
            });
        } else {
            writingPart2Ref.value?.focusNote({
                selectedText: note.selectedText,
                start: note.start,
                end: note.end,
            });
        }
    });
};

const handleDeleteNote = (data: { id: number; part: string }) => {
    if (data.part === 'Task 1') writingPart1Ref.value?.deleteNote(data.id);
    else writingPart2Ref.value?.deleteNote(data.id);
};

const handleFontSizeChange = (size: number) => {
    fontSize.value = size;
};

const handleBgColorChange = (color: 'white' | 'gray') => {
    bgColor.value = color;
};

const handleShowSubmitModal = () => {
    showSubmitModal.value = true;
};

const handleCancelSubmit = () => {
    // Prevent closing modal when time is up
    if (isTimeUp.value) return;
    showSubmitModal.value = false;
};

const navigateToExamList = () => {
    router.visit(`/exam/${props.slug}`);
};

const handleConfirmSubmit = async () => {
    showSubmitModal.value = false;
    isLeavingPage.value = true;

    // Clear sessionStorage flag before leaving
    sessionStorage.removeItem(`writing_visited_${props.slug}`);

    const part1Answers = writingPart1Ref.value?.getAnswers() || { part1: null };
    const part2Answers = writingPart2Ref.value?.getAnswers() || { part2: null };

    const questionAnswers = [
        {
            q: 'The charts detail the proportion of Australian secondary school graduates who were unemployed, employed or further education in 1980, 1990, and 2000. Write at least 150 words.',
            ans: part1Answers.part1 || '',
        },
        {
            q: 'Nowadays young people spend too much of their free time in shopping malls. Some people fear that this may have negative effects on young people and the society they live in. To what extent do you agree or disagree? \n  Give reasons for your answer and include any relevant examples from your own knowledge or experience. \n Write at least 250 words.',
            ans: part2Answers.part2 || '',
        },
    ];

    await fetch('/api/results/store-section', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            result_id: resultId.value,
            section: 'writing',
            section_data: questionAnswers,
        }),
    });

    navigateToExamList();
};

const handleTimeUp = () => {
    isTimeUp.value = true;
    // Auto-show submit modal when time is up
    showSubmitModal.value = true;
};

// Handle force submit from modal
const handleForceSubmit = async () => {
    showForceSubmitModal.value = false;
    isLeavingPage.value = true;

    // Clear sessionStorage flag before leaving
    sessionStorage.removeItem(`writing_visited_${props.slug}`);

    const part1Answers = writingPart1Ref.value?.getAnswers() || { part1: null };
    const part2Answers = writingPart2Ref.value?.getAnswers() || { part2: null };

    const questionAnswers = [
        {
            q: 'The charts below give information about the way in which water was used in different countries in 2000. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.',
            ans: part1Answers.part1 || '',
        },
        {
            q: 'Young people in the modern world seem to have more power and influence than previous young generations. Why is this the case? What impact does this have on the relationship between old and young people?',
            ans: part2Answers.part2 || '',
        },
    ];

    await fetch('/api/results/store-section', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            result_id: resultId.value,
            section: 'writing',
            section_data: questionAnswers,
        }),
    });

    navigateToExamList();
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

// Setup event listeners
onMounted(() => {
    // Auto-submit on page load (handles reload scenario)
    const hasVisitedWriting = sessionStorage.getItem(`writing_visited_${props.slug}`);

    if (hasVisitedWriting === 'true') {
        // User has reloaded the page - auto submit
        showForceSubmitModal.value = true;
    } else {
        // First visit - mark as visited
        sessionStorage.setItem(`writing_visited_${props.slug}`, 'true');
    }

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
    <Head title="Writing Section - IELTS Mock Test" />

    <div class="min-h-screen" :class="contrastClasses">
        <WritingHeaderIELTS010
            :part1-word-count="part1WordCount"
            :part2-word-count="part2WordCount"
            :is-time-up="isTimeUp"
            :active-part="activePart"
            :part1-notes="part1Notes"
            :part2-notes="part2Notes"
            @show-submit-modal="handleShowSubmitModal"
            @time-up="handleTimeUp"
            @font-size-change="handleFontSizeChange"
            @bg-color-change="handleBgColorChange"
            @contrast-change="handleContrastChange"
            @focus-note="handleFocusNote"
            @delete-note="handleDeleteNote"
        />

        <!-- Keep both components mounted but show/hide them -->
        <div :class="{ hidden: activePart !== 'part1' }" class="w-full">
            <WritingPart1IELTS010
                ref="writingPart1Ref"
                :font-size="fontSize"
                @word-count-change="handlePart1WordCount"
                @notes-change="handlePart1NotesChange"
            />
        </div>
        <div :class="{ hidden: activePart !== 'part2' }" class="w-full">
            <WritingPart2IELTS010
                ref="writingPart2Ref"
                :font-size="fontSize"
                @word-count-change="handlePart2WordCount"
                @notes-change="handlePart2NotesChange"
            />
        </div>

        <!-- Pass function to footer -->
        <WritingFooterIELTS010
            :active-part="activePart"
            @navigate="handleNavigate"
            @exit="handleShowSubmitModal"
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
        <WritingSubmitModalIELTS010
            :is-visible="showSubmitModal"
            :is-time-up="isTimeUp"
            @close="handleCancelSubmit"
            @submit="handleConfirmSubmit"
        />
    </div>
</template>
