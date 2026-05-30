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

const hoveredQuestion = ref<number | null>(null);

const isQuestionFlagged = (questionNumber: number): boolean => {
    return props.flaggedQuestions.has(questionNumber);
};

const toggleFlag = (questionNumber: number) => {
    emit('toggleFlag', questionNumber);
};

const contentZoom = computed(() => ({
    zoom: props.fontSize / 16,
}));

// ── Answers ───────────────────────────────────────────────────────────────────
// Q21–23: single choice A/B/C
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

// ── Highlight ─────────────────────────────────────────────────────────────────
const contentTextRef = ref<HTMLDivElement | null>(null);
const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();
const notes = ref<Note[]>([]);

// ── Text segments ─────────────────────────────────────────────────────────────
const textSegments = ref<TextSegment[]>([
    // Header
    { id: 'part3-title', text: 'Part 3',                              offset: 0   },
    { id: 'part3-desc',  text: 'Listen and answer questions 21–30.',  offset: 7   },

    // Q21-23 block
    { id: 'q21-23-title', text: 'Questions 21–23',                    offset: 43  },
    { id: 'q21-23-inst',  text: 'Choose the correct letter, A, B or C.', offset: 59 },

    // Q21
    { id: 'q21-num',  text: '21.',                                               offset: 100 },
    { id: 'q21-text', text: 'Information on the test is from',                   offset: 104 },
    { id: 'q21-a',    text: 'the teacher',                                        offset: 137 },
    { id: 'q21-b',    text: 'a class',                                            offset: 150 },
    { id: 'q21-c',    text: 'a handout',                                          offset: 159 },

    // Q22
    { id: 'q22-num',  text: '22.',                                               offset: 170 },
    { id: 'q22-text', text: 'This assignment is important because',              offset: 174 },
    { id: 'q22-a',    text: 'it will become a permanent record',                  offset: 212 },
    { id: 'q22-b',    text: 'it is a must for passing 11th grade English',        offset: 247 },
    { id: 'q22-c',    text: 'it will affect the English level next year',          offset: 291 },

    // Q23
    { id: 'q23-num',  text: '23.',                                               offset: 335 },
    { id: 'q23-text', text: 'Bobby chooses football as project topic because',   offset: 339 },
    { id: 'q23-a',    text: 'he often plays football',                            offset: 388 },
    { id: 'q23-b',    text: 'his father loves football',                           offset: 413 },
    { id: 'q23-c',    text: 'he is interested in football',                        offset: 440 },

    // Q24-30 block
    { id: 'q24-30-title', text: 'Questions 24–30',                               offset: 475 },
    { id: 'q24-30-inst1', text: 'What problems do the speakers identify for this project?', offset: 492 },
    { id: 'q24-30-inst2', text: 'Choose SEVEN answers from the box and write the letters A–H next to questions 24–30.', offset: 553 },

    // Problems box
    { id: 'prob-box-title', text: 'Problems',                                    offset: 645 },
    { id: 'prob-a', text: 'A  too vague',                                         offset: 657 },
    { id: 'prob-b', text: 'B  too factual',                                       offset: 671 },
    { id: 'prob-c', text: 'C  too unreliable',                                    offset: 686 },
    { id: 'prob-d', text: 'D  too noisy',                                         offset: 704 },
    { id: 'prob-e', text: 'E  too long',                                          offset: 717 },
    { id: 'prob-f', text: 'F  too short',                                         offset: 729 },
    { id: 'prob-g', text: 'G  too complicated',                                   offset: 742 },
    { id: 'prob-h', text: 'H  too simple',                                        offset: 762 },

    // Q24-30 statement texts
    { id: 'q24-num',  text: '24.',                                               offset: 778 },
    { id: 'q24-text', text: 'Background sounds',                                 offset: 782 },
    { id: 'q25-num',  text: '25.',                                               offset: 802 },
    { id: 'q25-text', text: 'Answers of questions',                              offset: 806 },
    { id: 'q26-num',  text: '26.',                                               offset: 829 },
    { id: 'q26-text', text: 'One of the questions',                              offset: 833 },
    { id: 'q27-num',  text: '27.',                                               offset: 856 },
    { id: 'q27-text', text: 'Time of answering',                                 offset: 860 },
    { id: 'q28-num',  text: '28.',                                               offset: 880 },
    { id: 'q28-text', text: 'Recording equipment',                               offset: 884 },
    { id: 'q29-num',  text: '29.',                                               offset: 906 },
    { id: 'q29-text', text: 'Topic of project',                                  offset: 910 },
    { id: 'q30-num',  text: '30.',                                               offset: 929 },
    { id: 'q30-text', text: 'Report on project',                                 offset: 933 },
]);

