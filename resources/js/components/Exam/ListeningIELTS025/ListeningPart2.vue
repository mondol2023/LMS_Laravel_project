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

// Listening to Part 2 component

// Single choice answers for questions 11-16
const answers = ref<Record<string, string>>({
    q11: '',
    q12: '',
    q13: '',
    q14: '',
    q15: '',
    q16: '',
    q17: '',
    q18: '',
    q19: '',
    q20: '',
});

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const notes = ref<Note[]>([]);

// Text segments with their cumulative offsets (each offset = previous offset + previous text length + 1)
const textSegments = ref<TextSegment[]>([
    { id: 'part2-title', text: 'Part 2: Museum Galleries', offset: 0 },
    { id: 'part2-desc', text: 'SECTION 2 – Questions 11–20', offset: 25 },

    { id: 0, text: 'Questions 11–16', offset: 52 },
    { id: 1, text: 'Choose the correct letter, A, B or C.', offset: 68 },
    { id: 2, text: 'Museum tour', offset: 104 },

    { id: 'q11', text: '11.', offset: 116 },
    { id: 3, text: 'What does the tour guide advise the visitors to do in the museum today?', offset: 120 },
    { id: 4, text: 'see the most popular exhibits first', offset: 188 },
    { id: 5, text: 'pay a brief visit to each gallery', offset: 224 },
    { id: 6, text: 'go to the photography gallery last', offset: 258 },

    { id: 'q12', text: '12.', offset: 294 },
    { id: 7, text: 'The museum was designed by William Craven, who also designed', offset: 298 },
    { id: 8, text: 'a textile factory', offset: 357 },
    { id: 9, text: 'the town hall', offset: 375 },
    { id: 10, text: 'the railway station', offset: 389 },

    { id: 'q13', text: '13.', offset: 409 },
    { id: 11, text: 'The museum won an award for the preservation of the', offset: 413 },
    { id: 12, text: 'staircase', offset: 463 },
    { id: 13, text: 'floor', offset: 473 },
    { id: 14, text: 'windows', offset: 479 },

    { id: 'q14', text: '14.', offset: 487 },
    { id: 15, text: 'Most of the money for the project came from', offset: 491 },
    { id: 16, text: 'the public', offset: 535 },
    { id: 17, text: 'the government', offset: 546 },
    { id: 18, text: 'local businesses', offset: 561 },

    { id: 'q15', text: '15.', offset: 578 },
    { id: 19, text: 'Over the next five years, the museum will invest mainly in', offset: 582 },
    { id: 20, text: 'restoring existing collections.', offset: 640 },
    { id: 21, text: 'developing educational programmes.', offset: 671 },
    { id: 22, text: 'purchasing new objects for display.', offset: 707 },

    { id: 'q16', text: '16.', offset: 744 },
    { id: 23, text: 'Visitors who are interested in learning more about the exhibits should', offset: 748 },
    { id: 24, text: "visit the museum's website", offset: 816 },
    { id: 25, text: 'read the leaflets on display', offset: 844 },
    { id: 26, text: 'attend the monthly lectures', offset: 872 },

    { id: 'q17-20', text: 'Questions 17–20', offset: 901 },
    { id: 'q17-20-inst1', text: 'What information does the guide give about each of the following collections?', offset: 917 },
    { id: 'q17-20-inst2', text: 'Choose FOUR answers from the box and write the correct letter, A–F.', offset: 989 },

    { id: 'info-title', text: 'Information', offset: 1055 },
    { id: 27, text: 'A. has been shown in different museums', offset: 1067 },
    { id: 28, text: 'B. consists of work by a local resident', offset: 1105 },
    { id: 29, text: 'C. has exhibits from various countries', offset: 1143 },
    { id: 30, text: 'D. is only on temporary display', offset: 1181 },
    { id: 31, text: 'E. shows things that are no longer common', offset: 1212 },
    { id: 32, text: 'F. is on loan from foreign museum', offset: 1252 },

    { id: 'collections-title', text: 'Collections', offset: 1286 },
    { id: 'q17', text: '17. 18th-century paintings →', offset: 1298 },
    { id: 'q18', text: '18. Farnley collection →', offset: 1327 },
    { id: 'q19', text: '19. kitchen appliances →', offset: 1351 },
    { id: 'q20', text: '20. fashion gallery →', offset: 1376 },
]);
// Use the tooltip composable
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
    handleContentTextSelect: tooltipHandleContentTextSelect,
    handleContentClick: tooltipHandleContentClick,
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
                // Just render the mark with data attributes - tooltip will be rendered via Teleport
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
    return answers.value ;
};

