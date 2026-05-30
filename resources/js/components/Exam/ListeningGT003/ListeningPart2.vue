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

// Multiple choice answers for questions 17-20


// Highlight functionality
const contentTextRef = ref<HTMLDivElement | null>(null);

const { highlights, addHighlight, deleteHighlight, findOverlappingHighlights } = useHighlight();

// Note functionality
const notes = ref<Note[]>([]);

// Text segments with their cumulative offsets (each offset = previous offset + previous text length + 1)
// Text segments with their cumulative offsets calculated later
const rawTextSegments = [
    { id: 'part1-title', text: 'Part 2'},
    { id: 'part1-desc', text: 'Listen and answer questions 11–20.'},
    { id: 0, text: 'SECTION 2' },
    { id: 1, text: 'Questions 11–20' },
    { id: 2, text: 'Choose the correct letter, A, B or C.' },
    { id: 3, text: '11. The lecture was organised by' },
    { id: 4, text: 'A. City of Nottingham' },
    { id: 5, text: "B. University of Nottingham Students' Union" },
    { id: 6, text: 'C. Nottingham Police Department' },
    { id: 7, text: '12. The majority of crime on campus is' },
    { id: 8, text: 'A. Drugs and Alcohol' },
    { id: 9, text: 'B. Violence' },
    { id: 10, text: 'C. Theft' },
    { id: 11, text: '13. The campus crime rate has so far this year.' },
    { id: 12, text: 'A. increased' },
    { id: 13, text: 'B. decreased' },
    { id: 14, text: 'C. stayed the same' },
    { id: 15, text: '14. Why is there added concern about crime?' },
    { id: 16, text: 'A. exaggeration in media' },
    { id: 17, text: 'B. crime TV shows' },
    { id: 18, text: 'C. factual news articles' },
    { id: 19, text: '15. Carlos says if you are the victim of a crime, you should' },
    { id: 20, text: 'A. run away' },
    { id: 21, text: 'B. resist' },
    { id: 22, text: 'C. seek help' },
    { id: 23, text: '16. What is the primary method for increasing safety?' },
    { id: 24, text: 'A. informing students and staff of safety precautions' },
    { id: 25, text: 'B. offering free self-defense courses to students' },
    { id: 26, text: 'C. reminding students to carry a mobile phone at all times' },
    { id: 27, text: '17. If a student must work late, it is most important to' },
    { id: 28, text: 'A. not return home until the morning' },
    { id: 29, text: 'B. go back with a friend' },
    { id: 30, text: 'C. bring a mobile phone' },
    { id: 31, text: '18. It is dangerous to' },
    { id: 32, text: 'A. drive home late at night' },
    { id: 33, text: 'B. carry a knife' },
    { id: 34, text: 'C. carry pepper spray' },
    { id: 35, text: '19. Students who complete a self-defense course are' },
    { id: 36, text: 'A. more aware of dangers' },
    { id: 37, text: 'B. mentally tougher' },
    { id: 38, text: 'C. walking more confidently' },
    { id: 39, text: '20. A university is' },
    { id: 40, text: 'A. not surrounded by walls' },
    { id: 41, text: 'B. patrolled by military' },
    { id: 42, text: 'C. completely safe' },
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
    const allAnswers = { ...answers.value };
    return allAnswers;
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
    // Handle grouped questions for 17-18 and 19-20
    let targetId = `question-${questionNumber}`;

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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="tooltipHandleContentClick" class="px-4 py-2 pb-24 select-text sm:px-6">
        <div class="flex min-h-screen w-full flex-col" :style="contentZoom">
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


            <!-- Scrollable Questions Content -->
            <div @mouseup="handleContentTextSelect" class="flex-1 overflow-y-auto pb-24 select-text">
                <div class="p-2 sm:p-3">
                    <div class="space-y-4 p-3 select-text sm:p-4">

                        <!-- Instructions -->
                        <div class="mb-2">
                            <p class="text-sm text-gray-700 select-text" data-segment-id="2" v-html="getHighlightedSegmentById(2)"></p>
                        </div>

                        <!-- Question 11 -->
                        <div
                            id="question-11"
                            class="relative rounded  border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 11"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 11 || isQuestionFlagged(11)"
                                @click.stop="toggleFlag(11)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(11) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(11) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="3" v-html="getHighlightedSegmentById(3)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q11" value="A" name="q11" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="4" v-html="getHighlightedSegmentById(4)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q11" value="B" name="q11" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="5" v-html="getHighlightedSegmentById(5)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q11" value="C" name="q11" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="6" v-html="getHighlightedSegmentById(6)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 12 -->
                        <div
                            id="question-12"
                            class="relative rounded  border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 12"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 12 || isQuestionFlagged(12)"
                                @click.stop="toggleFlag(12)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(12) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(12) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="7" v-html="getHighlightedSegmentById(7)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q12" value="A" name="q12" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="8" v-html="getHighlightedSegmentById(8)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q12" value="B" name="q12" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="9" v-html="getHighlightedSegmentById(9)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q12" value="C" name="q12" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="10" v-html="getHighlightedSegmentById(10)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 13 -->
                        <div
                            id="question-13"
                            class="relative rounded  border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 13"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 13 || isQuestionFlagged(13)"
                                @click.stop="toggleFlag(13)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(13) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(13) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="11" v-html="getHighlightedSegmentById(11)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q13" value="A" name="q13" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="12" v-html="getHighlightedSegmentById(12)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q13" value="B" name="q13" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="13" v-html="getHighlightedSegmentById(13)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q13" value="C" name="q13" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="14" v-html="getHighlightedSegmentById(14)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 14 -->
                        <div
                            id="question-14"
                            class="relative rounded border border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 14"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 14 || isQuestionFlagged(14)"
                                @click.stop="toggleFlag(14)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(14) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(14) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="15" v-html="getHighlightedSegmentById(15)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q14" value="A" name="q14" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="16" v-html="getHighlightedSegmentById(16)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q14" value="B" name="q14" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="17" v-html="getHighlightedSegmentById(17)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q14" value="C" name="q14" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="18" v-html="getHighlightedSegmentById(18)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 15 -->
                        <div
                            id="question-15"
                            class="relative rounded  border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 15"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 15 || isQuestionFlagged(15)"
                                @click.stop="toggleFlag(15)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(15) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(15) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="19" v-html="getHighlightedSegmentById(19)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q15" value="A" name="q15" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q15" value="B" name="q15" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q15" value="C" name="q15" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 16 -->
                        <div
                            id="question-16"
                            class="relative rounded border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 16"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 16 || isQuestionFlagged(16)"
                                @click.stop="toggleFlag(16)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(16) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(16) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="23" v-html="getHighlightedSegmentById(23)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q16" value="A" name="q16" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q16" value="B" name="q16" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q16" value="C" name="q16" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 17 -->
                        <div
                            id="question-17"
                            class="relative rounded  border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 17"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 17 || isQuestionFlagged(17)"
                                @click.stop="toggleFlag(17)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(17) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(17) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="27" v-html="getHighlightedSegmentById(27)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q17" value="A" name="q17" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="28" v-html="getHighlightedSegmentById(28)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q17" value="B" name="q17" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="29" v-html="getHighlightedSegmentById(29)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q17" value="C" name="q17" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="30" v-html="getHighlightedSegmentById(30)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 18 -->
                        <div
                            id="question-18"
                            class="relative rounded  border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 18"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 18 || isQuestionFlagged(18)"
                                @click.stop="toggleFlag(18)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(18) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(18) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="31" v-html="getHighlightedSegmentById(31)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q18" value="A" name="q18" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="32" v-html="getHighlightedSegmentById(32)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q18" value="B" name="q18" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="33" v-html="getHighlightedSegmentById(33)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q18" value="C" name="q18" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="34" v-html="getHighlightedSegmentById(34)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 19 -->
                        <div
                            id="question-19"
                            class="relative rounded  border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 19"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 19 || isQuestionFlagged(19)"
                                @click.stop="toggleFlag(19)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(19) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(19) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="35" v-html="getHighlightedSegmentById(35)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q19" value="A" name="q19" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="36" v-html="getHighlightedSegmentById(36)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q19" value="B" name="q19" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="37" v-html="getHighlightedSegmentById(37)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q19" value="C" name="q19" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="38" v-html="getHighlightedSegmentById(38)"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 20 -->
                        <div
                            id="question-20"
                            class="relative rounded  border-gray-200 p-4"
                            @mouseenter="hoveredQuestion = 20"
                            @mouseleave="hoveredQuestion = null"
                        >
                            <button
                                v-show="hoveredQuestion === 20 || isQuestionFlagged(20)"
                                @click.stop="toggleFlag(20)"
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(20) ? 'border-gray-400 bg-transparent text-red-500' : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'"
                                :title="isQuestionFlagged(20) ? 'Remove bookmark' : 'Bookmark this question'"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                            </button>
                            <p class="mb-3 text-base font-semibold text-gray-900 select-text pr-8" data-segment-id="39" v-html="getHighlightedSegmentById(39)"></p>
                            <div class="space-y-2">
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q20" value="A" name="q20" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="40" v-html="getHighlightedSegmentById(40)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q20" value="B" name="q20" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="41" v-html="getHighlightedSegmentById(41)"></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2 rounded  border-gray-200 p-2 transition-colors hover:bg-gray-50">
                                    <input type="radio" v-model="answers.q20" value="C" name="q20" class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black"/>
                                    <span class="text-base text-gray-900 select-text" data-segment-id="42" v-html="getHighlightedSegmentById(42)"></span>
                                </label>
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
