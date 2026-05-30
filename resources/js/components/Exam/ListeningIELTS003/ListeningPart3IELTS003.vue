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

// Listening to Part 3 component

// Single choice answers for questions 21-24
const answers = ref<Record<string, string>>({
    q21: '',
    q22: '',
    q23: '',
    q24: '',
});

// Fill-in-the-blank answers for questions 25-30
const fillAnswers = ref<Record<string, string>>({
    q25: '',
    q26: '',
    q27: '',
    q28: '',
    q29: '',
    q30: '',
});

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const notes = ref<Note[]>([]);

// Text segments with their cumulative offsets
const textSegments = ref<TextSegment[]>([
    // Part header box text segments
    { id: 'part3-title', text: 'Part 3', offset: 0 },
    { id: 'part3-desc', text: 'Listen and answer questions 21–30.', offset: 7 },
    // Instruction section text segments
    { id: 0, text: 'Questions 21–24', offset: 43 },
    { id: 1, text: 'Choose the correct letter, A, B or C.', offset: 59 },
    { id: 2, text: 'Honey Bees in Australia', offset: 97 },
    // Question 21
    { id: 'q21', text: '21.', offset: 121 },
    { id: 3, text: 'Where in Australia have Asian honey bees been found in the past?', offset: 125 },
    { id: 4, text: 'Queensland', offset: 191 },
    { id: 5, text: 'New South Wales', offset: 204 },
    { id: 6, text: 'several states', offset: 222 },
    // Question 22
    { id: 'q22', text: '22.', offset: 238 },
    { id: 7, text: 'A problem with Asian honey bees is that they', offset: 242 },
    { id: 8, text: 'attack native bees', offset: 288 },
    { id: 9, text: 'carry parasites', offset: 309 },
    { id: 10, text: 'damage crops', offset: 327 },
    // Question 23
    { id: 'q23', text: '23.', offset: 341 },
    { id: 11, text: 'What point is made about Australian bees?', offset: 345 },
    { id: 12, text: 'Their honey varies in quality', offset: 388 },
    { id: 13, text: 'Their size stops them from pollinating some flowers', offset: 419 },
    { id: 14, text: 'They are sold to customers abroad', offset: 472 },
    // Question 24
    { id: 'q24', text: '24.', offset: 507 },
    { id: 15, text: 'Grant Freeman says that if Asian honey bees got into Australia,', offset: 511 },
    { id: 16, text: "the country's economy would be affected", offset: 575 },
    { id: 17, text: 'they could be used in the study of allergies', offset: 617 },
    { id: 18, text: 'certain areas of agriculture would benefit', offset: 664 },
    // Questions 25-30 section
    { id: 'q25-30-heading', text: 'Questions 25–30', offset: 709 },
    { id: 'q25-30-inst1', text: 'Complete the summary below.', offset: 725 },
    { id: 'q25-30-inst2', text: 'Write ONE WORD ONLY for each answer', offset: 753 },
    { id: 'q25-30-title', text: 'LOOKING FOR ASIAN HONEY BEES', offset: 789 },
    // Question 25
    { id: 'q25-pre', text: 'Birds called Rainbow Bee Eaters eat only', offset: 819 },
    { id: 'q25-post', text: 'and cough up small bits of skeleton and other products in a pellet.', offset: 873 },
    // Question 26
    { id: 'q26-pre', text: 'Researchers go to the locations the bee eaters like to use for', offset: 943 },
    // Question 27
    { id: 'q27-pre', text: 'They collect the pellets and take them to a', offset: 1007 },
    { id: 'q27-post', text: 'for analysis.', offset: 1052 },
    // Question 28
    { id: 'q28-pre', text: 'Here', offset: 1067 },
    { id: 'q28-post', text: 'is used to soften them, and the researchers look for the', offset: 1083 },
    // Question 29
    { id: 'q29-pre', text: '', offset: 1140 },
    { id: 'q29-post', text: 'of Asian bees in the pellets.', offset: 1141 },
    // Question 30
    { id: 'q30-pre', text: 'The benefit of this research is that the result is more', offset: 1171 },
    { id: 'q30-post', text: 'than searching for live Asian bees', offset: 1228 },
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
    let targetId = `question-${questionNumber}`;
    if (questionNumber >= 25 && questionNumber <= 30) {
        targetId = `question-25-30`;
    }

    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        // Add highlight effect
        element.classList.add('highlight-question');
        setTimeout(() => {
            element.classList.remove('highlight-question');
        }, 2000);
    }
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="tooltipHandleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6">
        <!-- Questions Section - Full Width -->
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
            <div class="mb-3 rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part3-title"
                    v-html="getHighlightedSegmentById('part3-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part3-desc"
                    v-html="getHighlightedSegmentById('part3-desc')"></p>
            </div>

            <!-- Instructions Section -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)">
                </p>
            </div>

            <!-- Section Title -->
            <div class="px-2 sm:px-3">
                <h2 class="text-lg font-bold text-gray-900 select-text" data-segment-id="2"
                    v-html="getHighlightedSegmentById(2)"></h2>
            </div>

            <!-- Scrollable Questions Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <!-- Questions 21-24: Single Choice MCQ -->
                    <div class="space-y-2">
                        <!-- Question 21 -->
                        <div id="question-21" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 21"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q21"
                                    v-html="getHighlightedSegmentById('q21')"></span>
                                <div class="min-w-0 flex-1">
                                    <!-- Bookmark Button -->
                                    <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                        @click.stop="toggleFlag(21)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(21)
                                            ? 'border-gray-400 bg-transparent text-red-500'
                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="3"
                                            v-html="getHighlightedSegmentById(3)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q21" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="4" v-html="getHighlightedSegmentById(4)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q21" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q21" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 22 -->
                        <div id="question-22" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 22"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q22"
                                    v-html="getHighlightedSegmentById('q22')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                        @click.stop="toggleFlag(22)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(22)
                                            ? 'border-gray-400 bg-transparent text-red-500'
                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="7"
                                            v-html="getHighlightedSegmentById(7)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q22" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q22" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q22" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 23 -->
                        <div id="question-23" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 23"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q23"
                                    v-html="getHighlightedSegmentById('q23')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                        @click.stop="toggleFlag(23)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(23)
                                            ? 'border-gray-400 bg-transparent text-red-500'
                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="11"
                                            v-html="getHighlightedSegmentById(11)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q23" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q23" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q23" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 24 -->
                        <div id="question-24" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 24"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q24"
                                    v-html="getHighlightedSegmentById('q24')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                        @click.stop="toggleFlag(24)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(24)
                                            ? 'border-gray-400 bg-transparent text-red-500'
                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="15"
                                            v-html="getHighlightedSegmentById(15)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q24" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q24" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q24" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 25-30: Fill in the blank (summary completion) -->
                        <div id="question-25-30" class="relative p-2 sm:p-3">
                            <!-- Section heading -->
                            <div class="mb-2">
                                <h3 class="text-base font-bold text-gray-900 select-text"
                                    data-segment-id="q25-30-heading"
                                    v-html="getHighlightedSegmentById('q25-30-heading')"></h3>
                            </div>
                            <div class="mb-3 p-2">
                                <p class="text-sm text-gray-700 select-text" data-segment-id="q25-30-inst1"
                                    v-html="getHighlightedSegmentById('q25-30-inst1')"></p>
                                <p class="text-sm text-gray-700 select-text" data-segment-id="q25-30-inst2"
                                    v-html="getHighlightedSegmentById('q25-30-inst2')"></p>
                            </div>

                            <!-- Summary box -->
                            <div class="">
                                <h4 class="mb-3 text-base font-bold text-gray-900 select-text"
                                    data-segment-id="q25-30-title" v-html="getHighlightedSegmentById('q25-30-title')">
                                </h4>

                                <ul class="space-y-3 list-disc pl-5">
                                    <!-- Q25 -->
                                    <li class="relative text-base text-gray-900 group">
                                        <div class="inline-flex flex-wrap items-center gap-2">
                                            <span class="select-text " data-segment-id="q25-pre"
                                                v-html="getHighlightedSegmentById('q25-pre')"></span>
                                            <div class="inline-flex items-center gap-2">
                                                <div
                                                    class="inline-flex items-center border border-gray-400 rounded bg-white">
                                                    <input v-model="fillAnswers.q25" type="text" placeholder="25"
                                                        class="w-32 px-2 py-0.5 text-base text-gray-900 focus:border-black focus:outline-none  text-center" />
                                                </div>
                                                <span class="select-text" data-segment-id="q25-post"
                                                    v-html="getHighlightedSegmentById('q25-post')"></span>
                                                <button @click.stop="toggleFlag(25)"
                                                    class="flex h-5 w-5 items-center justify-center rounded transition-all opacity-0 group-hover:opacity-100"
                                                    :class="isQuestionFlagged(25) ? 'text-red-500 opacity-100' : 'text-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </li>

                                    <!-- Q26 -->
                                    <li class="relative text-base text-gray-900 group">
                                        <div class="inline-flex flex-wrap items-center gap-2">
                                            <span class="select-text " data-segment-id="q26-pre"
                                                v-html="getHighlightedSegmentById('q26-pre')"></span>
                                            <div class="inline-flex items-center gap-2">
                                                <div
                                                    class="inline-flex items-center border border-gray-400 rounded bg-white">
                                                    <input v-model="fillAnswers.q26" type="text" placeholder="26"
                                                        class="w-32 px-2 py-0.5 text-base text-gray-900 focus:border-black focus:outline-none rounded text-center" />
                                                </div>
                                                <button @click.stop="toggleFlag(26)"
                                                    class="flex h-5 w-5 items-center justify-center rounded transition-all opacity-0 group-hover:opacity-100"
                                                    :class="isQuestionFlagged(26) ? 'text-red-500 opacity-100' : 'text-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </li>

                                    <!-- Q27 -->
                                    <li class="relative text-base text-gray-900 group">
                                        <div class="inline-flex flex-wrap items-center gap-2">
                                            <span class="select-text " data-segment-id="q27-pre"
                                                v-html="getHighlightedSegmentById('q27-pre')"></span>
                                            <div class="inline-flex items-center gap-2">
                                                <div
                                                    class="inline-flex items-center border border-gray-400 rounded bg-white">
                                                    <input v-model="fillAnswers.q27" type="text" placeholder="27"
                                                        class="w-32 px-2 py-0.5 text-base text-gray-900 focus:border-black focus:outline-none rounded text-center" />
                                                </div>
                                                <span class="select-text" data-segment-id="q27-post"
                                                    v-html="getHighlightedSegmentById('q27-post')"></span>
                                                <button @click.stop="toggleFlag(27)"
                                                    class="flex h-5 w-5 items-center justify-center rounded transition-all opacity-0 group-hover:opacity-100"
                                                    :class="isQuestionFlagged(27) ? 'text-red-500 opacity-100' : 'text-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </li>

                                    <!-- Q28 + Q29 combined sentence -->
                                    <li class="relative text-base text-gray-900 group">
                                        <div class="inline-flex flex-wrap items-center gap-2">
                                            <span class="select-text " data-segment-id="q28-pre"
                                                v-html="getHighlightedSegmentById('q28-pre')"></span>
                                            <div class="inline-flex items-center gap-2">
                                                <div
                                                    class="inline-flex items-center border border-gray-400 rounded bg-white">
                                                    <input v-model="fillAnswers.q28" type="text" placeholder="28"
                                                        class="w-32 px-2 py-0.5 text-base text-gray-900 focus:border-black focus:outline-none rounded text-center" />
                                                </div>
                                                <button @click.stop="toggleFlag(28)"
                                                    class="flex h-5 w-5 items-center justify-center rounded transition-all opacity-0 group-hover:opacity-100"
                                                    :class="isQuestionFlagged(28) ? 'text-red-500 opacity-100' : 'text-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <span class="select-text" data-segment-id="q28-post"
                                                v-html="getHighlightedSegmentById('q28-post')"></span>
                                            <div class="inline-flex items-center gap-2">
                                                <div
                                                    class="inline-flex items-center border border-gray-400 rounded bg-white">
                                                    <input v-model="fillAnswers.q29" type="text" placeholder="29"
                                                        class="w-32 px-2 py-0.5 text-base text-gray-900 focus:border-black focus:outline-none rounded text-center" />
                                                </div>
                                                <span class="select-text" data-segment-id="q29-post"
                                                    v-html="getHighlightedSegmentById('q29-post')"></span>
                                                <button @click.stop="toggleFlag(29)"
                                                    class="flex h-5 w-5 items-center justify-center rounded transition-all opacity-0 group-hover:opacity-100"
                                                    :class="isQuestionFlagged(29) ? 'text-red-500 opacity-100' : 'text-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </li>

                                    <!-- Q30 -->
                                    <li class="relative text-base text-gray-900 group">
                                        <div class="inline-flex flex-wrap items-center gap-2">
                                            <span class="select-text " data-segment-id="q30-pre"
                                                v-html="getHighlightedSegmentById('q30-pre')"></span>
                                            <div class="inline-flex items-center gap-2">
                                                <div
                                                    class="inline-flex items-center border border-gray-400 rounded bg-white">
                                                    <input v-model="fillAnswers.q30" type="text" placeholder="30"
                                                        class="w-32 px-2 py-0.5 text-base text-gray-900 focus:border-black focus:outline-none rounded text-center" />
                                                </div>
                                                <span class="select-text" data-segment-id="q30-post"
                                                    v-html="getHighlightedSegmentById('q30-post')"></span>
                                                <button @click.stop="toggleFlag(30)"
                                                    class="flex h-5 w-5 items-center justify-center rounded transition-all opacity-0 group-hover:opacity-100"
                                                    :class="isQuestionFlagged(30) ? 'text-red-500 opacity-100' : 'text-gray-400 hover:text-gray-600'"
                                                    :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Highlight Tooltips Component -->
    <HighlightTooltips :show-context-menu="showContextMenu" :context-menu-position="contextMenuPosition"
        :show-delete-tooltip="showDeleteTooltip" :delete-tooltip-position="deleteTooltipPosition"
        :show-note-tooltip="showNoteTooltip" :note-tooltip-position="noteTooltipPosition"
        :hovered-note-text="hoveredNoteText" :show-note-input="showNoteInput" :note-input-position="noteInputPosition"
        :selected-text="selectedText" :note-input-text="noteInputText" @open-note-input="openNoteInput"
        @apply-highlight="applyHighlight" @confirm-delete-highlight="confirmDeleteHighlight"
        @close-delete-tooltip="closeDeleteTooltip" @delete-note-from-tooltip="deleteNoteFromTooltip"
        @handle-tooltip-mouse-leave="handleTooltipMouseLeave" @save-note="saveNote" @cancel-note="cancelNote"
        @update:note-input-text="noteInputText = $event" />
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