// Use the tooltip composable's handleContentTextSelect
const handleContentTextSelect = () => {
    tooltipHandleContentTextSelect();
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

// Delete highlight confirmation handler
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
        showNoteTooltip.value = false;
        hoveredNoteId.value = null;
        hoveredNoteText.value = '';
    }
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();

    const targetId = `question-${questionNumber}`;
    const element = document.getElementById(targetId);

    if (!element) return; // Exit if the element does not exist

    // Remove previous highlights
    document.querySelectorAll('.highlight-question').forEach(el => {
        el.classList.remove('highlight-question');
    });

    // Scroll smoothly to the question
    element.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    });

    // Add temporary highlight
    element.classList.add('highlight-question');
    setTimeout(() => {
        element.classList.remove('highlight-question');
    }, 2000);
};

// Load saved answers when component mounts
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="tooltipHandleContentClick" class="px-4 py-2 pb-24 select-text sm:px-6">
        <!-- Questions Section - Full Width -->
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
            <!-- Part Header -->
            <div class="mb-3 rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part2-title"
                    v-html="getHighlightedSegmentById('part2-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part2-desc"
                    v-html="getHighlightedSegmentById('part2-desc')"
                ></p>
            </div>

            <!-- Instructions Section -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"
                ></p>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="1"
                    v-html="getHighlightedSegmentById(1)"
                ></p>
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

                        <!-- Question 11 -->
                        <div
                            id="question-11"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 11"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q11" v-html="getHighlightedSegmentById('q11')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                        @click.stop="toggleFlag(11)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q11" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q11" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q11" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 12 -->
                        <div
                            id="question-12"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 12"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q12" v-html="getHighlightedSegmentById('q12')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                        @click.stop="toggleFlag(12)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q12" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q12" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q12" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 13 -->
                        <div
                            id="question-13"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 13"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q13" v-html="getHighlightedSegmentById('q13')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                        @click.stop="toggleFlag(13)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q13" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q13" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q13" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 14 -->
                        <div
                            id="question-14"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 14"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q14" v-html="getHighlightedSegmentById('q14')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                        @click.stop="toggleFlag(14)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q14" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q14" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q14" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 15 -->
                        <div
                            id="question-15"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 15"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q15" v-html="getHighlightedSegmentById('q15')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                        @click.stop="toggleFlag(15)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q15" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q15" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q15" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 16 -->
                        <div
                            id="question-16"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 16"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q16" v-html="getHighlightedSegmentById('q16')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                        @click.stop="toggleFlag(16)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q16" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q16" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q16" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 17–20: Matching (dropdown per collection, choose from A–F box) -->
                        <div
                            id="question-17-20"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 17"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 17 || isQuestionFlagged(17) || isQuestionFlagged(18) || isQuestionFlagged(19) || isQuestionFlagged(20)"
                                @click.stop="toggleFlag(17); toggleFlag(18); toggleFlag(19); toggleFlag(20);"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(17) || isQuestionFlagged(18) || isQuestionFlagged(19) || isQuestionFlagged(20)
                                    ? 'border-gray-400 bg-transparent text-red-500'
                                    : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(17) || isQuestionFlagged(18) || isQuestionFlagged(19) || isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                </svg>
                            </button>

                            <!-- Section heading -->
                            <h3
                                class="mb-1 text-base font-bold text-gray-900 select-text"
                                data-segment-id="q17-20"
                                v-html="getHighlightedSegmentById('q17-20')"
                            ></h3>
                            <p
                                class="mb-1 text-sm text-gray-700 select-text"
                                data-segment-id="q17-20-inst1"
                                v-html="getHighlightedSegmentById('q17-20-inst1')"
                            ></p>
                            <p
                                class="mb-3 text-sm text-gray-700 select-text"
                                data-segment-id="q17-20-inst2"
                                v-html="getHighlightedSegmentById('q17-20-inst2')"
                            ></p>

                            <!-- Information Box (A–F options) -->
                            <div class="mb-4 rounded border border-gray-300 bg-gray-50 p-3">
                                <h4
                                    class="mb-2 text-sm font-bold text-gray-900 select-text"
                                    data-segment-id="info-title"
                                    v-html="getHighlightedSegmentById('info-title')"
                                ></h4>
                                <div class="space-y-1">
                                    <p class="text-sm text-gray-800 select-text" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></p>
                                    <p class="text-sm text-gray-800 select-text" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></p>
                                    <p class="text-sm text-gray-800 select-text" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></p>
                                    <p class="text-sm text-gray-800 select-text" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></p>
                                    <p class="text-sm text-gray-800 select-text" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></p>
                                    <p class="text-sm text-gray-800 select-text" data-segment-id="32" v-html="getHighlightedSegmentById(32)"></p>
                                </div>
                            </div>

                            <!-- Collections heading -->
                            <h4
                                class="mb-2 text-sm font-bold text-gray-900 select-text"
                                data-segment-id="collections-title"
                                v-html="getHighlightedSegmentById('collections-title')"
                            ></h4>

                            <!-- Q17 -->
                            <div
                                id="question-17"
                                class="mb-3 flex flex-wrap items-center gap-2"
                                @mouseenter="hoveredQuestion = 17"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="text-sm font-semibold text-gray-900 select-text"
                                    data-segment-id="q17"
                                    v-html="getHighlightedSegmentById('q17')"
                                ></span>
                                <select
                                    v-model="answers.q17"
                                    class="rounded border border-gray-900 px-2 py-0.5 text-sm text-gray-900 focus:border-black focus:outline-none"
                                >
                                    <option value="" disabled>Select</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                    <option value="F">F</option>
                                </select>
                            </div>

                            <!-- Q18 -->
                            <div
                                id="question-18"
                                class="mb-3 flex flex-wrap items-center gap-2"
                                @mouseenter="hoveredQuestion = 18"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="text-sm font-semibold text-gray-900 select-text"
                                    data-segment-id="q18"
                                    v-html="getHighlightedSegmentById('q18')"
                                ></span>
                                <select
                                    v-model="answers.q18"
                                    class="rounded border border-gray-900 px-2 py-0.5 text-sm text-gray-900 focus:border-black focus:outline-none"
                                >
                                    <option value="" disabled>Select</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                    <option value="F">F</option>
                                </select>
                            </div>

                            <!-- Q19 -->
                            <div
                                id="question-19"
                                class="mb-3 flex flex-wrap items-center gap-2"
                                @mouseenter="hoveredQuestion = 19"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="text-sm font-semibold text-gray-900 select-text"
                                    data-segment-id="q19"
                                    v-html="getHighlightedSegmentById('q19')"
                                ></span>
                                <select
                                    v-model="answers.q19"
                                    class="rounded border border-gray-900 px-2 py-0.5 text-sm text-gray-900 focus:border-black focus:outline-none"
                                >
                                    <option value="" disabled>Select</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                    <option value="F">F</option>
                                </select>
                            </div>

                            <!-- Q20 -->
                            <div
                                id="question-20"
                                class="mb-3 flex flex-wrap items-center gap-2"
                                @mouseenter="hoveredQuestion = 20"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span
                                    class="text-sm font-semibold text-gray-900 select-text"
                                    data-segment-id="q20"
                                    v-html="getHighlightedSegmentById('q20')"
                                ></span>
                                <select
                                    v-model="answers.q20"
                                    class="rounded border border-gray-900 px-2 py-0.5 text-sm text-gray-900 focus:border-black focus:outline-none"
                                >
                                    <option value="" disabled>Select</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                    <option value="F">F</option>
                                </select>
                            </div>

                        </div>
                        <!-- End Q17–20 -->

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
