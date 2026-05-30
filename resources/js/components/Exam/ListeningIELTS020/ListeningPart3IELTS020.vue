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

const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

const notes = ref<Note[]>([]);

// Q21–25: single choice MCQ
const answers = ref<Record<string, string>>({
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
});

// Q26–28: fill-in-the-blank
const fillAnswers = ref({
    q26: '',
    q27: '',
    q28: '',
});

// Q29–30: choose TWO from A-D — same data shape as q25_26 / q27_28
const multipleAnswers = ref<{ q29_30: string[] }>({
    q29_30: [],
});

const textSegments = ref<TextSegment[]>([
    { id: 'part3-title', text: 'Part 3', offset: 0 },
    { id: 'part3-desc', text: 'Listen and answer questions 21–30.', offset: 6 },
    { id: 0, text: 'Questions 21–25', offset: 40 },
    { id: 1, text: 'Choose the correct letter, A, B or C.', offset: 55 },
    { id: 2, text: 'Private Space Travel Presentation', offset: 92 },
    { id: 'q21', text: '21.', offset: 125 },
    { id: 3, text: 'What do the speakers agree helped them most in their preparation?', offset: 128 },
    { id: 4, text: "their professor's lectures", offset: 193 },
    { id: 5, text: 'a documentary on the internet', offset: 219 },
    { id: 6, text: 'interviewing an expert', offset: 248 },
    { id: 'q22', text: '22.', offset: 270 },
    { id: 7, text: 'One of the speakers struggled with ...', offset: 273 },
    { id: 8, text: 'finding relevant sources.', offset: 311 },
    { id: 9, text: 'summarizing their research.', offset: 336 },
    { id: 10, text: 'identifying a theme.', offset: 363 },
    { id: 'q23', text: '23.', offset: 383 },
    { id: 11, text: 'What do the speakers think about Cosmos Dollars?', offset: 386 },
    { id: 12, text: 'Its style is distracting.', offset: 434 },
    { id: 13, text: 'Its case studies are overly specific.', offset: 459 },
    { id: 14, text: 'Its content lacks depth.', offset: 496 },
    { id: 'q24', text: '24.', offset: 520 },
    { id: 15, text: 'What do the speakers decide to omit from their presentation?', offset: 523 },
    { id: 16, text: 'projected slides', offset: 583 },
    { id: 17, text: 'handouts', offset: 599 },
    { id: 18, text: 'film clips', offset: 607 },
    { id: 'q25', text: '25.', offset: 617 },
    { id: 19, text: 'The speakers agree the most important thing is to make their presentation ...', offset: 620 },
    { id: 20, text: 'accessible.', offset: 697 },
    { id: 21, text: 'broad.', offset: 708 },
    { id: 22, text: 'memorable.', offset: 714 },
    // Q26–28
    { id: 'q26-28', text: 'Questions 26–28', offset: 724 },
    { id: 'q26-28-inst1', text: 'Complete the notes below.', offset: 739 },
    { id: 'q26-28-inst2', text: 'Write ONE WORD ONLY for each answer.', offset: 764 },
    { id: 'q26-28-title', text: 'Private Space Travel Presentation — Notes', offset: 800 },
    { id: 'seg-static1', text: 'Initial stages of space travel dominated by nations', offset: 841 },
    { id: 'seg-pre26', text: 'Private companies acted as', offset: 892 },
    { id: 'seg-post26', text: 'for governmental space agencies', offset: 918 },
    { id: 'seg-pre27', text: 'Privately owned', offset: 949 },
    { id: 'seg-post27', text: 'satellites allowed from 1962', offset: 964 },
    { id: 'seg-pre28', text: 'Arianespace: first company to conduct private', offset: 992 },
    { id: 'seg-static2', text: 'Founded by European Space Agency', offset: 1037 },
    // Q29–30 — TWO letters A-D (offsets recalculated to be sequential after seg-static2 ends at 1070)
    { id: 'q29-30', text: 'Questions 29 and 30', offset: 1070 },              // 1070-1089
    { id: 'q29-30-inst', text: 'Choose TWO letters, A-D.', offset: 1089 },    // 1089-1113
    { id: 31, text: 'Which person or group applies to each question below?', offset: 1113 }, // 1113-1166
    // Q29 stem with label
    { id: 'seg-q29-label', text: '29.', offset: 1166 },                       // 1166-1169
    { id: 'seg-q29-stem', text: 'plan to operate a space tourism business', offset: 1169 }, // 1169-1209
    // Q30 stem with label
    { id: 'seg-q30-label', text: '30.', offset: 1209 },                       // 1209-1212
    { id: 'seg-q30-stem', text: 'competed for the same government contract', offset: 1212 }, // 1212-1253
    // Options A-D
    { id: 32, text: 'Elon Musk & Jeff Bezos', offset: 1253 },                 // 1253-1275
    { id: 33, text: 'Jeff Bezos & Richard Branson', offset: 1275 },           // 1275-1303
    { id: 34, text: 'Richard Branson & Elon Musk', offset: 1303 },            // 1303-1330
    { id: 35, text: 'All three', offset: 1330 },                              // 1330-1339
]);

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

