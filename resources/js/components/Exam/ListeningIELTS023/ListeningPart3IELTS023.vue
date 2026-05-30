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

// Listening Part 3 component - Sports Foot Injuries

// Single choice answers for questions 21-26
const answers = ref<Record<string, string>>({
    q21: '',
    q22: '',
    q23: '',
    q24: '',
    q25: '',
    q26: '',
});

// Multiple choice answers for questions 27-30 (TWO letters each)
const multipleAnswers = ref<{ q27_28: string[]; q29_30: string[] }>({
    q27_28: [],
    q29_30: [],
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
    { id: 0, text: 'Questions 21–30', offset: 43 },
    { id: 1, text: 'Choose the correct letter, A, B or C.', offset: 59 },
    { id: 2, text: 'Presentation on foot injuries among sports players', offset: 97 },
    // Question 21
    { id: 'q21', text: '21.', offset: 149 },
    { id: 3, text: 'John and Cath\'s presentation plans are different in', offset: 153 },
    { id: 4, text: 'the examples used', offset: 208 },
    { id: 5, text: 'the order of information', offset: 228 },
    { id: 6, text: 'the main points made', offset: 255 },
    // Question 22
    { id: 'q22', text: '22.', offset: 278 },
    { id: 7, text: 'What do the students agree about the anatomy section of their presentation?', offset: 282 },
    { id: 8, text: 'It would be better if Gath spoke about it', offset: 365 },
    { id: 9, text: 'It should be kept quite short', offset: 410 },
    { id: 10, text: 'It should be based on information from the internet', offset: 443 },
    // Question 23
    { id: 'q23', text: '23.', offset: 503 },
    { id: 11, text: 'What do the students agree to include in the last section?', offset: 507 },
    { id: 12, text: 'visuals of injuries', offset: 572 },
    { id: 13, text: 'demonstrations of treatment', offset: 594 },
    { id: 14, text: 'interviews with patients', offset: 626 },
    // Question 24
    { id: 'q24', text: '24.', offset: 654 },
    { id: 15, text: 'What is said about the different types of heel injury?', offset: 658 },
    { id: 16, text: 'Diagnosis is straightforward', offset: 719 },
    { id: 17, text: 'They are expensive to treat', offset: 749 },
    { id: 18, text: 'Some are more serious than others', offset: 779 },
    // Question 25
    { id: 'q25', text: '25.', offset: 816 },
    { id: 19, text: 'On the subject of causes of heel injuries, the students agree to', offset: 820 },
    { id: 20, text: 'focus on a single reason', offset: 894 },
    { id: 21, text: 'reject certain approaches', offset: 921 },
    { id: 22, text: 'use a source written by their professor', offset: 948 },
    // Question 26
    { id: 'q26', text: '26.', offset: 995 },
    { id: 23, text: 'What does Gath say about stretching as a treatment?', offset: 999 },
    { id: 24, text: 'It is potentially risky', offset: 1059 },
    { id: 25, text: 'It is commonly confused with strengthening', offset: 1082 },
    { id: 26, text: 'It is the least effective part of treatment', offset: 1128 },
    // Questions 27-28
    { id: 'q27-28', text: 'Questions 27 and 28', offset: 1177 },
    { id: 'q27-28-inst', text: 'Choose TWO letters, A-E.', offset: 1204 },
    { id: 27, text: 'Which TWO treatment techniques did the female runner find useful for her swollen heel?', offset: 1229 },
    { id: 28, text: 'massage', offset: 1339 },
    { id: 29, text: 'ultrasound', offset: 1351 },
    { id: 30, text: 'rest', offset: 1366 },
    { id: 31, text: 'balancing exercise', offset: 1375 },
    { id: 32, text: 'ice', offset: 1397 },
    // Questions 29-30
    { id: 'q29-30', text: 'Questions 29 and 30', offset: 1405 },
    { id: 'q29-30-inst', text: 'Choose TWO letters, A-E.', offset: 1432 },
    { id: 33, text: 'Which TWO sports did the male sprinter find most effective during his rehabilitation program?', offset: 1457 },
    { id: 34, text: 'swimming', offset: 1560 },
    { id: 35, text: 'weight-training', offset: 1573 },
    { id: 36, text: 'running on grass', offset: 1593 },
    { id: 37, text: 'cycling', offset: 1613 },
    { id: 38, text: 'jumping', offset: 1625 },
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

    // Handle multiple choice questions
    if (multipleAnswers.value.q27_28?.length > 0) {
        allAnswers.q27 = multipleAnswers.value.q27_28[0] || '';
        allAnswers.q28 = multipleAnswers.value.q27_28[1] || '';
    } else {
        allAnswers.q27 = '';
        allAnswers.q28 = '';
    }

    if (multipleAnswers.value.q29_30?.length > 0) {
        allAnswers.q29 = multipleAnswers.value.q29_30[0] || '';
        allAnswers.q30 = multipleAnswers.value.q29_30[1] || '';
    } else {
        allAnswers.q29 = '';
        allAnswers.q30 = '';
    }

    return allAnswers;
};

const handleMultipleChoice = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    const index = currentAnswers.indexOf(option);

    if (index > -1) {
        // Remove if already selected
        currentAnswers.splice(index, 1);
    } else if (currentAnswers.length < 2) {
        // Add if less than 2 selected
        currentAnswers.push(option);
    }
};

