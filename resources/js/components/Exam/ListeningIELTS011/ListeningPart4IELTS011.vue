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

// Single choice answers for questions 31-36
const answers = ref<Record<string, string>>({
    q31: '',
    q32: '',
    q33: '',
    q34: '',
    q35: '',
    q36: '',
});

// Fill-in-the-blank answers for questions 37-40
const fillAnswers = ref<Record<string, string>>({
    q37: '',
    q38: '',
    q39: '',
    q40: '',
});

// Text segments with their cumulative offsets
const textSegments = ref<TextSegment[]>([
    // Part header box text segments
    { id: 'part4-title', text: 'Part 4', offset: 0 },
    { id: 'part4-desc', text: 'Listen and answer questions 31–40.', offset: 7 },
    // Instruction section text segments
    { id: 0, text: 'Questions 31–36', offset: 43 },
    { id: 1, text: 'Choose the correct letter, A, B or C.', offset: 59 },
    { id: 2, text: 'Wildlife in city gardens', offset: 97 },
    // Question 31
    { id: 'q31', text: '31.', offset: 122 },
    { id: 3, text: 'What led the group to choose their topic?', offset: 126 },
    { id: 4, text: 'They were concerned about the decline of one species.', offset: 169 },
    { id: 5, text: 'They were interested in the effects of city growth.', offset: 224 },
    { id: 6, text: 'They wanted to investigate a recent phenomenon.', offset: 277 },
    // Question 32
    { id: 'q32', text: '32.', offset: 327 },
    { id: 7, text: 'The exact proportion of land devoted to private gardens was confirmed by', offset: 331 },
    { id: 8, text: 'consulting some official documents.', offset: 405 },
    { id: 9, text: 'taking large-scale photos.', offset: 443 },
    { id: 10, text: 'discussions with town surveyors.', offset: 472 },
    // Question 33
    { id: 'q33', text: '33.', offset: 507 },
    { id: 11, text: 'The group asked garden owners to', offset: 511 },
    { id: 12, text: 'take part in formal interviews.', offset: 546 },
    { id: 13, text: 'keep a record of animals they saw.', offset: 580 },
    { id: 14, text: 'get in contact when they saw a rare species.', offset: 617 },
    // Question 34
    { id: 'q34', text: '34.', offset: 664 },
    { id: 15, text: 'The group made their observations in gardens', offset: 668 },
    { id: 16, text: 'which had a large number of animal species.', offset: 715 },
    { id: 17, text: 'which they considered to be representative.', offset: 761 },
    { id: 18, text: 'which had stable populations of rare animals.', offset: 807 },
    // Question 35
    { id: 'q35', text: '35.', offset: 856 },
    { id: 19, text: 'The group did extensive reading on', offset: 860 },
    { id: 20, text: 'wildlife problems in rural areas.', offset: 896 },
    { id: 21, text: 'urban animal populations.', offset: 931 },
    { id: 22, text: 'current gardening practices.', offset: 959 },
    // Question 36
    { id: 'q36', text: '36.', offset: 990 },
    { id: 23, text: 'The speaker focuses on three animal species because', offset: 994 },
    { id: 24, text: 'a lot of data has been obtained about them.', offset: 1047 },
    { id: 25, text: 'the group were most interested in them.', offset: 1093 },
    { id: 26, text: 'they best indicated general trends.', offset: 1135 },
    // Questions 37-40 section header
    { id: 'q37-40', text: 'Questions 37–40', offset: 1173 },
    { id: 'q37-40-inst', text: 'Complete the table below. Write ONE WORD ONLY.', offset: 1189 },
    // Table header
    { id: 'th-animals', text: 'Animals', offset: 1237 },
    { id: 'th-reason', text: 'Reason for population increase in gardens', offset: 1245 },
    { id: 'th-comments', text: 'Comments', offset: 1288 },
    // Row 1 - Q37 (animal name)
    { id: 'q37-label', text: '', offset: 1299 },
    { id: 27, text: 'suitable stretches of water', offset: 1303 },
    { id: 28, text: 'massive increase in urban population', offset: 1333 },
    // Row 2 - Q38 (hedgehogs)
    { id: 'r2-animal', text: 'Hedgehogs', offset: 1371 },
    { id: 'q38-label', text: '', offset: 1382 },
    { id: 29, text: 'safe from', offset: 1386 },
    { id: 30, text: 'when in cities', offset: 1396 },
    { id: 'q39-label', text: '', offset: 1413 },
    { id: 31, text: 'easy to', offset: 1417 },
    { id: 32, text: 'them accurately', offset: 1425 },
    // Row 3 - Q40 (song thrushes)
    { id: 'r3-animal', text: 'Song Thrushes', offset: 1443 },
    { id: 'q40-label', text: '', offset: 1458 },
    { id: 33, text: 'a variety of', offset: 1462 },
    { id: 34, text: 'to eat', offset: 1475 },
    { id: 35, text: 'more nesting places available', offset: 1484 },
    { id: 36, text: 'large survey starting soon', offset: 1516 },
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
    return {
        ...answers.value,
        ...fillAnswers.value,
    };
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
    // Handle grouped questions for 37-40
    let targetId = `question-${questionNumber}`;
    if (questionNumber >= 37 && questionNumber <= 40) {
        targetId = 'question-37-40';
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
        <!-- Questions Section - Full Width -->
        <div class="flex w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
            <div class="mb-3 part-header-box rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part4-title"
                    v-html="getHighlightedSegmentById('part4-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part4-desc"
                    v-html="getHighlightedSegmentById('part4-desc')"
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
                    <!-- Questions 31-36: Single Choice MCQ -->
                    <div class="space-y-2">

                        <!-- Question 31 -->
                        <div
                            id="question-31"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 31"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q31" v-html="getHighlightedSegmentById('q31')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                        @click.stop="toggleFlag(31)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(31)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'"
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
                                                v-model="answers.q31"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q31"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q31"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 32 -->
                        <div
                            id="question-32"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 32"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q32" v-html="getHighlightedSegmentById('q32')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                        @click.stop="toggleFlag(32)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(32)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'"
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
                                                v-model="answers.q32"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q32"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q32"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 33 -->
                        <div
                            id="question-33"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 33"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q33" v-html="getHighlightedSegmentById('q33')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                        @click.stop="toggleFlag(33)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(33)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'"
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
                                                v-model="answers.q33"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q33"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q33"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 34 -->
                        <div
                            id="question-34"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 34"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q34" v-html="getHighlightedSegmentById('q34')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                        @click.stop="toggleFlag(34)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(34)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'"
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
                                                v-model="answers.q34"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q34"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q34"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 35 -->
                        <div
                            id="question-35"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 35"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q35" v-html="getHighlightedSegmentById('q35')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                        @click.stop="toggleFlag(35)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(35)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'"
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
                                                v-model="answers.q35"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q35"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q35"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 36 -->
                        <div
                            id="question-36"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 36"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q36" v-html="getHighlightedSegmentById('q36')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                        @click.stop="toggleFlag(36)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(36)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q36"
                                                value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q36"
                                                value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input
                                                type="radio"
                                                v-model="answers.q36"
                                                value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"
                                            />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 37-40: Table Completion -->
<div id="question-37-40" class="relative p-2 sm:p-3">
    <!-- Section header -->
    <div class="mb-2">
        <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q37-40" v-html="getHighlightedSegmentById('q37-40')"></h3>
    </div>
    <div class="mb-3 p-2">
        <p class="text-sm text-gray-700 select-text" data-segment-id="q37-40-inst" v-html="getHighlightedSegmentById('q37-40-inst')"></p>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold text-gray-900 select-text w-1/4" data-segment-id="th-animals" v-html="getHighlightedSegmentById('th-animals')"></th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold text-gray-900 select-text w-2/5" data-segment-id="th-reason" v-html="getHighlightedSegmentById('th-reason')"></th>
                    <th class="border border-gray-300 px-3 py-2 text-left font-bold text-gray-900 select-text" data-segment-id="th-comments" v-html="getHighlightedSegmentById('th-comments')"></th>
                </tr>
            </thead>
            <tbody>
                <!-- Row 1: Q37 animal name blank -->
<tr 
    @mouseenter="hoveredQuestion = 37"
    @mouseleave="hoveredQuestion = null"
>
    <td class="border border-gray-300 px-3 py-3 align-top">
        <div class="flex items-center gap-2">
            <span class="font-bold text-gray-900 select-text whitespace-nowrap" data-segment-id="q37-label" v-html="getHighlightedSegmentById('q37-label')"></span>
            <input
                v-model="fillAnswers.q37"
                type="text"
                placeholder="37"
                class="w-32  border text-center border-gray-600 px-2 py-1 text-sm text-gray-900 focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
            />
            <button
                v-show="hoveredQuestion === 37 || isQuestionFlagged(37)"
                @click.stop="toggleFlag(37)"
                class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded border transition-all"
                :class="isQuestionFlagged(37) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                :title="isQuestionFlagged(37) ? 'Remove bookmark' : 'Bookmark this question'"
            >
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                </svg>
            </button>
        </div>
    </td>
    <td class="border border-gray-300 px-3 py-3 align-top text-gray-900 select-text" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></td>
    <td class="border border-gray-300 px-3 py-3 align-top text-gray-900 select-text" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></td>
</tr>
                
                <!-- Row 2: Q38 and Q39 blanks -->
                <tr>
                    <td class="border border-gray-300 px-3 py-3 align-top font-semibold text-gray-900 select-text" data-segment-id="r2-animal" v-html="getHighlightedSegmentById('r2-animal')"></td>
                    <td 
                        class="border border-gray-300 px-3 py-3 align-top relative"
                        @mouseenter="hoveredQuestion = 38"
                        @mouseleave="hoveredQuestion = null"
                    >
                        <div class="flex flex-wrap items-center gap-1 text-gray-900">
                            <span class="select-text" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></span>
                            <span class="font-bold text-gray-900 select-text whitespace-nowrap" data-segment-id="q38-label" v-html="getHighlightedSegmentById('q38-label')"></span>
                            <input
                                v-model="fillAnswers.q38"
                                type="text"
                                placeholder="38"
                                class="w-32 border border-gray-600 px-2 py-1 text-sm text-center text-gray-900 focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                            />
                            <span class="select-text" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></span>
                        </div>
                        <!-- Bookmark button for Q38 -->
                        <div class="absolute top-2 right-2">
                            <button
                                v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                @click.stop="toggleFlag(38)"
                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td 
                        class="border border-gray-300 px-3 py-3 align-top relative"
                        @mouseenter="hoveredQuestion = 39"
                        @mouseleave="hoveredQuestion = null"
                    >
                        <div class="flex flex-wrap items-center gap-1 text-gray-900">
                            <span class="select-text" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></span>
                            <span class="font-bold text-gray-900 select-text whitespace-nowrap" data-segment-id="q39-label" v-html="getHighlightedSegmentById('q39-label')"></span>
                            <input
                                v-model="fillAnswers.q39"
                                type="text"
                                placeholder="39"
                                class="w-32 border border-gray-600 px-2 py-1 text-sm text-center text-gray-900 focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                            />
                            <span class="select-text" data-segment-id="32" v-html="getHighlightedSegmentById(32)"></span>
                        </div>
                        <!-- Bookmark button for Q39 -->
                        <div class="absolute top-2 right-2">
                            <button
                                v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                @click.stop="toggleFlag(39)"
                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                
                <!-- Row 3: Q40 blank -->
                <tr>
                    <td class="border border-gray-300 px-3 py-3 align-top font-semibold text-gray-900 select-text" data-segment-id="r3-animal" v-html="getHighlightedSegmentById('r3-animal')"></td>
                    <td 
                        class="border border-gray-300 px-3 py-3 align-top relative"
                        @mouseenter="hoveredQuestion = 40"
                        @mouseleave="hoveredQuestion = null"
                    >
                        <div class="flex flex-wrap items-center gap-1 text-gray-900">
                            <span class="select-text" data-segment-id="33" v-html="getHighlightedSegmentById(33)"></span>
                            <span class="font-bold text-gray-900 select-text whitespace-nowrap" data-segment-id="q40-label" v-html="getHighlightedSegmentById('q40-label')"></span>
                            <input
                                v-model="fillAnswers.q40"
                                type="text"
                                placeholder="40"
                                class="w-32 border border-gray-600 px-2 py-1 text-sm text-center text-gray-900 focus:border-black focus:outline-none focus:ring-1 focus:ring-black"
                            />
                            <span class="select-text" data-segment-id="34" v-html="getHighlightedSegmentById(34)"></span>
                        </div>
                        <div class="mt-1">
                            <span class="select-text text-gray-900" data-segment-id="35" v-html="getHighlightedSegmentById(35)"></span>
                        </div>
                        <!-- Bookmark button for Q40 -->
                        <div class="absolute top-2 right-2">
                            <button
                                v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                @click.stop="toggleFlag(40)"
                                class="flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td class="border border-gray-300 px-3 py-3 align-top text-gray-900 select-text" data-segment-id="36" v-html="getHighlightedSegmentById(36)"></td>
                </tr>
            </tbody>
        </table>
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