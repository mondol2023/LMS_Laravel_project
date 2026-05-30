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

// Q11–16: map labeling (text inputs for letters A–I)
const answers = ref<Record<string, string>>({
    q11: '',
    q12: '',
    q13: '',
    q14: '',
    q15: '',
    q16: '',
    // Q17–20: single MCQ
    q17: '',
    q18: '',
    q19: '',
    q20: '',
});

// Text segments with precise sequential offsets
const textSegments = ref<TextSegment[]>([
    { id: 'part2-title', text: 'Part 2', offset: 0 },
    { id: 'part2-desc', text: 'Listen and answer questions 11–20.', offset: 6 },
    { id: 0, text: 'Questions 11–16', offset: 40 },
    { id: 1, text: 'Label the map below.', offset: 55 },
    { id: 2, text: 'Write the correct letter, A–I, next to questions 11-16.', offset: 75 },
    { id: 'q11-label', text: 'Restaurant', offset: 130 },
    { id: 'q12-label', text: 'Cinema', offset: 140 },
    { id: 'q13-label', text: 'Kids Play Area', offset: 146 },
    { id: 'q14-label', text: 'Shop Zone', offset: 160 },
    { id: 'q15-label', text: 'Sun Lounge', offset: 169 },
    { id: 'q16-label', text: 'Observation Platform', offset: 179 },
    { id: 3, text: 'Questions 17–20', offset: 199 },
    { id: 4, text: 'Choose the correct letter, A, B or C.', offset: 214 },
    { id: 'q17', text: '17.', offset: 251 },
    { id: 5, text: 'What time will the ferry arrive?', offset: 254 },
    { id: 6, text: '10am', offset: 286 },
    { id: 7, text: '9pm', offset: 290 },
    { id: 8, text: '12am', offset: 293 },
    { id: 'q18', text: '18.', offset: 297 },
    { id: 9, text: 'What is available from the Main Entrance for a reduced price?', offset: 300 },
    { id: 10, text: 'train tickets', offset: 361 },
    { id: 11, text: 'bus tickets', offset: 374 },
    { id: 12, text: 'cabins', offset: 385 },
    { id: 'q19', text: '19.', offset: 391 },
    { id: 13, text: 'What can passengers get for no extra cost?', offset: 394 },
    { id: 14, text: 'a cabin upgrade', offset: 436 },
    { id: 15, text: 'a tour', offset: 451 },
    { id: 16, text: 'a map', offset: 457 },
    { id: 'q20', text: '20.', offset: 462 },
    { id: 17, text: 'What is available in the cabins?', offset: 465 },
    { id: 18, text: 'wireless internet', offset: 497 },
    { id: 19, text: 'computers', offset: 514 },
    { id: 20, text: 'a working space', offset: 523 },
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
    return answers.value;
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
        handleTooltipMouseLeave();
    }
};

