<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

import WritingFooter from '@/components/Exam/Writing/WritingFooter.vue';
import WritingHeaderIELTS011 from '@/components/Exam/WritingIELTS011/WritingHeader011.vue';
import WritingPart1IELTS011 from '@/components/Exam/WritingIELTS011/WritingPart1IELTS011.vue';
import WritingPart2IELTS011 from '@/components/Exam/WritingIELTS011/WritingPart2IELTS011.vue';

interface Props {
    slug: string;
    section: string;
    userPhone?: string;
    examId?: string;
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

const userPhone = ref(props.userPhone || localStorage.getItem('userPhone'));
const examId = computed(() => props.examId || props.slug);

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

    await nextTick();

    // Call focusNote method on the appropriate child component
    setTimeout(() => {
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
    }, 100);
};

const handleDeleteNote = (data: { id: number; part: string }) => {
    if (data.part === 'Task 1') writingPart1Ref.value?.deleteNote(data.id);
    else writingPart2Ref.value?.deleteNote(data.id);
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

    await fetch('/api/drafts/save-results', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            phone: userPhone.value,
            exam_id: examId.value,
            section: 'writing',
            score_data: questionAnswers,
        }),
    });

    await fetch('/api/drafts/cleanup', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone: userPhone.value, exam_id: examId.value, section: 'writing' }),
    });

    // Clean up sessionStorage
    sessionStorage.removeItem(`writing_visited_${examId.value}`);

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

    await fetch('/api/drafts/save-results', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            phone: userPhone.value,
            exam_id: examId.value,
            section: 'writing',
            score_data: questionAnswers,
        }),
    });

    await fetch('/api/drafts/cleanup', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone: userPhone.value, exam_id: examId.value, section: 'writing' }),
    });

    // Clean up sessionStorage
    sessionStorage.removeItem(`writing_visited_${examId.value}`);

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
    const hasVisitedWriting = sessionStorage.getItem(`writing_visited_${examId.value}`);

    if (hasVisitedWriting === 'true') {
        // User has reloaded the page - auto submit
        showForceSubmitModal.value = true;
    } else {
        // First visit - mark as visited
        sessionStorage.setItem(`writing_visited_${examId.value}`, 'true');
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

    // Store interval ID for cleanup
    (window as any).__historyInterval = setInterval(() => {
        if (!isLeavingPage.value) {
            window.history.pushState(null, '', window.location.href);
        }
    }, 100);
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

    // Clear interval
    if ((window as any).__historyInterval) {
        clearInterval((window as any).__historyInterval);
    }
});
</script>

<template>
    <Head title="Writing Section - Nextive Solution" />

    <div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-pink-50 text-black">
        <WritingHeaderIELTS011
            :part1-word-count="part1WordCount"
            :part2-word-count="part2WordCount"
            :is-time-up="isTimeUp"
            :part1-notes="part1Notes"
            :part2-notes="part2Notes"
            :active-part="activePart"
            @show-submit-modal="handleShowSubmitModal"
            @time-up="handleTimeUp"
            @focus-note="handleFocusNote"
            @delete-note="handleDeleteNote"
        />

        <!-- Keep both components mounted but show/hide them -->
        <div :class="{ hidden: activePart !== 'part1' }" class="w-full">
            <WritingPart1IELTS011
                ref="writingPart1Ref"
                :phone="userPhone"
                :exam-id="examId"
                @word-count-change="handlePart1WordCount"
                @notes-change="handlePart1NotesChange"
            />
        </div>
        <div :class="{ hidden: activePart !== 'part2' }" class="w-full">
            <WritingPart2IELTS011
                ref="writingPart2Ref"
                :phone="userPhone"
                :exam-id="examId"
                @word-count-change="handlePart2WordCount"
                @notes-change="handlePart2NotesChange"
            />
        </div>

        <!-- Pass function to footer -->
        <WritingFooter
            :active-part="activePart"
            :part1-word-count="part1WordCount"
            :part2-word-count="part2WordCount"
            @navigate="handleNavigate"
            @exit="handleShowSubmitModal"
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

        <!-- Submit Confirmation Modal -->
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
                            ></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 sm:text-lg">
                        {{ isTimeUp ? 'Time is Up!' : 'Submit Test' }}
                    </h3>
                </div>
                <p class="mb-6 text-sm text-gray-600 sm:text-base">
                    <span v-if="isTimeUp" class="font-semibold text-orange-600"> Your time has expired. You must submit your test now. </span>
                    <span v-else> Are you sure you want to submit your writing test? This action cannot be undone. </span>
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