const isSelected = (questionGroup: string, option: string) => {
    return multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value].includes(option);
};

const isDisabled = (questionGroup: string, option: string) => {
    const currentAnswers = multipleAnswers.value[questionGroup as keyof typeof multipleAnswers.value];
    // Disable if 2 already selected AND this option is not one of them
    return currentAnswers.length >= 2 && !currentAnswers.includes(option);
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
    // Handle grouped questions for 27-28 and 29-30
    let targetId = `question-${questionNumber}`;
    if (questionNumber === 27 || questionNumber === 28) {
        targetId = 'question-27-28';
    } else if (questionNumber === 29 || questionNumber === 30) {
        targetId = 'question-29-30';
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
    <div ref="contentTextRef" @mouseup="handleContentTextSelect" @click="handleContentClick"
        class="px-4 py-2 pb-24 select-text sm:px-6">
        <!-- Questions Section - Full Width -->
        <div class="flex w-full flex-col" :style="contentZoom">
            <!-- Part Header Box - Gray Background -->
            <div class="mb-3 part-header-box rounded border border-gray-200 bg-gray-100 px-4 py-3" @mouseup="handleContentTextSelect">
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
                    <!-- Questions 21-26: Single Choice MCQ -->
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

                        <!-- Question 25 -->
                        <div id="question-25" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 25"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q25"
                                    v-html="getHighlightedSegmentById('q25')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 25 || isQuestionFlagged(25)"
                                        @click.stop="toggleFlag(25)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(25)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(25) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="19"
                                            v-html="getHighlightedSegmentById(19)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q25" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="20" v-html="getHighlightedSegmentById(20)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q25" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="21" v-html="getHighlightedSegmentById(21)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q25" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="22" v-html="getHighlightedSegmentById(22)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Question 26 -->
                        <div id="question-26" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 26"
                            @mouseleave="hoveredQuestion = null">
                            <div class="flex items-start gap-2">
                                <span class="text-base font-bold text-gray-900 select-text" data-segment-id="q26"
                                    v-html="getHighlightedSegmentById('q26')"></span>
                                <div class="min-w-0 flex-1">
                                    <button v-show="hoveredQuestion === 26 || isQuestionFlagged(26)"
                                        @click.stop="toggleFlag(26)"
                                        class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                        :class="isQuestionFlagged(26)
                                                ? 'border-gray-400 bg-transparent text-red-500'
                                                : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                            "
                                        :title="isQuestionFlagged(26) ? 'Remove bookmark' : 'Bookmark this question'">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                        </svg>
                                    </button>
                                    <h4 class="mb-2 text-lg leading-tight font-bold text-gray-900">
                                        <span class="select-text" data-segment-id="23"
                                            v-html="getHighlightedSegmentById(23)"></span>
                                    </h4>
                                    <div class="space-y-1">
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q26" value="A"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="24" v-html="getHighlightedSegmentById(24)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q26" value="B"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="25" v-html="getHighlightedSegmentById(25)"></span>
                                        </label>
                                        <label
                                            class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                            <input type="radio" v-model="answers.q26" value="C"
                                                class="mt-0.5 h-4 w-4 flex-shrink-0 border-gray-300 text-black focus:ring-black" />
                                            <span class="text-base leading-relaxed text-gray-900 select-text"
                                                data-segment-id="26" v-html="getHighlightedSegmentById(26)"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions 27-28: Multiple Choice -->
                        <div id="question-27-28" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 27"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 27 || isQuestionFlagged(27) || isQuestionFlagged(28)"
                                @click.stop="
                                    toggleFlag(27);
                                toggleFlag(28);
                                "
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(27) || isQuestionFlagged(28)
                                        ? 'border-gray-400 bg-transparent text-red-500'
                                        : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                    "
                                :title="isQuestionFlagged(27) || isQuestionFlagged(28) ? 'Remove bookmark' : 'Bookmark this question'">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
                                </svg>
                            </button>
                            <div class="mb-2">
                                <div class="mb-2">
                                    <h3 class="text-base font-bold text-gray-900 select-text" data-segment-id="q27-28"
                                        v-html="getHighlightedSegmentById('q27-28')"></h3>
                                </div>
                                <div class="p-2">
                                    <p class="mb-2 text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="q27-28-inst" v-html="getHighlightedSegmentById('q27-28-inst')">
                                    </p>
                                    <p class="text-lg leading-tight font-bold text-gray-900 select-text"
                                        data-segment-id="27" v-html="getHighlightedSegmentById(27)"></p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q27_28', 'A')"
                                        :disabled="isDisabled('q27_28', 'A')"
                                        @change="handleMultipleChoice('q27_28', 'A')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="28" v-html="getHighlightedSegmentById(28)"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q27_28', 'B')"
                                        :disabled="isDisabled('q27_28', 'B')"
                                        @change="handleMultipleChoice('q27_28', 'B')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="29" v-html="getHighlightedSegmentById(29)"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q27_28', 'C')"
                                        :disabled="isDisabled('q27_28', 'C')"
                                        @change="handleMultipleChoice('q27_28', 'C')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="30" v-html="getHighlightedSegmentById(30)"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q27_28', 'D')"
                                        :disabled="isDisabled('q27_28', 'D')"
                                        @change="handleMultipleChoice('q27_28', 'D')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="31" v-html="getHighlightedSegmentById(31)"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q27_28', 'E')"
                                        :disabled="isDisabled('q27_28', 'E')"
                                        @change="handleMultipleChoice('q27_28', 'E')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="32" v-html="getHighlightedSegmentById(32)"></span>
                                </label>
                            </div>

                            <div class="mt-2 p-2">
                                <p class="text-sm font-medium text-gray-900">Selected: {{ multipleAnswers.q27_28.length
                                    }}/2 answers</p>
                            </div>
                        </div>

                        <!-- Questions 29-30: Multiple Choice -->
                        <div id="question-29-30" class="relative p-2 sm:p-3" @mouseenter="hoveredQuestion = 29"
                            @mouseleave="hoveredQuestion = null">
                            <button v-show="hoveredQuestion === 29 || isQuestionFlagged(29) || isQuestionFlagged(30)"
                                @click.stop="
                                    toggleFlag(29);
                                toggleFlag(30);
                                "
                                class="absolute top-2 right-2 flex h-7 w-7 items-center justify-center rounded border transition-all"
                                :class="isQuestionFlagged(29) || isQuestionFlagged(30)
                                        ? 'border-gray-400 bg-transparent text-red-500'
                                        : 'border-gray-300 bg-transparent text-gray-400 hover:border-gray-400 hover:text-gray-600'
                                    "
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
                                    <p class="text-lg leading-tight font-bold text-gray-900 select-text"
                                        data-segment-id="33" v-html="getHighlightedSegmentById(33)"></p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q29_30', 'A')"
                                        :disabled="isDisabled('q29_30', 'A')"
                                        @change="handleMultipleChoice('q29_30', 'A')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="34" v-html="getHighlightedSegmentById(34)"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q29_30', 'B')"
                                        :disabled="isDisabled('q29_30', 'B')"
                                        @change="handleMultipleChoice('q29_30', 'B')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="35" v-html="getHighlightedSegmentById(35)"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q29_30', 'C')"
                                        :disabled="isDisabled('q29_30', 'C')"
                                        @change="handleMultipleChoice('q29_30', 'C')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="36" v-html="getHighlightedSegmentById(36)"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q29_30', 'D')"
                                        :disabled="isDisabled('q29_30', 'D')"
                                        @change="handleMultipleChoice('q29_30', 'D')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="37" v-html="getHighlightedSegmentById(37)"></span>
                                </label>
                                <label
                                    class="flex cursor-pointer items-start gap-1 rounded p-1 transition-colors hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected('q29_30', 'E')"
                                        :disabled="isDisabled('q29_30', 'E')"
                                        @change="handleMultipleChoice('q29_30', 'E')"
                                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-black focus:ring-black disabled:cursor-not-allowed disabled:opacity-50" />
                                    <span class="text-base leading-relaxed text-gray-900 select-text"
                                        data-segment-id="38" v-html="getHighlightedSegmentById(38)"></span>
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
