<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { useTooltip, type Note, type TextSegment } from '@/composables/useTooltip';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import HighlightTooltips from './HighlightTooltips.vue';

interface Props {
    fontSize?: number;
    flaggedQuestions?: Set<number>;
}

const props = withDefaults(defineProps<Props>(), {
    fontSize: 16,
    flaggedQuestions: () => new Set<number>(),
});

const emit = defineEmits<{
    toggleFlag: [questionNumber: number];
}>();

// Track hovered question for showing flag icon
const hoveredQuestion = ref<number | null>(null);

// Check if a question is flagged
const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

// Toggle flag for a question
const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));


// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const notes = ref<Note[]>([]);
// Listening Part 3 component

// Single choice answers for questions 21-24
const answers = ref<Record<string, string>>({
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
    q27: '',
    q28: '',

});

// Multiple choice answers for questions 25-30
const multipleAnswers = ref<{  q29_30: string[] }>({

    q29_30: [],
});



// Text segments with their cumulative offsets (each offset = previous offset + previous text length + 1)
const textSegments = ref([
{ id: 0, text: 'Part 3', offset: 0 },
{ id: 1, text: 'Questions 21-25', offset: 10 },
{ id: 2, text: 'Complete the sentences below.', offset: 26 },
{ id: 3, text: 'Write NO MORE THAN TWO WORDS for each answer.', offset: 56 },
{ id: 4, text: '', offset: 102 }, // Removed title
{ id: 5, text: 'Students must follow', offset: 103 },
{ id: 6, text: 'to prevent accidents in the lab.', offset: 128 },
{ id: 7, text: 'The students have not been using', offset: 161 },
{ id: 8, text: 'while in the lab.', offset: 198 },
{ id: 9, text: 'Students cannot eat or drink until', offset: 216 },
{ id: 10, text: 'is finished and they have washed their hands.', offset: 255 },
{ id: 11, text: 'Tessa should tie her hair back to avoid danger when she is working with a', offset: 301 },
{ id: 12, text: 'or chemicals.', offset: 379 },
{ id: 13, text: 'Students must wear long sleeves and shoes made of', offset: 393 },
{ id: 14, text: 'in the lab.', offset: 446 },
{ id: 15, text: 'Questions 26-28', offset: 458 },
{ id: 16, text: 'Choose the correct letter, A, B or C.', offset: 474 },
{ id: 17, text: '26. Which student is currently using an appropriate notebook?', offset: 512 },
{ id: 18, text: 'A. Vincent', offset: 574 },
{ id: 19, text: 'B. Tessa', offset: 585 },
{ id: 20, text: 'C. Neither student', offset: 594 },
{ id: 21, text: '27. The tutor says that writing observations in complete sentences', offset: 613 },
{ id: 22, text: 'A. is often not a good use of time', offset: 680 },
{ id: 23, text: 'B. makes them easier to interpret later', offset: 715 },
{ id: 24, text: 'C. means that others can understand them', offset: 755 },
{ id: 25, text: '28. The students must write dates', offset: 796 },
{ id: 26, text: 'A. next to each drawing', offset: 830 },
{ id: 27, text: 'B. next to each written section', offset: 854 },
{ id: 28, text: 'C. next to each drawing and written section', offset: 886 },
{ id: 29, text: 'Questions 29-30', offset: 930 },
{ id: 30, text: 'Choose TWO letters, A-E.', offset: 946 },
{ id: 31, text: 'Which TWO things must be included in the conclusion to the experiment?', offset: 971 },
{ id: 32, text: 'A. the questions investigated', offset: 1042 },
{ id: 33, text: 'B. the solutions to the questions', offset: 1072 },
{ id: 34, text: "C. the student's own thoughts about the experiment", offset: 1106 },
{ id: 35, text: 'D. the length of time spent on the experiment', offset: 1157 },
{ id: 36, text: "E. the student's signature", offset: 1203 },
]);


// Initialize tooltip composable
const {
    contextMenuPosition,
    showContextMenu,
    showDeleteTooltip,
    deleteTooltipPosition,
    highlightToDelete,
    closeDeleteTooltip,
    showNoteTooltip,
    noteTooltipPosition,
    hoveredNoteId,
    hoveredNoteText,
    handleTooltipMouseLeave,
    showNoteInput,
    noteInputText,
    noteInputPosition,
    openNoteInput,
    cancelNote,
    selectedText,
    selectionRange,
    handleContentTextSelect,
    handleContentClick,
    setupEventListeners,
    cleanupEventListeners,
} = useTooltip(textSegments, notes);

