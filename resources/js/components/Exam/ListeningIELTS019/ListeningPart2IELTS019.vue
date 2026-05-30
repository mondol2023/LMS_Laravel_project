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

// All 10 questions are single-choice A/B/C
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

// ─── TEXT SEGMENTS ────────────────────────────────────────────────────────────
// Campus Crime / Safety lecture — Questions 11–20
// All 10 questions are single-choice A/B/C.
// ─────────────────────────────────────────────────────────────────────────────
const textSegments = ref<TextSegment[]>([
    // ── Header box ──────────────────────────────────────────────────────────
    { id: 'part2-title', text: 'Part 2', offset: 0 },
    { id: 'part2-desc', text: 'Listen and answer questions 11–20.', offset: 7 },

    // ── Instructions ────────────────────────────────────────────────────────
    { id: 1, text: 'Questions 11–20', offset: 53 },
    { id: 2, text: 'Choose the correct letter, A, B or C.', offset: 70 },

    // ── Q11 ─────────────────────────────────────────────────────────────────
    { id: 'q11', text: '11.', offset: 108 },
    { id: 3, text: 'The lecture was organised by', offset: 112 },
    { id: 4, text: 'City of Nottingham', offset: 141 },
    { id: 5, text: 'University of Nottingham Students\' Union', offset: 161 },
    { id: 6, text: 'Nottingham Police Department', offset: 201 },

    // ── Q12 ─────────────────────────────────────────────────────────────────
    { id: 'q12', text: '12.', offset: 230 },
    { id: 7, text: 'The majority of crime on campus is', offset: 234 },
    { id: 8, text: 'Drugs and Alcohol', offset: 268 },
    { id: 9, text: 'Violence', offset: 287 },
    { id: 10, text: 'Theft', offset: 296 },

    // ── Q13 ─────────────────────────────────────────────────────────────────
    { id: 'q13', text: '13.', offset: 302 },
    { id: 11, text: 'The campus crime rate has so far this year.', offset: 306 },
    { id: 12, text: 'increased', offset: 350 },
    { id: 13, text: 'decreased', offset: 360 },
    { id: 14, text: 'stayed the same', offset: 370 },

    // ── Q14 ─────────────────────────────────────────────────────────────────
    { id: 'q14', text: '14.', offset: 386 },
    { id: 15, text: 'Why is there added concern about crime?', offset: 390 },
    { id: 16, text: 'exaggeration in media', offset: 429 },
    { id: 17, text: 'crime TV shows', offset: 451 },
    { id: 18, text: 'factual news articles', offset: 466 },

    // ── Q15 ─────────────────────────────────────────────────────────────────
    { id: 'q15', text: '15.', offset: 488 },
    { id: 19, text: 'Carlos says if you are the victim of a crime, you should', offset: 492 },
    { id: 20, text: 'run away', offset: 548 },
    { id: 21, text: 'resist', offset: 557 },
    { id: 22, text: 'seek help', offset: 564 },

    // ── Q16 ─────────────────────────────────────────────────────────────────
    { id: 'q16', text: '16.', offset: 574 },
    { id: 23, text: 'What is the primary method for increasing safety?', offset: 578 },
    { id: 24, text: 'informing students and staff of safety precautions', offset: 628 },
    { id: 25, text: 'offering free self-defense courses to students', offset: 679 },
    { id: 26, text: 'reminding students to carry a mobile phone at all times', offset: 725 },

    // ── Q17 ─────────────────────────────────────────────────────────────────
    { id: 'q17', text: '17.', offset: 780 },
    { id: 27, text: 'If a student must work late, it is most important to', offset: 784 },
    { id: 28, text: 'not return home until the morning', offset: 837 },
    { id: 29, text: 'go back with a friend', offset: 869 },
    { id: 30, text: 'bring a mobile phone', offset: 891 },

    // ── Q18 ─────────────────────────────────────────────────────────────────
    { id: 'q18', text: '18.', offset: 911 },
    { id: 31, text: 'It is dangerous to', offset: 915 },
    { id: 32, text: 'drive home late at night', offset: 934 },
    { id: 33, text: 'carry a knife', offset: 958 },
    { id: 34, text: 'carry pepper spray', offset: 971 },

    // ── Q19 ─────────────────────────────────────────────────────────────────
    { id: 'q19', text: '19.', offset: 990 },
    { id: 35, text: 'Students who complete a self-defense course are', offset: 994 },
    { id: 36, text: 'more aware of dangers', offset: 1042 },
    { id: 37, text: 'mentally tougher', offset: 1064 },
    { id: 38, text: 'walking more confidently', offset: 1081 },

    // ── Q20 ─────────────────────────────────────────────────────────────────
    { id: 'q20', text: '20.', offset: 1105 },
    { id: 39, text: 'A university is', offset: 1109 },
    { id: 40, text: 'not surrounded by walls', offset: 1125 },
    { id: 41, text: 'patrolled by military', offset: 1149 },
    { id: 42, text: 'completely safe', offset: 1170 },
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

// Use the tooltip composable's handleContentTextSelect
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
    const element = document.getElementById(`question-${questionNumber}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
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

// ── Per-question configuration ───────────────────────────────────────────────
// Keeps the template DRY while staying fully type-safe.
const questions = [
    {
        num: 11,
        modelKey: 'q11',
        qSegId: 'q11',
        textSegId: 3,
        options: [
            { value: 'A', segId: 4 },
            { value: 'B', segId: 5 },
            { value: 'C', segId: 6 },
        ],
    },
    {
        num: 12,
        modelKey: 'q12',
        qSegId: 'q12',
        textSegId: 7,
        options: [
            { value: 'A', segId: 8 },
            { value: 'B', segId: 9 },
            { value: 'C', segId: 10 },
        ],
    },
    {
        num: 13,
        modelKey: 'q13',
        qSegId: 'q13',
        textSegId: 11,
        options: [
            { value: 'A', segId: 12 },
            { value: 'B', segId: 13 },
            { value: 'C', segId: 14 },
        ],
    },
    {
        num: 14,
        modelKey: 'q14',
        qSegId: 'q14',
        textSegId: 15,
        options: [
            { value: 'A', segId: 16 },
            { value: 'B', segId: 17 },
            { value: 'C', segId: 18 },
        ],
    },
    {
        num: 15,
        modelKey: 'q15',
        qSegId: 'q15',
        textSegId: 19,
        options: [
            { value: 'A', segId: 20 },
            { value: 'B', segId: 21 },
            { value: 'C', segId: 22 },
        ],
    },
    {
        num: 16,
        modelKey: 'q16',
        qSegId: 'q16',
        textSegId: 23,
        options: [
            { value: 'A', segId: 24 },
            { value: 'B', segId: 25 },
            { value: 'C', segId: 26 },
        ],
    },
    {
        num: 17,
        modelKey: 'q17',
        qSegId: 'q17',
        textSegId: 27,
        options: [
            { value: 'A', segId: 28 },
            { value: 'B', segId: 29 },
            { value: 'C', segId: 30 },
        ],
    },
    {
        num: 18,
        modelKey: 'q18',
        qSegId: 'q18',
        textSegId: 31,
        options: [
            { value: 'A', segId: 32 },
            { value: 'B', segId: 33 },
            { value: 'C', segId: 34 },
        ],
    },
    {
        num: 19,
        modelKey: 'q19',
        qSegId: 'q19',
        textSegId: 35,
        options: [
            { value: 'A', segId: 36 },
            { value: 'B', segId: 37 },
            { value: 'C', segId: 38 },
        ],
    },
    {
        num: 20,
        modelKey: 'q20',
        qSegId: 'q20',
        textSegId: 39,
        options: [
            { value: 'A', segId: 40 },
            { value: 'B', segId: 41 },
            { value: 'C', segId: 42 },
        ],
    },
] as const;
</script>

<template>
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="tooltipHandleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6">
        <!-- Questions Section - Full Width -->
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">

            <!-- Part Header Box - Gray Background -->
            <div class="mb-3 rounded border border-gray-200 bg-[#F1F2EC] px-4 py-3" @mouseup="handleContentTextSelect">
                <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="part2-title"
                    v-html="getHighlightedSegmentById('part2-title')"></h3>
                <p class="text-sm text-gray-700 select-text" data-segment-id="part2-desc"
                    v-html="getHighlightedSegmentById('part2-desc')"></p>
            </div>

            <!-- Instructions Section -->
            <div class="shrink-0 px-2 pb-2 sm:px-3" @mouseup="handleContentTextSelect">
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="0"
                    v-html="getHighlightedSegmentById(0)"></p>
                <p class="text-base font-bold text-gray-900 select-text" data-segment-id="1"
                    v-html="getHighlightedSegmentById(1)"></p>
                <p class="text-sm text-gray-700 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)">
                </p>
            </div>

            <!-- Scrollable Questions Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <div class="space-y-2">

                        <!-- Questions 11–20: Single Choice A/B/C -->
                        <template v-for="q in questions" :key="q.num">
                            <div :id="`question-${q.num}`" class="relative p-2 sm:p-3"
                                @mouseenter="hoveredQuestion = q.num" @mouseleave="hoveredQuestion = null">
                                <div class="flex items-start gap-2">
                                    <!-- Question number -->
                                    <span class="text-base font-bold text-gray-900 select-text"
                                        :data-segment-id="q.qSegId" v-html="getHighlightedSegmentById(q.qSegId)"></span>

                                    <div class="min-w-0 flex-1">
                                        <!-- Bookmark Button -->
                                        <button v-show="hoveredQuestion === q.num || isQuestionFlagged(q.num)"
                                            @click.stop="toggleFlag(q.num)"
                                            class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                            :class="isQuestionFlagged(q.num)
                                                    ? 'border-gray-400 bg-transparent text-red-500'
                                                    : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                                "
                                            :title="isQuestionFlagged(q.num) ? 'Remove bookmark' : 'Bookmark this question'">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                            </svg>
                                        </button>

                                        <!-- Question text -->
                                        <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                            <span class="select-text" :data-segment-id="q.textSegId"
                                                v-html="getHighlightedSegmentById(q.textSegId)"></span>
                                        </h4>

                                        <!-- Options A / B / C -->
                                        <div class="space-y-1">
                                            <label v-for="opt in q.options" :key="opt.value"
                                                class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                                <input type="radio" v-model="answers[q.modelKey]" :value="opt.value"
                                                    class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                                <span class="text-base leading-relaxed text-gray-900 select-text"
                                                    :data-segment-id="opt.segId"
                                                    v-html="getHighlightedSegmentById(opt.segId)"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

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