// ── Tooltip composable ────────────────────────────────────────────────────────
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

// ── Highlighted segment helper ────────────────────────────────────────────────
const getHighlightedSegmentById = (segmentId: number | string) => {
    const segment = textSegments.value.find((s) => s.id === segmentId);
    if (!segment) return '';

    const plainText = segment.text;
    const segmentOffset = segment.offset;
    const segmentEnd = segmentOffset + plainText.length;

    const overlappingHighlights = highlights.value.filter((h) => h.start_offset < segmentEnd && h.end_offset > segmentOffset);
    const overlappingNotes = notes.value.filter((n) => n.start < segmentEnd && n.end > segmentOffset);

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) return plainText;

    const sorted = [
        ...overlappingHighlights.map((h) => ({ start: h.start_offset, end: h.end_offset, type: 'highlight' as const, color: h.color, id: h.id })),
        ...overlappingNotes.map((n) => ({ start: n.start, end: n.end, type: 'note' as const, color: 'blue', id: n.id, noteText: n.note })),
    ].sort((a, b) => b.start - a.start);

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


// Drag and drop functionality for Q24-30
const draggedOption = ref<string | null>(null);
const dragOverQuestion = ref<number | null>(null);

// Problem options for Q24-30
const problemOptions = [
    { value: 'A', label: 'too vague' },
    { value: 'B', label: 'too factual' },
    { value: 'C', label: 'too unreliable' },
    { value: 'D', label: 'too noisy' },
    { value: 'E', label: 'too long' },
    { value: 'F', label: 'too short' },
    { value: 'G', label: 'too complicated' },
    { value: 'H', label: 'too simple' },
];

const handleDragStart = (e: DragEvent, option: string) => {
    draggedOption.value = option;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', option);
    }
};

const handleDragEnd = () => {
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const handleDragOver = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'move';
    }
    dragOverQuestion.value = questionNum;
};

const handleDragLeave = () => {
    dragOverQuestion.value = null;
};

const handleDrop = (e: DragEvent, questionNum: number) => {
    e.preventDefault();
    const option = e.dataTransfer?.getData('text/plain') || draggedOption.value;
    if (option) {
        const key = `q${questionNum}` as keyof typeof answers.value;
        answers.value[key] = option;
    }
    draggedOption.value = null;
    dragOverQuestion.value = null;
};

const clearAnswer = (questionNum: number) => {
    const key = `q${questionNum}` as keyof typeof answers.value;
    answers.value[key] = '';
};


const usedProblemOptions = computed(() =>
    new Set([
        answers.value.q24, answers.value.q25, answers.value.q26, answers.value.q27,
        answers.value.q28, answers.value.q29, answers.value.q30,
    ].filter(Boolean))
);

const availableProblemOptions = computed(() =>
    problemOptions.filter(opt => !usedProblemOptions.value.has(opt.value))
);
const getProblemOptionLabel = (value: string): string => {
    const option = problemOptions.find(opt => opt.value === value);
    return option ? `${option.value}  ${option.label}` : value;
};

// ── Expose ────────────────────────────────────────────────────────────────────
const getAnswers = () => ({ ...answers.value });

const handleContentTextSelect = () => { tooltipHandleContentTextSelect(); };

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;
    notes.value = notes.value.filter((n) => !(n.start < selectionRange.value!.end && n.end > selectionRange.value!.start));
    addHighlight(selectedText.value, color, selectionRange.value.start, selectionRange.value.end);
    showContextMenu.value = false;
    window.getSelection()?.removeAllRanges();
};

const saveNote = () => {
    if (!selectionRange.value || !selectedText.value || !noteInputText.value.trim()) return;
    const newStart = selectionRange.value.start;
    const newEnd = selectionRange.value.end;
    findOverlappingHighlights(newStart, newEnd).forEach((h) => deleteHighlight(h.id));
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
    const noteId = Date.now();
    notes.value.push({ id: noteId, text: selectedText.value, selectedText: selectedText.value, note: noteInputText.value.trim(), start: newStart, end: newEnd });
    noteInputText.value = '';
    showNoteInput.value = false;
    window.getSelection()?.removeAllRanges();
};

const deleteNote = (id: number) => { notes.value = notes.value.filter((note) => note.id !== id); };

const confirmDeleteHighlight = () => {
    if (highlightToDelete.value !== null) {
        deleteHighlight(highlightToDelete.value);
        closeDeleteTooltip();
    }
};

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
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
    }
};

onMounted(() => { setupEventListeners(); });
onBeforeUnmount(() => { cleanupEventListeners(); });

