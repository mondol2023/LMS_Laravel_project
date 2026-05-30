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
    q29: '',
    q30: '',
});

// Multiple choice answers for questions 25-30



// Text segments with their cumulative offsets (each offset = previous offset + previous text length + 1)
// Text segments with their cumulative offsets calculated later
const rawTextSegments = [
    { id: 'part1-title', text: 'Part 3'},
    { id: 'part1-desc', text: 'Listen and answer questions 21–30.'},
    { id: 0, text: 'SECTION 3' },
    { id: 1, text: 'Questions 21–30' },
    { id: 2, text: 'Questions 21–23' },
    { id: 3, text: 'Choose the correct letter, A, B or C.' },
    { id: 4, text: '21. Information on the test is from' },
    { id: 5, text: 'A. the teacher' },
    { id: 6, text: 'B. a class' },
    { id: 7, text: 'C. a handout' },
    { id: 8, text: '22. This assignment is important because' },
    { id: 9, text: 'A. it will become a permanent record' },
    { id: 10, text: 'B. it is a must for passing 11th grade English' },
    { id: 11, text: 'C. it will affect the English level next year' },
    { id: 12, text: '23. Bobby chooses football as project topic because' },
    { id: 13, text: 'A. he often plays football' },
    { id: 14, text: 'B. his father loves football' },
    { id: 15, text: 'C. he is interested in football' },
    { id: 16, text: 'Questions 24–30' },
    { id: 17, text: 'What problems do the speakers identify for this project?' },
    { id: 18, text: 'Choose SEVEN answers from the box and write the letters A–H next to questions 24–30.' },
    { id: 19, text: 'Problems' },
    { id: 20, text: 'too vague' },
    { id: 21, text: 'too factual' },
    { id: 22, text: 'too unreliable' },
    { id: 23, text: 'too noisy' },
    { id: 24, text: 'too long' },
    { id: 25, text: 'too short' },
    { id: 26, text: 'too complicated' },
    { id: 27, text: 'too simple' },
    { id: 28, text: 'Background sounds' },
    { id: 29, text: 'Answers of questions' },
    { id: 30, text: 'One of the questions' },
    { id: 31, text: 'Time of answering' },
    { id: 32, text: 'Recording equipment' },
    { id: 33, text: 'Topic of project' },
    { id: 34, text: 'Report on project' },
];

const buildTextSegments = () => {
    let offset = 0;

    return rawTextSegments.map((segment) => {
        const result = {
            ...segment,
            offset,
        };

        offset += segment.text.length + 1;
        return result;
    });
};

const textSegments = ref(buildTextSegments());
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
    // Handle grouped questions for 25-26, 27-28 and 29-30
    let targetId = `question-${questionNumber}`;


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

// ── Drag-and-drop state ──────────────────────────────────────────
const draggingValue = ref(null);
const dropTarget = ref(null);

// The 8 options derived from text segments 20–27
const matchingOptions = [
    { value: 'A', label: 'too vague' },        // segment 20
    { value: 'B', label: 'too factual' },       // segment 21
    { value: 'C', label: 'too unreliable' },    // segment 22
    { value: 'D', label: 'too noisy' },         // segment 23
    { value: 'E', label: 'too long' },          // segment 24
    { value: 'F', label: 'too short' },         // segment 25
    { value: 'G', label: 'too complicated' },   // segment 26
    { value: 'H', label: 'too simple' },        // segment 27
];

const matchingQuestions = ['q24', 'q25', 'q26', 'q27', 'q28', 'q29', 'q30'];

// Returns true if this option letter is already dropped on any question
const isOptionUsed = (value: string) =>
    matchingQuestions.some((q) => answers.value[q] === value);

const getLabelForValue = (value) =>
    matchingOptions.find((o) => o.value === value)?.label ?? '';

// ── Drag handlers ────────────────────────────────────────────────
const onDragStart = (event, value) => {
    draggingValue.value = value;
    event.dataTransfer.setData('text/plain', value);
    event.dataTransfer.effectAllowed = 'move';
};

const onDragEnd = () => {
    draggingValue.value = null;
    dropTarget.value = null;
};

const onDragOver = (event, qKey) => {
    event.dataTransfer.dropEffect = 'move';
    dropTarget.value = qKey;
};

const onDragLeave = (qKey) => {
    if (dropTarget.value === qKey) dropTarget.value = null;
};

const onDrop = (event: DragEvent, qKey: string) => {
    const value = event.dataTransfer?.getData('text/plain');
    if (value) answers.value[qKey] = value;
    dropTarget.value = null;
    draggingValue.value = null;
};

