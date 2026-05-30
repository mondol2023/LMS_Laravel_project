<script setup lang="ts">
import { useHighlight } from '@/composables/useHighlight';
import { useTooltip, type TextSegment } from '@/composables/useTooltip';
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

// Listening Part 4 component

// Answers for questions 31-40
const answers = ref({
    q31: '',
    q32: '',
    q33: '',
    q34: '',
    q35: '',
    q36: '',
    q37: '',
    q38: '',
    q39: '',
    q40: '',
});

// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const notes = ref<Array<{ id: number; text: string; selectedText: string; note: string; start: number; end: number }>>([]);

// Text segments with their cumulative offsets
// Text segments with their cumulative offsets - Correct questions from PDF
const textSegments = ref([
{ id: 0, text: 'Part 4', offset: 0 },
{ id: 1, text: 'Questions 31-40', offset: 10 },
{ id: 2, text: 'Complete the notes below.', offset: 26 },
{ id: 3, text: 'Write ONE WORD AND/OR A NUMBER for each answer.', offset: 52 },
// First section (causes)
{ id: 4, text: '• Cutting down trees for', offset: 100 },
{ id: 5, text: '• Industrial Revolution', offset: 125 },
{ id: 6, text: '•', offset: 149 },
{ id: 7, text: '• Increase in population → deforestation', offset: 151 },
// KNOWN EFFECTS
{ id: 8, text: 'KNOWN EFFECTS', offset: 192 },
{ id: 9, text: '• Over previous 130 years: temperature ↑ by 0.6°C', offset: 206 },
{ id: 10, text: '• Since Industrial Revolution:', offset: 256 },
{ id: 11, text: '– CO₂ ↑ by 30%', offset: 287 },
{ id: 12, text: '– Methane ↑ by 33% (from mining, animals, rice paddies)', offset: 302 },
{ id: 13, text: '• N₂O ↑ (from', offset: 358 },
{ id: 14, text: 'especially fertiliser; waste management; car exhausts)', offset: 372 },
{ id: 15, text: '• Greenhouse Effect: gases form', offset: 427 },
{ id: 16, text: '→ heat trapped → Earth warms up', offset: 459 },
// FUTURE EFFECTS
{ id: 17, text: 'FUTURE EFFECTS', offset: 492 },
{ id: 18, text: '1. Rise in sea levels due to ice melting', offset: 507 },
{ id: 19, text: 'Sea level', offset: 548 },
{ id: 20, text: 'Number of people at risk', offset: 558 },
{ id: 21, text: '1998 levels', offset: 583 },
{ id: 22, text: '+50cm', offset: 595 },
{ id: 23, text: '92 million', offset: 601 },
{ id: 24, text: '+1 metre', offset: 612 },
{ id: 25, text: '2. Change in', offset: 621 },
{ id: 26, text: '→ more arid areas → population movement to cities', offset: 634 },
{ id: 27, text: '3. Increase in pests and', offset: 684 },
{ id: 28, text: '(e.g. malaria)', offset: 709 },
{ id: 29, text: '4. Change in ecosystems:', offset: 724 },
{ id: 30, text: '• shift in', offset: 749 },
{ id: 31, text: '– some die, others multiply', offset: 760 },
{ id: 32, text: '• deserts get hotter and bigger', offset: 788 },
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
                // Just render the mark with data attributes - tooltip will be rendered via HighlightTooltips component
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

// Focus handling for better UX
const focusedInput = ref<string | null>(null);

const handleFocus = (questionKey: string) => {
    focusedInput.value = questionKey;
};

const handleBlur = () => {
    focusedInput.value = null;
};

// Input validation - limit to 2 words
const handleInput = (questionKey: string, event: Event) => {
    const target = event.target as HTMLInputElement;
    const words = target.value.trim().split(/\s+/);

    if (words.length > 2 && target.value.trim() !== '') {
        // Limit to first 2 words
        const limitedValue = words.slice(0, 2).join(' ');
        answers.value[questionKey as keyof typeof answers.value] = limitedValue;
        target.value = limitedValue;
    } else {
        answers.value[questionKey as keyof typeof answers.value] = target.value;
    }
};

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
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header Box -->
            <div class="mb-3 rounded part-header-box border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="0" v-html="getHighlightedSegmentById(0)"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="1" v-html="getHighlightedSegmentById(1)"></p>
            </div>

            <!-- Instructions -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></p>
            </div>

            <!-- Scrollable Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">

                    <!-- Notes Completion Box -->
                    <div class="border border-gray-300 p-4 select-text sm:p-6">

                        <!-- ── CAUSES Section ── -->
                        <div class="mb-6">

                            <!-- Q31: Cutting down trees for ___ -->
                            <div
                                id="question-31"
                                class="relative mb-2 flex flex-wrap items-center gap-1 rounded p-1"
                                @mouseenter="hoveredQuestion = 31"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base text-gray-900 select-text" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></span>
                                <span class="inline-flex items-center gap-1">

                                    <input
                                        v-model="answers.q31"
                                        type="text"
                                        spellcheck="false"
                                        autocomplete="off"
                                        class="min-w-[140px] border border-gray-900 px-0.5 py-0.5  text-center text-base focus:border-black focus:outline-none"
                                        placeholder="31"
                                        @focus="hoveredQuestion = 31"
                                        @blur="hoveredQuestion = null"
                                    />
                                </span>
                                <button
                                    v-show="hoveredQuestion === 31 || isQuestionFlagged(31)"
                                    @click.stop="toggleFlag(31)"
                                    class="absolute top-1 right-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(31) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(31) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Industrial Revolution (static) -->
                            <div class="mb-2">
                                <span class="text-base text-gray-900 select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                            </div>

                            <!-- Q32: • ___ -->
                            <div
                                id="question-32"
                                class="relative mb-2 flex flex-wrap items-center gap-1 rounded p-1"
                                @mouseenter="hoveredQuestion = 32"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base text-gray-900 select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                <span class="inline-flex items-center gap-1">

                                    <input
                                        v-model="answers.q32"
                                        type="text"
                                        spellcheck="false"
                                        autocomplete="off"
                                        class="min-w-[140px] border border-gray-900 px-0.5 py-0.5  text-center text-base focus:border-black focus:outline-none"
                                        placeholder="32"
                                        @focus="hoveredQuestion = 32"
                                        @blur="hoveredQuestion = null"
                                    />
                                </span>
                                <button
                                    v-show="hoveredQuestion === 32 || isQuestionFlagged(32)"
                                    @click.stop="toggleFlag(32)"
                                    class="absolute top-1 right-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(32) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(32) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Increase in population (static) -->
                            <div class="mb-2">
                                <span class="text-base text-gray-900 select-text" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></span>
                            </div>
                        </div>

                        <!-- ── KNOWN EFFECTS Section ── -->
                        <div class="mb-6">
                            <h3 class="mb-3 text-lg font-bold text-gray-900 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></h3>

                            <!-- Over previous 130 years (static) -->
                            <div class="mb-2">
                                <span class="text-base text-gray-900 select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                            </div>

                            <!-- Since Industrial Revolution (static) -->
                            <div class="mb-1">
                                <span class="text-base text-gray-900 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                            </div>
                            <div class="mb-1 ml-4">
                                <span class="text-base text-gray-900 select-text" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></span>
                            </div>
                            <div class="mb-2 ml-4">
                                <span class="text-base text-gray-900 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                            </div>

                            <!-- Q33: missing in old template — placed between seg12 and seg13 -->
                            <div
                                id="question-33"
                                class="relative mb-2 flex flex-wrap items-center gap-1 rounded p-1"
                                @mouseenter="hoveredQuestion = 33"
                                @mouseleave="hoveredQuestion = null"
                            >

                                <input
                                    v-model="answers.q33"
                                    type="text"
                                    spellcheck="false"
                                    autocomplete="off"
                                    class="min-w-[140px] border border-gray-900 px-0.5 py-0.5  text-center text-base focus:border-black focus:outline-none"
                                    placeholder="33"
                                    @focus="hoveredQuestion = 33"
                                    @blur="hoveredQuestion = null"
                                />
                                <button
                                    v-show="hoveredQuestion === 33 || isQuestionFlagged(33)"
                                    @click.stop="toggleFlag(33)"
                                    class="absolute top-1 right-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(33) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(33) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Q34: N₂O ↑ (from ___ especially ...) -->
                            <div
                                id="question-34"
                                class="relative mb-2 flex flex-wrap items-center gap-1 rounded p-1"
                                @mouseenter="hoveredQuestion = 34"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base text-gray-900 select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                <span class="inline-flex items-center gap-1">

                                    <input
                                        v-model="answers.q34"
                                        type="text"
                                        spellcheck="false"
                                        autocomplete="off"
                                        class="min-w-[100px] border border-gray-900 px-0.5 py-0.5  text-center text-base focus:border-black focus:outline-none"
                                        placeholder="34"
                                        @focus="hoveredQuestion = 34"
                                        @blur="hoveredQuestion = null"
                                    />
                                </span>
                                <span class="text-base text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                <button
                                    v-show="hoveredQuestion === 34 || isQuestionFlagged(34)"
                                    @click.stop="toggleFlag(34)"
                                    class="absolute top-1 right-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(34) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(34) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Q35: Greenhouse Effect: gases form ___ → heat trapped -->
                            <div
                                id="question-35"
                                class="relative mb-2 flex flex-wrap items-center gap-1 rounded p-1"
                                @mouseenter="hoveredQuestion = 35"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base text-gray-900 select-text" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></span>
                                <span class="inline-flex items-center gap-1">

                                    <input
                                        v-model="answers.q35"
                                        type="text"
                                        spellcheck="false"
                                        autocomplete="off"
                                        class="min-w-[100px] border border-gray-900 px-0.5 py-0.5  text-center text-base focus:border-black focus:outline-none"
                                        placeholder="35"
                                        @focus="hoveredQuestion = 35"
                                        @blur="hoveredQuestion = null"
                                    />
                                </span>
                                <span class="text-base text-gray-900 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                <button
                                    v-show="hoveredQuestion === 35 || isQuestionFlagged(35)"
                                    @click.stop="toggleFlag(35)"
                                    class="absolute top-1 right-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(35) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(35) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- ── FUTURE EFFECTS Section ── -->
                        <div class="mb-6">
                            <h3 class="mb-3 text-lg font-bold text-gray-900 select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></h3>

                            <!-- 1. Rise in sea levels -->
                            <div class="mb-4">
                                <p class="mb-2 text-base font-semibold text-gray-900 select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></p>

                                <!-- Table: Q36 and Q37 -->
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[400px] border-collapse border border-gray-300">
                                        <thead>
                                            <tr class="bg-gray-100">
                                                <th class="border border-gray-300 p-2 text-left text-sm font-bold text-gray-900 select-text" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></th>
                                                <th class="border border-gray-300 p-2 text-left text-sm font-bold text-gray-900 select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Row 1: 1998 levels → Q36 -->
                                            <tr
                                                @mouseenter="hoveredQuestion = 36"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <td class="border border-gray-300 p-1 text-sm text-gray-900 select-text" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></td>
                                                <td id="question-36" class="border border-gray-300 p-1">
                                                    <div class="flex items-center gap-1">

                                                        <input
                                                            v-model="answers.q36"
                                                            type="text"
                                                            spellcheck="false"
                                                            autocomplete="off"
                                                            class="w-auto min-w-[80px] border border-gray-900 px-0.5 py-0.5  text-center text-sm focus:border-black focus:outline-none"
                                                            placeholder="36"
                                                            @focus="hoveredQuestion = 36"
                                                            @blur="hoveredQuestion = null"
                                                        />
                                                        <button
                                                            v-show="hoveredQuestion === 36 || isQuestionFlagged(36)"
                                                            @click.stop="toggleFlag(36)"
                                                            class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded border transition-all"
                                                            :class="isQuestionFlagged(36) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                                            :title="isQuestionFlagged(36) ? 'Remove bookmark' : 'Bookmark this question'"
                                                        >
                                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <!-- Row 2: +50cm → 92 million (static) -->
                                            <tr>
                                                <td class="border border-gray-300 p-1 text-sm text-gray-900 select-text" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></td>
                                                <td class="border border-gray-300 p-1 text-sm text-gray-900 select-text" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></td>
                                            </tr>
                                            <!-- Row 3: +1 metre → Q37 -->
                                            <tr
                                                @mouseenter="hoveredQuestion = 37"
                                                @mouseleave="hoveredQuestion = null"
                                            >
                                                <td class="border border-gray-300 p-2 text-sm text-gray-900 select-text" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></td>
                                                <td id="question-37" class="border border-gray-300 p-0">
                                                    <div class="flex items-center gap-1">

                                                        <input
                                                            v-model="answers.q37"
                                                            type="text"
                                                            spellcheck="false"
                                                            autocomplete="off"
                                                            class="w-auto min-w-[80px] border border-gray-900 px-0 py-0 text-center text-sm focus:border-black focus:outline-none"
                                                            placeholder="37"
                                                            @focus="hoveredQuestion = 37"
                                                            @blur="hoveredQuestion = null"
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
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Q38: 2. Change in ___ → more arid areas -->
                            <div
                                id="question-38"
                                class="relative mb-2 flex flex-wrap items-center gap-1 rounded p-1"
                                @mouseenter="hoveredQuestion = 38"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base text-gray-900 select-text" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                                <span class="inline-flex items-center gap-1">

                                    <input
                                        v-model="answers.q38"
                                        type="text"
                                        spellcheck="false"
                                        autocomplete="off"
                                        class="min-w-[120px] border border-gray-900 px-0.5 py-0.5  text-center text-base focus:border-black focus:outline-none"
                                        placeholder="38"
                                        @focus="hoveredQuestion = 38"
                                        @blur="hoveredQuestion = null"
                                    />
                                </span>
                                <span class="text-base text-gray-900 select-text" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
                                <button
                                    v-show="hoveredQuestion === 38 || isQuestionFlagged(38)"
                                    @click.stop="toggleFlag(38)"
                                    class="absolute top-1 right-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(38) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(38) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Q39: 3. Increase in pests and ___ (e.g. malaria) -->
                            <div
                                id="question-39"
                                class="relative mb-2 flex flex-wrap items-center gap-1 rounded p-1"
                                @mouseenter="hoveredQuestion = 39"
                                @mouseleave="hoveredQuestion = null"
                            >
                                <span class="text-base text-gray-900 select-text" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></span>
                                <span class="inline-flex items-center gap-1">

                                    <input
                                        v-model="answers.q39"
                                        type="text"
                                        spellcheck="false"
                                        autocomplete="off"
                                        class="min-w-[100px] border border-gray-900 px-0.5 py-0.5 text-center text-base focus:border-black focus:outline-none"
                                        placeholder="39"
                                        @focus="hoveredQuestion = 39"
                                        @blur="hoveredQuestion = null"
                                    />
                                </span>
                                <span class="text-base text-gray-900 select-text" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></span>
                                <button
                                    v-show="hoveredQuestion === 39 || isQuestionFlagged(39)"
                                    @click.stop="toggleFlag(39)"
                                    class="absolute top-1 right-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                    :class="isQuestionFlagged(39) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                    :title="isQuestionFlagged(39) ? 'Remove bookmark' : 'Bookmark this question'"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- 4. Change in ecosystems -->
                            <div class="mb-2">
                                <p class="mb-1 text-base font-semibold text-gray-900 select-text" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></p>

                                <!-- Q40: • shift in ___ – some die, others multiply -->
                                <div
                                    id="question-40"
                                    class="relative mb-1 ml-4 flex flex-wrap items-center gap-1 rounded p-1"
                                    @mouseenter="hoveredQuestion = 40"
                                    @mouseleave="hoveredQuestion = null"
                                >
                                    <span class="text-base text-gray-900 select-text" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></span>
                                    <span class="inline-flex items-center gap-1">

                                        <input
                                            v-model="answers.q40"
                                            type="text"
                                            spellcheck="false"
                                            autocomplete="off"
                                            class="min-w-[140px] border border-gray-900 px-0 py-0.5 text-center text-base focus:border-black focus:outline-none"
                                            placeholder="40"
                                            @focus="hoveredQuestion = 40"
                                            @blur="hoveredQuestion = null"
                                        />
                                    </span>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></span>
                                    <button
                                        v-show="hoveredQuestion === 40 || isQuestionFlagged(40)"
                                        @click.stop="toggleFlag(40)"
                                        class="absolute top-1 right-1 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(40) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                        :title="isQuestionFlagged(40) ? 'Remove bookmark' : 'Bookmark this question'"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- • deserts get hotter and bigger (static) -->
                                <div class="ml-4">
                                    <span class="text-base text-gray-900 select-text" data-segment-id="32" v-html="getHighlightedSegmentById(32)"></span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inline Teleport for Highlight Tooltips -->
    <Teleport to="body">
        <!-- Highlight Context Menu -->
        <div v-if="showContextMenu" class="pointer-events-none fixed inset-0 z-[9998]">
            <div
                class="highlight-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${contextMenuPosition.x}px`, top: `${contextMenuPosition.y}px`, transform: 'translateX(-50%)' }"
                @click.stop
            >
                <div class="flex items-stretch overflow-hidden rounded border border-gray-300 bg-white shadow-md">
                    <button @click="openNoteInput" class="flex flex-col items-center justify-center border-r border-gray-300 px-5 py-2 transition-colors hover:bg-gray-50" title="Add Note">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 01-3.5 3.5 3.871 3.871 0 01-2.748-1.179z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Note</span>
                    </button>
                    <button @click="applyHighlight('yellow')" class="flex flex-col items-center justify-center px-3 py-2 transition-colors hover:bg-gray-50" title="Highlight">
                        <svg class="h-5 w-5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span class="mt-1 text-xs font-medium text-gray-700">Highlight</span>
                    </button>
                </div>
                <div class="tooltip-arrow"></div>
            </div>
        </div>

        <!-- Delete Highlight Tooltip -->
        <div v-if="showDeleteTooltip" class="fixed inset-0 z-[9998]" @click="closeDeleteTooltip">
            <div
                class="delete-highlight-tooltip fixed z-[9999]"
                :style="{ left: `${deleteTooltipPosition.x}px`, top: `${deleteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
            >
                <div class="tooltip-arrow-up"></div>
                <div class="flex flex-col items-center overflow-hidden rounded border border-gray-300 bg-white shadow-md" @click.stop>
                    <button @click="confirmDeleteHighlight" type="button" class="flex flex-col items-center justify-center px-4 py-3 transition-colors hover:bg-gray-50 active:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                        <span class="mt-1.5 text-xs leading-tight font-medium text-gray-600">Delete</span>
                        <span class="text-xs leading-tight font-medium text-gray-600">Highlight</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Note Hover Tooltip -->
        <div v-if="showNoteTooltip" class="pointer-events-none fixed inset-0 z-[9998]">
            <div
                class="note-hover-tooltip pointer-events-auto fixed z-[9999]"
                :style="{ left: `${noteTooltipPosition.x}px`, top: `${noteTooltipPosition.y}px`, transform: 'translateX(-50%)' }"
                @mouseleave="handleTooltipMouseLeave"
                @click.stop
            >
                <div class="flex items-center gap-2.5 rounded border border-gray-300 bg-white px-3 py-2.5 shadow-md">
                    <span class="shrink-0">
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </span>
                    <span class="max-w-[180px] text-sm break-words text-gray-700">{{ hoveredNoteText }}</span>
                    <button @click="deleteNoteFromTooltip" class="shrink-0 rounded p-1 transition-colors hover:bg-red-50" title="Delete note">
                        <svg class="h-4 w-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="tooltip-arrow-down"></div>
            </div>
        </div>

        <!-- Note Input Modal -->
        <div
            v-if="showNoteInput"
            class="fixed z-[9999] w-80 max-w-[calc(100vw-20px)] border-2 border-gray-900 bg-white p-4 shadow-2xl"
            :style="{ left: `${noteInputPosition.x}px`, top: `${noteInputPosition.y}px`, transform: 'translateX(-50%)' }"
            @click.stop
        >
            <div class="mb-3">
                <p class="mb-3 max-h-16 overflow-y-auto rounded border border-gray-200 bg-gray-50 p-2 text-xs text-gray-600 italic">"{{ selectedText }}"</p>
                <input
                    v-model="noteInputText"
                    type="text"
                    spellcheck="false"
                    autocomplete="off"
                    placeholder="Write your note here..."
                    class="note-input-field w-full border-2 border-gray-900 px-3 py-2 text-sm focus:border-black focus:outline-none"
                    @keyup.enter="saveNote"
                    @keyup.escape="cancelNote"
                />
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancelNote" class="border-2 border-gray-900 bg-white px-3 py-1.5 text-xs font-medium text-gray-900 transition-colors hover:bg-gray-100">Cancel</button>
                <button @click="saveNote" class="bg-black px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800">Save Note</button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.highlight-question {
    background-color: rgba(59, 130, 246, 0.15);
    border-radius: 4px;
    padding: 2px 4px;
    margin: 0 -2px;
    animation: highlightPulse 2s ease-in-out;
}

@keyframes highlightPulse {
    0% {
        background-color: rgba(59, 130, 246, 0.15);
        transform: scale(1);
    }
    50% {
        background-color: rgba(59, 130, 246, 0.3);
        transform: scale(1.05);
    }
    100% {
        background-color: rgba(59, 130, 246, 0.15);
        transform: scale(1);
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

.highlight-nocolor {
    background-color: transparent;
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