const getAnswers = () => {
    const allAnswers: Record<string, string> = {
        ...answers.value,
        q26: fillAnswers.value.q26,
        q27: fillAnswers.value.q27,
        q28: fillAnswers.value.q28,
        q29: multipleAnswers.value.q29_30[0] || '',
        q30: multipleAnswers.value.q29_30[1] || '',
    };
    return allAnswers;
};

// ── Identical helpers to Q25-26 / Q27-28 ──
const handleMultipleChoice = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    const index = currentAnswers.indexOf(option);
    if (index > -1) {
        currentAnswers.splice(index, 1);
    } else if (currentAnswers.length < 2) {
        currentAnswers.push(option);
    }
};

const isSelected = (questionGroup: string, option: string) => {
    return multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value].includes(option);
};

const isDisabled = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    return currentAnswers.length >= 2 && !currentAnswers.includes(option);
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
    notes.value = notes.value.filter((n) => !(n.start < newEnd && n.end > newStart));
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
    let targetId = `question-${questionNumber}`;
    if (questionNumber === 29 || questionNumber === 30) {
        targetId = 'question-29-30';
    }
    const element = document.getElementById(targetId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('highlight-question');
        setTimeout(() => element.classList.remove('highlight-question'), 2000);
    }
};

onMounted(() => setupEventListeners());
onBeforeUnmount(() => cleanupEventListeners());