const scrollToQuestion = async (questionNumber: number) => {
    await nextTick();
    const element = document.getElementById(`question-${questionNumber}`);
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick" class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex w-full flex-col" :style="contentZoom">

            <!-- Part Header Box -->
            <div class="mb-3 part-header-box rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
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

            <!-- Scrollable Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3 space-y-6">

                    <!-- ── QUESTIONS 11–16: MAP LABELING ── -->
                    <div>
                        <!-- Section header -->
                        <div class="mb-2 px-1">
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
                            <p
                                class="text-sm text-gray-700 select-text"
                                data-segment-id="2"
                                v-html="getHighlightedSegmentById(2)"
                            ></p>
                        </div>

                        <!-- Ferry Map Image -->
                        <div class="my-3  ">
                            <img
                                src="/images/listening/IELTS020section02.png"
                                alt="Ferry Map"
                                class="w-full h-auto max-w-xl rounded border border-gray-200"
                            />
                        </div>

                        <!-- Q11–16 input list -->
<div class="space-y-2 px-1">

    <!-- Q11 -->
    <div
        id="question-11"
        class="relative flex flex-wrap items-center gap-2 rounded p-2"
        @mouseenter="hoveredQuestion = 11"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="text-base font-bold text-gray-900 w-6 shrink-0">11.</span>
        <span
            class="text-base text-gray-800 select-text"
            data-segment-id="q11-label"
            v-html="getHighlightedSegmentById('q11-label')"
        ></span>
        <select
            v-model="answers.q11"
            class="question-select w-24 text-center border border-gray-900 px-1 py-0.5   focus:border-black focus:outline-none bg-white"
            @focus="hoveredQuestion = 11"
            @blur="hoveredQuestion = null"
        >
            <option value="" disabled selected>select</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
            <option value="E">E</option>
            <option value="F">F</option>
            <option value="G">G</option>
            <option value="H">H</option>
            <option value="I">I</option>
        </select>
        <button
            v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
            @click.stop="toggleFlag(11)"
            class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>

    <!-- Q12 -->
    <div
        id="question-12"
        class="relative flex flex-wrap items-center gap-2 rounded p-2"
        @mouseenter="hoveredQuestion = 12"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="text-base font-bold text-gray-900 w-6 shrink-0">12.</span>
        <span
            class="text-base text-gray-800 select-text"
            data-segment-id="q12-label"
            v-html="getHighlightedSegmentById('q12-label')"
        ></span>
        <select
            v-model="answers.q12"
            class="question-select w-24 text-center border border-gray-900 px-1 py-0.5   focus:border-black focus:outline-none bg-white"
            @focus="hoveredQuestion = 12"
            @blur="hoveredQuestion = null"
        >
            <option value="" disabled selected>select</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
            <option value="E">E</option>
            <option value="F">F</option>
            <option value="G">G</option>
            <option value="H">H</option>
            <option value="I">I</option>
        </select>
        <button
            v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
            @click.stop="toggleFlag(12)"
            class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>

    <!-- Q13 -->
    <div
        id="question-13"
        class="relative flex flex-wrap items-center gap-2 rounded p-2"
        @mouseenter="hoveredQuestion = 13"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="text-base font-bold text-gray-900 w-6 shrink-0">13.</span>
        <span
            class="text-base text-gray-800 select-text"
            data-segment-id="q13-label"
            v-html="getHighlightedSegmentById('q13-label')"
        ></span>
        <select
            v-model="answers.q13"
            class="question-select w-24 text-center border border-gray-900 px-1 py-0.5   focus:border-black focus:outline-none bg-white"
            @focus="hoveredQuestion = 13"
            @blur="hoveredQuestion = null"
        >
            <option value="" disabled selected>select</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
            <option value="E">E</option>
            <option value="F">F</option>
            <option value="G">G</option>
            <option value="H">H</option>
            <option value="I">I</option>
        </select>
        <button
            v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
            @click.stop="toggleFlag(13)"
            class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>

    <!-- Q14 -->
    <div
        id="question-14"
        class="relative flex flex-wrap items-center gap-2 rounded p-2"
        @mouseenter="hoveredQuestion = 14"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="text-base font-bold text-gray-900 w-6 shrink-0">14.</span>
        <span
            class="text-base text-gray-800 select-text"
            data-segment-id="q14-label"
            v-html="getHighlightedSegmentById('q14-label')"
        ></span>
        <select
            v-model="answers.q14"
            class="question-select w-24 text-center border border-gray-900 px-1 py-0.5   focus:border-black focus:outline-none bg-white"
            @focus="hoveredQuestion = 14"
            @blur="hoveredQuestion = null"
        >
            <option value="" disabled selected>select</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
            <option value="E">E</option>
            <option value="F">F</option>
            <option value="G">G</option>
            <option value="H">H</option>
            <option value="I">I</option>
        </select>
        <button
            v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
            @click.stop="toggleFlag(14)"
            class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>

    <!-- Q15 -->
    <div
        id="question-15"
        class="relative flex flex-wrap items-center gap-2 rounded p-2"
        @mouseenter="hoveredQuestion = 15"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="text-base font-bold text-gray-900 w-6 shrink-0">15.</span>
        <span
            class="text-base text-gray-800 select-text"
            data-segment-id="q15-label"
            v-html="getHighlightedSegmentById('q15-label')"
        ></span>
        <select
            v-model="answers.q15"
            class="question-select w-24 text-center border border-gray-900 px-1 py-0.5   focus:border-black focus:outline-none bg-white"
            @focus="hoveredQuestion = 15"
            @blur="hoveredQuestion = null"
        >
            <option value="" disabled selected>select</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
            <option value="E">E</option>
            <option value="F">F</option>
            <option value="G">G</option>
            <option value="H">H</option>
            <option value="I">I</option>
        </select>
        <button
            v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
            @click.stop="toggleFlag(15)"
            class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>

    <!-- Q16 -->
    <div
        id="question-16"
        class="relative flex flex-wrap items-center gap-2 rounded p-2"
        @mouseenter="hoveredQuestion = 16"
        @mouseleave="hoveredQuestion = null"
    >
        <span class="text-base font-bold text-gray-900 w-6 shrink-0">16.</span>
        <span
            class="text-base text-gray-800 select-text"
            data-segment-id="q16-label"
            v-html="getHighlightedSegmentById('q16-label')"
        ></span>
        <select
            v-model="answers.q16"
            class="question-select w-24 text-center border border-gray-900 px-1 py-0.5   focus:border-black focus:outline-none bg-white"
            @focus="hoveredQuestion = 16"
            @blur="hoveredQuestion = null"
        >
            <option value="" disabled selected>select</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
            <option value="E">E</option>
            <option value="F">F</option>
            <option value="G">G</option>
            <option value="H">H</option>
            <option value="I">I</option>
        </select>
        <button
            v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
            @click.stop="toggleFlag(16)"
            class="ml-auto flex h-7 w-7 items-center justify-center rounded border transition-all"
            :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
            :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
            </svg>
        </button>
    </div>

</div>
                    </div>

                    <!-- ── QUESTIONS 17–20: SINGLE CHOICE MCQ ── -->
                    <div class="border-t pt-4">

                        <!-- Section header -->
                        <div class="mb-3 px-1">
                            <p
                                class="text-base font-bold text-gray-900 select-text"
                                data-segment-id="3"
                                v-html="getHighlightedSegmentById(3)"
                            ></p>
                            <p
                                class="text-sm text-gray-700 select-text"
                                data-segment-id="4"
                                v-html="getHighlightedSegmentById(4)"
                            ></p>
                        </div>

                        <div class="space-y-2">

                            <!-- Q17 -->
                            <div
                                id="question-17"
                                class="relative p-2 sm:p-3"
                                @mouseenter="hoveredQuestion = 17"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex items-start gap-2">
                                    <span
                                        class="text-base font-bold text-gray-900 select-text shrink-0"
                                        data-segment-id="q17"
                                        v-html="getHighlightedSegmentById('q17')"
                                    ></span>
                                    <div class="min-w-0 flex-1">
                                        <button
                                            v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                            @click.stop="toggleFlag(17)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                                        </h4>
                                        <div class="space-y-1">
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q17" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q17" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q17" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Q18 -->
                            <div
                                id="question-18"
                                class="relative p-2 sm:p-3"
                                @mouseenter="hoveredQuestion = 18"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex items-start gap-2">
                                    <span
                                        class="text-base font-bold text-gray-900 select-text shrink-0"
                                        data-segment-id="q18"
                                        v-html="getHighlightedSegmentById('q18')"
                                    ></span>
                                    <div class="min-w-0 flex-1">
                                        <button
                                            v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                            @click.stop="toggleFlag(18)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                        </h4>
                                        <div class="space-y-1">
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q18" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q18" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q18" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Q19 -->
                            <div
                                id="question-19"
                                class="relative p-2 sm:p-3"
                                @mouseenter="hoveredQuestion = 19"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex items-start gap-2">
                                    <span
                                        class="text-base font-bold text-gray-900 select-text shrink-0"
                                        data-segment-id="q19"
                                        v-html="getHighlightedSegmentById('q19')"
                                    ></span>
                                    <div class="min-w-0 flex-1">
                                        <button
                                            v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                            @click.stop="toggleFlag(19)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                        </h4>
                                        <div class="space-y-1">
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q19" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q19" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q19" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Q20 -->
                            <div
                                id="question-20"
                                class="relative p-2 sm:p-3"
                                @mouseenter="hoveredQuestion = 20"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <div class="flex items-start gap-2">
                                    <span
                                        class="text-base font-bold text-gray-900 select-text shrink-0"
                                        data-segment-id="q20"
                                        v-html="getHighlightedSegmentById('q20')"
                                    ></span>
                                    <div class="min-w-0 flex-1">
                                        <button
                                            v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                            @click.stop="toggleFlag(20)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                            :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>
                                        <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                            <span class="select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                                        </h4>
                                        <div class="space-y-1">
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q20" value="A" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q20" value="B" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers.q20" value="C" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                                            </label>
                                        </div>
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

/* Placeholder styling for map inputs */
.question-input::placeholder {
    font-weight: 700;
    color: #374151;
    font-size: 0.7rem;
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