defineExpose({ getAnswers, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="tooltipHandleContentClick" class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header -->
            <div class="mb-3 rounded border border-gray-200 bg-[#F1F2EC] px-4 py-3" @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part3-title" v-html="getHighlightedSegmentById('part3-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part3-desc" v-html="getHighlightedSegmentById('part3-desc')"></p>
            </div>

            <!-- Q21-23 Instructions -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q21-23-title" v-html="getHighlightedSegmentById('q21-23-title')"></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="q21-23-inst" v-html="getHighlightedSegmentById('q21-23-inst')"></p>
            </div>

            <!-- Scrollable content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <div class="space-y-2">

                        <!-- ════════════════════════════════
                             Question 21
                             ════════════════════════════════ -->
                        <div
                            id="question-21"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 21"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q21-num" v-html="getHighlightedSegmentById('q21-num')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                        @click.stop="toggleFlag(21)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q21-text" v-html="getHighlightedSegmentById('q21-text')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q21" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="q21-a" v-html="getHighlightedSegmentById('q21-a')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q21" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="q21-b" v-html="getHighlightedSegmentById('q21-b')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q21" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="q21-c" v-html="getHighlightedSegmentById('q21-c')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ════════════════════════════════
                             Question 22
                             ════════════════════════════════ -->
                        <div
                            id="question-22"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 22"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q22-num" v-html="getHighlightedSegmentById('q22-num')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                        @click.stop="toggleFlag(22)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q22-text" v-html="getHighlightedSegmentById('q22-text')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q22" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="q22-a" v-html="getHighlightedSegmentById('q22-a')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q22" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="q22-b" v-html="getHighlightedSegmentById('q22-b')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q22" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="q22-c" v-html="getHighlightedSegmentById('q22-c')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ════════════════════════════════
                             Question 23
                             ════════════════════════════════ -->
                        <div
                            id="question-23"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 23"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q23-num" v-html="getHighlightedSegmentById('q23-num')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                        @click.stop="toggleFlag(23)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" /></svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q23-text" v-html="getHighlightedSegmentById('q23-text')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q23" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="q23-a" v-html="getHighlightedSegmentById('q23-a')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q23" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="q23-b" v-html="getHighlightedSegmentById('q23-b')"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q23" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="q23-c" v-html="getHighlightedSegmentById('q23-c')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ───── Q24-30 ───── -->
<div class="p-2 sm:p-3">
    <div class="mb-4">
        <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q24-30-title"
            v-html="getHighlightedSegmentById('q24-30-title')"></p>
        <div class="mt-1 space-y-0.5">
            <p class="text-sm text-gray-800 select-text" data-segment-id="q24-30-inst1"
                v-html="getHighlightedSegmentById('q24-30-inst1')"></p>
            <p class="text-sm text-gray-800 select-text" data-segment-id="q24-30-inst2"
                v-html="getHighlightedSegmentById('q24-30-inst2')"></p>
        </div>
    </div>

    

    <!-- Drop zones + options side-by-side -->
    <div class="flex flex-wrap my-4 ">
        <!-- Drop zones for Q24-30 -->
        <div class="space-y-5 min-w-0  pr-4">

            <!-- Q24 -->
            <div id="question-24" class="flex items-center gap-3 flex-wrap"
                @mouseenter="hoveredQuestion = 24" @mouseleave="hoveredQuestion = null">
                <span class="text-base font-bold text-gray-900 select-none shrink-0" data-segment-id="q24-num"
                    v-html="getHighlightedSegmentById('q24-num')"></span>
                <span class="text-base text-gray-700 select-text" data-segment-id="q24-text"
                    v-html="getHighlightedSegmentById('q24-text')"></span>
                <span
                    class="inline-flex min-h-9 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all cursor-pointer"
                    :class="dragOverQuestion === 24 ? 'border-blue-500 bg-blue-50' : answers.q24 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 24)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 24)" @click="clearAnswer(24)"
                    :title="answers.q24 ? 'Click to clear' : 'Drop answer here'">
                    <span v-if="answers.q24">{{ getProblemOptionLabel(answers.q24) }}</span>
                    <span v-else class="font-bold text-gray-900">24</span>
                </span>
                <div class="w-7 h-7 shrink-0">
                    <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                        @click.stop="toggleFlag(24)"
                        class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                        :class="isQuestionFlagged(24) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Q25 -->
            <div id="question-25" class="flex items-center gap-3 flex-wrap"
                @mouseenter="hoveredQuestion = 25" @mouseleave="hoveredQuestion = null">
                <span class="text-base font-bold text-gray-900 select-none shrink-0" data-segment-id="q25-num"
                    v-html="getHighlightedSegmentById('q25-num')"></span>
                <span class="text-base text-gray-700 select-text" data-segment-id="q25-text"
                    v-html="getHighlightedSegmentById('q25-text')"></span>
                <span
                    class="inline-flex min-h-9 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all cursor-pointer"
                    :class="dragOverQuestion === 25 ? 'border-blue-500 bg-blue-50' : answers.q25 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 25)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 25)" @click="clearAnswer(25)"
                    :title="answers.q25 ? 'Click to clear' : 'Drop answer here'">
                    <span v-if="answers.q25">{{ getProblemOptionLabel(answers.q25) }}</span>
                    <span v-else class="font-bold text-gray-900">25</span>
                </span>
                <div class="w-7 h-7 shrink-0">
                    <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                        @click.stop="toggleFlag(25)"
                        class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                        :class="isQuestionFlagged(25) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Q26 -->
            <div id="question-26" class="flex items-center gap-3 flex-wrap"
                @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                <span class="text-base font-bold text-gray-900 select-none shrink-0" data-segment-id="q26-num"
                    v-html="getHighlightedSegmentById('q26-num')"></span>
                <span class="text-base text-gray-700 select-text" data-segment-id="q26-text"
                    v-html="getHighlightedSegmentById('q26-text')"></span>
                <span
                    class="inline-flex min-h-9 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all cursor-pointer"
                    :class="dragOverQuestion === 26 ? 'border-blue-500 bg-blue-50' : answers.q26 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 26)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 26)" @click="clearAnswer(26)"
                    :title="answers.q26 ? 'Click to clear' : 'Drop answer here'">
                    <span v-if="answers.q26">{{ getProblemOptionLabel(answers.q26) }}</span>
                    <span v-else class="font-bold text-gray-900">26</span>
                </span>
                <div class="w-7 h-7 shrink-0">
                    <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                        @click.stop="toggleFlag(26)"
                        class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                        :class="isQuestionFlagged(26) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Q27 -->
            <div id="question-27" class="flex items-center gap-3 flex-wrap"
                @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                <span class="text-base font-bold text-gray-900 select-none shrink-0" data-segment-id="q27-num"
                    v-html="getHighlightedSegmentById('q27-num')"></span>
                <span class="text-base text-gray-700 select-text" data-segment-id="q27-text"
                    v-html="getHighlightedSegmentById('q27-text')"></span>
                <span
                    class="inline-flex min-h-9 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all cursor-pointer"
                    :class="dragOverQuestion === 27 ? 'border-blue-500 bg-blue-50' : answers.q27 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 27)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 27)" @click="clearAnswer(27)"
                    :title="answers.q27 ? 'Click to clear' : 'Drop answer here'">
                    <span v-if="answers.q27">{{ getProblemOptionLabel(answers.q27) }}</span>
                    <span v-else class="font-bold text-gray-900">27</span>
                </span>
                <div class="w-7 h-7 shrink-0">
                    <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                        @click.stop="toggleFlag(27)"
                        class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                        :class="isQuestionFlagged(27) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Q28 -->
            <div id="question-28" class="flex items-center gap-3 flex-wrap"
                @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
                <span class="text-base font-bold text-gray-900 select-none shrink-0" data-segment-id="q28-num"
                    v-html="getHighlightedSegmentById('q28-num')"></span>
                <span class="text-base text-gray-700 select-text" data-segment-id="q28-text"
                    v-html="getHighlightedSegmentById('q28-text')"></span>
                <span
                    class="inline-flex min-h-9 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all cursor-pointer"
                    :class="dragOverQuestion === 28 ? 'border-blue-500 bg-blue-50' : answers.q28 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 28)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 28)" @click="clearAnswer(28)"
                    :title="answers.q28 ? 'Click to clear' : 'Drop answer here'">
                    <span v-if="answers.q28">{{ getProblemOptionLabel(answers.q28) }}</span>
                    <span v-else class="font-bold text-gray-900">28</span>
                </span>
                <div class="w-7 h-7 shrink-0">
                    <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                        @click.stop="toggleFlag(28)"
                        class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                        :class="isQuestionFlagged(28) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Q29 -->
            <div id="question-29" class="flex items-center gap-3 flex-wrap"
                @mouseenter="hoveredQuestion = 29" @mouseleave="hoveredQuestion = null">
                <span class="text-base font-bold text-gray-900 select-none shrink-0" data-segment-id="q29-num"
                    v-html="getHighlightedSegmentById('q29-num')"></span>
                <span class="text-base text-gray-700 select-text" data-segment-id="q29-text"
                    v-html="getHighlightedSegmentById('q29-text')"></span>
                <span
                    class="inline-flex min-h-9 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all cursor-pointer"
                    :class="dragOverQuestion === 29 ? 'border-blue-500 bg-blue-50' : answers.q29 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 29)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 29)" @click="clearAnswer(29)"
                    :title="answers.q29 ? 'Click to clear' : 'Drop answer here'">
                    <span v-if="answers.q29">{{ getProblemOptionLabel(answers.q29) }}</span>
                    <span v-else class="font-bold text-gray-900">29</span>
                </span>
                <div class="w-7 h-7 shrink-0">
                    <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29)"
                        @click.stop="toggleFlag(29)"
                        class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                        :class="isQuestionFlagged(29) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Q30 -->
            <div id="question-30" class="flex items-center gap-3 flex-wrap"
                @mouseenter="hoveredQuestion = 30" @mouseleave="hoveredQuestion = null">
                <span class="text-base font-bold text-gray-900 select-none shrink-0" data-segment-id="q30-num"
                    v-html="getHighlightedSegmentById('q30-num')"></span>
                <span class="text-base text-gray-700 select-text" data-segment-id="q30-text"
                    v-html="getHighlightedSegmentById('q30-text')"></span>
                <span
                    class="inline-flex min-h-9 min-w-32 items-center justify-center rounded border-2 border-dashed px-3 py-1.5 text-center text-sm transition-all cursor-pointer"
                    :class="dragOverQuestion === 30 ? 'border-blue-500 bg-blue-50' : answers.q30 ? 'border-gray-900 bg-white font-medium' : 'border-gray-400 bg-white'"
                    @dragover="handleDragOver($event, 30)" @dragleave="handleDragLeave"
                    @drop="handleDrop($event, 30)" @click="clearAnswer(30)"
                    :title="answers.q30 ? 'Click to clear' : 'Drop answer here'">
                    <span v-if="answers.q30">{{ getProblemOptionLabel(answers.q30) }}</span>
                    <span v-else class="font-bold text-gray-900">30</span>
                </span>
                <div class="w-7 h-7 shrink-0">
                    <button v-show="hoveredQuestion === 30 || isQuestionFlagged(30)"
                        @click.stop="toggleFlag(30)"
                        class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-60 hover:opacity-100"
                        :class="isQuestionFlagged(30) ? 'border-gray-400 text-red-500 opacity-100' : 'border-gray-300 text-gray-400 hover:border-gray-400 hover:text-gray-600'">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                        </svg>
                    </button>
                </div>
            </div>

        </div>

        <!-- Draggable options panel -->
<div class="w-56 shrink-0 self-start sticky top-12">
    <p class="mb-3 text-sm font-semibold text-gray-700">Drag options to fill blanks:</p>
    <div class="border border-gray-900 p-3 bg-white">
        <div class="space-y-2 min-h-[40px]">
            <div v-for="option in availableProblemOptions" :key="option.value"
                class="flex cursor-grab items-center gap-2 rounded border border-gray-300 px-3 py-2 transition-all hover:border-gray-500 hover:bg-gray-50 active:cursor-grabbing"
                :class="{ 'opacity-50': draggedOption === option.value }"
                draggable="true" @dragstart="handleDragStart($event, option.value)"
                @dragend="handleDragEnd">
                <span class="font-bold text-gray-900 text-sm">{{ option.value }}</span>
                <span class="text-gray-700 text-sm">{{ option.label }}</span>
            </div>
            <p v-if="availableProblemOptions.length === 0" class="text-xs text-gray-400 italic">All options placed.</p>
        </div>
    </div>
    <p class="mt-2 text-xs text-gray-500">Click a filled box to clear it.</p>
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
    0%   { background-color: rgba(0, 0, 0, 0.15); }
    100% { background-color: rgba(0, 0, 0, 0.08); }
}

.overflow-y-auto::-webkit-scrollbar       { width: 6px; }
.overflow-y-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-y-auto::-webkit-scrollbar-thumb { background: #000000; }
.overflow-y-auto::-webkit-scrollbar-thumb:hover { background: #374151; }

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

.highlight-yellow { background-color: rgba(254, 240, 138, 0.5); }
.highlight-green  { background-color: rgba(187, 247, 208, 0.5); }
.highlight-blue   { background-color: rgba(191, 219, 254, 0.5); }
.highlight-pink   { background-color: rgba(251, 207, 232, 0.5); }
.highlight-orange { background-color: rgba(254, 215, 170, 0.5); }
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
.note-highlight:hover { border-bottom-color: #000; }
.note-highlight:hover::after { opacity: 1; font-size: 0.75em; }
</style>