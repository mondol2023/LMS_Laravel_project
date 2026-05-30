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

// Gap fill answers for questions 11-13
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

// Text segments with their cumulative offsets
const textSegments = ref<TextSegment[]>([
    // Part header
    { id: 'part2-title', text: 'Part 2', offset: 0 },
    { id: 'part2-desc', text: 'Listen and answer questions 11–20.', offset: 7 },
    // Q11-13 instructions
    { id: 0, text: 'Questions 11–13', offset: 43 },
    { id: 1, text: 'Complete the table below.', offset: 59 },
    { id: 2, text: 'Write NO MORE THAN THREE WORDS.', offset: 85 },
    { id: 3, text: 'PARKS AND OPEN SPACES', offset: 117 },
    // Table headers
    { id: 'th1', text: 'Name of place', offset: 139 },
    { id: 'th2', text: 'Of particular interest', offset: 153 },
    { id: 'th3', text: 'Open', offset: 176 },
    // Row 1
    { id: 'r1c1', text: 'Halland Common', offset: 181 },
    { id: 'r1c2', text: 'Source of River Ouse', offset: 196 },
    { id: 'r1c3', text: '24 hours', offset: 217 },
    // Row 2 - q11, q12
    { id: 'r2c1', text: 'Hot Island', offset: 226 },
    { id: 'r2c2-pre', text: 'Many different', offset: 237 },
    { id: 'q11-label', text: '11', offset: 252 },
    { id: 'q12-label', text: '12', offset: 255 },
    // Row 3 - q13
    { id: 'r3c1', text: 'Longfield Country Park', offset: 274 },
    { id: 'r3c2-pre', text: 'Reconstruction of a 2000 year old', offset: 297 },
    { id: 'q13-label', text: '13', offset: 331 },
    { id: 'r3c2-post', text: 'with activities for children', offset: 334 },
    { id: 'r3c3', text: 'Daylight hours', offset: 363 },
    // Q14-16 instructions
    { id: 4, text: 'Questions 14–16', offset: 378 },
    { id: 5, text: 'Choose the correct letter, A, B or C.', offset: 394 },
    { id: 6, text: 'Longfield Park', offset: 432 },
    // Question 14
    { id: 'q14-label', text: '14.', offset: 447 },
    { id: 7, text: "As part of Monday's activity, visitors will", offset: 451 },
    { id: 8, text: 'prepare food with herbs', offset: 495 },
    { id: 9, text: 'meet a well-known herbalist', offset: 521 },
    { id: 10, text: 'dye cloth with herbs', offset: 550 },
    // Question 15
    { id: 'q15-label', text: '15.', offset: 572 },
    { id: 11, text: 'For the activity on Wednesday,', offset: 576 },
    { id: 12, text: 'only group bookings are accepted', offset: 607 },
    { id: 13, text: 'visitors should book in advance', offset: 641 },
    { id: 14, text: 'attendance is free', offset: 674 },
    // Question 16
    { id: 'q16-label', text: '16.', offset: 694 },
    { id: 15, text: 'For the activity on Saturday, visitors should', offset: 698 },
    { id: 16, text: 'come in suitable clothing', offset: 744 },
    { id: 17, text: 'make sure they are able to stay for the whole day', offset: 771 },
    { id: 18, text: 'tell the rangers before the event what they wish to do', offset: 822 },
    // Q17-20 instructions
    { id: 'q17-20', text: 'Questions 17–20', offset: 877 },
    { id: 'q17-20-inst1', text: 'Label the map below.', offset: 893 },
    { id: 'q17-20-inst2', text: 'Write the correct letter A-I next to questions 17-20.', offset: 914 },
    { id: 'map-title', text: 'Hinchingbrooke Park', offset: 968 },
    // Q17-20 question labels
    { id: 'q17-label', text: '17', offset: 988 },
    { id: 'q17-text', text: 'bird hide', offset: 991 },
    { id: 'q18-label', text: '18', offset: 1001 },
    { id: 'q18-text', text: 'dog-walking area', offset: 1004 },
    { id: 'q19-label', text: '19', offset: 1021 },
    { id: 'q19-text', text: 'flower garden', offset: 1024 },
    { id: 'q20-label', text: '20', offset: 1038 },
    { id: 'q20-text', text: 'wooded area', offset: 1041 },
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

    const overlappingHighlights = highlights.value.filter((h) => {
        return h.start_offset < segmentEnd && h.end_offset > segmentOffset;
    });

    const overlappingNotes = notes.value.filter((n) => {
        return n.start < segmentEnd && n.end > segmentOffset;
    });

    if (overlappingHighlights.length === 0 && overlappingNotes.length === 0) {
        return plainText;
    }

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
    return { ...answers.value };
};

const handleContentTextSelect = () => {
    tooltipHandleContentTextSelect();
};

const applyHighlight = (color: string) => {
    if (!selectionRange.value || !selectedText.value) return;

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

    const overlappingHighlights = findOverlappingHighlights(newStart, newEnd);
    overlappingHighlights.forEach((h) => deleteHighlight(h.id));

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
    let targetId = `question-${questionNumber}`;
    if (questionNumber === 12) {
        targetId = 'question-11-13';
    } else if (questionNumber === 13) {
        targetId = 'question-11-13';
    } else if (questionNumber === 11) {
        targetId = 'question-11-13';
    } else if (questionNumber >= 17 && questionNumber <= 20) {
        targetId = 'question-17-20';
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

            <!-- Scrollable Questions Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">

                    <!-- ===== Questions 11-13: Table Completion ===== -->
                    <div id="question-11-13" class="mb-4">
                        <!-- Instructions -->
                        <div class="shrink-0 px-2 pb-2 sm:px-3">
                            <p
                                class="text-base font-bold text-gray-900 select-text"
                                data-segment-id="0"
                                v-html="getHighlightedSegmentById(0)"
                            ></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></p>
                            <p class="text-sm font-bold text-gray-700 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></p>
                        </div>

                        <!-- Section Title -->
                        <div class="px-2 sm:px-3 mb-2">
                            <h2
                                class="text-lg font-bold text-gray-900 select-text"
                                data-segment-id="3"
                                v-html="getHighlightedSegmentById(3)"
                            ></h2>
                        </div>

                        <!-- Table -->
                        <div class="px-2 sm:px-3 overflow-x-auto">
                            <table class="w-full border-collapse border border-gray-900 text-sm">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border border-gray-900 px-3 py-2 text-left font-bold text-gray-900 select-text" data-segment-id="th1" v-html="getHighlightedSegmentById('th1')"></th>
                                        <th class="border border-gray-900 px-3 py-2 text-left font-bold text-gray-900 select-text" data-segment-id="th2" v-html="getHighlightedSegmentById('th2')"></th>
                                        <th class="border border-gray-900 px-3 py-2 text-left font-bold text-gray-900 select-text" data-segment-id="th3" v-html="getHighlightedSegmentById('th3')"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Row 1: Halland Common -->
                                    <tr>
                                        <td class="border border-gray-900 px-3 py-2 text-gray-900 select-text align-top" data-segment-id="r1c1" v-html="getHighlightedSegmentById('r1c1')"></td>
                                        <td class="border border-gray-900 px-3 py-2 text-gray-900 select-text align-top" data-segment-id="r1c2" v-html="getHighlightedSegmentById('r1c2')"></td>
                                        <td class="border border-gray-900 px-3 py-2 text-gray-900 select-text align-top" data-segment-id="r1c3" v-html="getHighlightedSegmentById('r1c3')"></td>
                                    </tr>

                                    <!-- Row 2: Hot Island - Q11, Q12 -->
                                    <tr>
                                        <td class="border border-gray-900 px-3 py-2 text-gray-900 select-text align-top" data-segment-id="r2c1" v-html="getHighlightedSegmentById('r2c1')"></td>
                                        <!-- Q11 cell -->
                                        <td class="border border-gray-900 px-3 py-2 align-top">
                                            <div
                                                id="question-11"
                                                class="flex flex-wrap items-center gap-0.5 group"
                                                @mouseenter="hoveredQuestion = 11"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="text-gray-900 select-text" data-segment-id="r2c2-pre" v-html="getHighlightedSegmentById('r2c2-pre')"></span>
                                                <span class="inline-flex items-center gap-0.5">
                                                    <input
                                                        v-model="answers.q11"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                                        placeholder="11"
                                                        @focus="hoveredQuestion = 11"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                </span>
                                                <button
                                                    @click.stop="toggleFlag(11)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-0 group-hover:opacity-100"
                                                    :class="
                                                        isQuestionFlagged(11)
                                                            ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                                    "
                                                    :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                        <!-- Q12 cell -->
                                        <td class="border border-gray-900 px-3 py-2 align-top">
                                            <div
                                                id="question-12"
                                                class="flex flex-wrap items-center gap-0.5 group"
                                                @mouseenter="hoveredQuestion = 12"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="inline-flex items-center gap-0.5">
                                                    <input
                                                        v-model="answers.q12"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                                        placeholder="12"
                                                        @focus="hoveredQuestion = 12"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                </span>
                                                <button
                                                    @click.stop="toggleFlag(12)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-0 group-hover:opacity-100"
                                                    :class="
                                                        isQuestionFlagged(12)
                                                            ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                                    "
                                                    :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Row 3: Longfield Country Park - Q13 -->
                                    <tr>
                                        <td class="border border-gray-900 px-3 py-2 text-gray-900 select-text align-top" data-segment-id="r3c1" v-html="getHighlightedSegmentById('r3c1')"></td>
                                        <!-- Q13 cell -->
                                        <td class="border border-gray-900 px-3 py-2 align-top">
                                            <div
                                                id="question-13"
                                                class="flex flex-wrap items-center gap-0.5 group"
                                                @mouseenter="hoveredQuestion = 13"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <span class="text-gray-900 select-text" data-segment-id="r3c2-pre" v-html="getHighlightedSegmentById('r3c2-pre')"></span>
                                                <span class="inline-flex items-center gap-0.5">
                                                    <input
                                                        v-model="answers.q13"
                                                        type="text"
                                                        spellcheck="false"
                                                        autocomplete="off"
                                                        class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                                        placeholder="13"
                                                        @focus="hoveredQuestion = 13"
                                                        @blur="hoveredQuestion = null"
                                                    />
                                                </span>
                                                <span class="text-gray-900 select-text" data-segment-id="r3c2-post" v-html="getHighlightedSegmentById('r3c2-post')"></span>
                                                <button
                                                    @click.stop="toggleFlag(13)"
                                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-0 group-hover:opacity-100"
                                                    :class="
                                                        isQuestionFlagged(13)
                                                            ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                                    "
                                                    :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                                                >
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="border border-gray-900 px-3 py-2 text-gray-900 select-text align-top" data-segment-id="r3c3" v-html="getHighlightedSegmentById('r3c3')"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ===== Questions 14-16: Single Choice MCQ ===== -->
                    <div class="mt-6 space-y-2">
                        <!-- Instructions -->
                        <div class="shrink-0 px-2 pb-2 sm:px-3">
                            <p
                                class="text-base font-bold text-gray-900 select-text"
                                data-segment-id="4"
                                v-html="getHighlightedSegmentById(4)"
                            ></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></p>
                        </div>

                        <!-- Section Title -->
                        <div class="px-2 sm:px-3">
                            <h2
                                class="text-lg font-bold text-gray-900 select-text"
                                data-segment-id="6"
                                v-html="getHighlightedSegmentById(6)"
                            ></h2>
                        </div>

                        <!-- Question 14 -->
                        <div
                            id="question-14"
                            class="relative p-2 sm:p-3"
                            @mouseenter="hoveredQuestion = 14"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q14-label" v-html="getHighlightedSegmentById('q14-label')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                        @click.stop="toggleFlag(14)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(14)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
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
                                            <input type="radio" v-model="answers.q14" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q14" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q14" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
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
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q15-label" v-html="getHighlightedSegmentById('q15-label')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                        @click.stop="toggleFlag(15)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(15)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
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
                                            <input type="radio" v-model="answers.q15" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q15" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q15" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
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
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q16-label" v-html="getHighlightedSegmentById('q16-label')"></span>
                                <div class="min-w-0 flex-1">
                                    <button
                                        v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                        @click.stop="toggleFlag(16)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="
                                            isQuestionFlagged(16)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        "
                                        :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
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
                                            <input type="radio" v-model="answers.q16" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q16" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q16" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== Questions 17-20: Map Labeling ===== -->
                    <div id="question-17-20" class="mt-6 p-2 sm:p-3">
                        <!-- Instructions -->
                        <div class="shrink-0 pb-2">
                            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q17-20" v-html="getHighlightedSegmentById('q17-20')"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="q17-20-inst1" v-html="getHighlightedSegmentById('q17-20-inst1')"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="q17-20-inst2" v-html="getHighlightedSegmentById('q17-20-inst2')"></p>
                        </div>

                        <!-- Map Title -->
                        <div class="mb-2">
                            <h3 class="text-lg font-bold text-gray-900 select-text" data-segment-id="map-title" v-html="getHighlightedSegmentById('map-title')"></h3>
                        </div>

                        <img src="/images/listening/IELTS010-p-2.png" class="w-1/2" alt="">

                        <!-- Q17-20 Answer inputs -->
                        <div class="space-y-3 mt-4">
                            <!-- Question 17 -->
                            <div
                                id="question-17"
                                class="flex items-center gap-6 p-1 group"
                                @mouseenter="hoveredQuestion = 17"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base font-bold text-gray-900 w-8 select-text" data-segment-id="q17-label" v-html="getHighlightedSegmentById('q17-label')"></span>
                                <div class="flex items-center gap-2 flex-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="q17-text" v-html="getHighlightedSegmentById('q17-text')"></span>
                                    <div class="inline-flex items-center gap-1">
                                        <select
                                            v-model="answers.q17"
                                            class="border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none bg-white min-w-[60px]"
                                            @focus="hoveredQuestion = 17"
                                            @blur="hoveredQuestion = null"
                                        >
                                            <option value="">select</option>
                                            <option v-for="opt in ['A','B','C','D','E','F','G','H','I']" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                        <button
                                            @click.stop="toggleFlag(17)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-0 group-hover:opacity-100"
                                            :class="
                                                isQuestionFlagged(17)
                                                    ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                    : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Question 18 -->
                            <div
                                id="question-18"
                                class="flex items-center gap-6 p-1 group"
                                @mouseenter="hoveredQuestion = 18"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base font-bold text-gray-900 w-8 select-text" data-segment-id="q18-label" v-html="getHighlightedSegmentById('q18-label')"></span>
                                <div class="flex items-center gap-2 flex-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="q18-text" v-html="getHighlightedSegmentById('q18-text')"></span>
                                    <div class="inline-flex items-center gap-1">
                                        <select
                                            v-model="answers.q18"
                                            class="border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none bg-white min-w-[60px]"
                                            @focus="hoveredQuestion = 18"
                                            @blur="hoveredQuestion = null"
                                        >
                                            <option value="">select</option>
                                            <option v-for="opt in ['A','B','C','D','E','F','G','H','I']" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                        <button
                                            @click.stop="toggleFlag(18)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-0 group-hover:opacity-100"
                                            :class="
                                                isQuestionFlagged(18)
                                                    ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                    : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Question 19 -->
                            <div
                                id="question-19"
                                class="flex items-center gap-6 p-1 group"
                                @mouseenter="hoveredQuestion = 19"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base font-bold text-gray-900 w-8 select-text" data-segment-id="q19-label" v-html="getHighlightedSegmentById('q19-label')"></span>
                                <div class="flex items-center gap-2 flex-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="q19-text" v-html="getHighlightedSegmentById('q19-text')"></span>
                                    <div class="inline-flex items-center gap-1">
                                        <select
                                            v-model="answers.q19"
                                            class="border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none bg-white min-w-[60px]"
                                            @focus="hoveredQuestion = 19"
                                            @blur="hoveredQuestion = null"
                                        >
                                            <option value="">select</option>
                                            <option v-for="opt in ['A','B','C','D','E','F','G','H','I']" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                        <button
                                            @click.stop="toggleFlag(19)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-0 group-hover:opacity-100"
                                            :class="
                                                isQuestionFlagged(19)
                                                    ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                    : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Question 20 -->
                            <div
                                id="question-20"
                                class="flex items-center gap-6 p-1 group"
                                @mouseenter="hoveredQuestion = 20"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base font-bold text-gray-900 w-8 select-text" data-segment-id="q20-label" v-html="getHighlightedSegmentById('q20-label')"></span>
                                <div class="flex items-center gap-2 flex-1">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="q20-text" v-html="getHighlightedSegmentById('q20-text')"></span>
                                    <div class="inline-flex items-center gap-1">
                                        <select
                                            v-model="answers.q20"
                                            class="border border-gray-900 px-2 py-0.5 text-sm focus:border-black focus:outline-none bg-white min-w-[60px]"
                                            @focus="hoveredQuestion = 20"
                                            @blur="hoveredQuestion = null"
                                        >
                                            <option value="">select</option>
                                            <option v-for="opt in ['A','B','C','D','E','F','G','H','I']" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                        <button
                                            @click.stop="toggleFlag(20)"
                                            class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-0 group-hover:opacity-100"
                                            :class="
                                                isQuestionFlagged(20)
                                                    ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                                    : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                            :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
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

/* Bold placeholder styling for question inputs */
.question-input::placeholder {
    font-weight: 700;
    color: #374151;
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