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

// Single choice answers for questions 21-25
const answers = ref<Record<string, string>>({
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
});

// Notes completion answers for questions 26-30 (max two words each)
const notesAnswers = ref<Record<string, string>>({
    q26: '',
    q27: '',
    q28: '',
    q29: '',
    q30: '',
});

// Text segments with their cumulative offsets
const textSegments = ref<TextSegment[]>([
    // Part header box text segments
    { id: 'part3-title', text: 'Part 3', offset: 0 },                                           // 0-6
    { id: 'part3-desc', text: 'Listen and answer questions 21–30.', offset: 7 },                // 7-42
    // Instruction section text segments
    { id: 0, text: 'Questions 21–25', offset: 43 },                                              // 43-55
    { id: 1, text: 'Choose the correct letter A, B or C.', offset: 56 },                         // 56-87
    { id: 2, text: 'INTERNSHIP DISCUSSION', offset: 88 },                                        // 88-107
    // Question 21
    { id: 'q21', text: '21.', offset: 108 },
    { id: 3, text: 'Why did Joanne accept the offer from Gregory Associates?', offset: 112 },
    { id: 4, text: 'it covered her travel expenses', offset: 169 },
    { id: 5, text: 'it was from a well-known company', offset: 204 },
    { id: 6, text: 'it was the only offer she received', offset: 240 },
    // Question 22
    { id: 'q22', text: '22.', offset: 279 },
    { id: 7, text: 'Joanne was disappointed because', offset: 283 },
    { id: 8, text: 'she found the work routine repetitive', offset: 321 },
    { id: 9, text: 'the staff were not very helpful', offset: 365 },
    { id: 10, text: 'the work was not related to her studies', offset: 403 },
    // Question 23
    { id: 'q23', text: '23.', offset: 448 },
    { id: 11, text: 'What did Joanne like best about her internship?', offset: 452 },
    { id: 12, text: 'observing how the workplace operates', offset: 502 },
    { id: 13, text: 'being responsible for completing projects', offset: 544 },
    { id: 14, text: 'working closely with the project managers', offset: 590 },
    // Question 24
    { id: 'q24', text: '24.', offset: 638 },
    { id: 15, text: 'What was the hardest part of the internship?', offset: 642 },
    { id: 16, text: 'combining it with her studies', offset: 687 },
    { id: 17, text: 'living on so little money', offset: 722 },
    { id: 18, text: 'working such long hours', offset: 753 },
    // Question 25
    { id: 'q25', text: '25.', offset: 784 },
    { id: 19, text: 'During the internship Joanne', offset: 788 },
    { id: 20, text: 'changed her mind about her career', offset: 822 },
    { id: 21, text: 'received a job offer from the company', offset: 864 },
    { id: 22, text: 'decided not to continue her studies', offset: 907 },
    // Questions 26-30 instruction
    { id: 'q26-30-title', text: 'Questions 26–30', offset: 949 },
    { id: 'q26-30-inst', text: 'Complete the notes below.', offset: 972 },
    { id: 'q26-30-note', text: 'Write NO MORE THAN TWO WORDS for each answer.', offset: 1002 },
    { id: 23, text: 'How to apply for an internship', offset: 1050 },
    { id: 24, text: '• Organise your ____26____ in advance', offset: 1085 },
    { id: 25, text: '• Research a variety of companies', offset: 1133 },
    { id: 26, text: '• Create a ____27____ of appropriate positions', offset: 1172 },
    { id: 27, text: '• _____28___ the applications for each position', offset: 1222 },
    { id: 28, text: '• _____29___ companies after one week', offset: 1270 },
    { id: 29, text: '• Prepare for the interview', offset: 1311 },
    { id: 30, text: '• ____30____ during the interview', offset: 1344 },
]);

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
    const allAnswers = { ...answers.value, ...notesAnswers.value };
    return allAnswers;
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
    let targetId = `question-${questionNumber}`;
    // Handle notes questions 26-30 - each has its own container with input field
    if (questionNumber >= 26 && questionNumber <= 30) {
        targetId = `question-${questionNumber}`;
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
        <!-- Questions Section - Full Width -->
        <div class="flex w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
            <div class="mb-3 rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part3-title"
                    v-html="getHighlightedSegmentById('part3-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part3-desc"
                    v-html="getHighlightedSegmentById('part3-desc')"
                ></p>
            </div>

            <!-- Instructions Section -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"
                ></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></p>
            </div>

            <!-- Section Title -->
            <div class="px-2 sm:px-3">
                <h2
                    class="text-lg font-bold text-gray-900 select-text"
                    data-segment-id="2"
                    v-html="getHighlightedSegmentById(2)"
                ></h2>
            </div>

            <!-- Scrollable Questions Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <div class="space-y-2">
                        <!-- Question 21 -->
                        <div
                            id="question-21"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 21"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q21" v-html="getHighlightedSegmentById('q21')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                        @click.stop="toggleFlag(21)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(21)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q21"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="4"
                                                v-html="getHighlightedSegmentById(4)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q21"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="5"
                                                v-html="getHighlightedSegmentById(5)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q21"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="6"
                                                v-html="getHighlightedSegmentById(6)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 22 -->
                        <div
                            id="question-22"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 22"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q22" v-html="getHighlightedSegmentById('q22')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                        @click.stop="toggleFlag(22)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(22)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q22"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="8"
                                                v-html="getHighlightedSegmentById(8)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q22"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="9"
                                                v-html="getHighlightedSegmentById(9)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q22"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="10"
                                                v-html="getHighlightedSegmentById(10)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 23 -->
                        <div
                            id="question-23"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 23"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q23" v-html="getHighlightedSegmentById('q23')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                        @click.stop="toggleFlag(23)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(23)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q23"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="12"
                                                v-html="getHighlightedSegmentById(12)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q23"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="13"
                                                v-html="getHighlightedSegmentById(13)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q23"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="14"
                                                v-html="getHighlightedSegmentById(14)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 24 -->
                        <div
                            id="question-24"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 24"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q24" v-html="getHighlightedSegmentById('q24')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                        @click.stop="toggleFlag(24)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(24)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q24"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="16"
                                                v-html="getHighlightedSegmentById(16)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q24"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="17"
                                                v-html="getHighlightedSegmentById(17)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q24"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="18"
                                                v-html="getHighlightedSegmentById(18)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 25 -->
                        <div
                            id="question-25"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 25"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q25" v-html="getHighlightedSegmentById('q25')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                        @click.stop="toggleFlag(25)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(25)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q25"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="20"
                                                v-html="getHighlightedSegmentById(20)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q25"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="21"
                                                v-html="getHighlightedSegmentById(21)"
                                            ></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q25"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="22"
                                                v-html="getHighlightedSegmentById(22)"
                                            ></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 26-30: Notes Completion -->
<div class="mt-4">
    <div class="mb-3">
        <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q26-30-title" v-html="getHighlightedSegmentById('q26-30-title')"></h3>
        <p class="text-sm text-gray-700 select-text" data-segment-id="q26-30-inst" v-html="getHighlightedSegmentById('q26-30-inst')"></p>
        <p class="text-sm italic text-gray-600 select-text" data-segment-id="q26-30-note" v-html="getHighlightedSegmentById('q26-30-note')"></p>
    </div>
    <div class="space-y-3">
        <p class="text-base font-semibold text-gray-800 select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></p>
        
        <!-- Question 26 -->
        <div 
            id="question-26" 
            class="relative flex flex-wrap items-center gap-2 rounded p-1" 
            @mouseenter="hoveredQuestion = 26" 
            @mouseleave="hoveredQuestion = null"
        >
            <span class="select-text text-gray-700" data-segment-id="24" v-html="getHighlightedSegmentById(24).replace('____26____', '')"></span>
            <input
                type="text"
                v-model="notesAnswers.q26"
                maxlength="20"
                class="w-32  border border-gray-800 font-bold text-center px-2 py-0.5 text-sm focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                placeholder="26"
            />
            <div class="relative inline-flex h-7 w-7 items-center justify-center shrink-0">
                <button
                    @click.stop="toggleFlag(26)"
                    class="absolute inset-0 flex items-center justify-center rounded border transition-all duration-200"
                    :class="[
                        (hoveredQuestion === 26 || isQuestionFlagged(26))
                            ? 'opacity-100'
                            : 'opacity-0 pointer-events-none',
                        isQuestionFlagged(26)
                            ? 'border-gray-400 bg-transparent text-red-500'
                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                    ]"
                    :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Question 27 -->
        <div 
            id="question-27" 
            class="relative flex flex-wrap items-center gap-2 rounded p-1" 
            @mouseenter="hoveredQuestion = 27" 
            @mouseleave="hoveredQuestion = null"
        >
            <span class="select-text text-gray-700" data-segment-id="26" v-html="getHighlightedSegmentById(26).replace('____27____', '')"></span>
            <input
                type="text"
                v-model="notesAnswers.q27"
                maxlength="20"
                class="w-32  border border-gray-800 font-bold text-center px-2 py-0.5 text-sm focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                placeholder="27"
            />
            <div class="relative inline-flex h-7 w-7 items-center justify-center shrink-0">
                <button
                    @click.stop="toggleFlag(27)"
                    class="absolute inset-0 flex items-center justify-center rounded border transition-all duration-200"
                    :class="[
                        (hoveredQuestion === 27 || isQuestionFlagged(27))
                            ? 'opacity-100'
                            : 'opacity-0 pointer-events-none',
                        isQuestionFlagged(27)
                            ? 'border-gray-400 bg-transparent text-red-500'
                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                    ]"
                    :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Question 28 -->
        <div 
            id="question-28" 
            class="relative flex flex-wrap items-center gap-2 rounded p-1" 
            @mouseenter="hoveredQuestion = 28" 
            @mouseleave="hoveredQuestion = null"
        >
            <span class="select-text text-gray-700" data-segment-id="27" v-html="getHighlightedSegmentById(27).replace('_____28___', '')"></span>
            <input
                type="text"
                v-model="notesAnswers.q28"
                maxlength="20"
                class="w-32  border border-gray-800 font-bold text-center px-2 py-0.5 text-sm focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                placeholder="28"
            />
            <div class="relative inline-flex h-7 w-7 items-center justify-center shrink-0">
                <button
                    @click.stop="toggleFlag(28)"
                    class="absolute inset-0 flex items-center justify-center rounded border transition-all duration-200"
                    :class="[
                        (hoveredQuestion === 28 || isQuestionFlagged(28))
                            ? 'opacity-100'
                            : 'opacity-0 pointer-events-none',
                        isQuestionFlagged(28)
                            ? 'border-gray-400 bg-transparent text-red-500'
                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                    ]"
                    :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Question 29 -->
        <div 
            id="question-29" 
            class="relative flex flex-wrap items-center gap-2 rounded p-1" 
            @mouseenter="hoveredQuestion = 29" 
            @mouseleave="hoveredQuestion = null"
        >
            <span class="select-text text-gray-700" data-segment-id="28" v-html="getHighlightedSegmentById(28).replace('_____29___', '')"></span>
            <input
                type="text"
                v-model="notesAnswers.q29"
                maxlength="20"
                class="w-32  border border-gray-800 font-bold text-center px-2 py-0.5 text-sm focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                placeholder="29"
            />
            <div class="relative inline-flex h-7 w-7 items-center justify-center shrink-0">
                <button
                    @click.stop="toggleFlag(29)"
                    class="absolute inset-0 flex items-center justify-center rounded border transition-all duration-200"
                    :class="[
                        (hoveredQuestion === 29 || isQuestionFlagged(29))
                            ? 'opacity-100'
                            : 'opacity-0 pointer-events-none',
                        isQuestionFlagged(29)
                            ? 'border-gray-400 bg-transparent text-red-500'
                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                    ]"
                    :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Question 30 -->
        <div 
            id="question-30" 
            class="relative flex flex-wrap items-center gap-2 rounded p-1" 
            @mouseenter="hoveredQuestion = 30" 
            @mouseleave="hoveredQuestion = null"
        >
            <span class="select-text text-gray-700" data-segment-id="30" v-html="getHighlightedSegmentById(30).replace('____30____', '')"></span>
            <input
                type="text"
                v-model="notesAnswers.q30"
                maxlength="20"
                class="w-32  border border-gray-800 font-bold text-center px-2 py-0.5 text-sm focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                placeholder="30"
            />
            <div class="relative inline-flex h-7 w-7 items-center justify-center shrink-0">
                <button
                    @click.stop="toggleFlag(30)"
                    class="absolute inset-0 flex items-center justify-center rounded border transition-all duration-200"
                    :class="[
                        (hoveredQuestion === 30 || isQuestionFlagged(30))
                            ? 'opacity-100'
                            : 'opacity-0 pointer-events-none',
                        isQuestionFlagged(30)
                            ? 'border-gray-400 bg-transparent text-red-500'
                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                    ]"
                    :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Highlight Tooltips Component -->
    <HighlightTooltips
        :show-context-menu="showContextMenu"
        :context-menu-position="contextMenuPosition"
        :show-delete-tooltip="showDeleteTooltip"
        :delete-tooltip-position="deleteTooltipPosition"
        :show-note-tooltip="showNoteTooltip"
        :note-tooltip-position="noteTooltipPosition"
        :hovered-note-text="hoveredNoteText"
        :show-note-input="showNoteInput"
        :note-input-position="noteInputPosition"
        :selected-text="selectedText"
        :note-input-text="noteInputText"
        @open-note-input="openNoteInput"
        @apply-highlight="applyHighlight"
        @confirm-delete-highlight="confirmDeleteHighlight"
        @close-delete-tooltip="closeDeleteTooltip"
        @delete-note-from-tooltip="deleteNoteFromTooltip"
        @handle-tooltip-mouse-leave="handleTooltipMouseLeave"
        @save-note="saveNote"
        @cancel-note="cancelNote"
        @update:note-input-text="noteInputText = $event"
    />
</template>

<style scoped>
.highlight-question {
    background-color: rgba(0, 0, 0, 0.08);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightFade 1.5s ease-out;
}

@keyframes highlightFade {
    0% {
        background-color: rgba(0, 0, 0, 0.15);
    }
    100% {
        background-color: rgba(0, 0, 0, 0.08);
    }
}

/* Custom scrollbar styling */
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

/* Highlight functionality styles */
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

<!-- Non-scoped styles for v-html generated note indicators -->
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