defineExpose({ getAnswers, scrollToQuestion, notes, deleteNote });
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex w-full flex-col" :style="contentZoom">

            <!-- Part Header Box -->
            <div class="mb-3 part-header-box rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part3-title"
                    v-html="getHighlightedSegmentById('part3-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part3-desc"
                    v-html="getHighlightedSegmentById('part3-desc')"></p>
            </div>

            <!-- Instructions -->
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

            <!-- Scrollable Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <div class="space-y-2">

                        <!-- ── Q21 ── -->
                        <div id="question-21" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 21"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q21"
                                    v-html="getHighlightedSegmentById('q21')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 21 || isQuestionFlagged(21)"
                                        @click.stop="toggleFlag(21)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(21) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(21) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900"><span
                                            class="select-text" data-segment-id="3"
                                            v-html="getHighlightedSegmentById(3)"></span></h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q21" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="4"
                                                v-html="getHighlightedSegmentById(4)"></span></label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q21" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="5"
                                                v-html="getHighlightedSegmentById(5)"></span></label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q21" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="6"
                                                v-html="getHighlightedSegmentById(6)"></span></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Q22 ── -->
                        <div id="question-22" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 22"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q22"
                                    v-html="getHighlightedSegmentById('q22')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 22 || isQuestionFlagged(22)"
                                        @click.stop="toggleFlag(22)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(22) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(22) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900"><span
                                            class="select-text" data-segment-id="7"
                                            v-html="getHighlightedSegmentById(7)"></span></h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q22" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="8"
                                                v-html="getHighlightedSegmentById(8)"></span></label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q22" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="9"
                                                v-html="getHighlightedSegmentById(9)"></span></label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q22" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="10"
                                                v-html="getHighlightedSegmentById(10)"></span></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Q23 ── -->
                        <div id="question-23" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 23"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q23"
                                    v-html="getHighlightedSegmentById('q23')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 23 || isQuestionFlagged(23)"
                                        @click.stop="toggleFlag(23)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(23) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(23) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900"><span
                                            class="select-text" data-segment-id="11"
                                            v-html="getHighlightedSegmentById(11)"></span></h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q23" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="12"
                                                v-html="getHighlightedSegmentById(12)"></span></label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q23" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="13"
                                                v-html="getHighlightedSegmentById(13)"></span></label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q23" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="14"
                                                v-html="getHighlightedSegmentById(14)"></span></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Q24 ── -->
                        <div id="question-24" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 24"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q24"
                                    v-html="getHighlightedSegmentById('q24')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 24 || isQuestionFlagged(24)"
                                        @click.stop="toggleFlag(24)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(24) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(24) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900"><span
                                            class="select-text" data-segment-id="15"
                                            v-html="getHighlightedSegmentById(15)"></span></h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q24" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="16"
                                                v-html="getHighlightedSegmentById(16)"></span></label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q24" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="17"
                                                v-html="getHighlightedSegmentById(17)"></span></label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q24" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="18"
                                                v-html="getHighlightedSegmentById(18)"></span></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Q25 ── -->
                        <div id="question-25" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 25"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q25"
                                    v-html="getHighlightedSegmentById('q25')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                        @click.stop="toggleFlag(25)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(25) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900"><span
                                            class="select-text" data-segment-id="19"
                                            v-html="getHighlightedSegmentById(19)"></span></h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q25" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="20"
                                                v-html="getHighlightedSegmentById(20)"></span></label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q25" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="21"
                                                v-html="getHighlightedSegmentById(21)"></span></label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50"><input
                                                type="radio" v-model="answers.q25" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" /><span
                                                class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="22"
                                                v-html="getHighlightedSegmentById(22)"></span></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Q26–28: Fill-in-the-blank ── -->
                        <div class="border-t pt-4">
                            <div class="mb-2 px-1">
                                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q26-28"
                                    v-html="getHighlightedSegmentById('q26-28')"></p>
                                <p class="text-sm text-gray-700 select-text" data-segment-id="q26-28-inst1"
                                    v-html="getHighlightedSegmentById('q26-28-inst1')"></p>
                                <p class="text-sm text-gray-700 select-text" data-segment-id="q26-28-inst2"
                                    v-html="getHighlightedSegmentById('q26-28-inst2')"></p>
                            </div>
                            <h3 class="mb-3 px-1 text-base font-bold text-gray-900 select-text"
                                data-segment-id="q26-28-title" v-html="getHighlightedSegmentById('q26-28-title')"></h3>
                            <div class="space-y-3 px-1 text-base leading-relaxed text-gray-800">
                                <div class="flex items-start gap-1">
                                    <span class="mt-1 shrink-0 text-gray-500">•</span>
                                    <span class="select-text" data-segment-id="seg-static1"
                                        v-html="getHighlightedSegmentById('seg-static1')"></span>
                                </div>
                                <!-- Q26 -->
                                <div id="question-26" class="flex flex-wrap items-center gap-1"
                                    @mouseenter="hoveredQuestion = 26" @mouseleave="hoveredQuestion = null">
                                    <span class="mt-1 shrink-0 text-gray-500">•</span>
                                    <span class="select-text" data-segment-id="seg-pre26"
                                        v-html="getHighlightedSegmentById('seg-pre26')"></span>
                                    <input v-model="fillAnswers.q26" type="text" spellcheck="false"
                                        autocomplete="off"
                                        class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                        placeholder="26" @focus="hoveredQuestion = 26"
                                        @blur="hoveredQuestion = null" />
                                    <span class="select-text" data-segment-id="seg-post26"
                                        v-html="getHighlightedSegmentById('seg-post26')"></span>
                                    <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                        @click.stop="toggleFlag(26)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(26) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <!-- Q27 -->
                                <div id="question-27" class="flex flex-wrap items-center gap-1"
                                    @mouseenter="hoveredQuestion = 27" @mouseleave="hoveredQuestion = null">
                                    <span class="mt-1 shrink-0 text-gray-500">•</span>
                                    <span class="select-text" data-segment-id="seg-pre27"
                                        v-html="getHighlightedSegmentById('seg-pre27')"></span>
                                    <input v-model="fillAnswers.q27" type="text" spellcheck="false"
                                        autocomplete="off"
                                        class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                        placeholder="27" @focus="hoveredQuestion = 27"
                                        @blur="hoveredQuestion = null" />
                                    <span class="select-text" data-segment-id="seg-post27"
                                        v-html="getHighlightedSegmentById('seg-post27')"></span>
                                    <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27)"
                                        @click.stop="toggleFlag(27)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(27) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(27) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <!-- Q28 -->
                                <div id="question-28" class="flex flex-wrap items-center gap-1"
                                    @mouseenter="hoveredQuestion = 28" @mouseleave="hoveredQuestion = null">
                                    <span class="mt-1 shrink-0 text-gray-500">•</span>
                                    <span class="select-text" data-segment-id="seg-pre28"
                                        v-html="getHighlightedSegmentById('seg-pre28')"></span>
                                    <input v-model="fillAnswers.q28" type="text" spellcheck="false"
                                        autocomplete="off"
                                        class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[80px]"
                                        placeholder="28" @focus="hoveredQuestion = 28"
                                        @blur="hoveredQuestion = null" />
                                    <button v-show="hoveredQuestion === 28 || isQuestionFlagged(28)"
                                        @click.stop="toggleFlag(28)"
                                        class="ml-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(28) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-start gap-1">
                                    <span class="mt-1 shrink-0 text-gray-500">•</span>
                                    <span class="select-text" data-segment-id="seg-static2"
                                        v-html="getHighlightedSegmentById('seg-static2')"></span>
                                </div>
                            </div>
                        </div>

                        <!-- ── Q29–30: Choose TWO letters A-D ── EXACT same structure as Q25-26 / Q27-28 ── -->
                        <div id="question-29-30" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 29"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29) || isQuestionFlagged(30)"
                                @click.stop="toggleFlag(29); toggleFlag(30);"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(29) || isQuestionFlagged(30) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(29) || isQuestionFlagged(30) ? 'Remove bookmark' : 'Bookmark this question'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                </svg>
                            </button>

                            <div class="mb-2">
                                <div class="mb-2">
                                    <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q29-30"
                                        v-html="getHighlightedSegmentById('q29-30')"></h3>
                                </div>
                                <div class="p-2">
                                    <p class="mb-2 text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="q29-30-inst" v-html="getHighlightedSegmentById('q29-30-inst')">
                                    </p>
                                    <p class="mb-1 text-base font-bold text-gray-900 select-text" data-segment-id="31"
                                        v-html="getHighlightedSegmentById(31)"></p>
                                    <!-- Q29 stem -->
                                    <p class="text-gray-700 select-text">
                                        <span class="font-bold text-gray-900 select-text" data-segment-id="seg-q29-label"
                                            v-html="getHighlightedSegmentById('seg-q29-label')"></span>
                                        <span data-segment-id="seg-q29-stem"
                                            v-html="getHighlightedSegmentById('seg-q29-stem')"></span>
                                    </p>
                                    <!-- Q30 stem -->
                                    <p class="text-gray-700 select-text">
                                        <span class="font-bold text-gray-900 select-text" data-segment-id="seg-q30-label"
                                            v-html="getHighlightedSegmentById('seg-q30-label')"></span>
                                        <span data-segment-id="seg-q30-stem"
                                            v-html="getHighlightedSegmentById('seg-q30-stem')"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Options A–D — identical checkbox label markup to Q25-26 / Q27-28 -->
                            <div class="space-y-1">
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q29_30', 'A')"
                                        :disabled="isDisabled('q29_30', 'A')"
                                        @change="handleMultipleChoice('q29_30', 'A')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="32" v-html="getHighlightedSegmentById(32)"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q29_30', 'B')"
                                        :disabled="isDisabled('q29_30', 'B')"
                                        @change="handleMultipleChoice('q29_30', 'B')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="33" v-html="getHighlightedSegmentById(33)"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q29_30', 'C')"
                                        :disabled="isDisabled('q29_30', 'C')"
                                        @change="handleMultipleChoice('q29_30', 'C')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="34" v-html="getHighlightedSegmentById(34)"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q29_30', 'D')"
                                        :disabled="isDisabled('q29_30', 'D')"
                                        @change="handleMultipleChoice('q29_30', 'D')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="35" v-html="getHighlightedSegmentById(35)"></span>
                                </label>
                            </div>

                            <div class="mt-2 p-2">
                                <p class="text-sm font-medium text-gray-900">Selected: {{ multipleAnswers.q29_30.length
                                    }}/2 answers</p>
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
