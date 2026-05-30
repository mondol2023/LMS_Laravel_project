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

// Answers for questions 11-20
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

    // Q11 instructions
    { id: 'q11-section-label', text: 'Part 2: Question 11', offset: 42 },
    { id: 'q11-inst', text: 'Choose the correct letter A, B or C.', offset: 62 },
    // Q11
    { id: 'q11-label', text: '11.', offset: 99 },
    { id: 'q11-stem', text: 'According to the speaker, the main purposes of the park are', offset: 103 },
    { id: 'q11-a', text: 'education and entertainment', offset: 163 },
    { id: 'q11-b', text: 'research and education', offset: 191 },
    { id: 'q11-c', text: 'research and entertainment', offset: 214 },

    // Q12-14 instructions
    { id: 'q12-14-label', text: 'Questions 12–14', offset: 241 },
    { id: 'q12-14-inst1', text: 'Label the plan below.', offset: 257 },
    { id: 'q12-14-inst2', text: 'Write NO MORE THAN TWO WORDS.', offset: 279 },
    { id: 'map-title', text: 'Agricultural Park', offset: 309 },
    // Map fill-in labels
    { id: 'q12-pre', text: '•', offset: 327 },
    { id: 'q12-post', text: 'Area', offset: 330 },
    { id: 'q13-pre', text: '• The ', offset: 335 },
    { id: 'q14-pre', text: '•', offset: 342 },
    { id: 'q14-post', text: 'Area', offset: 345 },

    // Q15-20 instructions
    { id: 'q15-20-label', text: 'Questions 15–20', offset: 350 },
    { id: 'q15-20-inst', text: 'Choose the correct letter A, B or C.', offset: 366 },

    // Q15
    { id: 'q15-label', text: '15.', offset: 403 },
    { id: 'q15-stem', text: 'When are the experimental areas closed to the public?', offset: 407 },
    { id: 'q15-a', text: 'all the year round', offset: 461 },
    { id: 'q15-b', text: 'almost all the year', offset: 480 },
    { id: 'q15-c', text: 'a short time every year', offset: 500 },

    // Q16
    { id: 'q16-label', text: '16.', offset: 524 },
    { id: 'q16-stem', text: 'How can you move around the park?', offset: 528 },
    { id: 'q16-a', text: 'by tram, walking or bicycle', offset: 562 },
    { id: 'q16-b', text: 'by solar car or bicycle', offset: 590 },
    { id: 'q16-c', text: 'by bicycle, walking or bus', offset: 614 },

    // Q17
    { id: 'q17-label', text: '17.', offset: 641 },
    { id: 'q17-stem', text: 'The rare breed animals kept in the park include', offset: 645 },
    { id: 'q17-a', text: 'hens and horses', offset: 693 },
    { id: 'q17-b', text: 'goats and cows', offset: 709 },
    { id: 'q17-c', text: 'goats and hens', offset: 724 },

    // Q18
    { id: 'q18-label', text: '18.', offset: 739 },
    { id: 'q18-stem', text: 'What is the main purpose of having the Rare Breeds Section?', offset: 743 },
    { id: 'q18-a', text: 'to save unusual animals', offset: 803 },
    { id: 'q18-b', text: 'to keep a variety of breeds', offset: 827 },
    { id: 'q18-c', text: 'to educate the public', offset: 855 },

    // Q19
    { id: 'q19-label', text: '19.', offset: 877 },
    { id: 'q19-stem', text: 'What can you see in the park at the present time?', offset: 881 },
    { id: 'q19-a', text: 'the arrival of wild birds', offset: 931 },
    { id: 'q19-b', text: 'fruit tree blossom', offset: 956 },
    { id: 'q19-c', text: 'a demonstration of fishing', offset: 975 },

    // Q20
    { id: 'q20-label', text: '20.', offset: 1001 },
    { id: 'q20-stem', text: 'The shop contains books about', offset: 1005 },
    { id: 'q20-a', text: 'animals', offset: 1034 },
    { id: 'q20-b', text: 'local traditions', offset: 1042 },
    { id: 'q20-c', text: 'the history of the park', offset: 1059 },
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
    if (questionNumber >= 12 && questionNumber <= 14) {
        targetId = 'question-12-14';
    } else if (questionNumber >= 15 && questionNumber <= 20) {
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
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part2-title"
                    v-html="getHighlightedSegmentById('part2-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part2-desc"
                    v-html="getHighlightedSegmentById('part2-desc')"></p>
            </div>

            <!-- Scrollable Questions Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">

                    <!-- ===== Question 11: Single Choice MCQ ===== -->
                    <div class="mb-6">
                        <!-- Section header -->
                        <div class="shrink-0 px-2 pb-2 sm:px-3">
                            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q11-section-label"
                                v-html="getHighlightedSegmentById('q11-section-label')"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="q11-inst"
                                v-html="getHighlightedSegmentById('q11-inst')"></p>
                        </div>

                        <!-- Question 11 -->
                        <div id="question-11" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 11"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q11-label"
                                    v-html="getHighlightedSegmentById('q11-label')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                        @click.stop="toggleFlag(11)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(11)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q11-stem"
                                            v-html="getHighlightedSegmentById('q11-stem')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q11" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                <strong></strong>&nbsp;<span data-segment-id="q11-a"
                                                    v-html="getHighlightedSegmentById('q11-a')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q11" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                <strong></strong>&nbsp;<span data-segment-id="q11-b"
                                                    v-html="getHighlightedSegmentById('q11-b')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q11" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                <strong></strong>&nbsp;<span data-segment-id="q11-c"
                                                    v-html="getHighlightedSegmentById('q11-c')"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== Questions 12-14: Map Labeling ===== -->
                    <div id="question-12-14" class="mb-6">
                        <!-- Instructions -->
                        <div class="shrink-0 px-2 pb-2 sm:px-3">
                            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q12-14-label"
                                v-html="getHighlightedSegmentById('q12-14-label')"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="q12-14-inst1"
                                v-html="getHighlightedSegmentById('q12-14-inst1')"></p>
                            <p class="text-sm font-bold text-gray-700 select-text" data-segment-id="q12-14-inst2"
                                v-html="getHighlightedSegmentById('q12-14-inst2')"></p>
                        </div>

                        <!-- Map Title -->
                        <div class="px-2 sm:px-3 mb-3">
                            <h2 class="text-lg font-bold text-gray-900   select-text" data-segment-id="map-title"
                                v-html="getHighlightedSegmentById('map-title')"></h2>
                        </div>

                        <!-- Map image placeholder with labeled answer inputs overlaid -->
                        <div class="px-2 sm:px-3">
                            <img src="/images/listening/IELTS003.PNG" class="w-1/2" alt="Agricultural Park map" />
                        </div>

                        <!-- Map answer inputs below the map -->
                        <div class="mt-4 space-y-3 px-2 sm:px-3">
                            <!-- Question 12 -->
                            <div id="question-12" class="flex flex-wrap items-center gap-1 group"
                                @mouseenter="hoveredQuestion = 12" @mouseleave="hoveredQuestion = null">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q12-pre"
                                    v-html="getHighlightedSegmentById('q12-pre')"></span>
                                <input v-model="answers.q12" type="text" spellcheck="false" autocomplete="off"
                                    class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                    placeholder="12" @focus="hoveredQuestion = 12" @blur="hoveredQuestion = null" />
                                <span class="text-base text-gray-900 select-text" data-segment-id="q12-post"
                                    v-html="getHighlightedSegmentById('q12-post')"></span>
                                <button @click.stop="toggleFlag(12)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-0 group-hover:opacity-100"
                                    :class="isQuestionFlagged(12)
                                            ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        " :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 13 -->
                            <div id="question-13" class="flex flex-wrap items-center gap-1 group"
                                @mouseenter="hoveredQuestion = 13" @mouseleave="hoveredQuestion = null">
                                <span class="text-base text-gray-900 select-text" data-segment-id="q13-pre"
                                    v-html="getHighlightedSegmentById('q13-pre')"></span>
                                <input v-model="answers.q13" type="text" spellcheck="false" autocomplete="off"
                                    class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                    placeholder="13" @focus="hoveredQuestion = 13" @blur="hoveredQuestion = null" />
                                <button @click.stop="toggleFlag(13)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-0 group-hover:opacity-100"
                                    :class="isQuestionFlagged(13)
                                            ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        " :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Question 14 -->
                            <div id="question-14" class="flex flex-wrap items-center gap-1 group"
                                @mouseenter="hoveredQuestion = 14" @mouseleave="hoveredQuestion = null">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q14-pre"
                                    v-html="getHighlightedSegmentById('q14-pre')"></span>
                                <input v-model="answers.q14" type="text" spellcheck="false" autocomplete="off"
                                    class="question-input mx-1 min-w-[45px] border border-gray-900 px-1.5 py-0.5 text-center text-sm focus:border-black focus:outline-none sm:min-w-[120px]"
                                    placeholder="14" @focus="hoveredQuestion = 14" @blur="hoveredQuestion = null" />
                                <span class="text-base text-gray-900 select-text" data-segment-id="q14-post"
                                    v-html="getHighlightedSegmentById('q14-post')"></span>
                                <button @click.stop="toggleFlag(14)"
                                    class="flex h-7 w-7 items-center justify-center rounded border transition-all opacity-0 group-hover:opacity-100"
                                    :class="isQuestionFlagged(14)
                                            ? 'border-gray-400 bg-transparent text-red-500 opacity-100'
                                            : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                        " :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ===== Questions 15-20: Single Choice MCQ ===== -->
                    <div class="mt-6 space-y-2">
                        <!-- Instructions -->
                        <div class="shrink-0 px-2 pb-2 sm:px-3">
                            <p class="text-base font-bold text-gray-900 select-text" data-segment-id="q15-20-label"
                                v-html="getHighlightedSegmentById('q15-20-label')"></p>
                            <p class="text-sm text-gray-700 select-text" data-segment-id="q15-20-inst"
                                v-html="getHighlightedSegmentById('q15-20-inst')"></p>
                        </div>

                        <!-- Question 15 -->
                        <div id="question-15" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 15"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q15-label"
                                    v-html="getHighlightedSegmentById('q15-label')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                        @click.stop="toggleFlag(15)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(15)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q15-stem"
                                            v-html="getHighlightedSegmentById('q15-stem')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q15" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q15-a"
                                                    v-html="getHighlightedSegmentById('q15-a')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q15" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q15-b"
                                                    v-html="getHighlightedSegmentById('q15-b')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q15" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q15-c"
                                                    v-html="getHighlightedSegmentById('q15-c')"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 16 -->
                        <div id="question-16" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 16"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q16-label"
                                    v-html="getHighlightedSegmentById('q16-label')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                        @click.stop="toggleFlag(16)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(16)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q16-stem"
                                            v-html="getHighlightedSegmentById('q16-stem')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q16" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q16-a"
                                                    v-html="getHighlightedSegmentById('q16-a')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q16" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q16-b"
                                                    v-html="getHighlightedSegmentById('q16-b')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q16" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q16-c"
                                                    v-html="getHighlightedSegmentById('q16-c')"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 17 -->
                        <div id="question-17" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 17"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q17-label"
                                    v-html="getHighlightedSegmentById('q17-label')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                        @click.stop="toggleFlag(17)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(17)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q17-stem"
                                            v-html="getHighlightedSegmentById('q17-stem')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q17" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q17-a"
                                                    v-html="getHighlightedSegmentById('q17-a')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q17" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q17-b"
                                                    v-html="getHighlightedSegmentById('q17-b')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q17" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q17-c"
                                                    v-html="getHighlightedSegmentById('q17-c')"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 18 -->
                        <div id="question-18" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 18"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q18-label"
                                    v-html="getHighlightedSegmentById('q18-label')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                        @click.stop="toggleFlag(18)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(18)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q18-stem"
                                            v-html="getHighlightedSegmentById('q18-stem')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q18" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q18-a"
                                                    v-html="getHighlightedSegmentById('q18-a')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q18" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q18-b"
                                                    v-html="getHighlightedSegmentById('q18-b')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q18" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q18-c"
                                                    v-html="getHighlightedSegmentById('q18-c')"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 19 -->
                        <div id="question-19" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 19"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q19-label"
                                    v-html="getHighlightedSegmentById('q19-label')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                        @click.stop="toggleFlag(19)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(19)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q19-stem"
                                            v-html="getHighlightedSegmentById('q19-stem')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q19" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q19-a"
                                                    v-html="getHighlightedSegmentById('q19-a')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q19" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q19-b"
                                                    v-html="getHighlightedSegmentById('q19-b')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q19" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q19-c"
                                                    v-html="getHighlightedSegmentById('q19-c')"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 20 -->
                        <div id="question-20" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 20"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q20-label"
                                    v-html="getHighlightedSegmentById('q20-label')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                        @click.stop="toggleFlag(20)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(20)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-base leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="q20-stem"
                                            v-html="getHighlightedSegmentById('q20-stem')"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q20" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q20-a"
                                                    v-html="getHighlightedSegmentById('q20-a')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q20" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q20-b"
                                                    v-html="getHighlightedSegmentById('q20-b')"></span>
                                            </span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q20" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text">
                                                &nbsp;<span data-segment-id="q20-c"
                                                    v-html="getHighlightedSegmentById('q20-c')"></span>
                                            </span>
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