// Helper to get a highlighted version of a specific text segment by ID
const getHighlightedSegmentById = (segmentId: number | string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + plainText.length;

    // Check if any highlights overlap with this segment
    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    // Also check notes that overlap with this segment
    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return plainText;
    }

    // Combine highlights and notes into a single list of annotations
    const annotations = [
        ...overlappingHighlights.map((h) => ({
            start: h.start_offset,
            end: h.end_offset,
            type: 'highlight' as const,
            color: h.color,
            id: h.id,
        })),
        ...overlappingNotes.map((n) => ({
            start: n.start,
            end: n.end,
            type: 'note' as const,
            color: 'blue',
            id: n.id,
            noteText: n.note,
        })),
    ];

    // Sort by start offset descending so we can apply from end to start
    const sorted = annotations.sort((a, b) => b.start - a.start);

    let result = plainText;
    for (const annotation of sorted) {
        const annotationStart = Math.max(0, annotation.start - segmentOffset);
        const annotationEnd = Math.min(plainText.length, annotation.end - segmentOffset);

        if (annotationStart < annotationEnd) {
            const before = result.substring(0, annotationStart);
            const annotated = result.substring(annotationStart, annotationEnd);
            const after = result.substring(annotationEnd);

            if (annotation.type === 'note') {
                result = `${before}<mark class="highlight-${annotation.color} note-highlight" data-note-id="${annotation.id}">${annotated}</mark>${after}`;
            } else {
                result = `${before}<mark class="highlight-${annotation.color}" data-highlight-id="${annotation.id}">${annotated}</mark>${after}`;
            }
        }
    }

    return result;
};

// Expose methods for parent component
const getAnswers = () => {
    const allAnswers = { ...answers.value };

    // Handle multiple choice questions
    if (multipleAnswers.value.q29_30?.length > 0) {
        allAnswers.q29 = multipleAnswers.value.q29_30[0] || '';
        allAnswers.q30 = multipleAnswers.value.q29_30[1] || '';
    } else {
        allAnswers.q29 = '';
        allAnswers.q30 = '';
    }

    return allAnswers;
};

const handleMultipleChoice = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    const index = currentAnswers.indexOf(option);

    if (index > -1) {
        // Remove if already selected
        currentAnswers.splice(index, 1);
    } else if (currentAnswers.length < 2) {
        // Add if less than 2 selected
        currentAnswers.push(option);
    }
};

const isSelected = (questionGroup: string, option: string) => {
    return multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value].includes(option);
};

const isDisabled = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    // Disable if 2 already selected AND this option is not one of them
    return currentAnswers.length >= 2 && !currentAnswers.includes(option);
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;

    // Remove overlapping notes (last action wins - highlight overwrites note)
    notes.value = notes.value.filter((n) => {
        return !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start);
    });

    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);

    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;

    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;

    // Remove overlapping highlights (last action wins - note overwrites highlight)
    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

    // Remove any existing notes that overlap with this range
    notes.value = notes.value.filter((n) => {
        const overlaps = n.start < newEnd && n.end > newStart;
        return !overlaps;
    });

    const noteId = Date.now();
    notes.value.push({
        id: noteId,
        text: selectedText.value,
        selectedText: selectedText.value,
        note: noteInputText.value.trim(),
        start: newStart,
        end: newEnd,
    });

    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const deleteNote = (id: number) => {
    notes.value = notes.value.filter((note) => note.id !== id);
};

// Confirm delete highlight (called from tooltip)
const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        closeDeleteTooltip();
    }
};

// Delete note from hover tooltip
const deleteNoteFromTooltip = () => {
    if (hoveredNoteId.value !== null) {
        deleteNote(hoveredNoteId.value);
        handleTooltipMouseLeave();
    }
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    // Handle grouped questions for 25-26, 27-28 and 29-30
    let targetId = `question-${questionNumber}`;
    if (questionNumber === 25 || questionNumber === 26) {
        targetId = 'question-25-26';
    } else if (questionNumber === 27 || questionNumber === 28) {
        targetId = 'question-27-28';
    } else if (questionNumber === 29 || questionNumber === 30) {
        targetId = 'question-29-30';
    }

    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
        }, 2000);
    }
};

// Setup event listeners when component mounts
onMounted(() => {
    setupEventListeners();
});