const clearAnswer = (qKey: string) => {
    answers.value[qKey] = '';
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
            <div class="part-header-box mb-3 rounded border border-gray-200 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3
                    class="text-base font-bold text-gray-900 select-text"
                    data-segment-id="part1-title"
                    v-html="getHighlightedSegmentById('part1-title')"
                ></h3>
                <p
                    class="text-sm text-gray-700 select-text"
                    data-segment-id="part1-desc"
                    v-html="getHighlightedSegmentById('part1-desc')"
                ></p>
            </div>

            <!-- Header -->


            <!-- Scrollable Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <div class="space-y-4 p-3 select-text sm:p-4">

                        <!-- ── Questions 21–23: Radio MCQ ── -->
                        <div class="mb-2">
                            <p class="text-sm font-semibold text-gray-900 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></p>
                        </div>

                        <!-- Question 21 -->
                        <div
                            id="question-21"
                            class="relative rounded  border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 21"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                @click.stop="toggleFlag(21)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q21" value="A" name="q21" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q21" value="B" name="q21" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q21" value="C" name="q21" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 22 -->
                        <div
                            id="question-22"
                            class="relative rounded  border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 22"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                @click.stop="toggleFlag(22)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q22" value="A" name="q22" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q22" value="B" name="q22" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q22" value="C" name="q22" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 23 -->
                        <div
                            id="question-23"
                            class="relative rounded  border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 23"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                @click.stop="toggleFlag(23)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q23" value="A" name="q23" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q23" value="B" name="q23" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q23" value="C" name="q23" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- ── Questions 24–30: Drag-and-Drop Matching ── -->
                        <div class="mt-6 border-t border-gray-300 pt-6">

                            <!-- Section header -->
                            <div class="mb-4">
                                <p class="text-sm font-semibold text-gray-900 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></p>
                                <p class="text-sm text-gray-700 select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></p>
                                <p class="mt-1 text-sm font-bold text-gray-900 select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></p>
                            </div>

                            <!-- Problems Bank -->
                            <div class="grid grid-cols-[260px_1fr] gap-6">

                            <!-- LEFT SIDE: OPTIONS -->
                            <div class="rounded border border-gray-200 bg-gray-50 p-3">
                                <p class="mb-2 text-sm font-bold text-gray-900">Problems</p>

                                <div class="space-y-2">
                                    <template v-for="opt in matchingOptions" :key="opt.value">

                                        <div
                                            v-if="!isOptionUsed(opt.value)"
                                            draggable="true"
                                            @dragstart="onDragStart($event, opt.value)"
                                            @dragend="onDragEnd"
                                            class="flex cursor-grab items-center gap-2 rounded border bg-white px-2 py-2 text-sm shadow hover:border-gray-500"
                                        >
                                            <span class="flex h-5 w-5 items-center justify-center rounded  text-black text-xs font-bold">
                                                {{ opt.value }}
                                            </span>

                                            <span>{{ opt.label }}</span>
                                        </div>

                                        <!-- Used option -->
                                        <div
                                            v-else
                                            class="flex cursor-not-allowed items-center gap-2 rounded border border-dashed bg-gray-200 px-2 py-2 text-sm text-gray-400 line-through"
                                        >
                                            <span class="flex h-5 w-5 items-center justify-center rounded  text-black text-xs font-bold">
                                                {{ opt.value }}
                                            </span>

                                            <span>{{ opt.label }}</span>
                                        </div>

                                    </template>
                                </div>
                            </div>

                            <!-- RIGHT SIDE: QUESTIONS -->
                            <div class="space-y-3">

                                <!-- your existing question 24-30 blocks go here -->

                                <!-- Question 24 -->
                                <div
                                    id="question-24"
                                    class="relative rounded border border-gray-200 p-3"
                                    @mouseenter="hoveredQuestion = 24"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <button
                                        v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                        @click.stop="toggleFlag(24)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                    <div class="flex flex-wrap items-center gap-3 pr-8">
                                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded ">
                                            <span class="text-[14px] font-bold text-black">24</span>
                                        </div>
                                        <span class="font-medium text-gray-800 select-text" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></span>
                                        <!-- Drop Zone -->
                                        <div
                                            @dragover.prevent="onDragOver($event, 'q24')"
                                            @dragleave="onDragLeave('q24')"
                                            @drop.prevent="onDrop($event, 'q24')"
                                            class="flex min-h-[36px] min-w-[110px] items-center justify-center rounded border-2 px-2 py-1 transition-all"
                                            :class="dropTarget === 'q24'
                                                ? 'border-black bg-gray-100 scale-105'
                                                : answers.q24
                                                    ? 'border-gray-400 bg-white'
                                                    : 'border-dashed border-gray-300 bg-gray-50'"
                                        >
                                            <template v-if="answers.q24">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-black text-[11px] font-bold text-white">{{ answers.q24 }}</span>
                                                    <span class="text-sm text-gray-700">{{ getLabelForValue(answers.q24) }}</span>
                                                    <button @click.stop="clearAnswer('q24')" class="ml-1 flex h-4 w-4 items-center justify-center rounded-full bg-gray-200 text-gray-500 hover:bg-red-100 hover:text-red-500 transition-colors" title="Remove">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span class="text-xs text-gray-400">Drop here</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Question 25 -->
                                <div
                                    id="question-25"
                                    class="relative rounded border border-gray-200 p-3"
                                    @mouseenter="hoveredQuestion = 25"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <button
                                        v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                        @click.stop="toggleFlag(25)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                    <div class="flex flex-wrap items-center gap-3 pr-8">
                                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded ">
                                            <span class="text-[14px] font-bold text-black">25</span>
                                        </div>
                                        <span class="font-medium text-gray-800 select-text" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></span>
                                        <div
                                            @dragover.prevent="onDragOver($event, 'q25')"
                                            @dragleave="onDragLeave('q25')"
                                            @drop.prevent="onDrop($event, 'q25')"
                                            class="flex min-h-[36px] min-w-[110px] items-center justify-center rounded border-2 px-2 py-1 transition-all"
                                            :class="dropTarget === 'q25' ? 'border-black bg-gray-100 scale-105' : answers.q25 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-300 bg-gray-50'"
                                        >
                                            <template v-if="answers.q25">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-black text-[11px] font-bold text-white">{{ answers.q25 }}</span>
                                                    <span class="text-sm text-gray-700">{{ getLabelForValue(answers.q25) }}</span>
                                                    <button @click.stop="clearAnswer('q25')" class="ml-1 flex h-4 w-4 items-center justify-center rounded-full bg-gray-200 text-gray-500 hover:bg-red-100 hover:text-red-500 transition-colors" title="Remove">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span class="text-xs text-gray-400">Drop here</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Question 26 -->
                                <div
                                    id="question-26"
                                    class="relative rounded border border-gray-200 p-3"
                                    @mouseenter="hoveredQuestion = 26"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <button
                                        v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                        @click.stop="toggleFlag(26)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                    <div class="flex flex-wrap items-center gap-3 pr-8">
                                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded">
                                            <span class="text-[14px] font-bold text-black">26</span>
                                        </div>
                                        <span class="font-medium text-gray-800 select-text" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></span>
                                        <div
                                            @dragover.prevent="onDragOver($event, 'q26')"
                                            @dragleave="onDragLeave('q26')"
                                            @drop.prevent="onDrop($event, 'q26')"
                                            class="flex min-h-[36px] min-w-[110px] items-center justify-center rounded border-2 px-2 py-1 transition-all"
                                            :class="dropTarget === 'q26' ? 'border-black bg-gray-100 scale-105' : answers.q26 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-300 bg-gray-50'"
                                        >
                                            <template v-if="answers.q26">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-black text-[11px] font-bold text-white">{{ answers.q26 }}</span>
                                                    <span class="text-sm text-gray-700">{{ getLabelForValue(answers.q26) }}</span>
                                                    <button @click.stop="clearAnswer('q26')" class="ml-1 flex h-4 w-4 items-center justify-center rounded-full bg-gray-200 text-gray-500 hover:bg-red-100 hover:text-red-500 transition-colors" title="Remove">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span class="text-xs text-gray-400">Drop here</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Question 27 -->
                                <div
                                    id="question-27"
                                    class="relative rounded border border-gray-200 p-3"
                                    @mouseenter="hoveredQuestion = 27"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <button
                                        v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                        @click.stop="toggleFlag(27)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                    <div class="flex flex-wrap items-center gap-3 pr-8">
                                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded ">
                                            <span class="text-[14px] font-bold text-black">27</span>
                                        </div>
                                        <span class="font-medium text-gray-800 select-text" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></span>
                                        <div
                                            @dragover.prevent="onDragOver($event, 'q27')"
                                            @dragleave="onDragLeave('q27')"
                                            @drop.prevent="onDrop($event, 'q27')"
                                            class="flex min-h-[36px] min-w-[110px] items-center justify-center rounded border-2 px-2 py-1 transition-all"
                                            :class="dropTarget === 'q27' ? 'border-black bg-gray-100 scale-105' : answers.q27 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-300 bg-gray-50'"
                                        >
                                            <template v-if="answers.q27">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-black text-[11px] font-bold text-white">{{ answers.q27 }}</span>
                                                    <span class="text-sm text-gray-700">{{ getLabelForValue(answers.q27) }}</span>
                                                    <button @click.stop="clearAnswer('q27')" class="ml-1 flex h-4 w-4 items-center justify-center rounded-full bg-gray-200 text-gray-500 hover:bg-red-100 hover:text-red-500 transition-colors" title="Remove">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span class="text-xs text-gray-400">Drop here</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Question 28 -->
                                <div
                                    id="question-28"
                                    class="relative rounded border border-gray-200 p-3"
                                    @mouseenter="hoveredQuestion = 28"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <button
                                        v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                        @click.stop="toggleFlag(28)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                    <div class="flex flex-wrap items-center gap-3 pr-8">
                                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded ">
                                            <span class="text-[14px] font-bold text-black">28</span>
                                        </div>
                                        <span class="font-medium text-gray-800 select-text" data-segment-id="32" v-html="getHighlightedSegmentById(32)"></span>
                                        <div
                                            @dragover.prevent="onDragOver($event, 'q28')"
                                            @dragleave="onDragLeave('q28')"
                                            @drop.prevent="onDrop($event, 'q28')"
                                            class="flex min-h-[36px] min-w-[110px] items-center justify-center rounded border-2 px-2 py-1 transition-all"
                                            :class="dropTarget === 'q28' ? 'border-black bg-gray-100 scale-105' : answers.q28 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-300 bg-gray-50'"
                                        >
                                            <template v-if="answers.q28">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-black text-[11px] font-bold text-white">{{ answers.q28 }}</span>
                                                    <span class="text-sm text-gray-700">{{ getLabelForValue(answers.q28) }}</span>
                                                    <button @click.stop="clearAnswer('q28')" class="ml-1 flex h-4 w-4 items-center justify-center rounded-full bg-gray-200 text-gray-500 hover:bg-red-100 hover:text-red-500 transition-colors" title="Remove">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span class="text-xs text-gray-400">Drop here</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Question 29 -->
                                <div
                                    id="question-29"
                                    class="relative rounded border border-gray-200 p-3"
                                    @mouseenter="hoveredQuestion = 29"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <button
                                        v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                                        @click.stop="toggleFlag(29)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(29) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(29) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                    <div class="flex flex-wrap items-center gap-3 pr-8">
                                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded ">
                                            <span class="text-[14px] font-bold text-black">29</span>
                                        </div>
                                        <span class="font-medium text-gray-800 select-text" data-segment-id="33" v-html="getHighlightedSegmentById(33)"></span>
                                        <div
                                            @dragover.prevent="onDragOver($event, 'q29')"
                                            @dragleave="onDragLeave('q29')"
                                            @drop.prevent="onDrop($event, 'q29')"
                                            class="flex min-h-[36px] min-w-[110px] items-center justify-center rounded border-2 px-2 py-1 transition-all"
                                            :class="dropTarget === 'q29' ? 'border-black bg-gray-100 scale-105' : answers.q29 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-300 bg-gray-50'"
                                        >
                                            <template v-if="answers.q29">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-black text-[11px] font-bold text-white">{{ answers.q29 }}</span>
                                                    <span class="text-sm text-gray-700">{{ getLabelForValue(answers.q29) }}</span>
                                                    <button @click.stop="clearAnswer('q29')" class="ml-1 flex h-4 w-4 items-center justify-center rounded-full bg-gray-200 text-gray-500 hover:bg-red-100 hover:text-red-500 transition-colors" title="Remove">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span class="text-xs text-gray-400">Drop here</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Question 30 -->
                                <div
                                    id="question-30"
                                    class="relative rounded border border-gray-200 p-3"
                                    @mouseenter="hoveredQuestion = 30"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <button
                                        v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                                        @click.stop="toggleFlag(30)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                                    </button>
                                    <div class="flex flex-wrap items-center gap-3 pr-8">
                                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded ">
                                            <span class="text-[14px] font-bold text-black">30</span>
                                        </div>
                                        <span class="font-medium text-gray-800 select-text" data-segment-id="34" v-html="getHighlightedSegmentById(34)"></span>
                                        <div
                                            @dragover.prevent="onDragOver($event, 'q30')"
                                            @dragleave="onDragLeave('q30')"
                                            @drop.prevent="onDrop($event, 'q30')"
                                            class="flex min-h-[36px] min-w-[110px] items-center justify-center rounded border-2 px-2 py-1 transition-all"
                                            :class="dropTarget === 'q30' ? 'border-black bg-gray-100 scale-105' : answers.q30 ? 'border-gray-400 bg-white' : 'border-dashed border-gray-300 bg-gray-50'"
                                        >
                                            <template v-if="answers.q30">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-black text-[11px] font-bold text-white">{{ answers.q30 }}</span>
                                                    <span class="text-sm text-gray-700">{{ getLabelForValue(answers.q30) }}</span>
                                                    <button @click.stop="clearAnswer('q30')" class="ml-1 flex h-4 w-4 items-center justify-center rounded-full bg-gray-200 text-gray-500 hover:bg-red-100 hover:text-red-500 transition-colors" title="Remove">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <span class="text-xs text-gray-400">Drop here</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- /space-y-3 -->
                        </div><!-- /matching section -->
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

.part-header-box {
    background-color: #F1F2EC;
}
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