onBeforeUnmount(() => {
    cleanupEventListeners();
});

defineExpose({
    getAnswers,
    scrollToQuestion,
    notes,
    deleteNote,
});
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex w-full flex-col" :style="contentZoom">

            <!-- Part Header Box -->
            <div class="mb-3 part-header-box rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="0" v-html="getHighlightedSegmentById(0)"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></p>
            </div>

            <!-- Instructions -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></p>
            </div>

            <!-- Scrollable Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <div class="space-y-2">

                        <!-- Questions 21-25: Sentence Completion -->
                        <div class="space-y-4 p-2 sm:p-3">

                            <!-- Question 21 -->
                            <div
                                id="question-21"
                                class="relative flex flex-wrap items-center gap-2 p-2 sm:p-3"
                                @mouseenter="hoveredQuestion = 21"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <span class="text-base text-gray-900 select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                                <input
                                    v-model="answers.q21"
                                    type="text"
                                    spellcheck="false"
                                    autocomplete="off"
                                    class="min-w-[140px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none"
                                    placeholder="21"
                                    @focus="hoveredQuestion = 21"
                                    @blur="hoveredQuestion = null"
                                />
                                <span class="text-base text-gray-900 select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                <button
                                    v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                    @click.stop="toggleFlag(21)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 22 -->
                            <div
                                id="question-22"
                                class="relative flex flex-wrap items-center gap-2 p-2 sm:p-3"
                                @mouseenter="hoveredQuestion = 22"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <span class="text-base text-gray-900 select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                                <input
                                    v-model="answers.q22"
                                    type="text"
                                    spellcheck="false"
                                    autocomplete="off"
                                    class="min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none"
                                    placeholder="22"
                                    @focus="hoveredQuestion = 22"
                                    @blur="hoveredQuestion = null"
                                />
                                <span class="text-base text-gray-900 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                <button
                                    v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                    @click.stop="toggleFlag(22)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 23 -->
                            <div
                                id="question-23"
                                class="relative flex flex-wrap items-center gap-2 p-2 sm:p-3"
                                @mouseenter="hoveredQuestion = 23"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <span class="text-base text-gray-900 select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                <input
                                    v-model="answers.q23"
                                    type="text"
                                    spellcheck="false"
                                    autocomplete="off"
                                    class="min-w-[100px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none"
                                    placeholder="23"
                                    @focus="hoveredQuestion = 23"
                                    @blur="hoveredQuestion = null"
                                />
                                <span class="text-base text-gray-900 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                <button
                                    v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                    @click.stop="toggleFlag(23)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 24 -->
                            <div
                                id="question-24"
                                class="relative flex flex-wrap items-center gap-2 p-2 sm:p-3"
                                @mouseenter="hoveredQuestion = 24"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <span class="text-base text-gray-900 select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                <input
                                    v-model="answers.q24"
                                    type="text"
                                    spellcheck="false"
                                    autocomplete="off"
                                    class="min-w-[120px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none"
                                    placeholder="24"
                                    @focus="hoveredQuestion = 24"
                                    @blur="hoveredQuestion = null"
                                />
                                <span class="text-base text-gray-900 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                <button
                                    v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                    @click.stop="toggleFlag(24)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 25 -->
                            <div
                                id="question-25"
                                class="relative flex flex-wrap items-center gap-2 p-2 sm:p-3"
                                @mouseenter="hoveredQuestion = 25"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <span class="text-base text-gray-900 select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                <input
                                    v-model="answers.q25"
                                    type="text"
                                    spellcheck="false"
                                    autocomplete="off"
                                    class="min-w-[100px] border border-gray-900 px-2 py-1 text-center text-base focus:border-black focus:outline-none"
                                    placeholder="25"
                                    @focus="hoveredQuestion = 25"
                                    @blur="hoveredQuestion = null"
                                />
                                <span class="text-base text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                <button
                                    v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                    @click.stop="toggleFlag(25)"
                                    class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Questions 26-28: Single Choice MCQ -->
                        <!-- Section sub-header -->
                        <div class="mt-4 rounded border border-gray-200 bg-gray-100 px-4 py-3">
                            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></p>
                        </div>

                        <!-- Question 26 -->
                        <div
                            id="question-26"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 26"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                        @click.stop="toggleFlag(26)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q26" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q26" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q26" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 27 -->
                        <div
                            id="question-27"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 27"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                        @click.stop="toggleFlag(27)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q27" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q27" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q27" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 28 -->
                        <div
                            id="question-28"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 28"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                        @click.stop="toggleFlag(28)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q28" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q28" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q28" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 29-30: Choose TWO -->
                        <div
                            id="question-29-30"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 29"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 29 || isQuestionFlagged(29) || isQuestionFlagged(30)"
                                @click.stop="toggleFlag(29); toggleFlag(30);"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(29) || isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(29) || isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                </svg>
                            </button>

                            <div class="mb-2">
                                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></h3>
                            </div>
                            <div class="p-2">
                                <p class="mb-2 text-base leading-relaxed text-gray-900 select-text" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></p>
                                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></p>
                            </div>

                            <div class="space-y-1">
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q29_30', 'A')"
                                        :disabled="isDisabled('q29_30', 'A')"
                                        @change="handleMultipleChoice('q29_30', 'A')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="32" v-html="getHighlightedSegmentById(32)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q29_30', 'B')"
                                        :disabled="isDisabled('q29_30', 'B')"
                                        @change="handleMultipleChoice('q29_30', 'B')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="33" v-html="getHighlightedSegmentById(33)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q29_30', 'C')"
                                        :disabled="isDisabled('q29_30', 'C')"
                                        @change="handleMultipleChoice('q29_30', 'C')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="34" v-html="getHighlightedSegmentById(34)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q29_30', 'D')"
                                        :disabled="isDisabled('q29_30', 'D')"
                                        @change="handleMultipleChoice('q29_30', 'D')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="35" v-html="getHighlightedSegmentById(35)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected('q29_30', 'E')"
                                        :disabled="isDisabled('q29_30', 'E')"
                                        @change="handleMultipleChoice('q29_30', 'E')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="36" v-html="getHighlightedSegmentById(36)"></span>
                                </label>
                            </div>

                            <div class="mt-2 p-2">
                                <p class="text-sm font-medium text-gray-900">Selected: {{ multipleAnswers.q29_30.length }}/2 answers</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inline Teleport for Highlight Tooltips -->
    <Teleport to="body">
        <!-- Highlight Context Menu -->
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div
                class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')" class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <!-- Delete Highlight Tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
            <div
                class="delete-highlight-tooltip fixed z-[9999]"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
            >
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                        <span class="mt-1.5 text-xs leading-tight font-medium text-gray-600">Delete</span>
                        <span class="text-xs leading-tight font-medium text-gray-600">Highlight</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Note Hover Tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div
                class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave"
                @click.stop
            >
                <div class="note-tooltip-content flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </span>
                    <span class="max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note Input Modal -->
        <div
            v-if="showNoteInput"
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop
        >
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                <input
                    v-model="noteInputText"
                    type="text"
                    spellcheck="false"
                    autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote"
                    @keyup.escape="cancelNote"
                />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.highlight-question {
    background-color: rgba(59, 130, 246, 0.15);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(59, 130, 246, 0.15);
        transform: scale(1);
    }
    50% {
        background-color: rgba(59, 130, 246, 0.3);
        transform: scale(1.05);
    }
    100% {
        background-color: rgba(59, 130, 246, 0.15);
        transform: scale(1);
    }
}

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #000000;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #374151;
}

.select-text {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

mark[data-highlight-id] {
    padding: 2px 0;
    border-radius: 2px;
    cursor: pointer;
}

.highlight-nocolor {
    background-color: transparent;
}

.highlight-yellow {
    background-color: rgba(254, 240, 138, 0.5);
}

.highlight-green {
    background-color: rgba(187, 247, 208, 0.5);
}

.highlight-blue {
    background-color: rgba(191, 219, 254, 0.5);
}

.highlight-pink {
    background-color: rgba(251, 207, 232, 0.5);
}

.highlight-orange {
    background-color: rgba(254, 215, 170, 0.5);
}
</style>

<style>
.note-highlight {
    border-bottom: 2px solid rgba(0, 0, 0, 0.4);
    cursor: help;
    position: relative;
    display: inline;
}

.note-highlight::after {
    content: '📝';
    display: inline-block;
    margin-left: 2px;
    font-size: 0.7em;
    vertical-align: super;
    line-height: 0;
    opacity: 0.9;
    pointer-events: none;
}

.note-highlight:hover {
    border-bottom-color: #000;
}

.note-highlight:hover::after {
    opacity: 1;
    font-size: 0.75em;
}
</style>
